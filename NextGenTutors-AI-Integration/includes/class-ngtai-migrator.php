<?php
/**
 * Database schema migrations.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installs and upgrades integration tables.
 */
final class NGTAI_Migrator {

	/**
	 * Current database schema version.
	 */
	const DB_VERSION = '1.1.0';

	/**
	 * Apply the current schema idempotently.
	 *
	 * @return bool
	 */
	public static function migrate() {
		global $wpdb;

		if ( ! isset( $wpdb ) ) {
			return false;
		}

		if ( function_exists( 'get_option' ) && self::DB_VERSION === get_option( 'ngtai_db_version', '' ) ) {
			return true;
		}

		if ( ! function_exists( 'dbDelta' ) ) {
			$upgrade_file = defined( 'ABSPATH' ) ? ABSPATH . 'wp-admin/includes/upgrade.php' : '';
			if ( '' === $upgrade_file || ! is_readable( $upgrade_file ) ) {
				self::log( 'error', 'Database upgrade library is unavailable.' );
				return false;
			}
			require_once $upgrade_file;
		}

		$charset_collate = method_exists( $wpdb, 'get_charset_collate' ) ? $wpdb->get_charset_collate() : '';
		$prefix          = $wpdb->prefix . 'ngtai_';
		$queries         = [
			"CREATE TABLE {$prefix}callback_nonces (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				nonce VARCHAR(191) NOT NULL,
				request_id VARCHAR(191) NULL,
				request_path VARCHAR(255) NOT NULL,
				received_at DATETIME NOT NULL,
				expires_at DATETIME NOT NULL,
				remote_hash VARCHAR(64) NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY nonce_unique (nonce),
				KEY expires_at (expires_at),
				KEY request_id (request_id)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}deliveries (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				event_id VARCHAR(191) NOT NULL,
				event_type VARCHAR(191) NOT NULL,
				schema_version INT UNSIGNED NOT NULL,
				correlation_id VARCHAR(191) NOT NULL,
				status VARCHAR(32) NOT NULL,
				attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
				next_attempt_at DATETIME NULL,
				locked_at DATETIME NULL,
				locked_by VARCHAR(191) NULL,
				http_status INT NULL,
				last_error TEXT NULL,
				request_hash VARCHAR(64) NULL,
				response_hash VARCHAR(64) NULL,
				payload_json LONGTEXT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				delivered_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY event_id_unique (event_id),
				KEY status_next_attempt (status, next_attempt_at),
				KEY correlation_id (correlation_id)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}agent_results (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				agent_run_id VARCHAR(191) NOT NULL,
				result_version INT UNSIGNED NOT NULL DEFAULT 1,
				event_id VARCHAR(191) NULL,
				correlation_id VARCHAR(191) NOT NULL,
				agent_name VARCHAR(191) NOT NULL,
				action_name VARCHAR(191) NOT NULL,
				status VARCHAR(32) NOT NULL,
				policy_decision VARCHAR(32) NULL,
				approval_id VARCHAR(191) NULL,
				result_json LONGTEXT NULL,
				error_json LONGTEXT NULL,
				received_at DATETIME NOT NULL,
				applied_at DATETIME NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY run_version_unique (agent_run_id, result_version),
				KEY correlation_id (correlation_id),
				KEY approval_id (approval_id),
				KEY status (status)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}approvals (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				approval_id VARCHAR(191) NOT NULL,
				agent_run_id VARCHAR(191) NOT NULL,
				action_name VARCHAR(191) NOT NULL,
				status VARCHAR(32) NOT NULL,
				requested_by VARCHAR(191) NULL,
				decided_by BIGINT UNSIGNED NULL,
				decision_reason TEXT NULL,
				payload_json LONGTEXT NULL,
				created_at DATETIME NOT NULL,
				decided_at DATETIME NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY approval_id_unique (approval_id),
				KEY agent_run_id (agent_run_id),
				KEY status (status)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}idempotency (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				idempotency_key VARCHAR(191) NOT NULL,
				result_hash VARCHAR(64) NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY idempotency_key_unique (idempotency_key)
			) {$charset_collate};",
		];

		foreach ( $queries as $query ) {
			call_user_func( 'dbDelta', $query );
		}

		if ( function_exists( 'update_option' ) ) {
			update_option( 'ngtai_db_version', self::DB_VERSION, false );
		}
		self::log( 'info', 'Database schema migrated.', [ 'version' => self::DB_VERSION ] );

		return true;
	}

	/**
	 * Log migration activity when the logger is available.
	 *
	 * @param string              $level   Log level.
	 * @param string              $message Message.
	 * @param array<string,mixed> $context Safe context.
	 * @return void
	 */
	private static function log( $level, $message, array $context = [] ) {
		if ( ! class_exists( 'NGTAI_Logger' ) ) {
			return;
		}

		if ( is_callable( [ 'NGTAI_Logger', $level ] ) ) {
			call_user_func( [ 'NGTAI_Logger', $level ], $message, $context );
		} elseif ( is_callable( [ 'NGTAI_Logger', 'log' ] ) ) {
			NGTAI_Logger::log( $level, $message, $context );
		}
	}
}
