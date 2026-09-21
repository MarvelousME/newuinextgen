<?php
/**
 * CLI: activate Elementor + seed kinetic Elementor pages.
 * Usage inside container:
 *   php /tmp/bi-activate-seed-elementor.php
 *   php /tmp/bi-activate-seed-elementor.php --force
 *   php /tmp/bi-activate-seed-elementor.php --restore-home
 */
if ( 'cli' !== PHP_SAPI ) {
	fwrite( STDERR, "CLI only\n" );
	exit( 1 );
}

require '/var/www/html/wp-load.php';

$argv  = isset( $GLOBALS['argv'] ) && is_array( $GLOBALS['argv'] ) ? $GLOBALS['argv'] : [];
$force = in_array( '--force', $argv, true );

if ( ! function_exists( 'activate_plugin' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$found = [];
foreach ( glob( WP_PLUGIN_DIR . '/*', GLOB_ONLYDIR ) as $dir ) {
	$name = basename( $dir );
	if ( false !== stripos( $name, 'elementor' ) ) {
		$found[] = $name;
	}
}
echo 'FOUND_DIRS=' . implode( ',', $found ) . PHP_EOL;

$activated = false;
$try       = [];
foreach ( $found as $dir ) {
	$try[] = $dir . '/elementor.php';
	$try[] = $dir . '/' . $dir . '.php';
}
$try[] = 'elementor/elementor.php';

foreach ( array_unique( $try ) as $rel ) {
	$file = WP_PLUGIN_DIR . '/' . $rel;
	if ( ! file_exists( $file ) ) {
		continue;
	}
	$r = activate_plugin( $rel, '', false, true );
	if ( is_wp_error( $r ) ) {
		echo 'ACTIVATE_ERR=' . $rel . ':' . $r->get_error_message() . PHP_EOL;
		continue;
	}
	echo 'ACTIVATED=' . $rel . PHP_EOL;
	$activated = true;
	include_once $file;
	break;
}

if ( ! $activated ) {
	echo "NO_ELEMENTOR_TO_ACTIVATE\n";
	echo 'Active plugins: ' . implode( ', ', (array) get_option( 'active_plugins', [] ) ) . PHP_EOL;
	exit( 2 );
}

echo 'CLASS_EXISTS=' . ( class_exists( '\Elementor\Plugin' ) ? '1' : '0' ) . PHP_EOL;
echo 'FORCE=' . ( $force ? '1' : '0' ) . PHP_EOL;

if ( ! function_exists( 'bi_elementor_seed_all_pages' ) ) {
	echo "MISSING_SEED_FN\n";
	exit( 1 );
}

$GLOBALS['bi_force_elementor_active'] = class_exists( '\Elementor\Plugin' );
add_filter(
	'bi_elementor_force_active',
	static function () {
		return ! empty( $GLOBALS['bi_force_elementor_active'] ) || class_exists( '\Elementor\Plugin' );
	}
);

if ( function_exists( 'bi_elementor_restore_kinetic_home' ) ) {
	$home = bi_elementor_restore_kinetic_home();
	echo 'RESTORE_HOME=' . wp_json_encode( $home ) . PHP_EOL;
}

$report = bi_elementor_seed_all_pages( $force );
echo wp_json_encode( $report, JSON_PRETTY_PRINT ) . PHP_EOL;

if ( function_exists( 'bi_elementor_restore_kinetic_home' ) ) {
	$home2 = bi_elementor_restore_kinetic_home();
	echo 'RESTORE_HOME_AFTER=' . wp_json_encode( $home2 ) . PHP_EOL;
}
