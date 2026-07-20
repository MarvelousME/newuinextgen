<?php
/**
 * Uninstall NextGenTutors Plugin Manager.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ngcpm_action_log' );
delete_option( 'ngcpm_enable_frontend' );
delete_option( 'ngcpm_enable_remote_zips' );
delete_option( 'ngcpm_local_zip_dir' );
delete_option( 'ngcpm_auto_install_local_zips' );
delete_option( 'ngcpm_custom_registry' );
delete_option( 'ngcpm_remote_zip_urls' );
delete_option( 'ngcpm_last_scan_time' );
delete_transient( 'ngcpm_scan_cache' );
