<?php
/**
 * Uninstall handler — drops tables only when NGC_DROP_TABLES is defined.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'NGC_DROP_TABLES' ) || ! NGC_DROP_TABLES ) {
	return;
}

$plugin_dir = dirname( __FILE__ );
require_once $plugin_dir . '/includes/class-ngc-database.php';

NGC_Database::drop_tables();

delete_option( 'ngc_db_version' );
delete_option( 'ngc_form_queue' );
