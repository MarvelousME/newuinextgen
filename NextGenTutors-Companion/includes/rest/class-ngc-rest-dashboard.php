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

	/** @internal */
	private const SESSION_LIST_LIMIT = 10;

	/** @internal */
	private const TUTOR_KPI_LIMIT = 50;

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
		$user_id  = get_current_user_id();
		$bookings = NGC_Bookings::query( [ 'student_user_id' => $user_id, 'limit' => self::SESSION_LIST_LIMIT ] );
		$digest   = self::session_digest( $bookings, $user_id );

		return new WP_REST_Response(
			self::response(
				array_merge(
					self::compose_learner_data(
						self::user_card( $user_id, true ),
						[
							'sessionsCompleted' => $digest['completed'],
							'avgRatingGiven'    => NGC_Reviews::average_given_by_parent( $user_id ) ?: null,
							'accountBalance'    => NGC_Wallet::balance( $user_id ),
							'achievementCount'  => class_exists( 'NGC_Gamification' ) ? (int) ( NGC_Gamification::scorecard( $user_id )['achievementCount'] ?? 0 ) : (int) get_user_meta( $user_id, 'ngc_achievement_count', true ),
						],
						$digest
					),
					self::maybe_charts( 'student', $user_id )
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
		$bookings = NGC_Bookings::query_for_parent( $user_id, self::SESSION_LIST_LIMIT );
		$digest   = self::session_digest( $bookings, $user_id );

		$learners = get_user_meta( $user_id, 'ngc_learners', true );
		if ( ! is_array( $learners ) ) {
			$learners = [];
		}

		return new WP_REST_Response(
			self::response(
				array_merge(
					self::compose_learner_data(
						self::user_card( $user_id, true ),
						[
							'sessionsCompleted' => $digest['completed'],
							'avgRatingGiven'    => NGC_Reviews::average_given_by_parent( $user_id ) ?: null,
							'accountBalance'    => NGC_Wallet::balance( $user_id ),
							'achievementCount'  => class_exists( 'NGC_Gamification' ) ? (int) ( NGC_Gamification::scorecard( $user_id )['achievementCount'] ?? count( $learners ) ) : count( $learners ),
							'learnerCount'      => count( $learners ),
						],
						$digest,
						[ 'learners' => $learners ]
					),
					self::maybe_charts( 'parent', $user_id )
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
		$digest  = self::session_digest(
			NGC_Bookings::query(
				[
					'tutor_user_id' => $user_id,
					'limit'         => self::SESSION_LIST_LIMIT,
				]
			),
			$user_id
		);

		return new WP_REST_Response(
			self::response(
				array_merge(
					self::compose_tutor_data(
						self::user_card( $user_id, false ),
						self::tutor_kpis( $user_id ),
						self::tutor_application_payload( $user_id ),
						$digest
					),
					self::maybe_charts( 'tutor', $user_id )
				),
				'real'
			),
			200
		);
	}

	/**
	 * Student/parent dashboard `data` shell (theme JS contract).
	 *
	 * @internal
	 * @param array<string, mixed> $user   User card.
	 * @param array<string, mixed> $kpis   KPI object.
	 * @param array<string, mixed> $digest session_digest() result.
	 * @param array<string, mixed> $extra  Extra keys (e.g. learners).
	 * @return array<string, mixed>
	 */
	public static function compose_learner_data( $user, $kpis, $digest, $extra = [] ) {
		return array_merge(
			[
				'user' => $user,
				'kpis' => $kpis,
			],
			is_array( $extra ) ? $extra : [],
			[
				'recentSessions' => $digest['recent'] ?? [],
				'nextSession'    => $digest['next'] ?? null,
			]
		);
	}

	/**
	 * Admin dashboard `data` shell.
	 *
	 * @internal
	 * @param array<string, mixed> $user User card.
	 * @param array<string, mixed> $kpis KPI object.
	 * @return array<string, mixed>
	 */
	public static function compose_admin_data( $user, $kpis ) {
		return [
			'user'           => $user,
			'kpis'           => $kpis,
			'recentSessions' => [],
			'nextSession'    => null,
		];
	}

	/**
	 * Assemble tutor dashboard `data` (theme JS contract). Used by tutor().
	 *
	 * @internal
	 * @param array<string, mixed>      $user        User card.
	 * @param array<string, mixed>      $kpis        KPI object.
	 * @param array<string, mixed>|null $application Application payload.
	 * @param array<string, mixed>      $digest      session_digest() result.
	 * @return array<string, mixed>
	 */
	public static function compose_tutor_data( $user, $kpis, $application, $digest ) {
		return [
			'user'           => $user,
			'kpis'           => $kpis,
			'application'    => $application,
			'recentSessions' => $digest['recent'] ?? [],
			'nextSession'    => $digest['next'] ?? null,
		];
	}

	/**
	 * Stable tutor KPI object. Used by tutor_kpis().
	 *
	 * @internal
	 * @param float      $month_earnings Month earnings.
	 * @param int        $sessions       Completed sessions this month.
	 * @param float|null $rating         Average rating.
	 * @param mixed      $payout         Pending payout.
	 * @return array<string, mixed>
	 */
	public static function compose_tutor_kpis( $month_earnings, $sessions, $rating, $payout ) {
		return [
			'monthEarnings' => $month_earnings,
			'sessionsMonth' => $sessions,
			'averageRating' => $rating,
			'pendingPayout' => $payout,
		];
	}

	/**
	 * @param object|null $app_row Application row.
	 * @return array<string, mixed>|null
	 */
	public static function application_payload( $app_row ) {
		if ( ! $app_row ) {
			return null;
		}
		return [
			'status'      => $app_row->status,
			'reviewNotes' => (string) $app_row->review_notes,
			'submittedAt' => $app_row->created_at,
			'updatedAt'   => $app_row->updated_at,
		];
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
					self::compose_admin_data(
						[
							'id'          => get_current_user_id(),
							'displayName' => wp_get_current_user()->display_name,
						],
						[
							'revenue'  => $revenue,
							'sessions' => $sessions,
							'tutors'   => $tutors,
							'pending'  => $pending,
						]
					),
					self::maybe_charts( 'admin' )
				),
				'real'
			),
			200
		);
	}

	/**
	 * @param array<int, object> $bookings Bookings.
	 * @param int                $user_id  Viewer.
	 * @return array{recent:array<int, mixed>,next:mixed,completed:int}
	 */
	private static function session_digest( $bookings, $user_id ) {
		$bookings = array_values( (array) $bookings );
		$recent   = array_map(
			static function ( $b ) use ( $user_id ) {
				return NGC_Bookings::format_session_row( $b, $user_id );
			},
			$bookings
		);
		$next      = null;
		$completed = 0;
		foreach ( $bookings as $i => $b ) {
			if ( ! is_object( $b ) ) {
				continue;
			}
			if ( ! $next && in_array( $b->status, [ 'requested', 'confirmed' ], true ) ) {
				$next = $recent[ $i ] ?? null;
			}
			if ( 'completed' === $b->status ) {
				++$completed;
			}
		}
		return [
			'recent'    => $recent,
			'next'      => $next,
			'completed' => $completed,
		];
	}

	/**
	 * @param int  $user_id     User ID.
	 * @param bool $include_email Include email.
	 * @return array<string, mixed>
	 */
	private static function user_card( $user_id, $include_email ) {
		$user = get_user_by( 'id', $user_id );
		$card = [
			'id'          => $user_id,
			'displayName' => $user ? $user->display_name : '',
		];
		if ( $include_email ) {
			$card['email'] = $user ? $user->user_email : '';
		}
		return $card;
	}

	/**
	 * @param string   $role    Role.
	 * @param int|null $user_id User ID.
	 * @return array<string, mixed>
	 */
	private static function maybe_charts( $role, $user_id = 0 ) {
		if ( ! class_exists( 'NGC_Dashboard_Analytics' ) ) {
			return [];
		}
		return [ 'charts' => NGC_Dashboard_Analytics::charts_for_role( $role, $user_id ) ];
	}

	/**
	 * @param int $user_id Tutor user ID.
	 * @return array<string, mixed>
	 */
	private static function tutor_kpis( $user_id ) {
		$bookings    = NGC_Bookings::query( [ 'tutor_user_id' => $user_id, 'limit' => self::TUTOR_KPI_LIMIT ] );
		$month       = 0;
		$sessions    = 0;
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

		return self::compose_tutor_kpis(
			$month_earnings ?: $month,
			$sessions,
			NGC_Reviews::average_for_tutor( $user_id ) ?: null,
			NGC_Reviews::pending_payout_for_tutor( $user_id )
		);
	}

	/**
	 * @param int $user_id Tutor user ID.
	 * @return array<string, mixed>|null
	 */
	private static function tutor_application_payload( $user_id ) {
		global $wpdb;
		$apps_table = NGC_Database::table( 'tutor_applications' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$app_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT status, review_notes, created_at, updated_at FROM {$apps_table} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
				$user_id
			)
		);
		return self::application_payload( $app_row );
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
