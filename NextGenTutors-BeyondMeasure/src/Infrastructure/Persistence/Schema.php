<?php
declare(strict_types=1);

namespace NGTBM\Infrastructure\Persistence;

/**
 * Control-plane persistence (notifications, config revisions, audit mirror).
 */
final class Schema {

	public const DB_VERSION = 1;

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$n       = $wpdb->prefix . 'ngtbm_notifications';
		$c       = $wpdb->prefix . 'ngtbm_config_revisions';
		$a       = $wpdb->prefix . 'ngtbm_audit';

		$sql = [];
		$sql[] = "CREATE TABLE {$n} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			severity varchar(32) NOT NULL DEFAULT 'info',
			source varchar(128) NOT NULL DEFAULT '',
			title varchar(191) NOT NULL DEFAULT '',
			body text NULL,
			correlation_id varchar(64) NOT NULL DEFAULT '',
			status varchar(32) NOT NULL DEFAULT 'open',
			action_label varchar(64) NOT NULL DEFAULT '',
			action_url varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			acked_at datetime NULL,
			PRIMARY KEY (id),
			KEY status_severity (status, severity),
			KEY created_at (created_at)
		) {$charset};";

		$sql[] = "CREATE TABLE {$c} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			subsystem_id varchar(128) NOT NULL DEFAULT '',
			payload longtext NOT NULL,
			actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(32) NOT NULL DEFAULT 'published',
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY subsystem_id (subsystem_id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$a} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			action varchar(128) NOT NULL DEFAULT '',
			resource varchar(128) NOT NULL DEFAULT '',
			resource_id varchar(128) NOT NULL DEFAULT '',
			detail longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY created_at (created_at)
		) {$charset};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
		update_option( 'ngtbm_db_version', self::DB_VERSION, false );
	}

	public static function maybe_upgrade(): void {
		$v = (int) get_option( 'ngtbm_db_version', 0 );
		if ( $v < self::DB_VERSION ) {
			self::install();
		}
	}
}
