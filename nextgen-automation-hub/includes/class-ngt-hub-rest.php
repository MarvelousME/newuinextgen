<?php
/**
 * REST API for dashboards, notifications, calendar, and matching.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_REST {

	public static function register_hooks(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	public static function register_routes(): void {
		register_rest_route(
			'ngt/v1',
			'/dashboard/(?P<role>[a-z_]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'dashboard' ],
				'permission_callback' => 'is_user_logged_in',
			]
		);

		register_rest_route(
			'ngt/v1',
			'/notifications',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'notifications' ],
				'permission_callback' => 'is_user_logged_in',
			]
		);

		register_rest_route(
			'ngt/v1',
			'/notifications/(?P<id>\d+)/read',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'mark_notification_read' ],
				'permission_callback' => 'is_user_logged_in',
			]
		);

		register_rest_route(
			'ngt/v1',
			'/calendar',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'calendar' ],
				'permission_callback' => 'is_user_logged_in',
			]
		);

		register_rest_route(
			'ngt/v1',
			'/gamification',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'gamification' ],
				'permission_callback' => 'is_user_logged_in',
			]
		);

		register_rest_route(
			'ngt/v1',
			'/matches/pending',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'pending_matches' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' ) || current_user_can( 'ngt_manage_hub' );
				},
			]
		);

		register_rest_route(
			'ngt/v1',
			'/lessons/(?P<id>\d+)/complete',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'complete_lesson' ],
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			]
		);
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public static function dashboard( WP_REST_Request $request ) {
		$role = sanitize_key( $request['role'] );
		if ( 'admin' === $role && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'forbidden', __( 'Admin access required.', 'nextgen-automation-hub' ), [ 'status' => 403 ] );
		}
		return rest_ensure_response( NGT_Hub_Dashboard::get_live_data( $role, get_current_user_id() ) );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function notifications( WP_REST_Request $request ): WP_REST_Response {
		$unread = (bool) $request->get_param( 'unread' );
		return rest_ensure_response(
			NGT_Hub_Notifications::for_user( get_current_user_id(), 30, $unread )
		);
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public static function mark_notification_read( WP_REST_Request $request ) {
		$id = (int) $request['id'];
		NGT_Hub_Notifications::mark_read( $id, get_current_user_id() );
		return rest_ensure_response( [ 'ok' => true ] );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function calendar( WP_REST_Request $request ): WP_REST_Response {
		$month = sanitize_text_field( (string) ( $request->get_param( 'month' ) ?: gmdate( 'Y-m' ) ) );
		$role  = sanitize_key( (string) ( $request->get_param( 'role' ) ?: 'student' ) );
		return rest_ensure_response(
			NGT_Hub_Calendar::events_for_user( get_current_user_id(), $role, $month )
		);
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function gamification(): WP_REST_Response {
		return rest_ensure_response( NGT_Hub_Gamification::user_summary( get_current_user_id() ) );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function pending_matches(): WP_REST_Response {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT * FROM " . NGT_Hub_Database::table( 'matches' ) . " WHERE status IN ('pending','proposed') ORDER BY created_at DESC LIMIT 50",
			ARRAY_A
		);
		return rest_ensure_response( is_array( $rows ) ? $rows : [] );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public static function complete_lesson( WP_REST_Request $request ) {
		$id   = (int) $request['id'];
		$note = sanitize_textarea_field( (string) $request->get_param( 'note' ) );
		if ( ! NGT_Hub_Lessons::mark_complete( $id, $note ) ) {
			return new WP_Error( 'ngt_invalid_lesson', __( 'Invalid lesson.', 'nextgen-automation-hub' ), [ 'status' => 400 ] );
		}
		return rest_ensure_response( [ 'ok' => true ] );
	}
}
