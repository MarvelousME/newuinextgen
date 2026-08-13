<?php
/**
 * dbDelta CREATE TABLE statements for NGC custom tables.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema SQL builder. Keep statements byte-stable with historical create_tables().
 */
class NGC_Schema_Statements {

	/**
	 * @param array<string, string> $t       Table names keyed by logical id.
	 * @param string                $charset Charset collate clause.
	 * @return string[]
	 */
	public static function create_sql( $t, $charset ) {
		$sql = [];

		$sql[] = "CREATE TABLE {$t['matches']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			student_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			parent_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			tutor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			subject varchar(191) NOT NULL DEFAULT '',
			grade varchar(64) NOT NULL DEFAULT '',
			province varchar(64) NOT NULL DEFAULT '',
			status varchar(32) NOT NULL DEFAULT 'pending',
			score decimal(5,2) NOT NULL DEFAULT 0.00,
			notes longtext NULL,
			meta longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY student_user_id (student_user_id),
			KEY tutor_user_id (tutor_user_id),
			KEY status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['bookings']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			match_id bigint(20) unsigned NOT NULL DEFAULT 0,
			student_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			tutor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			subject varchar(191) NOT NULL DEFAULT '',
			scheduled_at datetime NULL,
			duration_minutes int(11) NOT NULL DEFAULT 60,
			status varchar(32) NOT NULL DEFAULT 'requested',
			amount decimal(12,2) NOT NULL DEFAULT 0.00,
			currency varchar(8) NOT NULL DEFAULT 'ZAR',
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			amelia_booking_id bigint(20) unsigned NULL DEFAULT NULL,
			notes longtext NULL,
			meta longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY match_id (match_id),
			KEY student_user_id (student_user_id),
			KEY tutor_user_id (tutor_user_id),
			KEY status (status),
			KEY scheduled_at (scheduled_at),
			UNIQUE KEY amelia_booking_id (amelia_booking_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['sessions']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session_uuid varchar(64) NOT NULL DEFAULT '',
			correlation_id varchar(64) NOT NULL DEFAULT '',
			idempotency_key varchar(191) NOT NULL DEFAULT '',
			booking_provider varchar(32) NOT NULL DEFAULT 'ngc',
			booking_id bigint(20) unsigned NOT NULL DEFAULT 0,
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			order_item_id bigint(20) unsigned NOT NULL DEFAULT 0,
			product_id bigint(20) unsigned NOT NULL DEFAULT 0,
			student_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			parent_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			tutor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			subject_id varchar(64) NOT NULL DEFAULT '',
			subject_name varchar(191) NOT NULL DEFAULT '',
			masterstudy_course_id bigint(20) unsigned NOT NULL DEFAULT 0,
			masterstudy_lesson_id bigint(20) unsigned NOT NULL DEFAULT 0,
			meeting_provider varchar(32) NOT NULL DEFAULT '',
			meeting_id varchar(191) NOT NULL DEFAULT '',
			meeting_url_reference varchar(512) NOT NULL DEFAULT '',
			scheduled_start datetime NULL,
			scheduled_end datetime NULL,
			timezone varchar(64) NOT NULL DEFAULT 'Africa/Johannesburg',
			status varchar(32) NOT NULL DEFAULT 'draft',
			payment_status varchar(32) NOT NULL DEFAULT 'unpaid',
			booking_status varchar(32) NOT NULL DEFAULT '',
			lesson_status varchar(32) NOT NULL DEFAULT '',
			meeting_status varchar(32) NOT NULL DEFAULT '',
			student_joined_at datetime NULL,
			tutor_joined_at datetime NULL,
			started_at datetime NULL,
			completed_at datetime NULL,
			cancelled_at datetime NULL,
			version int(11) NOT NULL DEFAULT 1,
			meta longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY session_uuid (session_uuid),
			UNIQUE KEY idempotency_key (idempotency_key),
			KEY correlation_id (correlation_id),
			KEY booking_id (booking_id),
			KEY order_id (order_id),
			KEY student_user_id (student_user_id),
			KEY tutor_user_id (tutor_user_id),
			KEY status (status),
			KEY scheduled_start (scheduled_start)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['invoices']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			invoice_number varchar(64) NOT NULL DEFAULT '',
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			booking_id bigint(20) unsigned NOT NULL DEFAULT 0,
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			amount decimal(12,2) NOT NULL DEFAULT 0.00,
			currency varchar(8) NOT NULL DEFAULT 'ZAR',
			status varchar(32) NOT NULL DEFAULT 'issued',
			line_items longtext NULL,
			issued_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			paid_at datetime NULL,
			meta longtext NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY invoice_number (invoice_number),
			KEY user_id (user_id),
			KEY booking_id (booking_id),
			KEY status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['wallet_ledger']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			entry_type varchar(16) NOT NULL DEFAULT 'credit',
			amount decimal(12,2) NOT NULL DEFAULT 0.00,
			currency varchar(8) NOT NULL DEFAULT 'ZAR',
			balance_after decimal(12,2) NOT NULL DEFAULT 0.00,
			reference varchar(191) NOT NULL DEFAULT '',
			description text NULL,
			meta longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY entry_type (entry_type),
			KEY created_at (created_at),
			UNIQUE KEY reference (reference)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['payouts']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			tutor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			amount decimal(12,2) NOT NULL DEFAULT 0.00,
			currency varchar(8) NOT NULL DEFAULT 'ZAR',
			status varchar(32) NOT NULL DEFAULT 'pending',
			period_start date NULL,
			period_end date NULL,
			paid_at datetime NULL,
			meta longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY tutor_user_id (tutor_user_id),
			KEY status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['reviews']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_id bigint(20) unsigned NOT NULL DEFAULT 0,
			parent_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			tutor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			rating tinyint(3) unsigned NOT NULL DEFAULT 0,
			comment text NULL,
			status varchar(32) NOT NULL DEFAULT 'published',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY booking_id (booking_id),
			KEY tutor_user_id (tutor_user_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['audit_log']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id varchar(64) NOT NULL DEFAULT '',
			actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			actor_type varchar(32) NOT NULL DEFAULT 'user',
			action varchar(64) NOT NULL DEFAULT '',
			object_type varchar(64) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			workflow_key varchar(64) NOT NULL DEFAULT '',
			old_values longtext NULL,
			new_values longtext NULL,
			result varchar(32) NOT NULL DEFAULT 'success',
			correlation_id varchar(64) NOT NULL DEFAULT '',
			context longtext NULL,
			ip_address varchar(45) NOT NULL DEFAULT '',
			device varchar(191) NOT NULL DEFAULT '',
			session_id varchar(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY event_id (event_id),
			KEY actor_user_id (actor_user_id),
			KEY actor_type (actor_type),
			KEY action (action),
			KEY object_type (object_type),
			KEY correlation_id (correlation_id),
			KEY created_at (created_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['tutor_applications']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			full_name varchar(191) NOT NULL DEFAULT '',
			email varchar(191) NOT NULL DEFAULT '',
			phone varchar(64) NOT NULL DEFAULT '',
			subjects text NULL,
			province varchar(64) NOT NULL DEFAULT '',
			bio longtext NULL,
			status varchar(32) NOT NULL DEFAULT 'pending',
			review_notes text NULL,
			meta longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY email (email),
			KEY status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['session_logs']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_id bigint(20) unsigned NOT NULL DEFAULT 0,
			student_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			tutor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			attendance varchar(32) NOT NULL DEFAULT 'scheduled',
			progress_note text NULL,
			started_at datetime NULL,
			ended_at datetime NULL,
			meta longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY booking_id (booking_id),
			KEY student_user_id (student_user_id),
			KEY tutor_user_id (tutor_user_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['earnings']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			tutor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			booking_id bigint(20) unsigned NOT NULL DEFAULT 0,
			amount decimal(12,2) NOT NULL DEFAULT 0.00,
			currency varchar(8) NOT NULL DEFAULT 'ZAR',
			status varchar(32) NOT NULL DEFAULT 'pending',
			payout_id bigint(20) unsigned NOT NULL DEFAULT 0,
			earned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			meta longtext NULL,
			PRIMARY KEY  (id),
			KEY tutor_user_id (tutor_user_id),
			KEY booking_id (booking_id),
			KEY status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['ratings']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			tutor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			student_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			booking_id bigint(20) unsigned NOT NULL DEFAULT 0,
			rating tinyint(3) unsigned NOT NULL DEFAULT 0,
			comment text NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY tutor_user_id (tutor_user_id),
			KEY booking_id (booking_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['workflow_runs']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			workflow_key varchar(64) NOT NULL DEFAULT '',
			status varchar(32) NOT NULL DEFAULT 'running',
			context longtext NULL,
			results longtext NULL,
			error_message text NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NULL,
			PRIMARY KEY  (id),
			KEY workflow_key (workflow_key),
			KEY status (status),
			KEY created_at (created_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['analytics_events']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_key varchar(100) NOT NULL DEFAULT '',
			user_id bigint(20) unsigned NULL,
			visitor_id varchar(64) NOT NULL DEFAULT '',
			session_id varchar(64) NOT NULL DEFAULT '',
			page_url text NULL,
			referrer text NULL,
			payload longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY event_key (event_key),
			KEY user_id (user_id),
			KEY visitor_id (visitor_id),
			KEY created_at (created_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['visitor_profiles']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			visitor_id varchar(64) NOT NULL DEFAULT '',
			first_touch longtext NULL,
			last_touch longtext NULL,
			first_landing text NULL,
			last_landing text NULL,
			referrer text NULL,
			country varchar(64) NOT NULL DEFAULT '',
			region varchar(64) NOT NULL DEFAULT '',
			city varchar(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY visitor_id (visitor_id),
			KEY updated_at (updated_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['user_profiles']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			journey_state varchar(64) NOT NULL DEFAULT '',
			profile_completeness tinyint(3) unsigned NOT NULL DEFAULT 0,
			acquisition_source varchar(191) NOT NULL DEFAULT '',
			first_landing text NULL,
			last_landing text NULL,
			session_count int(11) NOT NULL DEFAULT 0,
			conversion_count int(11) NOT NULL DEFAULT 0,
			metadata longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_id (user_id),
			KEY journey_state (journey_state)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['acquisition_sources']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			visitor_id varchar(64) NOT NULL DEFAULT '',
			user_id bigint(20) unsigned NULL,
			touch_type varchar(16) NOT NULL DEFAULT 'first',
			source varchar(191) NOT NULL DEFAULT '',
			medium varchar(191) NOT NULL DEFAULT '',
			campaign varchar(191) NOT NULL DEFAULT '',
			term varchar(191) NOT NULL DEFAULT '',
			content varchar(191) NOT NULL DEFAULT '',
			affiliate_id varchar(191) NOT NULL DEFAULT '',
			partner varchar(191) NOT NULL DEFAULT '',
			click_ids longtext NULL,
			landing_page text NULL,
			referrer text NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY visitor_id (visitor_id),
			KEY user_id (user_id),
			KEY source (source),
			KEY campaign (campaign)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['affiliate_clicks']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			visitor_id varchar(64) NOT NULL DEFAULT '',
			user_id bigint(20) unsigned NULL,
			affiliate_id varchar(191) NOT NULL DEFAULT '',
			partner varchar(191) NOT NULL DEFAULT '',
			campaign varchar(191) NOT NULL DEFAULT '',
			landing_page text NULL,
			referrer text NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY visitor_id (visitor_id),
			KEY affiliate_id (affiliate_id),
			KEY campaign (campaign)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['attribution_links']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NULL,
			visitor_id varchar(64) NOT NULL DEFAULT '',
			object_type varchar(64) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			attribution longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY visitor_id (visitor_id),
			KEY object_type (object_type),
			KEY object_id (object_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['user_sessions']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session_id varchar(64) NOT NULL DEFAULT '',
			visitor_id varchar(64) NOT NULL DEFAULT '',
			user_id bigint(20) unsigned NULL,
			device_profile_id bigint(20) unsigned NULL,
			started_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			ended_at datetime NULL,
			last_seen_at datetime NULL,
			page_views int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY session_id (session_id),
			KEY visitor_id (visitor_id),
			KEY user_id (user_id),
			KEY started_at (started_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['device_profiles']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			visitor_id varchar(64) NOT NULL DEFAULT '',
			device_type varchar(32) NOT NULL DEFAULT '',
			browser varchar(64) NOT NULL DEFAULT '',
			os varchar(64) NOT NULL DEFAULT '',
			user_agent text NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY visitor_id (visitor_id),
			KEY device_type (device_type),
			KEY browser (browser)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['conversion_events']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_key varchar(100) NOT NULL DEFAULT '',
			user_id bigint(20) unsigned NULL,
			visitor_id varchar(64) NOT NULL DEFAULT '',
			object_type varchar(64) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			value decimal(12,2) NOT NULL DEFAULT 0.00,
			currency varchar(8) NOT NULL DEFAULT 'ZAR',
			attribution longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY event_key (event_key),
			KEY user_id (user_id),
			KEY visitor_id (visitor_id),
			KEY created_at (created_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['metric_snapshots']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			metric_key varchar(100) NOT NULL DEFAULT '',
			metric_value decimal(20,4) NOT NULL DEFAULT 0.0000,
			filters longtext NULL,
			computed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY metric_key (metric_key),
			KEY computed_at (computed_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['demo_seed_log']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			seed_key varchar(100) NOT NULL DEFAULT '',
			seed_hash varchar(64) NOT NULL DEFAULT '',
			created_records int(11) NOT NULL DEFAULT 0,
			status varchar(32) NOT NULL DEFAULT 'done',
			details longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY seed_key (seed_key),
			KEY created_at (created_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['consent_log']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			visitor_id varchar(64) NOT NULL DEFAULT '',
			user_id bigint(20) unsigned NULL,
			consent_status varchar(16) NOT NULL DEFAULT 'unknown',
			context longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY visitor_id (visitor_id),
			KEY user_id (user_id),
			KEY consent_status (consent_status)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['gamification_scores']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			point_type varchar(64) NOT NULL DEFAULT 'xp',
			balance decimal(12,2) NOT NULL DEFAULT 0.00,
			lifetime decimal(12,2) NOT NULL DEFAULT 0.00,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_point (user_id, point_type),
			KEY point_type (point_type)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['gamification_achievements']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			achievement_key varchar(100) NOT NULL DEFAULT '',
			category varchar(64) NOT NULL DEFAULT '',
			title varchar(191) NOT NULL DEFAULT '',
			points_awarded decimal(12,2) NOT NULL DEFAULT 0.00,
			meta longtext NULL,
			earned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_achievement (user_id, achievement_key),
			KEY category (category),
			KEY earned_at (earned_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['gamification_events']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			event_key varchar(100) NOT NULL DEFAULT '',
			point_type varchar(64) NOT NULL DEFAULT 'xp',
			points decimal(12,2) NOT NULL DEFAULT 0.00,
			source varchar(64) NOT NULL DEFAULT 'internal',
			context longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY event_key (event_key),
			KEY created_at (created_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['leaderboard_entries']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			board_key varchar(64) NOT NULL DEFAULT 'overall',
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			score decimal(12,2) NOT NULL DEFAULT 0.00,
			rank_position int(11) NOT NULL DEFAULT 0,
			period varchar(32) NOT NULL DEFAULT 'all_time',
			meta longtext NULL,
			computed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY board_user_period (board_key, user_id, period),
			KEY board_key (board_key),
			KEY rank_position (rank_position),
			KEY computed_at (computed_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['export_jobs']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			dataset varchar(64) NOT NULL DEFAULT '',
			format varchar(16) NOT NULL DEFAULT 'csv',
			status varchar(32) NOT NULL DEFAULT 'pending',
			filters longtext NULL,
			columns longtext NULL,
			file_path varchar(255) NOT NULL DEFAULT '',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			scheduled_at datetime NULL,
			completed_at datetime NULL,
			error_message text NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY dataset (dataset),
			KEY status (status),
			KEY scheduled_at (scheduled_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['export_templates']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL DEFAULT '',
			dataset varchar(64) NOT NULL DEFAULT '',
			format varchar(16) NOT NULL DEFAULT 'csv',
			columns longtext NULL,
			filters longtext NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY dataset (dataset),
			KEY name (name)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['repair_snapshots']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			snapshot_key varchar(64) NOT NULL DEFAULT '',
			repair_type varchar(64) NOT NULL DEFAULT '',
			payload longtext NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY snapshot_key (snapshot_key),
			KEY repair_type (repair_type)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['ai_diagnostics_log']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			scan_type varchar(64) NOT NULL DEFAULT 'health',
			provider varchar(64) NOT NULL DEFAULT '',
			diagnosis longtext NULL,
			confidence decimal(5,2) NOT NULL DEFAULT 0.00,
			root_cause text NULL,
			repair_plan longtext NULL,
			rollback_plan longtext NULL,
			status varchar(32) NOT NULL DEFAULT 'completed',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY scan_type (scan_type),
			KEY provider (provider),
			KEY created_at (created_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['system_log']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL DEFAULT '',
			level varchar(16) NOT NULL DEFAULT 'info',
			channel varchar(64) NOT NULL DEFAULT 'system',
			source varchar(64) NOT NULL DEFAULT '',
			message text NOT NULL,
			context longtext NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			correlation_id varchar(64) NOT NULL DEFAULT '',
			ip_address varchar(45) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY uuid (uuid),
			KEY level (level),
			KEY channel (channel),
			KEY source (source),
			KEY correlation_id (correlation_id),
			KEY created_at (created_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['referrals']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			referrer_id bigint(20) unsigned NOT NULL DEFAULT 0,
			referred_id bigint(20) unsigned NOT NULL DEFAULT 0,
			reward_amount decimal(10,2) NOT NULL DEFAULT 50.00,
			status varchar(20) NOT NULL DEFAULT 'pending',
			converted_at datetime NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY referrer_id (referrer_id),
			KEY referred_id (referred_id),
			KEY status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['reminder_schedules']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_id bigint(20) unsigned NOT NULL DEFAULT 0,
			reminder_key varchar(8) NOT NULL DEFAULT '',
			send_at datetime NOT NULL,
			recipient varchar(191) NOT NULL DEFAULT '',
			payload longtext NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			sent_at datetime NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NULL,
			PRIMARY KEY (id),
			KEY booking_id (booking_id),
			KEY status_send_at (status, send_at),
			UNIQUE KEY booking_reminder (booking_id, reminder_key)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['studio_workflows']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			workflow_key varchar(64) NOT NULL DEFAULT '',
			name varchar(191) NOT NULL DEFAULT '',
			description text NULL,
			status varchar(20) NOT NULL DEFAULT 'draft',
			version int(11) unsigned NOT NULL DEFAULT 1,
			graph_json longtext NULL,
			compiled_json longtext NULL,
			settings_json longtext NULL,
			template_key varchar(64) NOT NULL DEFAULT '',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
			published_at datetime NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY workflow_key (workflow_key),
			KEY status (status),
			KEY template_key (template_key)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['studio_versions']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			workflow_id bigint(20) unsigned NOT NULL DEFAULT 0,
			version int(11) unsigned NOT NULL DEFAULT 1,
			graph_json longtext NULL,
			compiled_json longtext NULL,
			snapshot_json longtext NULL,
			published_by bigint(20) unsigned NOT NULL DEFAULT 0,
			published_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY workflow_version (workflow_id, version)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['studio_triggers']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			workflow_id bigint(20) unsigned NOT NULL DEFAULT 0,
			trigger_key varchar(64) NOT NULL DEFAULT '',
			trigger_type varchar(32) NOT NULL DEFAULT 'event',
			config_json longtext NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY workflow_id (workflow_id),
			KEY trigger_key (trigger_key),
			KEY is_active (is_active)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['studio_forms']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_key varchar(64) NOT NULL DEFAULT '',
			name varchar(191) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'draft',
			schema_json longtext NULL,
			workflow_id bigint(20) unsigned NOT NULL DEFAULT 0,
			settings_json longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY form_key (form_key),
			KEY workflow_id (workflow_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['studio_emails']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email_key varchar(64) NOT NULL DEFAULT '',
			name varchar(191) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'draft',
			subject varchar(255) NOT NULL DEFAULT '',
			body_html longtext NULL,
			body_text longtext NULL,
			merge_fields_json longtext NULL,
			workflow_id bigint(20) unsigned NOT NULL DEFAULT 0,
			settings_json longtext NULL,
			version int(11) unsigned NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY email_key (email_key),
			KEY workflow_id (workflow_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['studio_notifications']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			notification_key varchar(64) NOT NULL DEFAULT '',
			name varchar(191) NOT NULL DEFAULT '',
			channel varchar(32) NOT NULL DEFAULT 'email',
			status varchar(20) NOT NULL DEFAULT 'draft',
			config_json longtext NULL,
			workflow_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY notification_key (notification_key),
			KEY workflow_id (workflow_id),
			KEY channel (channel)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['studio_dashboards']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			dashboard_key varchar(64) NOT NULL DEFAULT '',
			name varchar(191) NOT NULL DEFAULT '',
			role varchar(32) NOT NULL DEFAULT 'admin',
			status varchar(20) NOT NULL DEFAULT 'draft',
			layout_json longtext NULL,
			widgets_json longtext NULL,
			workflow_id bigint(20) unsigned NOT NULL DEFAULT 0,
			settings_json longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY dashboard_key (dashboard_key),
			KEY role (role),
			KEY workflow_id (workflow_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['studio_executions']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			workflow_id bigint(20) unsigned NOT NULL DEFAULT 0,
			workflow_version int(11) unsigned NOT NULL DEFAULT 1,
			trigger_event varchar(64) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'running',
			context_json longtext NULL,
			path_json longtext NULL,
			results_json longtext NULL,
			duration_ms int(11) unsigned NOT NULL DEFAULT 0,
			error_message text NULL,
			is_simulation tinyint(1) NOT NULL DEFAULT 0,
			actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			started_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			completed_at datetime NULL,
			PRIMARY KEY (id),
			KEY workflow_id (workflow_id),
			KEY status (status),
			KEY trigger_event (trigger_event),
			KEY started_at (started_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['child_learners']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL DEFAULT '',
			parent_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			student_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			display_name varchar(191) NOT NULL DEFAULT '',
			grade varchar(64) NOT NULL DEFAULT '',
			province varchar(64) NOT NULL DEFAULT '',
			email varchar(191) NOT NULL DEFAULT '',
			status varchar(32) NOT NULL DEFAULT 'active',
			meta longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uuid (uuid),
			KEY parent_user_id (parent_user_id),
			KEY student_user_id (student_user_id),
			KEY status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['intel_events']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL DEFAULT '',
			event_key varchar(100) NOT NULL DEFAULT '',
			plugin_slug varchar(64) NOT NULL DEFAULT '',
			module varchar(64) NOT NULL DEFAULT '',
			feature varchar(64) NOT NULL DEFAULT '',
			domain varchar(64) NOT NULL DEFAULT '',
			severity varchar(16) NOT NULL DEFAULT 'info',
			outcome varchar(16) NOT NULL DEFAULT 'unknown',
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			correlation_id varchar(64) NOT NULL DEFAULT '',
			request_id varchar(32) NOT NULL DEFAULT '',
			duration_ms int(11) unsigned NULL,
			message varchar(500) NOT NULL DEFAULT '',
			payload longtext NULL,
			context longtext NULL,
			source varchar(64) NOT NULL DEFAULT 'sdk',
			recorded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY event_key (event_key),
			KEY plugin_slug (plugin_slug),
			KEY domain (domain),
			KEY severity (severity),
			KEY recorded_at (recorded_at),
			KEY correlation_id (correlation_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['intel_kpi_hourly']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			bucket_hour datetime NOT NULL,
			metric_key varchar(128) NOT NULL DEFAULT '',
			plugin_slug varchar(64) NOT NULL DEFAULT '',
			domain varchar(64) NOT NULL DEFAULT '',
			event_count int(11) unsigned NOT NULL DEFAULT 0,
			error_count int(11) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY bucket_metric (bucket_hour, metric_key, plugin_slug),
			KEY domain (domain)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['intel_notifications']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL DEFAULT '',
			type varchar(16) NOT NULL DEFAULT 'info',
			title varchar(191) NOT NULL DEFAULT '',
			message text NOT NULL,
			meta longtext NULL,
			status varchar(16) NOT NULL DEFAULT 'open',
			dedupe_hash char(32) NOT NULL DEFAULT '',
			ack_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			ack_at datetime NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status),
			KEY dedupe_hash (dedupe_hash),
			KEY created_at (created_at)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['page_sections']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL DEFAULT '',
			page_key varchar(64) NOT NULL DEFAULT 'home',
			section_key varchar(64) NOT NULL DEFAULT '',
			title varchar(255) NOT NULL DEFAULT '',
			content_json longtext NULL,
			is_enabled tinyint(1) NOT NULL DEFAULT 1,
			sort_order int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY page_section (page_key, section_key),
			UNIQUE KEY uuid (uuid),
			KEY page_key (page_key)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['builder_documents']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			document_key varchar(64) NOT NULL DEFAULT '',
			kind varchar(32) NOT NULL DEFAULT 'page',
			title varchar(191) NOT NULL DEFAULT '',
			wp_post_id bigint(20) unsigned NULL,
			status varchar(20) NOT NULL DEFAULT 'draft',
			schema_version int(11) unsigned NOT NULL DEFAULT 1,
			document_json longtext NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
			published_at datetime NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY document_key (document_key),
			KEY kind_status (kind, status),
			KEY wp_post_id (wp_post_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$t['builder_revisions']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			document_id bigint(20) unsigned NOT NULL DEFAULT 0,
			version int(11) unsigned NOT NULL DEFAULT 1,
			document_json longtext NULL,
			compiled_json longtext NULL,
			published_by bigint(20) unsigned NOT NULL DEFAULT 0,
			published_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY document_version (document_id, version)
		) $charset;";

		if ( ! empty( $t['memory_identity_map'] ) ) {
			$sql[] = "CREATE TABLE {$t['memory_identity_map']} (
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
			) $charset;";
		}

		return $sql;
	}

	/**
	 * Dummy table names for byte-stability hashing. Not used at runtime.
	 *
	 * @internal
	 * @return array<string, string>
	 */
	public static function fixture_tables() {
		static $t = null;
		if ( null !== $t ) {
			return $t;
		}
		$src = (string) file_get_contents( __FILE__ );
		preg_match_all( "/\\\$t\['([^']+)'\]/", $src, $matches );
		$t = [];
		foreach ( array_unique( $matches[1] ) as $key ) {
			$t[ $key ] = 'wp_ngc_' . $key;
		}
		return $t;
	}

	/**
	 * SHA-256 of canonical CREATE TABLE SQL (fixture table names + charset).
	 *
	 * @internal
	 * @param string $charset Charset collate clause.
	 * @return string
	 */
	public static function canonical_sql_hash( $charset = 'DEFAULT CHARSET utf8mb4' ) {
		return hash( 'sha256', implode( "\n", self::create_sql( self::fixture_tables(), $charset ) ) );
	}
}
