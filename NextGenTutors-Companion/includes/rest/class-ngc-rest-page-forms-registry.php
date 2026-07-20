<?php
/**
 * REST — Page/Forms registry verification and repair.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry REST routes.
 */
class NGC_Rest_Page_Forms_Registry {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/registry/verify',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'verify' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/registry/repair',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'repair' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				'args'                => [
					'slug'  => [ 'type' => 'string', 'required' => false ],
					'force' => [ 'type' => 'boolean', 'default' => false ],
				],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/registry/map',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'map' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function verify() {
		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => NGC_Page_Forms_Registry::verify(),
			],
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function repair( $request ) {
		$slug  = sanitize_title( (string) $request->get_param( 'slug' ) );
		$force = (bool) $request->get_param( 'force' );
		$data  = NGC_Page_Forms_Registry::repair( $slug, $force );
		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => $data,
				'report'  => NGC_Page_Forms_Registry::last_report(),
			],
			200
		);
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function map() {
		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => NGC_Page_Forms_Registry::definitions(),
			],
			200
		);
	}
}
