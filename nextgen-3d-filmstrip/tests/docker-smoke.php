<?php
/**
 * Docker smoke: activate filmstrip + render tutors/subjects shortcodes.
 */

define( 'WP_USE_THEMES', false );
require '/var/www/html/wp-load.php';

if ( ! function_exists( 'activate_plugin' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$plugin = 'nextgen-3d-filmstrip/nextgen-3d-filmstrip.php';
$result = activate_plugin( $plugin );
if ( is_wp_error( $result ) ) {
	fwrite( STDERR, 'ACTIVATE_FAIL: ' . $result->get_error_message() . PHP_EOL );
	exit( 1 );
}

$checks = [
	'shortcode' => shortcode_exists( 'ngt_filmstrip' ),
	'active'    => in_array( $plugin, (array) get_option( 'active_plugins', [] ), true ),
	'data'      => class_exists( 'NGTFS_Data' ),
];

$tutors = do_shortcode( '[ngt_filmstrip source="tutors" limit="6"]' );
$subs   = do_shortcode( '[ngt_filmstrip source="subjects" limit="6"]' );

$checks['tutors_render'] = is_string( $tutors ) && false !== strpos( $tutors, 'data-ngtfs' ) && false !== strpos( $tutors, 'ngtfs-card' );
$checks['subjects_render'] = is_string( $subs ) && false !== strpos( $subs, 'ngtfs-card' );
$checks['portrait_footer'] = is_string( $tutors ) && false !== strpos( $tutors, 'ngtfs-card__portrait' ) && false !== strpos( $tutors, 'ngtfs-card__footer' );

foreach ( $checks as $name => $ok ) {
	echo ( $ok ? 'PASS' : 'FAIL' ) . ' ' . $name . PHP_EOL;
}
echo 'tutors_len=' . strlen( (string) $tutors ) . ' subjects_len=' . strlen( (string) $subs ) . PHP_EOL;
echo 'tutor_cards=' . count( NGTFS_Data::tutors( 8 ) ) . ' subject_cards=' . count( NGTFS_Data::subjects( 8 ) ) . PHP_EOL;

exit( in_array( false, $checks, true ) ? 1 : 0 );
