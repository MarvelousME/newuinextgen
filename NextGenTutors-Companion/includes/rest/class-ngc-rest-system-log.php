<?php
/**
 * REST API for system log.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * System log REST routes.
 */
class NGC_Rest_System_Log {

	/**
	 * Register routes.
	 */
	public static function register() {
		$perm = static function () {
			return current_user_can( 'ngc_view_audit' ) || current_user_can( 'manage_options' );
		};

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/platform/system-log',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'list_logs' ],
				'permission_callback' => $perm,
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/platform/system-log/stats',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'stats' ],
				'permission_callback' => $perm,
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function list_logs( $request ) {
		$args = [
			'q'      => $request->get_param( 'q' ),
			'level'  => $request->get_param( 'level' ),
			'from'   => $request->get_param( 'from' ),
			'to'     => $request->get_param( 'to' ),
			'limit'  => (int) $request->get_param( 'per_page' ) ?: 50,
			'offset' => ( (int) $request->get_param( 'page' ) - 1 ) * ( (int) $request->get_param( 'per_page' ) ?: 50 ),
		];
		return rest_ensure_response(
			[
				'rows'  => NGC_System_Log_Service::search( $args ),
				'total' => NGC_System_Log_Service::count( $args ),
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function stats( $request ) {
		return rest_ensure_response(
			NGC_System_Log_Service::stats(
				[
					'from' => $request->get_param( 'from' ),
					'to'   => $request->get_param( 'to' ),
				]
			)
		);
	}
}
