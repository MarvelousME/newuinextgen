<?php
/**
 * Schedule a paid join-window session for headed classroom verification.
 *
 * Usage:
 *   wp eval-file wp-content/plugins/NextGenTutors-Companion/scripts/seed-classroom-join-window.php --allow-root
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! class_exists( 'NGC_Demo_Seeder' ) ) {
	echo "FAIL NGC_Demo_Seeder missing\n";
	exit( 1 );
}

$out = NGC_Demo_Seeder::seed_classroom_join_window();
if ( is_wp_error( $out ) ) {
	echo 'FAIL ' . $out->get_error_message() . "\n";
	exit( 1 );
}

echo 'CLASSROOM_SEED ' . wp_json_encode( $out ) . "\n";
