<?php
/**
 * Wallet and invoice REST endpoints.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Finance REST routes.
 */
class NGC_Rest_Finance {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/wallet',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'wallet' ],
				'permission_callback' => [ __CLASS__, 'can_view_own_finance' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/wallet/ledger',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'wallet_ledger' ],
				'permission_callback' => [ __CLASS__, 'can_view_own_finance' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/invoices',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'list_invoices' ],
				'permission_callback' => [ __CLASS__, 'can_view_own_finance' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/invoices/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'get_invoice' ],
				'permission_callback' => [ __CLASS__, 'can_view_invoice' ],
			]
		);
	}

	/**
	 * @return bool
	 */
	public static function can_view_own_finance() {
		return is_user_logged_in();
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public static function can_view_invoice( $request ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) || current_user_can( 'ngc_admin_operations' ) ) {
			return true;
		}
		global $wpdb;
		$table = NGC_Database::table( 'invoices' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$owner = (int) $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$table} WHERE id = %d", (int) $request['id'] ) );
		return $owner === get_current_user_id();
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function wallet( $request ) {
		$user_id = self::resolve_user_id( $request );
		return new WP_REST_Response(
			[
				'user_id'  => $user_id,
				'balance'  => NGC_Wallet::balance( $user_id ),
				'currency' => 'ZAR',
			],
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function wallet_ledger( $request ) {
		$user_id = self::resolve_user_id( $request );
		$limit   = min( 100, max( 1, (int) $request->get_param( 'limit' ) ?: 20 ) );
		return new WP_REST_Response( NGC_Wallet::ledger( $user_id, $limit ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function list_invoices( $request ) {
		$user_id = self::resolve_user_id( $request );
		$limit   = min( 100, max( 1, (int) $request->get_param( 'limit' ) ?: 10 ) );
		return new WP_REST_Response( NGC_Invoices::for_user( $user_id, $limit ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_invoice( $request ) {
		$invoice = NGC_Invoices::get( (int) $request['id'] );
		return $invoice ? new WP_REST_Response( $invoice, 200 ) : NGC_Rest::error_response( 'not_found', '', 404 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return int
	 */
	private static function resolve_user_id( $request ) {
		$requested = (int) $request->get_param( 'user_id' );
		if ( $requested && ( current_user_can( 'manage_options' ) || current_user_can( 'ngc_admin_operations' ) ) ) {
			return $requested;
		}
		return get_current_user_id();
	}
}
