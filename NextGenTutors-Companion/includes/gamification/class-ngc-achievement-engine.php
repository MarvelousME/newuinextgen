<?php
/**
 * Internal achievement engine.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Achievement definitions and awards.
 */
class NGC_Achievement_Engine {

	/**
	 * Achievement catalog.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function catalog() {
		// Aligned to System Triggers GamiPress map (Match).
		return apply_filters(
			'ngc_achievement_catalog',
			[
				'beginner'                 => [ 'category' => 'student', 'title' => 'Beginner', 'points' => 100, 'slug' => 'beginner' ],
				'payment_first'            => [ 'category' => 'student', 'title' => 'Beginner', 'points' => 100, 'slug' => 'beginner' ],
				'quick_learner'            => [ 'category' => 'student', 'title' => 'Quick Learner', 'points' => 150 ],
				'scholar'                  => [ 'category' => 'student', 'title' => 'Scholar', 'points' => 500 ],
				'master'                   => [ 'category' => 'student', 'title' => 'Master', 'points' => 2000 ],
				'student_first_lesson'     => [ 'category' => 'student', 'title' => 'First Lesson', 'points' => 50 ],
				'student_streak_5'         => [ 'category' => 'student', 'title' => '5-Day Streak', 'points' => 100 ],
				'student_streak_30'        => [ 'category' => 'learning_streak', 'title' => '30-Day Streak', 'points' => 500 ],
				'tutor_first_booking'      => [ 'category' => 'tutor', 'title' => 'First Booking', 'points' => 75 ],
				'verified'                 => [ 'category' => 'tutor', 'title' => 'Verified', 'points' => 200, 'slug' => 'verified' ],
				'tutor_approved'           => [ 'category' => 'tutor', 'title' => 'Verified', 'points' => 200, 'slug' => 'verified' ],
				'highly_rated'             => [ 'category' => 'tutor', 'title' => 'Highly Rated', 'points' => 250 ],
				'popular'                  => [ 'category' => 'tutor', 'title' => 'Popular', 'points' => 300 ],
				'specialist'               => [ 'category' => 'tutor', 'title' => 'Specialist', 'points' => 400 ],
				'elite_earner'             => [ 'category' => 'tutor', 'title' => 'Elite Earner', 'points' => 1000 ],
				'tutor_10_sessions'        => [ 'category' => 'session', 'title' => '10 Sessions', 'points' => 150 ],
				'tutor_50_sessions'        => [ 'category' => 'milestone', 'title' => '50 Sessions', 'points' => 500 ],
				'parent_first_child'       => [ 'category' => 'parent', 'title' => 'First Learner', 'points' => 50 ],
				'referral_first'           => [ 'category' => 'referral', 'title' => 'First Referral', 'points' => 200 ],
				'referral_5'               => [ 'category' => 'referral', 'title' => '5 Referrals', 'points' => 750 ],
				'loyalty_3_months'         => [ 'category' => 'loyalty', 'title' => '3-Month Loyalty', 'points' => 300 ],
				'review_first'             => [ 'category' => 'session', 'title' => 'First Review', 'points' => 25 ],
				'profile_complete'         => [ 'category' => 'milestone', 'title' => 'Profile Complete', 'points' => 50 ],
			]
		);
	}

	/**
	 * Award an achievement if not already earned.
	 *
	 * @param int                  $user_id         User ID.
	 * @param string               $achievement_key Achievement key.
	 * @param array<string, mixed> $meta            Extra meta.
	 * @return bool
	 */
	public static function award( $user_id, $achievement_key, $meta = [] ) {
		global $wpdb;
		$user_id         = (int) $user_id;
		$achievement_key = sanitize_key( $achievement_key );
		$catalog         = self::catalog();

		if ( $user_id <= 0 || ! isset( $catalog[ $achievement_key ] ) ) {
			return false;
		}
		if ( self::has_achievement( $user_id, $achievement_key ) ) {
			return false;
		}

		$def   = $catalog[ $achievement_key ];
		$table = NGC_Database::table( 'gamification_achievements' );

		NGC_Database::insert(
			'gamification_achievements',
			[
				'user_id'         => $user_id,
				'achievement_key' => $achievement_key,
				'category'        => sanitize_key( $def['category'] ),
				'title'           => sanitize_text_field( $def['title'] ),
				'points_awarded'  => ! empty( $meta['skip_points_on_achievement'] ) ? 0.0 : (float) $def['points'],
				'meta'            => wp_json_encode( $meta ),
				'earned_at'       => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%s', '%f', '%s', '%s' ]
		);

		if ( empty( $meta['skip_points_on_achievement'] ) ) {
			NGC_Scoring_Engine::add_points( $user_id, 'xp', (float) $def['points'] );
		}
		update_user_meta( $user_id, 'ngc_achievement_count', (int) get_user_meta( $user_id, 'ngc_achievement_count', true ) + 1 );

		if ( class_exists( 'NGC_Gamipress_Adapter' ) && NGC_Gamipress_Adapter::is_active() ) {
			$gp_slug = sanitize_title( (string) ( $def['slug'] ?? $achievement_key ) );
			NGC_Gamipress_Adapter::award_achievement( $user_id, $gp_slug );
		}

		do_action( 'ngc_achievement_earned', $user_id, $achievement_key, $def );

		return true;
	}

	/**
	 * @param int    $user_id         User ID.
	 * @param string $achievement_key Achievement key.
	 * @return bool
	 */
	public static function has_achievement( $user_id, $achievement_key ) {
		global $wpdb;
		$table = NGC_Database::table( 'gamification_achievements' );
		$key   = sanitize_key( $achievement_key );
		$aliases = [
			'beginner'       => [ 'beginner', 'payment_first' ],
			'payment_first'  => [ 'beginner', 'payment_first' ],
			'verified'       => [ 'verified', 'tutor_approved' ],
			'tutor_approved' => [ 'verified', 'tutor_approved' ],
		];
		$keys = $aliases[ $key ] ?? [ $key ];
		foreach ( $keys as $candidate ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$found = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE user_id = %d AND achievement_key = %s",
					(int) $user_id,
					sanitize_key( $candidate )
				)
			);
			if ( $found ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param int $user_id User ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function history( $user_id, $limit = 50 ) {
		global $wpdb;
		$table = NGC_Database::table( 'gamification_achievements' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d ORDER BY earned_at DESC LIMIT %d", (int) $user_id, (int) $limit ), ARRAY_A );
	}

	/**
	 * Map earning events to achievements.
	 *
	 * @param int                  $user_id   User ID.
	 * @param string               $event_key Event slug.
	 * @param array<string, mixed> $context   Context.
	 */
	public static function check_event_achievements( $user_id, $event_key, $context = [] ) {
		$map = [
			'tutor_approval'         => 'verified',
			'lesson_completion'      => 'student_first_lesson',
			'session_completed'      => 'student_first_lesson',
			'review_submission'      => 'review_first',
			'referral_conversion'    => 'referral_first',
			'profile_completion'     => 'profile_complete',
			// First booking only — never award Beginner on every payment_completion.
			'first_booking'          => 'beginner',
			'parent_registration'    => 'parent_first_child',
			'consecutive_attendance' => 'student_streak_5',
		];
		if ( isset( $map[ $event_key ] ) ) {
			$meta = is_array( $context ) ? $context : [];
			if ( 'first_booking' === $event_key ) {
				$meta['skip_points_on_achievement'] = true;
			}
			self::award( $user_id, $map[ $event_key ], $meta );
		}
		if ( class_exists( 'NGC_Gamification_Milestones' ) ) {
			NGC_Gamification_Milestones::evaluate( $user_id, $event_key, $context );
		}
	}
}
