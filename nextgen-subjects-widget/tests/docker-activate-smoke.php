<?php
/**
 * Runtime activation + render smoke for Docker.
 *
 * @package NextGen_Subjects_Widget
 */

define( 'WP_USE_THEMES', false );
require '/var/www/html/wp-load.php';

$plugin = 'nextgen-subjects-widget/nextgen-subjects-widget.php';
if ( ! function_exists( 'activate_plugin' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$result = activate_plugin( $plugin );
if ( is_wp_error( $result ) ) {
	fwrite( STDERR, 'ACTIVATE_FAIL: ' . $result->get_error_message() . PHP_EOL );
	exit( 1 );
}

$checks = [
	'shortcode' => shortcode_exists( 'nextgen_subjects' ),
	'catalog'   => class_exists( 'NGSW_Catalog' ),
	'active'    => in_array( $plugin, (array) get_option( 'active_plugins', [] ), true ),
];

$html = do_shortcode( '[nextgen_subjects]' );
$checks['render'] = is_string( $html ) && false !== strpos( $html, 'ng-subjects' );
$checks['links']  = is_string( $html ) && (
	false !== strpos( $html, 'subject=' )
	|| false !== strpos( $html, '/subject/' )
	|| false !== strpos( $html, 'find-a-tutor' )
);

foreach ( $checks as $name => $ok ) {
	echo ( $ok ? 'PASS' : 'FAIL' ) . ' ' . $name . PHP_EOL;
}

echo 'html_len=' . strlen( (string) $html ) . PHP_EOL;
exit( in_array( false, $checks, true ) ? 1 : 0 );
