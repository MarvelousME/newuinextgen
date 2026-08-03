<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

delete_option( 'nuapi_settings' );
delete_option( 'nuapi_api_keys' );
delete_transient( 'nuapi_registry_cache' );

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nuapi_audit_log" );
