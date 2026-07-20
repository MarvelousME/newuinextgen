<?php
/**
 * Bridge between NextGen Automation Hub and Companion workflow/gamification systems.
 *
 * When the Automation Hub plugin fires events via fire_event(), this bridge
 * forwards them to NGC_Workflows::dispatch() and NGC_Gamification, enabling
 * the Companion's richer action set (email templates, FluentCRM, payout calc,
 * smart matching) to act on Hub triggers.
 *
 * Also registers the daily health check cron that the Hub expects but never schedules.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGC_Automation_Hub_Bridge {

	private static bool $booted = false;

	public static function init(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		add_action( 'ngt_automation_event_fired', [ __CLASS__, 'forward_to_companion' ], 10, 2 );
		add_action( 'ngc_daily_health_check', [ __CLASS__, 'run_health_check' ] );

		if ( ! wp_next_scheduled( 'ngc_daily_health_check' ) ) {
			wp_schedule_event( time(), 'daily', 'ngc_daily_health_check' );
		}

		add_action( 'rest_api_init', [ __CLASS__, 'register_rest' ] );
		add_filter( 'ngt_dashboard_widget_data', [ __CLASS__, 'inject_live_widget_data' ], 10, 2 );
	}

	/**
	 * Forward Hub automation events into Companion workflows + gamification.
	 *
	 * @param string               $event_key Hub event key (e.g. ngt.find_tutor.submitted).
	 * @param array<string, mixed> $payload   Event payload.
	 */
	public static function forward_to_companion( string $event_key, array $payload ): void {
		if ( class_exists( 'NGC_Workflows' ) && method_exists( 'NGC_Workflows', 'dispatch' ) ) {
			NGC_Workflows::dispatch( $event_key, $payload );
		}

		do_action( 'ngc_workflow_dispatched', $event_key, $payload );

		if ( class_exists( 'NGC_System_Log' ) ) {
			NGC_System_Log::info(
				'hub_bridge',
				sprintf( 'Forwarded Hub event "%s" to Companion', $event_key ),
				[ 'payload_keys' => array_keys( $payload ) ]
			);
		}
	}

	/**
	 * Daily health check — fires the Hub event + Companion diagnostics.
	 */
	public static function run_health_check(): void {
		$report = [];

		if ( class_exists( 'NGC_Health_Scanner' ) && method_exists( 'NGC_Health_Scanner', 'quick_scan' ) ) {
			$report = NGC_Health_Scanner::quick_scan();
		}

		$report['timestamp']       = current_time( 'mysql', true );
		$report['php_version']     = phpversion();
		$report['wp_version']      = get_bloginfo( 'version' );
		$report['active_plugins']  = count( get_option( 'active_plugins', [] ) );
		$report['hub_active']      = class_exists( 'NGT_Automation_Hub' );
		$report['companion_active'] = class_exists( 'NGC_Plugin' );

		if ( class_exists( 'NGC_Database' ) ) {
			global $wpdb;
			$tables = [ 'bookings', 'matches', 'payments' ];
			foreach ( $tables as $t ) {
				$tbl = NGC_Database::table( $t );
				$report[ $t . '_count' ] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tbl}" );
			}
		}

		if ( class_exists( 'NGC_System_Log' ) ) {
			NGC_System_Log::info( 'health_check', 'Daily health check completed', $report );
		}

		if ( class_exists( 'NGT_Automation_Hub' ) && method_exists( 'NGT_Automation_Hub', 'fire_event' ) ) {
			NGT_Automation_Hub::fire_event(
				'ngt.daily.health_check',
				'companion_cron',
				0,
				0,
				$report
			);
		}

		do_action( 'ngc_health_check_completed', $report );
	}

	/**
	 * REST endpoints for live dashboard widget data (replaces Hub placeholder cards).
	 */
	public static function register_rest(): void {
		register_rest_route(
			'ngc/v1',
			'/hub/dashboard-widgets/(?P<role>[a-z_]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'rest_dashboard_widgets' ],
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'args'                => [
					'role' => [
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => function ( $v ) {
							return in_array( $v, [ 'student', 'parent', 'tutor', 'admin', 'support' ], true );
						},
					],
				],
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_dashboard_widgets( WP_REST_Request $request ) {
		$role    = $request->get_param( 'role' );
		$user_id = get_current_user_id();
		$widgets = [];

		if ( 'admin' === $role && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'forbidden', 'Admin access required', [ 'status' => 403 ] );
		}

		$widgets['charts'] = class_exists( 'NGC_Dashboard_Analytics' )
			? NGC_Dashboard_Analytics::charts_for_role( $role, $user_id )
			: [];

		$widgets['stats'] = self::stats_for_role( $role, $user_id );

		if ( class_exists( 'NGC_Gamification' ) && method_exists( 'NGC_Gamification', 'user_summary' ) ) {
			$widgets['gamification'] = NGC_Gamification::user_summary( $user_id );
		}

		$widgets = apply_filters( 'ngt_dashboard_widget_data', $widgets, $role );

		return rest_ensure_response( $widgets );
	}

	/**
	 * Quick stats per role from Companion tables.
	 *
	 * @param string $role    Role key.
	 * @param int    $user_id User ID.
	 * @return array<string, int|float>
	 */
	private static function stats_for_role( string $role, int $user_id ): array {
		if ( ! class_exists( 'NGC_Database' ) ) {
			return [];
		}

		global $wpdb;
		$stats = [];

		switch ( $role ) {
			case 'student':
				$stats['upcoming_lessons'] = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM " . NGC_Database::table( 'bookings' ) . " WHERE student_user_id = %d AND status IN ('confirmed','pending') AND session_date >= NOW()",
						$user_id
					)
				);
				$stats['completed_lessons'] = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM " . NGC_Database::table( 'bookings' ) . " WHERE student_user_id = %d AND status = 'completed'",
						$user_id
					)
				);
				break;

			case 'parent':
				$children = NGC_Database::get_children_ids( $user_id );
				$stats['children_count']    = count( $children );
				$stats['active_bookings']   = 0;
				if ( $children ) {
					$in = implode( ',', array_map( 'intval', $children ) );
					$stats['active_bookings'] = (int) $wpdb->get_var(
						"SELECT COUNT(*) FROM " . NGC_Database::table( 'bookings' ) . " WHERE student_user_id IN ({$in}) AND status IN ('confirmed','pending')"
					);
				}
				break;

			case 'tutor':
				$stats['active_students'] = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT student_user_id) FROM " . NGC_Database::table( 'bookings' ) . " WHERE tutor_user_id = %d AND status IN ('confirmed','pending')",
						$user_id
					)
				);
				$stats['total_earnings'] = (float) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COALESCE(SUM(tutor_amount), 0) FROM " . NGC_Database::table( 'payments' ) . " WHERE tutor_user_id = %d AND status = 'completed'",
						$user_id
					)
				);
				$stats['pending_payouts'] = (float) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COALESCE(SUM(tutor_amount), 0) FROM " . NGC_Database::table( 'payments' ) . " WHERE tutor_user_id = %d AND status = 'pending'",
						$user_id
					)
				);
				break;

			case 'admin':
				$stats['total_bookings_today'] = (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM " . NGC_Database::table( 'bookings' ) . " WHERE DATE(created_at) = CURDATE()"
				);
				$stats['total_revenue_today'] = (float) $wpdb->get_var(
					"SELECT COALESCE(SUM(amount), 0) FROM " . NGC_Database::table( 'payments' ) . " WHERE DATE(created_at) = CURDATE() AND status = 'completed'"
				);
				$stats['pending_matches'] = (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM " . NGC_Database::table( 'matches' ) . " WHERE status = 'pending'"
				);
				$stats['open_support_cases'] = (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM " . NGC_Database::table( 'support_cases' ) . " WHERE status = 'open'"
				);
				$stats['active_tutors'] = (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM " . NGC_Database::table( 'tutors' ) . " WHERE status = 'approved'"
				);
				break;
		}

		return $stats;
	}

	/**
	 * Filter callback for live widget injection.
	 *
	 * @param array<string, mixed> $widgets Current widgets.
	 * @param string               $role    Role key.
	 * @return array<string, mixed>
	 */
	public static function inject_live_widget_data( array $widgets, string $role ): array {
		if ( class_exists( 'NGC_Matching' ) && 'admin' === $role ) {
			global $wpdb;
			$table = NGC_Database::table( 'matches' );
			$widgets['matching_queue'] = [
				'pending'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" ),
				'proposed' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'proposed'" ),
			];
		}

		return $widgets;
	}

	/**
	 * Deactivation cleanup.
	 */
	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( 'ngc_daily_health_check' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'ngc_daily_health_check' );
		}
	}
}
