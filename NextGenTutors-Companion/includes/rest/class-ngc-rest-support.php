<?php
/**
 * Public support ticket REST (Fluent Support bridge).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST routes under ngc/v1/support.
 */
class NGC_Rest_Support {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/support/tickets',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'create_ticket' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'name'     => [ 'type' => 'string', 'required' => false ],
					'email'    => [ 'type' => 'string', 'required' => true ],
					'subject'  => [ 'type' => 'string', 'required' => true ],
					'message'  => [ 'type' => 'string', 'required' => true ],
					'category' => [ 'type' => 'string', 'required' => false ],
				],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/support/status',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'status' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_ticket( WP_REST_Request $request ) {
		$email = sanitize_email( (string) $request->get_param( 'email' ) );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'ngc_invalid_email', __( 'A valid email is required.', 'nextgencompanion' ), [ 'status' => 400 ] );
		}

		// Light rate limit by IP.
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'ngc_fs_ticket_' . md5( $ip );
		$hits = (int) get_transient( $key );
		if ( $hits >= 8 ) {
			return new WP_Error( 'ngc_rate_limited', __( 'Too many tickets. Please try again later.', 'nextgencompanion' ), [ 'status' => 429 ] );
		}
		set_transient( $key, $hits + 1, HOUR_IN_SECONDS );

		$name = sanitize_text_field( (string) $request->get_param( 'name' ) );
		if ( '' === $name && is_user_logged_in() ) {
			$name = wp_get_current_user()->display_name;
		}
		if ( '' === $name ) {
			$name = 'Website Guest';
		}

		if ( ! class_exists( 'NGC_FluentSupport_Adapter' ) ) {
			return new WP_Error( 'ngc_fs_missing', __( 'Support adapter unavailable.', 'nextgencompanion' ), [ 'status' => 503 ] );
		}

		$adapter = new NGC_FluentSupport_Adapter();
		$result  = $adapter->create_ticket(
			[
				'name'     => $name,
				'email'    => $email,
				'subject'  => (string) $request->get_param( 'subject' ),
				'message'  => (string) $request->get_param( 'message' ),
				'category' => (string) $request->get_param( 'category' ),
			]
		);

		if ( empty( $result['ok'] ) ) {
			return new WP_Error(
				$result['code'] ?? 'ngc_fs_failed',
				$result['message'] ?? __( 'Could not create support ticket.', 'nextgencompanion' ),
				[ 'status' => 502, 'data' => $result ]
			);
		}

		return rest_ensure_response( $result );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function status() {
		$adapter = class_exists( 'NGC_FluentSupport_Adapter' ) ? new NGC_FluentSupport_Adapter() : null;
		$page    = get_page_by_path( 'support' );
		return rest_ensure_response(
			[
				'active'     => $adapter && $adapter->is_available(),
				'mailbox_id' => (int) get_option( NGC_FluentSupport_Adapter::OPTION_MAILBOX_ID, 0 ),
				'portal_url' => $page ? get_permalink( $page ) : home_url( '/support/' ),
				'verify'     => $adapter ? $adapter->verify() : [ 'ok' => false ],
			]
		);
	}
}
