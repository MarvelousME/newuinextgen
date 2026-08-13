<?php
/**
 * Database schema and table helpers.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom table management.
 */
class NGC_Database {

	/**
	 * @return string[]
	 */
	public static function table_names() {
		global $wpdb;
		return [
			'matches'            => $wpdb->prefix . 'ngc_matches',
			'bookings'           => $wpdb->prefix . 'ngc_bookings',
			'sessions'           => $wpdb->prefix . 'ngc_sessions',
			'invoices'           => $wpdb->prefix . 'ngc_invoices',
			'wallet_ledger'      => $wpdb->prefix . 'ngc_wallet_ledger',
			'payouts'            => $wpdb->prefix . 'ngc_payouts',
			'reviews'            => $wpdb->prefix . 'ngc_reviews',
			'audit_log'          => $wpdb->prefix . 'ngc_audit_log',
			'tutor_applications' => $wpdb->prefix . 'ngc_tutor_applications',
			'session_logs'       => $wpdb->prefix . 'ngc_session_logs',
			'earnings'           => $wpdb->prefix . 'ngc_earnings',
			'ratings'            => $wpdb->prefix . 'ngc_ratings',
			'workflow_runs'      => $wpdb->prefix . 'ngc_workflow_runs',
			'analytics_events'   => $wpdb->prefix . 'ngc_analytics_events',
			'visitor_profiles'   => $wpdb->prefix . 'ngc_visitor_profiles',
			'user_profiles'      => $wpdb->prefix . 'ngc_user_profiles',
			'acquisition_sources'=> $wpdb->prefix . 'ngc_acquisition_sources',
			'affiliate_clicks'   => $wpdb->prefix . 'ngc_affiliate_clicks',
			'attribution_links'  => $wpdb->prefix . 'ngc_attribution_links',
			'user_sessions'      => $wpdb->prefix . 'ngc_user_sessions',
			'device_profiles'    => $wpdb->prefix . 'ngc_device_profiles',
			'conversion_events'  => $wpdb->prefix . 'ngc_conversion_events',
			'metric_snapshots'   => $wpdb->prefix . 'ngc_dashboard_metric_snapshots',
			'demo_seed_log'      => $wpdb->prefix . 'ngc_demo_seed_log',
			'consent_log'        => $wpdb->prefix . 'ngc_consent_log',
			'gamification_scores'=> $wpdb->prefix . 'ngc_gamification_scores',
			'gamification_achievements' => $wpdb->prefix . 'ngc_gamification_achievements',
			'gamification_events'=> $wpdb->prefix . 'ngc_gamification_events',
			'leaderboard_entries'=> $wpdb->prefix . 'ngc_leaderboard_entries',
			'export_jobs'        => $wpdb->prefix . 'ngc_export_jobs',
			'export_templates'   => $wpdb->prefix . 'ngc_export_templates',
			'repair_snapshots'   => $wpdb->prefix . 'ngc_repair_snapshots',
			'ai_diagnostics_log' => $wpdb->prefix . 'ngc_ai_diagnostics_log',
			'referrals'          => $wpdb->prefix . 'ngc_referrals',
			'reminder_schedules' => $wpdb->prefix . 'ngc_reminder_schedules',
			'studio_workflows'   => $wpdb->prefix . 'ngc_studio_workflows',
			'studio_versions'    => $wpdb->prefix . 'ngc_studio_versions',
			'studio_triggers'    => $wpdb->prefix . 'ngc_studio_triggers',
			'studio_forms'       => $wpdb->prefix . 'ngc_studio_forms',
			'studio_emails'      => $wpdb->prefix . 'ngc_studio_emails',
			'studio_notifications' => $wpdb->prefix . 'ngc_studio_notifications',
			'studio_executions'  => $wpdb->prefix . 'ngc_studio_executions',
			'studio_dashboards'  => $wpdb->prefix . 'ngc_studio_dashboards',
			'child_learners'     => $wpdb->prefix . 'ngc_child_learners',
			'page_sections'      => $wpdb->prefix . 'ngc_page_sections',
			'builder_documents'  => $wpdb->prefix . 'ngc_builder_documents',
			'builder_revisions'  => $wpdb->prefix . 'ngc_builder_revisions',
			'system_log'         => $wpdb->prefix . 'ngc_system_log',
			'intel_events'       => $wpdb->prefix . 'ngc_intel_events',
			'intel_kpi_hourly'   => $wpdb->prefix . 'ngc_intel_kpi_hourly',
			'intel_notifications'=> $wpdb->prefix . 'ngc_intel_notifications',
			'memory_identity_map'=> $wpdb->prefix . 'ngc_memory_identity_map',
			'talent_evaluations' => $wpdb->prefix . 'ngc_talent_evaluations',
			'talent_evaluation_components' => $wpdb->prefix . 'ngc_talent_evaluation_components',
			'talent_requirement_profiles' => $wpdb->prefix . 'ngc_talent_requirement_profiles',
		];
	}

