<?php
/**
 * Database schema installation and upgrade management.
 *
 * Uses WordPress dbDelta() for safe schema creation and upgrades.
 * Schema version is stored in wp_options as ngt_3d_schema_version.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Schema {

	/**
	 * Current schema version. Increment when altering the table structure.
	 */
	const VERSION = 1;

	/**
	 * Option key that stores the installed schema version.
	 */
	const OPTION_KEY = 'ngt_3d_schema_version';

	/**
	 * Install or upgrade the table. Called on plugin activation.
	 */
	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$table   = $wpdb->prefix . 'ngt_3d_scroll_rules';

		/*
		 * Column inventory:
		 *   id               – PK auto-increment
		 *   page_id          – 0 = "all pages"; otherwise the WP post_id
		 *   page_slug        – "home", "front-page", "all", or a real slug
		 *   target_selector  – CSS selector ("#hero", ".ngi-section", etc.)
		 *   target_type      – id | class | selector | data-attr | element
		 *   animation_names  – comma-separated whitelist names ("depth-hero,parallax-slow")
		 *   style_classes    – space-separated CSS classes to add to the target
		 *   animation_options – sanitized JSON config object
		 *   sort_order       – lower = runs first
		 *   enabled          – 1 = active, 0 = paused
		 *   desktop_mode     – full | reduced | disabled
		 *   tablet_mode      – full | reduced | disabled
		 *   mobile_mode      – full | reduced | disabled
		 *   created_at       – UTC datetime
		 *   updated_at       – UTC datetime, auto-updated by MySQL
		 *   created_by       – WP user ID
		 *   updated_by       – WP user ID
		 */
		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			page_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			page_slug VARCHAR(200) NOT NULL DEFAULT '',
			target_selector VARCHAR(500) NOT NULL DEFAULT '',
			target_type VARCHAR(50) NOT NULL DEFAULT 'selector',
			animation_names VARCHAR(1000) NOT NULL DEFAULT '',
			style_classes VARCHAR(500) NOT NULL DEFAULT '',
			animation_options LONGTEXT DEFAULT NULL,
			sort_order SMALLINT(6) NOT NULL DEFAULT 10,
			enabled TINYINT(1) NOT NULL DEFAULT 1,
			desktop_mode VARCHAR(20) NOT NULL DEFAULT 'full',
			tablet_mode VARCHAR(20) NOT NULL DEFAULT 'reduced',
			mobile_mode VARCHAR(20) NOT NULL DEFAULT 'reduced',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			updated_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY idx_page_id (page_id),
			KEY idx_page_slug (page_slug(191)),
			KEY idx_enabled_order (enabled, sort_order),
			KEY idx_page_id_enabled (page_id, enabled)
		) {$charset};";

		dbDelta( $sql );

		update_option( self::OPTION_KEY, self::VERSION, false );
	}

	/**
	 * Called on every `plugins_loaded`. Only runs work when the installed
	 * version is lower than the current VERSION constant.
	 */
	public static function maybe_upgrade(): void {
		$installed = (int) get_option( self::OPTION_KEY, 0 );

		if ( $installed >= self::VERSION ) {
			return;
		}

		self::install();
	}

	/**
	 * Table name, with prefix.
	 *
	 * @return string
	 */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'ngt_3d_scroll_rules';
	}

	/**
	 * Whether the table currently exists in the database.
	 *
	 * @return bool
	 */
	public static function table_exists(): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (bool) $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', self::table() )
		);
	}
}
