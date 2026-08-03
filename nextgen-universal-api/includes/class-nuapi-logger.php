<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Lightweight audit trail for every write operation performed through the generated CRUD API. */
class NUAPI_Logger {

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'nuapi_audit_log';
	}

	public static function maybe_create_table() {
		global $wpdb;
		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			action VARCHAR(20) NOT NULL,
			target_table VARCHAR(191) NOT NULL,
			row_id BIGINT NOT NULL DEFAULT 0,
			actor VARCHAR(191) NOT NULL,
			ip VARCHAR(45) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY target_table (target_table),
			KEY created_at (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function log( $action, $table, $row_id, WP_REST_Request $request ) {
		global $wpdb;
		$actor = is_user_logged_in() ? wp_get_current_user()->user_login : 'api-key';
		$wpdb->insert( self::table_name(), array(
			'action'       => sanitize_text_field( $action ),
			'target_table' => sanitize_text_field( $table ),
			'row_id'       => (int) $row_id,
			'actor'        => sanitize_text_field( $actor ),
			'ip'           => self::get_ip(),
			'created_at'   => current_time( 'mysql' ),
		) );
	}

	private static function get_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	public static function get_recent( $limit = 50 ) {
		global $wpdb;
		$table = self::table_name();
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` ORDER BY id DESC LIMIT %d", $limit ) );
	}
}
