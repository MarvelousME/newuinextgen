<?php
/**
 * Dashboard REST endpoints.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GET /dashboard/student|tutor|admin
 */
class NGC_Rest_Dashboard {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/dashboard/student',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'student' ],
				'permission_callback' => [ 'NGC_Rest', 'require_login' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/dashboard/parent',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'parent' ],
				'permission_callback' => [ 'NGC_Rest', 'require_login' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/dashboard/tutor',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'tutor' ],
				'permission_callback' => [ 'NGC_Rest', 'require_login' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/dashboard/admin',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'admin' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function student( $request ) {
		$user_id = get_current_user_id();
		$user    = get_user_by( 'id', $user_id );

		$bookings = NGC_Bookings::query( [ 'student_user_id' => $user_id, 'limit' => 10 ] );
		$recent   = array_map(
			static function ( $b ) use ( $user_id ) {
				return NGC_Bookings::format_session_row( $b, $user_id );
			},
			$bookings
		);

		$next = null;
		foreach ( $bookings as $b ) {
			if ( in_array( $b->status, [ 'requested', 'confirmed' ], true ) ) {
				$next = NGC_Bookings::format_session_row( $b, $user_id );
				break;
			}
		}

		$completed = 0;
		foreach ( $bookings as $b ) {
			if ( 'completed' === $b->status ) {
				++$completed;
			}
		}

		return new WP_REST_Response(
			self::response(
				array_merge(
					[
				'user' => [
					'id'          => $user_id,
					'displayName' => $user ? $user->display_name : '',
					'email'       => $user ? $user->user_email : '',
				],
				'kpis' => [
					'sessionsCompleted' => $completed,
					'avgRatingGiven'    => NGC_Reviews::average_given_by_parent( $user_id ) ?: null,
					'accountBalance'    => NGC_Wallet::balance( $user_id ),
					'achievementCount'  => class_exists( 'NGC_Gamification' ) ? (int) ( NGC_Gamification::scorecard( $user_id )['achievementCount'] ?? 0 ) : (int) get_user_meta( $user_id, 'ngc_achievement_count', true ),
				],
				'recentSessions' => $recent,
				'nextSession'    => $next,
					],
					class_exists( 'NGC_Dashboard_Analytics' ) ? [ 'charts' => NGC_Dashboard_Analytics::charts_for_role( 'student', $user_id ) ] : []
				),
			'real'
			),
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function parent( $request ) {
		$user_id  = get_current_user_id();
		$user     = get_user_by( 'id', $user_id );
		$bookings = NGC_Bookings::query_for_parent( $user_id, 10 );
		$recent   = array_map(
			static function ( $b ) use ( $user_id ) {
				return NGC_Bookings::format_session_row( $b, $user_id );
			},
			$bookings
		);

		$next = null;
		foreach ( $bookings as $b ) {
			if ( in_array( $b->status, [ 'requested', 'confirmed' ], true ) ) {
				$next = NGC_Bookings::format_session_row( $b, $user_id );
				break;
			}
		}

		$completed = 0;
		foreach ( $bookings as $b ) {
			if ( 'completed' === $b->status ) {
				++$completed;
			}
		}

		$learners = get_user_meta( $user_id, 'ngc_learners', true );
		if ( ! is_array( $learners ) ) {
			$learners = [];
		}

		return new WP_REST_Response(
			self::response(
				array_merge(
					[
				'user' => [
					'id'          => $user_id,
					'displayName' => $user ? $user->display_name : '',
					'email'       => $user ? $user->user_email : '',
				],
				'kpis' => [
					'sessionsCompleted' => $completed,
					'avgRatingGiven'    => NGC_Reviews::average_given_by_parent( $user_id ) ?: null,
					'accountBalance'    => NGC_Wallet::balance( $user_id ),
					'achievementCount'  => class_exists( 'NGC_Gamification' ) ? (int) ( NGC_Gamification::scorecard( $user_id )['achievementCount'] ?? count( $learners ) ) : count( $learners ),
					'learnerCount'      => count( $learners ),
				],
				'learners'       => $learners,
				'recentSessions' => $recent,
				'nextSession'    => $next,
					],
					class_exists( 'NGC_Dashboard_Analytics' ) ? [ 'charts' => NGC_Dashboard_Analytics::charts_for_role( 'parent', $user_id ) ] : []
				),
			'real'
			),
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function tutor( $request ) {
		$user_id = get_current_user_id();
		$user    = get_user_by( 'id', $user_id );

		$bookings = NGC_Bookings::query( [ 'tutor_user_id' => $user_id, 'limit' => 50 ] );
		$month    = 0;
		$sessions = 0;
		$month_start = gmdate( 'Y-m-01' );

		foreach ( $bookings as $b ) {
			if ( 'completed' === $b->status && $b->updated_at >= $month_start ) {
				++$sessions;
				$month += (float) $b->amount;
			}
		}

		global $wpdb;
		$earnings_table = NGC_Database::table( 'earnings' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$month_earnings = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(amount) FROM {$earnings_table} WHERE tutor_user_id = %d AND earned_at >= %s",
				$user_id,
				$month_start
			)
		);

		return new WP_REST_Response(
			self::response(
				array_merge(
					[
				'user' => [
					'id'          => $user_id,
					'displayName' => $user ? $user->display_name : '',
				],
				'kpis' => [
					'monthEarnings'  => $month_earnings ?: $month,
					'sessionsMonth'  => $sessions,
					'averageRating'  => NGC_Reviews::average_for_tutor( $user_id ) ?: null,
					'pendingPayout'  => NGC_Reviews::pending_payout_for_tutor( $user_id ),
				],
				'recentSessions' => [],
				'nextSession'    => null,
					],
					class_exists( 'NGC_Dashboard_Analytics' ) ? [ 'charts' => NGC_Dashboard_Analytics::charts_for_role( 'tutor', $user_id ) ] : []
				),
			'real'
			),
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function admin( $request ) {
		global $wpdb;

		$bookings_table = NGC_Database::table( 'bookings' );
		$invoices_table = NGC_Database::table( 'invoices' );
		$apps_table     = NGC_Database::table( 'tutor_applications' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$revenue = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$invoices_table} WHERE status = 'paid'" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sessions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bookings_table} WHERE status = 'completed'" );
		$tutors   = count( get_users( [ 'role' => 'tutor', 'fields' => 'ID' ] ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$pending  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$apps_table} WHERE status = %s", 'pending' ) );

		return new WP_REST_Response(
			self::response(
				array_merge(
					[
				'user' => [
					'id'          => get_current_user_id(),
					'displayName' => wp_get_current_user()->display_name,
				],
				'kpis' => [
					'revenue'  => $revenue,
					'sessions' => $sessions,
					'tutors'   => $tutors,
					'pending'  => $pending,
				],
				'recentSessions' => [],
				'nextSession'    => null,
					],
					class_exists( 'NGC_Dashboard_Analytics' ) ? [ 'charts' => NGC_Dashboard_Analytics::charts_for_role( 'admin' ) ] : []
				),
			'real'
			),
			200
		);
	}

	/**
	 * @param array<string, mixed> $data   Dashboard payload.
	 * @param string               $source real|demo|fallback.
	 * @return array<string, mixed>
	 */
	private static function response( $data, $source ) {
		return [
			'success' => true,
			'data'    => $data,
			'meta'    => [
				'source'       => $source,
				'retrieved_at' => gmdate( 'c' ),
			],
		];
	}
}
