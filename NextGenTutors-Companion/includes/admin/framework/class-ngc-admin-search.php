<?php
/**
 * Global administration search (REST + AJAX).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Indexes registered screens for quick navigation.
 */
final class NGC_Admin_Search {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
		add_action( 'wp_ajax_ngt_admin_search', [ __CLASS__, 'ajax_search' ] );
	}

	/**
	 * REST route.
	 */
	public static function register_routes() {
		register_rest_route(
			'ngc/v1',
			'/admin/search',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'rest_search' ],
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' ) || current_user_can( 'ngc_manage_matches' );
				},
				'args'                => [
					'q' => [ 'type' => 'string', 'required' => true ],
				],
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_search( $request ) {
		$q = (string) $request->get_param( 'q' );
		return new WP_REST_Response( [ 'results' => NGC_Admin_Registry::search( $q ) ], 200 );
	}

	/**
	 * AJAX fallback.
	 */
	public static function ajax_search() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'ngc_manage_matches' ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		check_ajax_referer( 'ngt_admin_search', 'nonce' );
		$q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['q'] ) ) : '';
		wp_send_json_success( [ 'results' => NGC_Admin_Registry::search( $q ) ] );
	}
}
