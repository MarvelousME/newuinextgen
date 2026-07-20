<?php
/**
 * CLI: safe Amelia install — create tables then activate.
 *
 * Usage:
 *   wp plugin deactivate ameliabooking --skip-plugins --allow-root
 *   wp eval-file wp-content/plugins/NextGenTutors-Companion/scripts/amelia-safe-activate.php --allow-root
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

if ( ! class_exists( 'NGC_Amelia_Bootstrap' ) ) {
	fwrite( STDERR, "NGC_Amelia_Bootstrap not available.\n" );
	exit( 1 );
}

$result = NGC_Amelia_Bootstrap::safe_install_and_activate();
echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
exit( ! empty( $result['ok'] ) ? 0 : 1 );