	/**
	 * @param string $key Table key.
	 * @return string
	 */
	public static function table( $key ) {
		$tables = self::table_names();
		return $tables[ $key ] ?? '';
	}

	/**
	 * Create or upgrade all custom tables via dbDelta.
	 */
	public static function create_tables() {
		global $wpdb;
		if ( ! class_exists( 'NGC_Schema_Statements' ) ) {
			require_once __DIR__ . '/database/class-ngc-schema-statements.php';
		}
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$t       = self::table_names();

		foreach ( NGC_Schema_Statements::create_sql( $t, $charset ) as $statement ) {
			dbDelta( $statement );
		}

		self::ensure_uuid_columns();
		self::ensure_amelia_booking_id_nullable();
	}

	/**
	 * Allow multiple non-Amelia bookings: UNIQUE(amelia_booking_id) must use NULL, not 0.
	 */
	public static function ensure_amelia_booking_id_nullable() {
		global $wpdb;
		$table = self::table( 'bookings' );
		if ( ! $table ) {
			return;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
		if ( ! $exists ) {
			return;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$col = $wpdb->get_row( "SHOW COLUMNS FROM {$table} LIKE 'amelia_booking_id'" );
		if ( ! $col ) {
			return;
		}
		$null_ok = isset( $col->Null ) && 'YES' === strtoupper( (string) $col->Null );
		if ( ! $null_ok ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} MODIFY amelia_booking_id bigint(20) unsigned NULL DEFAULT NULL" );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "UPDATE {$table} SET amelia_booking_id = NULL WHERE amelia_booking_id = 0" );
	}

	/**
	 * Add uuid column + backfill on every ngc_* custom table.
	 * Never leave unique '' values — that blocks subsequent inserts.
	 */
	public static function ensure_uuid_columns() {
		global $wpdb;

		foreach ( self::table_names() as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
			if ( ! $exists ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$has_uuid = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'uuid'" );
			if ( empty( $has_uuid ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN uuid char(36) NOT NULL DEFAULT '' AFTER id" );
			}

			// Backfill in batches until none remain empty.
			for ( $i = 0; $i < 50; $i++ ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$rows = $wpdb->get_col( "SELECT id FROM {$table} WHERE uuid = '' OR uuid IS NULL LIMIT 500" );
				if ( empty( $rows ) ) {
					break;
				}
				foreach ( (array) $rows as $row_id ) {
					$uuid = class_exists( 'NGC_Uuid' ) ? NGC_Uuid::generate() : wp_generate_uuid4();
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->update( $table, [ 'uuid' => $uuid ], [ 'id' => (int) $row_id ], [ '%s' ], [ '%d' ] );
				}
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$empty_left = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE uuid = '' OR uuid IS NULL" );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$has_index = $wpdb->get_results( "SHOW INDEX FROM {$table} WHERE Key_name = 'uuid'" );
			if ( empty( $has_index ) && 0 === $empty_left ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( "ALTER TABLE {$table} ADD UNIQUE KEY uuid (uuid)" );
			}
		}
	}

	/**
	 * Inject a uuid when the target table has a uuid column and the payload omits it.
	 *
	 * @param string               $table Full table name.
	 * @param array<string,mixed>  $data  Row data.
	 * @return array<string,mixed>
	 */
	public static function ensure_row_uuid( $table, array $data ) {
		if ( isset( $data['uuid'] ) && is_string( $data['uuid'] ) && '' !== $data['uuid'] ) {
			return $data;
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$table}", 0 );
		if ( is_array( $cols ) && in_array( 'uuid', $cols, true ) ) {
			$data['uuid'] = class_exists( 'NGC_Uuid' ) ? NGC_Uuid::generate() : wp_generate_uuid4();
		}
		return $data;
	}

	/**
	 * Safe insert that auto-fills uuid when required by schema.
	 *
	 * @param string                   $table_key Table key.
	 * @param array<string,mixed>      $data Data.
	 * @param array<int,string>|null   $format Format.
	 * @return int|false
	 */
	public static function insert( $table_key, array $data, $format = null ) {
		global $wpdb;
		$table = self::table( $table_key );
		$data  = self::ensure_row_uuid( $table, $data );
		if ( null === $format ) {
			return $wpdb->insert( $table, $data );
		}
		// Align formats if uuid was injected.
		if ( isset( $data['uuid'] ) && count( $format ) === count( $data ) - 1 ) {
			$format[] = '%s';
		}
		return $wpdb->insert( $table, $data, $format );
	}

	/**
	 * Ensure talent intelligence tables on existing installs.
	 */
	public static function ensure_talent_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$t = self::table_names();

		if ( ! empty( $t['talent_evaluations'] ) ) {
			dbDelta(
				"CREATE TABLE {$t['talent_evaluations']} (
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					uuid char(36) NOT NULL DEFAULT '',
					tenant_id varchar(64) NOT NULL DEFAULT '1',
					candidate_type varchar(32) NOT NULL DEFAULT 'application',
					candidate_id varchar(191) NOT NULL DEFAULT '',
					requirement_id varchar(191) NOT NULL DEFAULT '',
					score decimal(8,2) NULL,
					recommendation varchar(64) NOT NULL DEFAULT '',
					model_version varchar(64) NOT NULL DEFAULT '',
					weight_config_version varchar(64) NOT NULL DEFAULT '',
					input_snapshot_hash varchar(96) NOT NULL DEFAULT '',
					result_json longtext NULL,
					idempotency_key varchar(191) NOT NULL DEFAULT '',
					correlation_id varchar(64) NOT NULL DEFAULT '',
					created_by bigint(20) unsigned NOT NULL DEFAULT 0,
					created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (id),
					UNIQUE KEY uuid (uuid),
					UNIQUE KEY idempotency_key (idempotency_key),
					KEY candidate (candidate_type, candidate_id),
					KEY created_at (created_at)
				) {$charset};"
			);
		}
		if ( ! empty( $t['talent_evaluation_components'] ) ) {
			dbDelta(
				"CREATE TABLE {$t['talent_evaluation_components']} (
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					evaluation_id bigint(20) unsigned NOT NULL DEFAULT 0,
					component_key varchar(64) NOT NULL DEFAULT '',
					score decimal(8,2) NULL,
					weight decimal(8,4) NULL,
					status varchar(32) NOT NULL DEFAULT '',
					meta_json longtext NULL,
					PRIMARY KEY (id),
					KEY evaluation_id (evaluation_id),
					KEY component_key (component_key)
				) {$charset};"
			);
		}
		if ( ! empty( $t['talent_requirement_profiles'] ) ) {
			dbDelta(
				"CREATE TABLE {$t['talent_requirement_profiles']} (
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					profile_key varchar(64) NOT NULL DEFAULT '',
					title varchar(191) NOT NULL DEFAULT '',
					profile_json longtext NULL,
					version varchar(32) NOT NULL DEFAULT '1',
					created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (id),
					UNIQUE KEY profile_key (profile_key)
				) {$charset};"
			);
		}
	}

	/**
	 * Ensure memory identity map exists on already-installed sites.
	 */
	public static function ensure_memory_identity_map() {
		global $wpdb;
		$table = self::table( 'memory_identity_map' );
		if ( ! $table ) {
			return;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
		if ( $exists ) {
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			bridge_type varchar(32) NOT NULL DEFAULT '',
			bridge_id varchar(191) NOT NULL DEFAULT '',
			tenant_id varchar(64) NOT NULL DEFAULT '1',
			provider varchar(32) NOT NULL DEFAULT 'tencentdb',
			remote_id varchar(191) NOT NULL DEFAULT '',
			remote_meta longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY provider_bridge (provider, bridge_type, bridge_id, tenant_id),
			KEY tenant_id (tenant_id),
			KEY remote_id (remote_id)
		) {$charset};";
		dbDelta( $sql );
	}

	/**
	 * Drop all custom tables (uninstall only).
	 */
	public static function drop_tables() {
		global $wpdb;
		foreach ( self::table_names() as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}
	}

	/**
	 * @return bool
	 */
	public static function tables_exist() {
		global $wpdb;
		$table = self::table( 'matches' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (bool) $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
	}
}
