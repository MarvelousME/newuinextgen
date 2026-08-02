<?php
/**
 * Platform REST — queue admin endpoints.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST: ngc/v1/platform/queue/*
 */
final class NGC_Rest_Platform_Kernel {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			'ngc/v1',
			'/platform/queue/stats',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'stats' ],
				'permission_callback' => [ __CLASS__, 'can_admin' ],
			]
		);
		register_rest_route(
			'ngc/v1',
			'/platform/queue/work',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'work' ],
				'permission_callback' => [ __CLASS__, 'can_admin' ],
			]
		);
		register_rest_route(
			'ngc/v1',
			'/platform/queue/dlq',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'dlq_list' ],
				'permission_callback' => [ __CLASS__, 'can_admin' ],
			]
		);
		register_rest_route(
			'ngc/v1',
			'/platform/queue/dlq/(?P<id>\d+)/replay',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'dlq_replay' ],
				'permission_callback' => [ __CLASS__, 'can_admin' ],
			]
		);
		register_rest_route(
			'ngc/v1',
			'/platform/audit/verify',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'audit_verify' ],
				'permission_callback' => [ __CLASS__, 'can_admin' ],
			]
		);
		register_rest_route(
			'ngc/v1',
			'/platform/recon/run',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'recon_run' ],
				'permission_callback' => [ __CLASS__, 'can_finance' ],
			]
		);
	}

	/**
	 * @return bool
	 */
	public static function can_admin() {
		return current_user_can( 'ngc_manage_platform' ) || current_user_can( 'manage_options' );
	}

	/**
	 * @return bool
	 */
	public static function can_finance() {
		return current_user_can( 'ngc_view_finance' ) || current_user_can( 'manage_options' );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function stats() {
		return rest_ensure_response( NGC_Durable_Queue::stats() );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function work( $request ) {
		$max = (int) $request->get_param( 'max_messages' );
		return rest_ensure_response( NGC_Queue_Worker::work( [ 'max_messages' => $max > 0 ? $max : 10 ] ) );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function dlq_list() {
		return rest_ensure_response( NGC_Queue_DLQ::list_open( 50 ) );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function dlq_replay( $request ) {
		$mid = NGC_Queue_DLQ::replay( (int) $request['id'] );
		if ( is_wp_error( $mid ) ) {
			return $mid;
		}
		return rest_ensure_response( [ 'message_id' => $mid ] );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function audit_verify() {
		return rest_ensure_response( NGC_Immutable_Audit::verify() );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function recon_run() {
		return rest_ensure_response( NGC_Reconciliation::run( [] ) );
	}
}
