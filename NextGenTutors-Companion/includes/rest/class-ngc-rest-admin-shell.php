<?php
/**
 * Enterprise admin REST — version, nav, theme, notifications, entities, export, prefs.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Routes under ngc/v1/admin/* for the shell frameworks.
 */
final class NGC_Rest_Admin_Shell {

	/**
	 * Register routes.
	 */
	public static function register() {
		$ns   = NGC_Rest::NAMESPACE;
		$auth = [ 'NGC_Rest', 'require_admin' ];

		register_rest_route( $ns, '/admin/version', [
			'methods' => 'GET', 'callback' => [ __CLASS__, 'version' ], 'permission_callback' => $auth,
		] );
		register_rest_route( $ns, '/admin/nav/tree', [
			'methods' => 'GET', 'callback' => [ __CLASS__, 'nav_tree' ], 'permission_callback' => $auth,
		] );
		register_rest_route( $ns, '/admin/nav/layout', [
			[ 'methods' => 'GET', 'callback' => [ __CLASS__, 'nav_get' ], 'permission_callback' => $auth ],
			[ 'methods' => 'POST', 'callback' => [ __CLASS__, 'nav_save' ], 'permission_callback' => $auth ],
		] );
		register_rest_route( $ns, '/admin/nav/reset', [
			'methods' => 'POST', 'callback' => [ __CLASS__, 'nav_reset' ], 'permission_callback' => $auth,
		] );
		register_rest_route( $ns, '/admin/theme', [
			[ 'methods' => 'GET', 'callback' => [ __CLASS__, 'theme_get' ], 'permission_callback' => $auth ],
			[ 'methods' => 'POST', 'callback' => [ __CLASS__, 'theme_save' ], 'permission_callback' => $auth ],
		] );
		register_rest_route( $ns, '/admin/notifications', [
			[ 'methods' => 'GET', 'callback' => [ __CLASS__, 'notif_list' ], 'permission_callback' => $auth ],
			[ 'methods' => 'POST', 'callback' => [ __CLASS__, 'notif_mutate' ], 'permission_callback' => $auth ],
		] );
		register_rest_route( $ns, '/admin/cockpit', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'cockpit' ],
			'permission_callback' => $auth,
		] );
		register_rest_route( $ns, '/admin/prefs', [
			[ 'methods' => 'GET', 'callback' => [ __CLASS__, 'prefs_get' ], 'permission_callback' => $auth ],
			[ 'methods' => 'POST', 'callback' => [ __CLASS__, 'prefs_save' ], 'permission_callback' => $auth ],
		] );
		register_rest_route( $ns, '/admin/entities/(?P<key>[a-z0-9_\-]+)', [
			'methods' => 'GET', 'callback' => [ __CLASS__, 'entity_list' ], 'permission_callback' => $auth,
		] );
		register_rest_route( $ns, '/admin/entities/(?P<key>[a-z0-9_\-]+)/(?P<id>\d+)', [
			[
				'methods' => 'GET', 'callback' => [ __CLASS__, 'entity_get' ], 'permission_callback' => $auth,
			],
			[
				'methods' => 'PUT', 'callback' => [ __CLASS__, 'entity_update' ], 'permission_callback' => $auth,
			],
			[
				'methods' => 'DELETE', 'callback' => [ __CLASS__, 'entity_delete' ], 'permission_callback' => $auth,
			],
		] );
		register_rest_route( $ns, '/admin/entities/(?P<key>[a-z0-9_\-]+)/export', [
			'methods' => 'POST', 'callback' => [ __CLASS__, 'entity_export' ], 'permission_callback' => $auth,
		] );
		register_rest_route( $ns, '/admin/components', [
			'methods' => 'GET', 'callback' => [ __CLASS__, 'components' ], 'permission_callback' => $auth,
		] );
	}

	/** @return WP_REST_Response */
	public static function version() {
		return new WP_REST_Response( NGC_Platform_Version::bundle(), 200 );
	}

	/** @return WP_REST_Response */
	public static function nav_tree() {
		return new WP_REST_Response( NGC_Admin_Nav_Tree::build(), 200 );
	}

	/** @return WP_REST_Response */
	public static function nav_get() {
		return new WP_REST_Response( NGC_Admin_Nav_Layout::get(), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function nav_save( $request ) {
		$body = (array) $request->get_json_params();
		$scope = sanitize_key( (string) ( $body['scope'] ?? 'user' ) );
		return new WP_REST_Response(
			[
				'ok'     => true,
				'layout' => NGC_Admin_Nav_Layout::save( (array) ( $body['layout'] ?? $body ), $scope ),
				'tree'   => NGC_Admin_Nav_Tree::build(),
			],
			200
		);
	}

	/** @return WP_REST_Response */
	public static function nav_reset() {
		return new WP_REST_Response(
			[ 'ok' => true, 'layout' => NGC_Admin_Nav_Layout::reset(), 'tree' => NGC_Admin_Nav_Tree::build() ],
			200
		);
	}

	/** @return WP_REST_Response */
	public static function theme_get() {
		return new WP_REST_Response( NGC_Admin_Theme::get(), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function theme_save( $request ) {
		$body  = (array) $request->get_json_params();
		$scope = sanitize_key( (string) ( $body['scope'] ?? 'user' ) );
		$theme = (array) ( $body['theme'] ?? $body );
		return new WP_REST_Response( [ 'ok' => true, 'theme' => NGC_Admin_Theme::save( $theme, $scope ) ], 200 );
	}

	/** @return WP_REST_Response */
	public static function notif_list() {
		return new WP_REST_Response( [ 'items' => NGC_Admin_Notifications::list_items() ], 200 );
	}

	/**
	 * Orchestration Cockpit live snapshot.
	 *
	 * @return WP_REST_Response
	 */
	public static function cockpit() {
		$snap = class_exists( 'NGC_Observability_Service' )
			? NGC_Observability_Service::cockpit_snapshot()
			: [ 'error' => 'observability_unavailable' ];
		return new WP_REST_Response( $snap, 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function notif_mutate( $request ) {
		$body = (array) $request->get_json_params();
		$op   = sanitize_key( (string) ( $body['op'] ?? 'ack' ) );
		$ids  = array_map( 'strval', (array) ( $body['ids'] ?? [] ) );
		return new WP_REST_Response( [ 'items' => NGC_Admin_Notifications::mutate( $ids, $op, $body ) ], 200 );
	}

	/** @return WP_REST_Response */
	public static function prefs_get() {
		return new WP_REST_Response( NGC_Admin_Prefs::get(), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function prefs_save( $request ) {
		return new WP_REST_Response( [ 'ok' => true, 'prefs' => NGC_Admin_Prefs::save( (array) $request->get_json_params() ) ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function entity_list( $request ) {
		$key  = sanitize_key( (string) $request['key'] );
		$args = [
			'page'     => (int) $request->get_param( 'page' ),
			'per_page' => (int) $request->get_param( 'per_page' ),
			'search'   => (string) $request->get_param( 'search' ),
			'status'   => (string) $request->get_param( 'status' ),
			'priority' => (string) $request->get_param( 'priority' ),
		];
		$result = NGC_Admin_Crud::list_items( $key, $args );
		$code   = empty( $result['ok'] ) ? 400 : 200;
		return new WP_REST_Response( $result, $code );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function entity_get( $request ) {
		$key    = sanitize_key( (string) $request['key'] );
		$result = NGC_Admin_Crud::get_item( $key, (int) $request['id'] );
		if ( ! empty( $result['ok'] ) && ! empty( $result['item'] ) ) {
			$result['detail_html'] = NGC_Admin_Crud::render_detail_html( $key, (array) $result['item'] );
		}
		return new WP_REST_Response( $result, empty( $result['ok'] ) ? 404 : 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function entity_update( $request ) {
		$key    = sanitize_key( (string) $request['key'] );
		$result = NGC_Admin_Crud::update_item( $key, (int) $request['id'], (array) $request->get_json_params() );
		return new WP_REST_Response( $result, empty( $result['ok'] ) ? 400 : 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function entity_delete( $request ) {
		$result = NGC_Admin_Crud::delete_item( sanitize_key( (string) $request['key'] ), (int) $request['id'] );
		return new WP_REST_Response( $result, empty( $result['ok'] ) ? 400 : 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function entity_export( $request ) {
		$body   = (array) $request->get_json_params();
		$result = NGC_Admin_Export::export_entity(
			sanitize_key( (string) $request['key'] ),
			sanitize_key( (string) ( $body['format'] ?? 'csv' ) ),
			$body
		);
		return new WP_REST_Response( $result, empty( $result['ok'] ) ? 400 : 200 );
	}

	/** @return WP_REST_Response */
	public static function components() {
		return new WP_REST_Response( [ 'items' => NGC_Admin_Components::catalog() ], 200 );
	}
}
