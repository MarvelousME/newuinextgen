<?php
/**
 * Internal scoring engine — point balances and earning events.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Point types and score persistence.
 */
class NGC_Scoring_Engine {

	/**
	 * Supported point types.
	 *
	 * @return string[]
	 */
	public static function point_types() {
		return [
			'xp',
			'tutor_points',
			'student_points',
			'parent_points',
			'reputation_points',
			'referral_points',
			'loyalty_points',
		];
	}

	/**
	 * Default points per earning event.
	 *
	 * @return array<string, array<string, float>>
	 */
	public static function event_points() {
		return [
			'tutor_registration'       => [ 'xp' => 50, 'tutor_points' => 100 ],
			'tutor_approval'           => [ 'xp' => 200, 'tutor_points' => 500, 'reputation_points' => 50 ],
			'parent_registration'      => [ 'xp' => 30, 'parent_points' => 100 ],
			'student_registration'     => [ 'xp' => 30, 'student_points' => 100 ],
			'booking_completion'       => [ 'xp' => 25, 'student_points' => 50, 'tutor_points' => 50 ],
			'session_attendance'       => [ 'xp' => 15, 'student_points' => 25 ],
			'lesson_completion'        => [ 'xp' => 40, 'student_points' => 75, 'tutor_points' => 75 ],
			'consecutive_attendance'   => [ 'xp' => 100, 'loyalty_points' => 50 ],
			'review_submission'        => [ 'xp' => 20, 'reputation_points' => 30 ],
			'tutor_rating'             => [ 'xp' => 10, 'reputation_points' => 20, 'tutor_points' => 15 ],
			'referral_conversion'      => [ 'xp' => 150, 'referral_points' => 200 ],
			'payment_completion'       => [ 'xp' => 35, 'loyalty_points' => 25 ],
			'profile_completion'       => [ 'xp' => 50, 'loyalty_points' => 30 ],
		];
	}

	/**
	 * Award points for an earning event.
	 *
	 * @param int                  $user_id   User ID.
	 * @param string               $event_key Event slug.
	 * @param array<string, mixed> $context   Extra context.
	 * @return array<string, float> Points awarded by type.
	 */
	public static function award_event( $user_id, $event_key, $context = [] ) {
		$user_id   = (int) $user_id;
		$event_key = sanitize_key( $event_key );
		if ( $user_id <= 0 || ! $event_key ) {
			return [];
		}

		$map     = self::event_points();
		$awards  = $map[ $event_key ] ?? [];
		$awarded = [];

		foreach ( $awards as $point_type => $amount ) {
			$amount = (float) apply_filters( 'ngc_gamification_points', $amount, $event_key, $point_type, $user_id, $context );
			if ( $amount <= 0 ) {
				continue;
			}
			self::add_points( $user_id, $point_type, $amount );
			self::log_event( $user_id, $event_key, $point_type, $amount, $context );
			$awarded[ $point_type ] = $amount;
		}

		if ( $awarded ) {
			update_user_meta( $user_id, 'ngc_last_gamification_event', $event_key );
			update_user_meta( $user_id, 'ngc_last_gamification_at', gmdate( 'c' ) );
		}

		return $awarded;
	}

	/**
	 * @param int    $user_id    User ID.
	 * @param string $point_type Point type.
	 * @param float  $amount     Amount to add.
	 */
	public static function add_points( $user_id, $point_type, $amount ) {
		global $wpdb;
		$table = NGC_Database::table( 'gamification_scores' );
		$point_type = sanitize_key( $point_type );
		$amount     = (float) $amount;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d AND point_type = %s", $user_id, $point_type ) );
		if ( $existing ) {
			$wpdb->update(
				$table,
				[
					'balance'    => (float) $existing->balance + $amount,
					'lifetime'   => (float) $existing->lifetime + $amount,
					'updated_at' => current_time( 'mysql', true ),
				],
				[ 'id' => (int) $existing->id ],
				[ '%f', '%f', '%s' ],
				[ '%d' ]
			);
		} else {
			NGC_Database::insert(
				'gamification_scores',
				[
					'user_id'    => $user_id,
					'point_type' => $point_type,
					'balance'    => $amount,
					'lifetime'   => $amount,
					'updated_at' => current_time( 'mysql', true ),
				],
				[ '%d', '%s', '%f', '%f', '%s' ]
			);
		}
	}

	/**
	 * @param int    $user_id User ID.
	 * @param string $point_type Point type.
	 * @return float
	 */
	public static function get_balance( $user_id, $point_type = 'xp' ) {
		global $wpdb;
		$table = NGC_Database::table( 'gamification_scores' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$val = $wpdb->get_var( $wpdb->prepare( "SELECT balance FROM {$table} WHERE user_id = %d AND point_type = %s", (int) $user_id, sanitize_key( $point_type ) ) );
		return $val ? (float) $val : 0.0;
	}

	/**
	 * @param int $user_id User ID.
	 * @return array<string, float>
	 */
	public static function get_all_balances( $user_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'gamification_scores' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT point_type, balance, lifetime FROM {$table} WHERE user_id = %d", (int) $user_id ), ARRAY_A );
		$out  = [];
		foreach ( (array) $rows as $row ) {
			$out[ $row['point_type'] ] = (float) $row['balance'];
		}
		return $out;
	}

	/**
	 * @param int                  $user_id    User ID.
	 * @param string               $event_key  Event.
	 * @param string               $point_type Point type.
	 * @param float                $points     Points.
	 * @param array<string, mixed> $context    Context.
	 */
	private static function log_event( $user_id, $event_key, $point_type, $points, $context ) {
		NGC_Database::insert(
			'gamification_events',
			[
				'user_id'    => (int) $user_id,
				'event_key'  => sanitize_key( $event_key ),
				'point_type' => sanitize_key( $point_type ),
				'points'     => (float) $points,
				'source'     => 'internal',
				'context'    => wp_json_encode( $context ),
				'created_at' => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%f', '%s', '%s', '%s' ]
		);
	}
}
