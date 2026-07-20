<?php
/**
 * Leaderboard engine — rankings across boards and periods.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leaderboard computation and caching.
 */
class NGC_Leaderboard_Engine {

	/**
	 * Supported leaderboard keys.
	 *
	 * @return string[]
	 */
	public static function board_keys() {
		return [
			'overall',
			'province',
			'tutor',
			'student',
			'parent',
			'subject',
			'monthly',
			'annual',
			'achievement',
		];
	}

	/**
	 * Recompute and cache a leaderboard.
	 *
	 * @param string $board_key Board key.
	 * @param string $period    Period slug.
	 * @param int    $limit     Max entries.
	 * @return array<int, array<string, mixed>>
	 */
	public static function compute( $board_key, $period = 'all_time', $limit = 50 ) {
		global $wpdb;
		$board_key = sanitize_key( $board_key );
		$period    = sanitize_key( $period );
		$limit     = max( 1, min( 200, (int) $limit ) );

		$scores_table = NGC_Database::table( 'gamification_scores' );
		$ach_table    = NGC_Database::table( 'gamification_achievements' );
		$lb_table     = NGC_Database::table( 'leaderboard_entries' );

		$date_filter = self::period_sql( $period, 'updated_at' );

		if ( 'achievement' === $board_key ) {
			$date_col = 'earned_at';
			$date_filter = self::period_sql( $period, $date_col );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, COUNT(*) AS score FROM {$ach_table} WHERE 1=1 {$date_filter} GROUP BY user_id ORDER BY score DESC LIMIT %d",
					$limit
				),
				ARRAY_A
			);
		} else {
			$point_type = self::board_point_type( $board_key );
			$role_join  = self::board_role_filter( $board_key );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sql = "SELECT s.user_id, s.balance AS score FROM {$scores_table} s
				INNER JOIN {$wpdb->users} u ON u.ID = s.user_id
				{$role_join}
				WHERE s.point_type = %s {$date_filter}
				ORDER BY s.balance DESC LIMIT %d";
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $point_type, $limit ), ARRAY_A );
		}

		$rank = 1;
		foreach ( (array) $rows as $row ) {
			$user_id = (int) $row['user_id'];
			$score   = (float) $row['score'];
			$user    = get_userdata( $user_id );
			$meta    = [
				'display_name' => $user ? $user->display_name : '',
				'province'     => get_user_meta( $user_id, 'ngc_province', true ),
			];

			$wpdb->replace(
				$lb_table,
				[
					'board_key'     => $board_key,
					'user_id'       => $user_id,
					'score'         => $score,
					'rank_position' => $rank,
					'period'        => $period,
					'meta'          => wp_json_encode( $meta ),
					'computed_at'   => current_time( 'mysql', true ),
				],
				[ '%s', '%d', '%f', '%d', '%s', '%s', '%s' ]
			);
			++$rank;
		}

		return self::get( $board_key, $period, $limit );
	}

	/**
	 * @param string $board_key Board key.
	 * @param string $period    Period.
	 * @param int    $limit     Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get( $board_key, $period = 'all_time', $limit = 50 ) {
		global $wpdb;
		$table = NGC_Database::table( 'leaderboard_entries' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE board_key = %s AND period = %s ORDER BY rank_position ASC LIMIT %d",
				sanitize_key( $board_key ),
				sanitize_key( $period ),
				max( 1, min( 200, (int) $limit ) )
			),
			ARRAY_A
		);
		foreach ( $rows as &$row ) {
			$row['meta'] = json_decode( (string) ( $row['meta'] ?? '{}' ), true ) ?: [];
		}
		return $rows;
	}

	/**
	 * @param int    $user_id   User ID.
	 * @param string $board_key Board key.
	 * @param string $period    Period.
	 * @return array<string, mixed>|null
	 */
	public static function user_rank( $user_id, $board_key = 'overall', $period = 'all_time' ) {
		global $wpdb;
		$table = NGC_Database::table( 'leaderboard_entries' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE board_key = %s AND period = %s AND user_id = %d",
				sanitize_key( $board_key ),
				sanitize_key( $period ),
				(int) $user_id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * @param string $board_key Board key.
	 * @return string
	 */
	private static function board_point_type( $board_key ) {
		$map = [
			'tutor'   => 'tutor_points',
			'student' => 'student_points',
			'parent'  => 'parent_points',
			'monthly' => 'xp',
			'annual'  => 'xp',
		];
		return $map[ $board_key ] ?? 'xp';
	}

	/**
	 * @param string $board_key Board key.
	 * @return string
	 */
	private static function board_role_filter( $board_key ) {
		global $wpdb;
		$map = [
			'tutor'   => 'tutor',
			'student' => 'student',
			'parent'  => 'parent',
		];
		if ( ! isset( $map[ $board_key ] ) ) {
			return '';
		}
		$role = esc_sql( $map[ $board_key ] );
		return "INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key = '{$wpdb->prefix}capabilities' AND um.meta_value LIKE '%{$role}%'";
	}

	/**
	 * @param string $period Period slug.
	 * @param string $column Column name.
	 * @return string
	 */
	private static function period_sql( $period, $column ) {
		if ( 'monthly' === $period ) {
			return " AND {$column} >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)";
		}
		if ( 'annual' === $period ) {
			return " AND {$column} >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 365 DAY)";
		}
		return '';
	}
}
