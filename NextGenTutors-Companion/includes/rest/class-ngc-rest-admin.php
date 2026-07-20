<?php
/**
 * Admin REST endpoints.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tutor approve/reject, analytics.
 */
class NGC_Rest_Admin {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/admin/tutors/(?P<id>\d+)/approve',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'approve_tutor' ],
				'permission_callback' => [ __CLASS__, 'can_review' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/admin/tutors/(?P<id>\d+)/reject',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'reject_tutor' ],
				'permission_callback' => [ __CLASS__, 'can_review' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/admin/analytics',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'analytics' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/admin/verification',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'verification' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/admin/tutors/(?P<id>\d+)/resubmit',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'resubmit_tutor' ],
				'permission_callback' => [ __CLASS__, 'can_review' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/admin/payouts',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'process_payout' ],
				'permission_callback' => [ __CLASS__, 'can_payout' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/admin/repair',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'repair' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);
	}

	/**
	 * @return bool
	 */
	public static function can_payout() {
		return current_user_can( 'ngc_manage_payouts' ) || current_user_can( 'manage_options' );
	}

	/**
	 * @return bool
	 */
	public static function can_review() {
		return current_user_can( 'ngc_review_tutors' ) || current_user_can( 'manage_options' );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function approve_tutor( $request ) {
		$result = NGC_Tutor_Lifecycle::approve( (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		return new WP_REST_Response( [ 'ok' => true, 'application' => NGC_Tutor_Lifecycle::get( (int) $request['id'] ) ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function reject_tutor( $request ) {
		$notes  = sanitize_textarea_field( $request->get_param( 'notes' ) ?? '' );
		$result = NGC_Tutor_Lifecycle::reject( (int) $request['id'], $notes );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		return new WP_REST_Response( [ 'ok' => true, 'application' => NGC_Tutor_Lifecycle::get( (int) $request['id'] ) ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function analytics( $request ) {
		$stats = NGC_Platform_Analytics::snapshot();
		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => [ 'stats' => $stats ],
				'meta'    => [ 'source' => 'real', 'retrieved_at' => gmdate( 'c' ) ],
			],
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function verification( $request ) {
		return new WP_REST_Response( NGC_Verification::run_checks(), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function resubmit_tutor( $request ) {
		$data   = $request->get_json_params() ?: $request->get_params();
		$result = NGC_Tutor_Lifecycle::resubmit( (int) $request['id'], $data );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		return new WP_REST_Response( [ 'ok' => true, 'application' => NGC_Tutor_Lifecycle::get( (int) $request['id'] ) ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function process_payout( $request ) {
		$tutor_id = (int) $request->get_param( 'tutor_user_id' );
		$amount   = (float) $request->get_param( 'amount' );
		if ( ! $tutor_id ) {
			return NGC_Rest::error_response( new WP_Error( 'ngc_missing_tutor', __( 'tutor_user_id required.', 'nextgencompanion' ) ) );
		}
		$result = NGC_Reviews::process_payout( $tutor_id, $amount );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		return new WP_REST_Response( [ 'ok' => true, 'payout_id' => $result ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function repair( $request ) {
		return new WP_REST_Response( [ 'ok' => true, 'repaired' => NGC_Self_Healing::repair_all() ], 200 );
	}
}
