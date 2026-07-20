<?php
/**
 * Gamification platform orchestrator.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central gamification service — GamiPress + internal engines.
 */
class NGC_Gamification {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'ngc_workflow_dispatched', [ __CLASS__, 'on_workflow' ], 10, 2 );
		add_action( 'ngc_tutor_approved', [ __CLASS__, 'on_tutor_approved' ] );
		add_action( 'ngc_review_submitted', [ __CLASS__, 'on_review_submitted' ] );
		add_action( 'ngc_lesson_completed', [ __CLASS__, 'on_lesson_completed' ] );
		add_action( 'ngc_payment_received', [ __CLASS__, 'on_payment' ] );
		add_action( 'user_register', [ __CLASS__, 'on_user_register' ], 20, 1 );
		add_action( 'admin_init', [ __CLASS__, 'ensure_gamipress_types' ] );
		add_action( 'plugins_loaded', [ __CLASS__, 'bootstrap_gamipress' ], 25 );
		add_action( 'ngc_recompute_leaderboards', [ __CLASS__, 'recompute_leaderboards' ] );
		if ( ! wp_next_scheduled( 'ngc_recompute_leaderboards' ) ) {
			wp_schedule_event( time(), 'hourly', 'ngc_recompute_leaderboards' );
		}
	}

	/**
	 * Process workflow events for gamification.
	 *
	 * @param string               $event Full event key.
	 * @param array<string, mixed> $vars  Variables.
	 */
	public static function on_workflow( $event, $vars ) {
		$map = [
			'ngt.tutor_application.submitted' => [ 'tutor_registration', 'tutor' ],
			'ngt.tutor.approved'              => [ 'tutor_approval', 'tutor' ],
			'ngt.parent_register.submitted'   => [ 'parent_registration', 'parent' ],
			'ngt.student_register.submitted'  => [ 'student_registration', 'student' ],
			'ngt.lesson.completed'            => [ 'lesson_completion', 'student' ],
			'ngt.review.submitted'            => [ 'review_submission', 'student' ],
			'ngt.payment.received'            => [ 'payment_completion', 'parent' ],
			'ngt.match.accepted'              => [ 'booking_completion', 'student' ],
		];
		if ( ! isset( $map[ $event ] ) ) {
			return;
		}
		$user_id = (int) ( $vars['user_id'] ?? $vars['tutor_user_id'] ?? $vars['student_user_id'] ?? $vars['parent_user_id'] ?? 0 );
		if ( $user_id <= 0 ) {
			return;
		}
		self::process_event( $user_id, $map[ $event ][0], $vars );
	}

	/**
	 * @param array<string, mixed> $vars Variables.
	 */
	public static function on_tutor_approved( $vars ) {
		$user_id = (int) ( $vars['user_id'] ?? 0 );
		if ( $user_id ) {
			self::process_event( $user_id, 'tutor_approval', $vars );
		}
	}

	/**
	 * @param array<string, mixed> $vars Variables.
	 */
	public static function on_review_submitted( $vars ) {
		$user_id = (int) ( $vars['user_id'] ?? $vars['student_user_id'] ?? 0 );
		if ( $user_id ) {
			self::process_event( $user_id, 'review_submission', $vars );
		}
	}

	/**
	 * @param array<string, mixed> $vars Variables.
	 */
	public static function on_lesson_completed( $vars ) {
		foreach ( [ 'student_user_id', 'tutor_user_id' ] as $key ) {
			$uid = (int) ( $vars[ $key ] ?? 0 );
			if ( $uid ) {
				self::process_event( $uid, 'lesson_completion', $vars );
			}
		}
	}

	/**
	 * @param array<string, mixed> $vars Variables.
	 */
	public static function on_payment( $vars ) {
		$user_id = (int) ( $vars['user_id'] ?? $vars['parent_user_id'] ?? 0 );
		if ( $user_id ) {
			self::process_event( $user_id, 'payment_completion', $vars );
		}
	}

	/**
	 * @param int $user_id User ID.
	 */
	public static function on_user_register( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		$roles = (array) $user->roles;
		if ( in_array( 'parent', $roles, true ) || in_array( 'parent_guardian', $roles, true ) ) {
			self::process_event( $user_id, 'parent_registration', [] );
		} elseif ( in_array( 'student', $roles, true ) ) {
			self::process_event( $user_id, 'student_registration', [] );
		} elseif ( in_array( 'tutor_applicant', $roles, true ) ) {
			self::process_event( $user_id, 'tutor_registration', [] );
		}
	}

	/**
	 * @param int                  $user_id   User ID.
	 * @param string               $event_key Event slug.
	 * @param array<string, mixed> $context   Context.
	 */
	public static function process_event( $user_id, $event_key, $context = [] ) {
		$awarded = NGC_Scoring_Engine::award_event( $user_id, $event_key, $context );
		NGC_Achievement_Engine::check_event_achievements( $user_id, $event_key, $context );

		foreach ( $awarded as $point_type => $amount ) {
			NGC_Gamipress_Adapter::award_points( $user_id, $point_type, $amount, $event_key );
		}

		NGC_Audit::log( 'gamification_event', 'user', $user_id, [
			'event_key' => $event_key,
			'awarded'   => $awarded,
		] );
	}

	/**
	 * Personal scorecard for dashboards.
	 *
	 * @param int $user_id User ID.
	 * @return array<string, mixed>
	 */
	public static function scorecard( $user_id ) {
		$balances = NGC_Scoring_Engine::get_all_balances( $user_id );
		$xp       = NGC_Gamipress_Adapter::is_active()
			? NGC_Gamipress_Adapter::get_points( $user_id, 'xp' )
			: NGC_Scoring_Engine::get_balance( $user_id, 'xp' );

		return [
			'xp'              => $xp,
			'balances'        => $balances,
			'achievementCount'=> (int) get_user_meta( $user_id, 'ngc_achievement_count', true ),
			'achievements'    => NGC_Achievement_Engine::history( $user_id, 10 ),
			'rank'            => NGC_Leaderboard_Engine::user_rank( $user_id, 'overall' ),
			'rankChange'      => get_user_meta( $user_id, 'ngc_rank_change', true ) ?: 0,
			'rewardsSummary'  => array_sum( $balances ),
		];
	}

	/**
	 * Badge rows for UI library components.
	 *
	 * @param int                  $user_id User ID.
	 * @param array<string, mixed> $args    Args.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_user_badges( $user_id, $args = [] ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return [];
		}

		$limit = max( 1, min( 24, (int) ( $args['limit'] ?? 12 ) ) );
		$badges = [];

		if ( function_exists( 'gamipress_get_user_achievements' ) ) {
			$achievements = gamipress_get_user_achievements(
				[
					'user_id'          => $user_id,
					'achievement_type' => 'badge',
					'limit'            => $limit,
				]
			);
			if ( is_array( $achievements ) ) {
				foreach ( $achievements as $achievement ) {
					$badges[] = [
						'id'    => (int) ( $achievement->ID ?? 0 ),
						'title' => (string) ( $achievement->post_title ?? '' ),
						'icon'  => get_the_post_thumbnail_url( $achievement->ID ?? 0, 'thumbnail' ) ?: '',
					];
				}
			}
		}

		if ( empty( $badges ) && class_exists( 'NGC_Achievement_Engine' ) ) {
			foreach ( NGC_Achievement_Engine::history( $user_id, $limit ) as $row ) {
				$badges[] = [
					'id'    => (int) ( $row['id'] ?? 0 ),
					'title' => (string) ( $row['title'] ?? $row['key'] ?? '' ),
					'icon'  => (string) ( $row['icon'] ?? '' ),
				];
			}
		}

		return $badges;
	}

	/**
	 * Ensure GamiPress point types.
	 */
	public static function ensure_gamipress_types() {
		if ( current_user_can( 'manage_options' ) ) {
			NGC_Gamipress_Adapter::ensure_point_types();
		}
	}

	/**
	 * Seed GamiPress types and achievements when the plugin is active.
	 */
	public static function bootstrap_gamipress() {
		if ( ! NGC_Gamipress_Adapter::is_active() ) {
			return;
		}
		NGC_Gamipress_Adapter::ensure_point_types();
		NGC_Gamipress_Adapter::ensure_achievements();
	}

	/**
	 * Hourly leaderboard recompute.
	 */
	public static function recompute_leaderboards() {
		foreach ( NGC_Leaderboard_Engine::board_keys() as $board ) {
			NGC_Leaderboard_Engine::compute( $board, 'all_time', 100 );
			if ( in_array( $board, [ 'monthly', 'annual' ], true ) ) {
				NGC_Leaderboard_Engine::compute( $board, $board, 100 );
			}
		}
	}
}
