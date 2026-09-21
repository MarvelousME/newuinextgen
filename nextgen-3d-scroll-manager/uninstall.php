<?php
/**
 * Uninstall handler — remove all plugin data when deleted from WP admin.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Remove the rules table.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ngt_3d_scroll_rules" );

// Remove options.
delete_option( 'ngt_3d_schema_version' );
delete_option( 'ngt_3d_settings' );
delete_option( 'ngt_3d_diagnostics_log' );

// Remove per-page cache transients.
$wpdb->query( $wpdb->prepare(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
	$wpdb->esc_like( '_transient_ngt3d_rules_' ) . '%'
) );
$wpdb->query( $wpdb->prepare(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
	$wpdb->esc_like( '_transient_timeout_ngt3d_rules_' ) . '%'
) );
