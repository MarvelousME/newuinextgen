<?php
/**
 * REST API for BYOK models, supervised agents, and admin chat.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI control panel REST surface (ngc/v1/ai/*).
 */
class NGC_Rest_Ai {

	/**
	 * Register AI routes.
	 */
	public static function register() {
		$admin = [ __CLASS__, 'can' ];

		register_rest_route( NGC_Rest::NAMESPACE, '/ai/models', [ 'methods' => 'GET', 'callback' => [ __CLASS__, 'models_list' ], 'permission_callback' => $admin ] );
		register_rest_route( NGC_Rest::NAMESPACE, '/ai/models', [ 'methods' => 'POST', 'callback' => [ __CLASS__, 'models_save' ], 'permission_callback' => $admin ] );
		register_rest_route( NGC_Rest::NAMESPACE, '/ai/models/delete', [ 'methods' => 'POST', 'callback' => [ __CLASS__, 'models_delete' ], 'permission_callback' => $admin ] );
		register_rest_route( NGC_Rest::NAMESPACE, '/ai/models/key', [ 'methods' => 'POST', 'callback' => [ __CLASS__, 'models_key' ], 'permission_callback' => $admin ] );
		register_rest_route( NGC_Rest::NAMESPACE, '/ai/models/test', [ 'methods' => 'POST', 'callback' => [ __CLASS__, 'models_test' ], 'permission_callback' => $admin ] );

		register_rest_route( NGC_Rest::NAMESPACE, '/ai/agents', [ 'methods' => 'GET', 'callback' => [ __CLASS__, 'agents_list' ], 'permission_callback' => $admin ] );
		register_rest_route( NGC_Rest::NAMESPACE, '/ai/agents', [ 'methods' => 'POST', 'callback' => [ __CLASS__, 'agents_save' ], 'permission_callback' => $admin ] );
		register_rest_route( NGC_Rest::NAMESPACE, '/ai/agents/delete', [ 'methods' => 'POST', 'callback' => [ __CLASS__, 'agents_delete' ], 'permission_callback' => $admin ] );
		register_rest_route( NGC_Rest::NAMESPACE, '/ai/skills', [ 'methods' => 'GET', 'callback' => [ __CLASS__, 'skills' ], 'permission_callback' => $admin ] );

		register_rest_route( NGC_Rest::NAMESPACE, '/ai/chat', [ 'methods' => 'POST', 'callback' => [ __CLASS__, 'chat' ], 'permission_callback' => $admin ] );
	}

	/**
	 * @return bool
	 */
	public static function can() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * @param mixed $result Result or error.
	 * @return WP_REST_Response
	 */
	private static function respond( $result ) {
		if ( is_wp_error( $result ) ) {
			$status = (int) ( $result->get_error_data()['status'] ?? 400 );
			return new WP_REST_Response( [ 'success' => false, 'data' => [ 'message' => $result->get_error_message() ] ], $status );
		}
		return new WP_REST_Response( [ 'success' => true, 'data' => $result ], 200 );
	}

	/** @return WP_REST_Response */
	public static function models_list() {
		return self::respond( NGC_AI_Models::list() );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function models_save( $request ) {
		return self::respond( NGC_AI_Models::save( (array) $request->get_json_params() ) );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function models_delete( $request ) {
		$id = sanitize_key( (string) ( $request->get_json_params()['id'] ?? '' ) );
		return self::respond( NGC_AI_Models::delete( $id ) );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function models_key( $request ) {
		$body = (array) $request->get_json_params();
		$id   = sanitize_key( (string) ( $body['id'] ?? '' ) );
		$key  = (string) ( $body['api_key'] ?? '' );
		return self::respond( NGC_AI_Models::set_key( $id, $key ) );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function models_test( $request ) {
		$id = sanitize_key( (string) ( $request->get_json_params()['id'] ?? '' ) );
		return self::respond( NGC_AI_Models::test( $id ) );
	}

	/** @return WP_REST_Response */
	public static function agents_list() {
		return self::respond( NGC_AI_Agents::list() );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function agents_save( $request ) {
		return self::respond( NGC_AI_Agents::save( (array) $request->get_json_params() ) );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function agents_delete( $request ) {
		$id = sanitize_key( (string) ( $request->get_json_params()['id'] ?? '' ) );
		return self::respond( NGC_AI_Agents::delete( $id ) );
	}

	/** @return WP_REST_Response */
	public static function skills() {
		return self::respond( NGC_AI_Agents::available_skills() );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function chat( $request ) {
		$body    = (array) $request->get_json_params();
		$message = sanitize_textarea_field( (string) ( $body['message'] ?? '' ) );
		if ( '' === $message ) {
			return self::respond( new WP_Error( 'ngc_msg', __( 'Message is required.', 'nextgencompanion' ), [ 'status' => 400 ] ) );
		}

		$history = [];
		foreach ( (array) ( $body['history'] ?? [] ) as $turn ) {
			if ( is_array( $turn ) ) {
				$history[] = [
					'role'    => sanitize_key( (string) ( $turn['role'] ?? 'user' ) ),
					'content' => sanitize_textarea_field( (string) ( $turn['content'] ?? '' ) ),
				];
			}
		}

		if ( ! empty( $body['agent_ids'] ) && is_array( $body['agent_ids'] ) ) {
			$ids = array_map( 'sanitize_key', $body['agent_ids'] );
			return self::respond( NGC_AI_Chat::run_swarm( $ids, $message ) );
		}

		$agent_id = sanitize_key( (string) ( $body['agent_id'] ?? '' ) );
		return self::respond( NGC_AI_Chat::run_single( $agent_id, $history, $message ) );
	}
}
