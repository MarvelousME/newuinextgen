<?php
/**
 * Minor PII retention, export, and deletion (PRIV-001 / POPIA).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress personal-data exporters/erasers + retention cron for child learners.
 */
final class NGC_Privacy {

	public const OPTION_MINOR_DAYS     = 'ngc_minor_pii_retention_days';
	public const OPTION_ANALYTICS_DAYS = 'ngc_analytics_retention_days';
	public const OPTION_LOG_DAYS       = 'ngc_system_log_retention_days';
	public const OPTION_AUTO_PURGE     = 'ngc_privacy_auto_purge';
	public const CRON_HOOK             = 'ngc_privacy_retention_tick';
	public const EXPORTER_KEY          = 'ngc-minor-pii';
	public const ERASER_KEY            = 'ngc-minor-pii';

	/** Default: 7 years (SA POPIA-aligned retention ceiling for education records). */
	public const DEFAULT_MINOR_DAYS = 2555;

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_filter( 'wp_privacy_personal_data_exporters', [ __CLASS__, 'register_exporter' ] );
		add_filter( 'wp_privacy_personal_data_erasers', [ __CLASS__, 'register_eraser' ] );
		add_action( self::CRON_HOOK, [ __CLASS__, 'run_retention_sweep' ] );
		add_action( 'admin_init', [ __CLASS__, 'maybe_schedule' ] );
	}

	/**
	 * Ensure daily retention cron when auto-purge is enabled.
	 */
	public static function maybe_schedule() {
		if ( ! self::auto_purge_enabled() ) {
			$ts = wp_next_scheduled( self::CRON_HOOK );
			if ( $ts ) {
				wp_unschedule_event( $ts, self::CRON_HOOK );
			}
			return;
		}
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * @return bool
	 */
	public static function auto_purge_enabled() {
		return '1' === (string) get_option( self::OPTION_AUTO_PURGE, '0' );
	}

	/**
	 * @return array{minor_days:int,analytics_days:int,log_days:int,auto_purge:bool}
	 */
	public static function settings() {
		return [
			'minor_days'     => max( 30, (int) get_option( self::OPTION_MINOR_DAYS, self::DEFAULT_MINOR_DAYS ) ),
			'analytics_days' => max( 7, (int) get_option( self::OPTION_ANALYTICS_DAYS, 365 ) ),
			'log_days'       => max( 7, (int) get_option( self::OPTION_LOG_DAYS, 90 ) ),
			'auto_purge'     => self::auto_purge_enabled(),
		];
	}

	/**
	 * Persist settings from admin POST (caller verifies nonce/caps).
	 *
	 * @param array<string, mixed> $input Raw input.
	 * @return array{minor_days:int,analytics_days:int,log_days:int,auto_purge:bool}
	 */
	public static function save_settings( $input ) {
		$minor = max( 30, (int) ( $input['minor_days'] ?? self::DEFAULT_MINOR_DAYS ) );
		$analytics = max( 7, (int) ( $input['analytics_days'] ?? 365 ) );
		$logs = max( 7, (int) ( $input['log_days'] ?? 90 ) );
		$auto = ! empty( $input['auto_purge'] ) ? '1' : '0';

		update_option( self::OPTION_MINOR_DAYS, $minor, false );
		update_option( self::OPTION_ANALYTICS_DAYS, $analytics, false );
		update_option( self::OPTION_LOG_DAYS, $logs, false );
		update_option( self::OPTION_AUTO_PURGE, $auto, false );
		self::maybe_schedule();

		return self::settings();
	}

	/**
	 * @param array<string, array<string, mixed>> $exporters Exporters.
	 * @return array<string, array<string, mixed>>
	 */
	public static function register_exporter( $exporters ) {
		$exporters[ self::EXPORTER_KEY ] = [
			'exporter_friendly_name' => __( 'NextGen Tutors — minor / child learner data', 'nextgencompanion' ),
			'callback'               => [ __CLASS__, 'export_personal_data' ],
		];
		return $exporters;
	}

	/**
	 * @param array<string, array<string, mixed>> $erasers Erasers.
	 * @return array<string, array<string, mixed>>
	 */
	public static function register_eraser( $erasers ) {
		$erasers[ self::ERASER_KEY ] = [
			'eraser_friendly_name' => __( 'NextGen Tutors — minor / child learner data', 'nextgencompanion' ),
			'callback'             => [ __CLASS__, 'erase_personal_data' ],
		];
		return $erasers;
	}

	/**
	 * WP personal data exporter callback.
	 *
	 * @param string $email_address Email.
	 * @param int    $page          Page (1-indexed).
	 * @return array{data:array<int,array<string,mixed>>,done:bool}
	 */
	public static function export_personal_data( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );
		if ( ! $user ) {
			return [ 'data' => [], 'done' => true ];
		}

		$groups = [];
		$rows   = self::collect_minor_records_for_user( (int) $user->ID );

		foreach ( $rows as $row ) {
			$item_id = 'ngc-child-' . (int) $row['id'];
			$groups[] = [
				'group_id'          => 'ngc_child_learners',
				'group_label'       => __( 'Child learners (minors)', 'nextgencompanion' ),
				'group_description' => __( 'Learner profiles linked to parent or student accounts.', 'nextgencompanion' ),
				'item_id'           => $item_id,
				'data'              => [
					[ 'name' => __( 'Record ID', 'nextgencompanion' ), 'value' => (string) $row['id'] ],
					[ 'name' => __( 'UUID', 'nextgencompanion' ), 'value' => (string) ( $row['uuid'] ?? '' ) ],
					[ 'name' => __( 'Display name', 'nextgencompanion' ), 'value' => (string) ( $row['display_name'] ?? '' ) ],
					[ 'name' => __( 'Email', 'nextgencompanion' ), 'value' => (string) ( $row['email'] ?? '' ) ],
					[ 'name' => __( 'Grade', 'nextgencompanion' ), 'value' => (string) ( $row['grade'] ?? '' ) ],
					[ 'name' => __( 'Province', 'nextgencompanion' ), 'value' => (string) ( $row['province'] ?? '' ) ],
					[ 'name' => __( 'Status', 'nextgencompanion' ), 'value' => (string) ( $row['status'] ?? '' ) ],
					[ 'name' => __( 'Created', 'nextgencompanion' ), 'value' => (string) ( $row['created_at'] ?? '' ) ],
				],
			];
		}

		$bookings = self::collect_bookings_for_minors( $rows );
		foreach ( $bookings as $b ) {
			$groups[] = [
				'group_id'    => 'ngc_minor_bookings',
				'group_label' => __( 'Bookings linked to child learners', 'nextgencompanion' ),
				'item_id'     => 'ngc-booking-' . (int) $b['id'],
				'data'        => [
					[ 'name' => __( 'Booking ID', 'nextgencompanion' ), 'value' => (string) $b['id'] ],
					[ 'name' => __( 'Subject', 'nextgencompanion' ), 'value' => (string) ( $b['subject'] ?? '' ) ],
					[ 'name' => __( 'Status', 'nextgencompanion' ), 'value' => (string) ( $b['status'] ?? '' ) ],
					[ 'name' => __( 'Scheduled', 'nextgencompanion' ), 'value' => (string) ( $b['scheduled_at'] ?? '' ) ],
				],
			];
		}

		unset( $page );
		return [
			'data' => $groups,
			'done' => true,
		];
	}

	/**
	 * WP personal data eraser callback.
	 *
	 * @param string $email_address Email.
	 * @param int    $page          Page.
	 * @return array{items_removed:bool,items_retained:bool,messages:string[],done:bool}
	 */
	public static function erase_personal_data( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );
		if ( ! $user ) {
			return [
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => [],
				'done'           => true,
			];
		}

		$rows     = self::collect_minor_records_for_user( (int) $user->ID );
		$removed  = 0;
		$messages = [];

		foreach ( $rows as $row ) {
			$result = self::anonymize_child_learner( (int) $row['id'], 'privacy_erase' );
			if ( ! is_wp_error( $result ) && $result ) {
				++$removed;
				$messages[] = sprintf(
					/* translators: %d: child learner id */
					__( 'Anonymized child learner #%d.', 'nextgencompanion' ),
					(int) $row['id']
				);
			}
		}

		unset( $page );
		return [
			'items_removed'  => $removed > 0,
			'items_retained' => false,
			'messages'       => $messages,
			'done'           => true,
		];
	}

	/**
	 * Child learner rows where user is parent or linked student.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function collect_minor_records_for_user( $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$table   = NGC_Database::table( 'child_learners' );
		if ( ! $table || ! $user_id ) {
			return [];
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE parent_user_id = %d OR student_user_id = %d ORDER BY id ASC",
				$user_id,
				$user_id
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @param array<int, array<string, mixed>> $children Child rows.
	 * @return array<int, array<string, mixed>>
	 */
	public static function collect_bookings_for_minors( $children ) {
		global $wpdb;
		$student_ids = [];
		foreach ( $children as $row ) {
			$sid = (int) ( $row['student_user_id'] ?? 0 );
			if ( $sid > 0 ) {
				$student_ids[] = $sid;
			}
		}
		$student_ids = array_values( array_unique( $student_ids ) );
		if ( empty( $student_ids ) ) {
			return [];
		}

		$table = NGC_Database::table( 'bookings' );
		if ( ! $table ) {
			return [];
		}

		$placeholders = implode( ',', array_fill( 0, count( $student_ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			"SELECT id, subject, status, scheduled_at, student_user_id FROM {$table} WHERE student_user_id IN ({$placeholders}) ORDER BY id ASC LIMIT 200",
			...$student_ids
		);
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Anonymize a child learner row (keeps FK integrity; clears direct identifiers).
	 *
	 * @param int    $child_id Child learner ID.
	 * @param string $reason   Audit reason.
	 * @return bool|WP_Error
	 */
	public static function anonymize_child_learner( $child_id, $reason = 'retention' ) {
		global $wpdb;
		$child_id = (int) $child_id;
		$row      = class_exists( 'NGC_Child_Learners' ) ? NGC_Child_Learners::get( $child_id ) : null;
		if ( ! $row ) {
			return new WP_Error( 'ngc_privacy_missing', __( 'Child learner not found.', 'nextgencompanion' ) );
		}
		if ( 'anonymized' === ( $row['status'] ?? '' ) ) {
			return true;
		}

		$table = NGC_Database::table( 'child_learners' );
		$ok    = (bool) $wpdb->update(
			$table,
			[
				'display_name' => sprintf( 'Learner #%d', $child_id ),
				'email'        => '',
				'grade'        => '',
				'province'     => '',
				'meta'         => wp_json_encode(
					[
						'anonymized_at'     => gmdate( 'c' ),
						'anonymize_reason'  => sanitize_key( $reason ),
						'had_student_user'  => (int) ( $row['student_user_id'] ?? 0 ) > 0,
					]
				),
				'status'       => 'anonymized',
				'updated_at'   => current_time( 'mysql', true ),
			],
			[ 'id' => $child_id ],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ],
			[ '%d' ]
		);

		$student_id = (int) ( $row['student_user_id'] ?? 0 );
		if ( $student_id > 0 ) {
			delete_user_meta( $student_id, 'ngc_child_learner_id' );
			// Soft-clear profile display for child_learner role only.
			$user = get_userdata( $student_id );
			if ( $user && in_array( 'child_learner', (array) $user->roles, true ) ) {
				wp_update_user(
					[
						'ID'           => $student_id,
						'display_name' => sprintf( 'Learner #%d', $child_id ),
						'nickname'     => sprintf( 'learner-%d', $child_id ),
					]
				);
			}
		}

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log(
				'child_learner_anonymized',
				'child_learner',
				$child_id,
				[ 'reason' => sanitize_key( $reason ) ],
				get_current_user_id()
			);
		}
		if ( class_exists( 'NGC_System_Log' ) ) {
			NGC_System_Log::info(
				'privacy',
				'retention',
				'Child learner anonymized',
				[ 'child_id' => $child_id, 'reason' => $reason ]
			);
		}

		return $ok;
	}

	/**
	 * Daily retention sweep: aged minor PII + analytics/logs.
	 *
	 * @return array<string, int>
	 */
	public static function run_retention_sweep() {
		$settings = self::settings();
		$stats    = [
			'children_anonymized' => 0,
			'analytics_deleted'   => 0,
			'logs_deleted'        => 0,
			'consent_deleted'     => 0,
		];

		$stats['children_anonymized'] = self::sweep_expired_minors( $settings['minor_days'] );
		$stats['analytics_deleted']   = self::delete_older_than( 'analytics_events', 'created_at', $settings['analytics_days'] );
		$stats['logs_deleted']        = self::delete_older_than( 'system_log', 'created_at', $settings['log_days'] );
		$stats['consent_deleted']     = self::delete_older_than( 'consent_log', 'created_at', $settings['analytics_days'] );

		if ( class_exists( 'NGC_System_Log' ) ) {
			NGC_System_Log::info( 'privacy', 'retention', 'Retention sweep completed', $stats );
		}

		/**
		 * Fires after privacy retention sweep.
		 *
		 * @param array<string, int> $stats Counts.
		 */
		do_action( 'ngc_privacy_retention_swept', $stats );

		return $stats;
	}

	/**
	 * Anonymize archived/inactive minors older than retention days.
	 *
	 * @param int $days Retention days.
	 * @return int Count anonymized.
	 */
	public static function sweep_expired_minors( $days ) {
		global $wpdb;
		$table = NGC_Database::table( 'child_learners' );
		if ( ! $table ) {
			return 0;
		}

		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( max( 30, (int) $days ) * DAY_IN_SECONDS ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table}
				WHERE status IN ('archived','inactive')
				  AND updated_at < %s
				LIMIT 200",
				$cutoff
			)
		);

		$count = 0;
		foreach ( (array) $ids as $id ) {
			$result = self::anonymize_child_learner( (int) $id, 'retention_cron' );
			if ( ! is_wp_error( $result ) && $result ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Delete rows older than N days from a known NGC table.
	 *
	 * @param string $table_key Table key in NGC_Database.
	 * @param string $date_col  Datetime column.
	 * @param int    $days      Retention days.
	 * @return int Rows deleted.
	 */
	public static function delete_older_than( $table_key, $date_col, $days ) {
		global $wpdb;
		$table = NGC_Database::table( $table_key );
		$col   = preg_replace( '/[^a-z0-9_]/i', '', $date_col );
		if ( ! $table || ! $col ) {
			return 0;
		}
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( max( 1, (int) $days ) * DAY_IN_SECONDS ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE {$col} < %s LIMIT 5000",
				$cutoff
			)
		);
		return is_numeric( $deleted ) ? (int) $deleted : 0;
	}

	/**
	 * Build a portable JSON export package for a parent/user (admin tool).
	 *
	 * @param int $user_id User ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function export_package( $user_id ) {
		$user_id = (int) $user_id;
		$user    = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_Error( 'ngc_privacy_user', __( 'User not found.', 'nextgencompanion' ) );
		}
		$children = self::collect_minor_records_for_user( $user_id );
		return [
			'exported_at' => gmdate( 'c' ),
			'user_id'     => $user_id,
			'user_email'  => $user->user_email,
			'children'    => $children,
			'bookings'    => self::collect_bookings_for_minors( $children ),
			'settings'    => self::settings(),
		];
	}
}
