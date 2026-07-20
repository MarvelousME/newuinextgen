<?php
/**
 * Debug FluentCRM bootstrap (WP-CLI).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! class_exists( 'NGC_Fluentcrm_Adapter' ) ) {
	WP_CLI::error( 'NGC_Fluentcrm_Adapter missing.' );
}

$adapter = new NGC_Fluentcrm_Adapter();
WP_CLI::line( 'available: ' . ( $adapter->is_available() ? 'yes' : 'no' ) );
$adapter->bootstrap_assets();
$verify = $adapter->verify();
WP_CLI::line( wp_json_encode( $verify, JSON_PRETTY_PRINT ) );
