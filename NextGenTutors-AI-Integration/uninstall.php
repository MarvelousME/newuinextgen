<?php
/**
 * Plugin uninstall cleanup.
 *
 * @package NextGenTutorsAIIntegration
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
global $wpdb;
$options = [
	'ngtai_agents_api_url',
	'ngtai_agents_api_key_id',
	'ngtai_agents_api_secret_encrypted',
	'ngtai_enabled',
	'ngtai_demo_mode',
	'ngtai_timeout_seconds',
	'ngtai_max_attempts',
	'ngtai_retry_base_seconds',
	'ngtai_callback_skew_seconds',
	'ngtai_nonce_retention_days',
	'ngtai_global_pause',
	'ngtai_db_version',
	'ngtai_signature_failure_total',
	'ngtai_duplicate_event_total',
	'ngtai_callback_failure_total',
	'ngtai_policy_denied_total',
	'ngtai_log_ring',
	'ngtai_last_health',
	'ngtai_last_agents_ping',
	'ngtai_last_delivery',
	'ngtai_last_callback',
	'ngtai_last_lock_recovery',
];
foreach ( $options as $option ) {
	delete_option( $option );
	delete_site_option( $option );
}
foreach ( [ 'callback_nonces', 'deliveries', 'agent_results', 'approvals', 'idempotency', 'audit' ] as $name ) {
	$table = $wpdb->prefix . 'ngtai_' . $name;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}
