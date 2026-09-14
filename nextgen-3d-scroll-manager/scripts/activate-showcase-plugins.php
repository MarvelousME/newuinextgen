<?php
/**
 * Ensure subjects widget + filmstrip plugins are active.
 */
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugins = [
	'nextgen-subjects-widget/nextgen-subjects-widget.php',
	'nextgen-3d-filmstrip/nextgen-3d-filmstrip.php',
	'nextgen-3d-scroll-manager/nextgen-3d-scroll-manager.php',
];

foreach ( $plugins as $plugin ) {
	$path = WP_PLUGIN_DIR . '/' . $plugin;
	$active = is_plugin_active( $plugin );
	echo $plugin . ' exists=' . ( file_exists( $path ) ? 'yes' : 'no' ) . ' active=' . ( $active ? 'yes' : 'no' ) . PHP_EOL;
	if ( file_exists( $path ) && ! $active ) {
		$result = activate_plugin( $plugin );
		echo '  activate=' . ( is_wp_error( $result ) ? $result->get_error_message() : 'ok' ) . PHP_EOL;
	}
}

echo 'shortcode nextgen_subjects=' . ( shortcode_exists( 'nextgen_subjects' ) ? 'yes' : 'no' ) . PHP_EOL;
echo 'shortcode ngt_filmstrip=' . ( shortcode_exists( 'ngt_filmstrip' ) ? 'yes' : 'no' ) . PHP_EOL;
