<?php
/**
 * REST API for Talent Intelligence.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ngc/v1/talent/* routes.
 */
class NGC_Rest_Talent {

	/**
	 * Register routes.
	 */
	public static function register() {
		$can = [ __CLASS__, 'can' ];

		register_rest_route( NGC_Rest::NAMESPACE, '/talent/health', [
			'methods' => 'GET', 'callback' => [ __CLASS__, 'health' ], 'permission_callback' => $can,
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/talent/settings', [
			'methods' => 'GET', 'callback' => [ __CLASS__, 'settings_get' ], 'permission_callback' => $can,
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/talent/settings', [
			'methods' => 'POST', 'callback' => [ __CLASS__, 'settings_save' ], 'permission_callback' => $can,
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/talent/evaluate', [
			'methods' => 'POST', 'callback' => [ __CLASS__, 'evaluate' ], 'permission_callback' => $can,
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/talent/rank', [
			'methods' => 'POST', 'callback' => [ __CLASS__, 'rank' ], 'permission_callback' => $can,
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/talent/evaluations', [
			'methods' => 'GET', 'callback' => [ __CLASS__, 'list_evaluations' ], 'permission_callback' => $can,
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/talent/evaluations/(?P<id>\d+)', [
			'methods' => 'GET', 'callback' => [ __CLASS__, 'get_evaluation' ], 'permission_callback' => $can,
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/talent/requirements/(?P<key>[a-z0-9_\-]+)', [
			'methods' => 'POST', 'callback' => [ __CLASS__, 'save_requirement' ], 'permission_callback' => $can,
		] );
	}

	/**
	 * @return bool
	 */
	public static function can() {
		return current_user_can( 'manage_options' )
			|| current_user_can( 'ngc_manage_matches' )
			|| current_user_can( 'ngc_manage_platform' )
			|| current_user_can( 'ngc_admin_operations' );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function health() {
		return rest_ensure_response( NGC_Talent_Service::health() );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function settings_get() {
		$cfg = NGC_Talent_Settings::get();
		$cfg['weights'] = NGC_Talent_Settings::weights();
		$cfg['modelVersion'] = NGC_Talent_Settings::MODEL_VERSION;
		$cfg['auto_approve_forbidden'] = true;
		return rest_ensure_response( $cfg );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function settings_save( $request ) {
		$body = (array) $request->get_json_params();
		$patch = [];
		foreach ( [ 'enabled', 'evaluate_applications', 'rank_find_tutor', 'nlp_sidecar_enabled', 'agent_tools_enabled' ] as $b ) {
			if ( array_key_exists( $b, $body ) ) {
				$patch[ $b ] = (bool) $body[ $b ];
			}
		}
		if ( isset( $body['mode'] ) ) {
			$patch['mode'] = sanitize_text_field( (string) $body['mode'] );
		}
		if ( isset( $body['nlp_sidecar_url'] ) ) {
			$patch['nlp_sidecar_url'] = esc_url_raw( (string) $body['nlp_sidecar_url'] );
		}
		$patch['auto_approve_forbidden'] = true;
		NGC_Talent_Settings::update( $patch );
		if ( isset( $body['weights'] ) && is_array( $body['weights'] ) ) {
			NGC_Talent_Settings::update_weights( $body['weights'] );
		}
		NGC_Talent_Service::reset_provider();
		return self::settings_get();
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function evaluate( $request ) {
		$body = (array) $request->get_json_params();
		$candidate = isset( $body['candidate'] ) && is_array( $body['candidate'] ) ? $body['candidate'] : [];
		$requirements = isset( $body['requirements'] ) && is_array( $body['requirements'] ) ? $body['requirements'] : NGC_Talent_Service::default_requirements();
		$options = [
			'persist'        => ! isset( $body['persist'] ) || ! empty( $body['persist'] ),
			'candidate_type' => (string) ( $body['candidateType'] ?? 'manual' ),
			'candidate_id'   => (string) ( $body['candidateId'] ?? '' ),
			'requirement_id' => (string) ( $body['requirementId'] ?? 'default' ),
			'idempotency_key'=> (string) ( $body['idempotencyKey'] ?? '' ),
		];
		return rest_ensure_response( NGC_Talent_Service::evaluate_safe( $candidate, $requirements, $options ) );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rank( $request ) {
		$body = (array) $request->get_json_params();
		$candidates = isset( $body['candidates'] ) && is_array( $body['candidates'] ) ? $body['candidates'] : [];
		$requirements = isset( $body['requirements'] ) && is_array( $body['requirements'] ) ? $body['requirements'] : [];
		return rest_ensure_response( NGC_Talent_Service::rank_safe( $candidates, $requirements ) );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function list_evaluations() {
		return rest_ensure_response( [ 'items' => NGC_Talent_Repository::query( [ 'limit' => 50 ] ) ] );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_evaluation( $request ) {
		$row = NGC_Talent_Repository::get( (int) $request['id'] );
		if ( ! $row ) {
			return new WP_Error( 'ngc_talent_missing', 'Evaluation not found', [ 'status' => 404 ] );
		}
		return rest_ensure_response( $row );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function save_requirement( $request ) {
		$body = (array) $request->get_json_params();
		$id = NGC_Talent_Repository::save_requirement_profile( (string) $request['key'], $body );
		if ( is_wp_error( $id ) ) {
			return NGC_Rest::error_response( $id );
		}
		return rest_ensure_response( [ 'id' => $id, 'key' => (string) $request['key'] ] );
	}
}
