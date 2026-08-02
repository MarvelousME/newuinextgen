<?php
/**
 * Platform schema — durable queue, DLQ, idempotency, GL, audit chain.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * dbDelta installer for platform kernel tables.
 */
final class NGC_Platform_Schema {

	public const OPTION_VERSION = 'ngc_platform_db_version';

	/**
	 * Idempotent install/upgrade.
	 */
	public static function maybe_install() {
		$ver = (string) get_option( self::OPTION_VERSION, '' );
		if ( version_compare( $ver, NGC_Platform::DB_VERSION, '<' ) ) {
			self::install();
			update_option( self::OPTION_VERSION, NGC_Platform::DB_VERSION, false );
		}
	}

	/**
	 * Create tables.
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$p       = $wpdb->prefix;

		dbDelta(
			"CREATE TABLE {$p}ngc_queue_messages (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				tenant_id bigint(20) unsigned NOT NULL DEFAULT 1,
				queue_name varchar(64) NOT NULL DEFAULT 'default',
				message_id varchar(64) NOT NULL DEFAULT '',
				idempotency_key varchar(191) NOT NULL DEFAULT '',
				priority int(11) NOT NULL DEFAULT 100,
				status varchar(24) NOT NULL DEFAULT 'pending',
				attempts int(11) NOT NULL DEFAULT 0,
				max_attempts int(11) NOT NULL DEFAULT 8,
				delay_until datetime NULL,
				visibility_until datetime NULL,
				lease_owner varchar(64) NOT NULL DEFAULT '',
				lease_token varchar(64) NOT NULL DEFAULT '',
				payload longtext NULL,
				fingerprint varchar(64) NOT NULL DEFAULT '',
				trace_id varchar(64) NOT NULL DEFAULT '',
				last_error text NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY message_id (message_id),
				KEY claim_idx (queue_name, status, priority, delay_until, visibility_until),
				KEY tenant_queue (tenant_id, queue_name),
				KEY idem_key (tenant_id, idempotency_key)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$p}ngc_queue_dlq (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				tenant_id bigint(20) unsigned NOT NULL DEFAULT 1,
				queue_name varchar(64) NOT NULL DEFAULT 'default',
				message_id varchar(64) NOT NULL DEFAULT '',
				original_id bigint(20) unsigned NOT NULL DEFAULT 0,
				payload longtext NULL,
				fingerprint varchar(64) NOT NULL DEFAULT '',
				reason text NULL,
				attempts int(11) NOT NULL DEFAULT 0,
				replayed tinyint(1) NOT NULL DEFAULT 0,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY queue_name (queue_name),
				KEY tenant_id (tenant_id),
				KEY message_id (message_id)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$p}ngc_idempotency_keys (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				tenant_id bigint(20) unsigned NOT NULL DEFAULT 1,
				idem_key varchar(191) NOT NULL DEFAULT '',
				fingerprint varchar(64) NOT NULL DEFAULT '',
				scope varchar(64) NOT NULL DEFAULT '',
				status varchar(24) NOT NULL DEFAULT 'started',
				result_json longtext NULL,
				expires_at datetime NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY tenant_idem (tenant_id, idem_key),
				KEY expires_at (expires_at)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$p}ngc_gl_accounts (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				tenant_id bigint(20) unsigned NOT NULL DEFAULT 1,
				code varchar(32) NOT NULL DEFAULT '',
				name varchar(191) NOT NULL DEFAULT '',
				type varchar(32) NOT NULL DEFAULT 'asset',
				normal_balance varchar(8) NOT NULL DEFAULT 'debit',
				is_active tinyint(1) NOT NULL DEFAULT 1,
				PRIMARY KEY (id),
				UNIQUE KEY tenant_code (tenant_id, code)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$p}ngc_gl_journals (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				tenant_id bigint(20) unsigned NOT NULL DEFAULT 1,
				journal_uuid varchar(64) NOT NULL DEFAULT '',
				idempotency_key varchar(191) NOT NULL DEFAULT '',
				source varchar(64) NOT NULL DEFAULT '',
				source_ref varchar(191) NOT NULL DEFAULT '',
				memo text NULL,
				currency varchar(8) NOT NULL DEFAULT 'ZAR',
				posted_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				created_by bigint(20) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY (id),
				UNIQUE KEY journal_uuid (journal_uuid),
				UNIQUE KEY tenant_idem (tenant_id, idempotency_key),
				KEY source_ref (source, source_ref)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$p}ngc_gl_entries (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				tenant_id bigint(20) unsigned NOT NULL DEFAULT 1,
				journal_id bigint(20) unsigned NOT NULL DEFAULT 0,
				account_code varchar(32) NOT NULL DEFAULT '',
				debit decimal(14,2) NOT NULL DEFAULT 0.00,
				credit decimal(14,2) NOT NULL DEFAULT 0.00,
				PRIMARY KEY (id),
				KEY journal_id (journal_id),
				KEY account_code (account_code)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$p}ngc_audit_chain (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				tenant_id bigint(20) unsigned NOT NULL DEFAULT 1,
				seq bigint(20) unsigned NOT NULL DEFAULT 0,
				event_uuid varchar(64) NOT NULL DEFAULT '',
				action varchar(64) NOT NULL DEFAULT '',
				object_type varchar(64) NOT NULL DEFAULT '',
				object_id bigint(20) unsigned NOT NULL DEFAULT 0,
				actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
				payload_hash varchar(64) NOT NULL DEFAULT '',
				prev_hash varchar(64) NOT NULL DEFAULT '',
				event_hash varchar(64) NOT NULL DEFAULT '',
				hmac varchar(128) NOT NULL DEFAULT '',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY event_uuid (event_uuid),
				UNIQUE KEY tenant_seq (tenant_id, seq),
				KEY action (action)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$p}ngc_authz_audit (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				tenant_id bigint(20) unsigned NOT NULL DEFAULT 1,
				actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
				resource varchar(64) NOT NULL DEFAULT '',
				resource_id bigint(20) unsigned NOT NULL DEFAULT 0,
				capability varchar(64) NOT NULL DEFAULT '',
				decision varchar(16) NOT NULL DEFAULT 'deny',
				reason varchar(191) NOT NULL DEFAULT '',
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY actor (actor_id),
				KEY decision (decision)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$p}ngc_safeguarding_evidence (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				tenant_id bigint(20) unsigned NOT NULL DEFAULT 1,
				case_id bigint(20) unsigned NOT NULL DEFAULT 0,
				evidence_type varchar(32) NOT NULL DEFAULT 'note',
				storage_path text NULL,
				checksum varchar(64) NOT NULL DEFAULT '',
				meta_json longtext NULL,
				created_by bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY case_id (case_id)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$p}ngc_fraud_evidence (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				tenant_id bigint(20) unsigned NOT NULL DEFAULT 1,
				case_id bigint(20) unsigned NOT NULL DEFAULT 0,
				evidence_type varchar(32) NOT NULL DEFAULT 'signal',
				explainability longtext NULL,
				confidence decimal(5,2) NOT NULL DEFAULT 0.00,
				meta_json longtext NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY case_id (case_id)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$p}ngc_recon_runs (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				tenant_id bigint(20) unsigned NOT NULL DEFAULT 1,
				run_uuid varchar(64) NOT NULL DEFAULT '',
				status varchar(24) NOT NULL DEFAULT 'ok',
				woo_total decimal(14,2) NOT NULL DEFAULT 0.00,
				ledger_total decimal(14,2) NOT NULL DEFAULT 0.00,
				drift decimal(14,2) NOT NULL DEFAULT 0.00,
				report_json longtext NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY run_uuid (run_uuid)
			) {$charset};"
		);

		self::seed_accounts();
		self::ensure_hmac_key();
		self::soft_add_tenant_columns();
	}

	/**
	 * Soft-add tenant_id on hot Companion tables when missing.
	 */
	private static function soft_add_tenant_columns() {
		global $wpdb;
		$tables = [
			$wpdb->prefix . 'ngc_bookings',
			$wpdb->prefix . 'ngc_wallet_ledger',
			$wpdb->prefix . 'ngc_audit_log',
			$wpdb->prefix . 'ngc_safeguarding_cases',
			$wpdb->prefix . 'ngc_fraud_cases',
		];
		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			if ( ! $exists ) {
				continue;
			}
			$col = $wpdb->get_results( $wpdb->prepare( 'SHOW COLUMNS FROM `' . str_replace( '`', '', $table ) . '` LIKE %s', 'tenant_id' ) ); // phpcs:ignore
			if ( ! empty( $col ) ) {
				continue;
			}
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$safe = preg_replace( '/[^a-zA-Z0-9_]/', '', $table );
			$wpdb->query( "ALTER TABLE `{$safe}` ADD COLUMN tenant_id bigint(20) unsigned NOT NULL DEFAULT 1, ADD KEY tenant_id (tenant_id)" );
		}
	}

	/**
	 * Seed chart of accounts.
	 */
	private static function seed_accounts() {
		global $wpdb;
		$table  = $wpdb->prefix . 'ngc_gl_accounts';
		$tenant = class_exists( 'NGC_Tenant_Context' ) ? NGC_Tenant_Context::id() : 1;
		$seed   = [
			[ 'cash', 'Cash / Clearing', 'asset', 'debit' ],
			[ 'ar', 'Accounts Receivable', 'asset', 'debit' ],
			[ 'fees', 'Platform Fees', 'revenue', 'credit' ],
			[ 'tutor_payable', 'Tutor Payables', 'liability', 'credit' ],
			[ 'refunds', 'Refunds', 'expense', 'debit' ],
			[ 'wallet_liability', 'Customer Wallet Liability', 'liability', 'credit' ],
		];
		foreach ( $seed as $row ) {
			$exists = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE tenant_id = %d AND code = %s LIMIT 1",
					$tenant,
					$row[0]
				)
			);
			if ( $exists ) {
				continue;
			}
			$wpdb->insert(
				$table,
				[
					'tenant_id'      => $tenant,
					'code'           => $row[0],
					'name'           => $row[1],
					'type'           => $row[2],
					'normal_balance' => $row[3],
					'is_active'      => 1,
				],
				[ '%d', '%s', '%s', '%s', '%s', '%d' ]
			);
		}
	}

	/**
	 * Ensure HMAC signing key exists.
	 */
	private static function ensure_hmac_key() {
		if ( ! get_option( 'ngc_audit_hmac_key' ) ) {
			update_option( 'ngc_audit_hmac_key', wp_generate_password( 64, true, true ), false );
		}
	}

	/**
	 * @param string $suffix Table suffix without prefix/ngc_.
	 * @return string
	 */
	public static function table( $suffix ) {
		global $wpdb;
		return $wpdb->prefix . 'ngc_' . preg_replace( '/[^a-z0-9_]/', '', $suffix );
	}
}
