<?php
/**
 * Demo reset (Phase 14 §14.28).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dependency-safe demo cleanup — preserves non-demo records.
 */
final class NGC_Demo_Reset {

	/**
	 * @param string $scenario Scenario or 'all'.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function reset( $scenario = 'all' ) {
		$gate = NGC_Demo_Env::assert_demo_ops_allowed();
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}
		if ( ! NGC_Demo_Env::allow_reset() ) {
			return new WP_Error( 'ngc_demo_reset_denied', __( 'Demo reset is disabled.', 'nextgencompanion' ) );
		}

		$report = [
			'scenario'          => sanitize_key( $scenario ),
			'users_deleted'     => 0,
			'bookings_deleted'  => 0,
			'matches_deleted'   => 0,
			'children_deleted'  => 0,
			'notifications_cleared' => false,
			'clock_reset'       => false,
		];

		$report['bookings_deleted'] = self::delete_demo_rows( 'bookings', 'notes' );
		$report['matches_deleted']  = self::delete_demo_note_rows( 'matches' );
		$report['children_deleted'] = self::delete_demo_children();

		NGC_Demo_Notifications::clear();
		$report['notifications_cleared'] = true;

		NGC_Demo_Clock::reset();
		$report['clock_reset'] = true;

		// Delete demo users last.
		$users = get_users(
			[
				'meta_key'   => 'ngc_is_demo_user',
				'meta_value' => '1',
				'fields'     => 'ID',
				'number'     => 500,
			]
		);
		require_once ABSPATH . 'wp-admin/includes/user.php';
		foreach ( $users as $uid ) {
			if ( wp_delete_user( (int) $uid ) ) {
				++$report['users_deleted'];
			}
		}

		delete_option( 'ngc_demo_user_map' );
		delete_option( NGC_Demo_Seeder::OPTION_GRAPH );
		delete_option( NGC_Demo_Seeder::OPTION_STATUS );

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'demo_reset', 'demo', 0, $report );
		}

		return $report;
	}

	/**
	 * @param string $table_key Table.
	 * @param string $col Column with demo notes.
	 * @return int
	 */
	private static function delete_demo_rows( $table_key, $col ) {
		global $wpdb;
		$table = NGC_Database::table( $table_key );
		if ( ! $table ) {
			return 0;
		}
		$col = preg_replace( '/[^a-z0-9_]/i', '', $col );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query( "DELETE FROM {$table} WHERE {$col} LIKE 'BOOK-%' OR {$col} LIKE '%Demo%' OR meta LIKE '%\"is_demo\":true%' LIMIT 500" );
		return is_numeric( $deleted ) ? (int) $deleted : 0;
	}

	/**
	 * @param string $table_key Table.
	 * @return int
	 */
	private static function delete_demo_note_rows( $table_key ) {
		global $wpdb;
		$table = NGC_Database::table( $table_key );
		if ( ! $table ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query( "DELETE FROM {$table} WHERE notes LIKE 'Demo scenario%' OR notes LIKE '%MATCH-%' LIMIT 200" );
		return is_numeric( $deleted ) ? (int) $deleted : 0;
	}

	/**
	 * @return int
	 */
	private static function delete_demo_children() {
		global $wpdb;
		$table = NGC_Database::table( 'child_learners' );
		if ( ! $table ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query( "DELETE FROM {$table} WHERE meta LIKE '%\"is_demo\":true%' OR email LIKE '%@nextgen.local' LIMIT 100" );
		return is_numeric( $deleted ) ? (int) $deleted : 0;
	}
}
