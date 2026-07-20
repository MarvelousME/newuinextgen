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
		return [
			'student_first_lesson'     => [ 'category' => 'student', 'title' => 'First Lesson', 'points' => 50 ],
			'student_streak_5'         => [ 'category' => 'student', 'title' => '5-Day Streak', 'points' => 100 ],
			'student_streak_30'        => [ 'category' => 'learning_streak', 'title' => '30-Day Streak', 'points' => 500 ],
			'tutor_first_booking'      => [ 'category' => 'tutor', 'title' => 'First Booking', 'points' => 75 ],
			'tutor_approved'           => [ 'category' => 'tutor', 'title' => 'Tutor Approved', 'points' => 200 ],
			'tutor_10_sessions'        => [ 'category' => 'session', 'title' => '10 Sessions', 'points' => 150 ],
			'tutor_50_sessions'        => [ 'category' => 'milestone', 'title' => '50 Sessions', 'points' => 500 ],
			'parent_first_child'       => [ 'category' => 'parent', 'title' => 'First Learner', 'points' => 50 ],
			'referral_first'           => [ 'category' => 'referral', 'title' => 'First Referral', 'points' => 200 ],
			'referral_5'               => [ 'category' => 'referral', 'title' => '5 Referrals', 'points' => 750 ],
			'loyalty_3_months'         => [ 'category' => 'loyalty', 'title' => '3-Month Loyalty', 'points' => 300 ],
			'review_first'             => [ 'category' => 'session', 'title' => 'First Review', 'points' => 25 ],
			'profile_complete'         => [ 'category' => 'milestone', 'title' => 'Profile Complete', 'points' => 50 ],
			'payment_first'            => [ 'category' => 'milestone', 'title' => 'First Payment', 'points' => 75 ],
		];
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

		$wpdb->insert(
			$table,
			[
				'user_id'         => $user_id,
				'achievement_key' => $achievement_key,
				'category'        => sanitize_key( $def['category'] ),
				'title'           => sanitize_text_field( $def['title'] ),
				'points_awarded'  => (float) $def['points'],
				'meta'            => wp_json_encode( $meta ),
				'earned_at'       => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%s', '%f', '%s', '%s' ]
		);

		NGC_Scoring_Engine::add_points( $user_id, 'xp', (float) $def['points'] );
		update_user_meta( $user_id, 'ngc_achievement_count', (int) get_user_meta( $user_id, 'ngc_achievement_count', true ) + 1 );

		if ( class_exists( 'NGC_Gamipress_Adapter' ) ) {
			NGC_Gamipress_Adapter::award_achievement( $user_id, $achievement_key );
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
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE user_id = %d AND achievement_key = %s", (int) $user_id, sanitize_key( $achievement_key ) ) );
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
			'tutor_approval'         => 'tutor_approved',
			'lesson_completion'      => 'student_first_lesson',
			'booking_completion'     => 'tutor_first_booking',
			'review_submission'      => 'review_first',
			'referral_conversion'    => 'referral_first',
			'profile_completion'     => 'profile_complete',
			'payment_completion'     => 'payment_first',
			'parent_registration'    => 'parent_first_child',
			'consecutive_attendance' => 'student_streak_5',
		];
		if ( isset( $map[ $event_key ] ) ) {
			self::award( $user_id, $map[ $event_key ], $context );
		}
	}
}
