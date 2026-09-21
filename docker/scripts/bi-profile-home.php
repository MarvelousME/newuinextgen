<?php
/**
 * Profile front-page bootstrap timing.
 */
$t0 = microtime( true );
require '/var/www/html/wp-load.php';
echo 'wp-load=' . round( microtime( true ) - $t0, 3 ) . "s\n";

$checks = [
	'bi_use_kinetic_home' => static function () {
		return function_exists( 'bi_use_kinetic_home' ) ? ( bi_use_kinetic_home() ? '1' : '0' ) : 'missing';
	},
	'bi_should_show_theme_fallback' => static function () {
		$f = (int) get_option( 'page_on_front' );
		return function_exists( 'bi_should_show_theme_fallback' ) ? ( bi_should_show_theme_fallback( $f ) ? '1' : '0' ) : 'missing';
	},
	'bi_kinetic_subject_tabs' => static function () {
		return function_exists( 'bi_kinetic_subject_tabs' ) ? (string) count( bi_kinetic_subject_tabs() ) : 'missing';
	},
	'bi_get_subject_tracks' => static function () {
		return function_exists( 'bi_get_subject_tracks' ) ? (string) count( bi_get_subject_tracks() ) : 'missing';
	},
	'bi_real_stat_cards' => static function () {
		return function_exists( 'bi_real_stat_cards' ) ? (string) count( bi_real_stat_cards() ) : 'missing';
	},
	'ngc_home_section_hero' => static function () {
		return function_exists( 'ngc_home_section' ) ? substr( (string) ngc_home_section( 'hero', 'title', '' ), 0, 40 ) : 'missing';
	},
];

foreach ( $checks as $label => $fn ) {
	$s = microtime( true );
	try {
		$out = $fn();
		echo $label . '=' . round( microtime( true ) - $s, 3 ) . 's result=' . $out . "\n";
	} catch ( Throwable $e ) {
		echo $label . '=ERR ' . $e->getMessage() . "\n";
	}
}

$s = microtime( true );
ob_start();
$path = get_stylesheet_directory() . '/template-parts/pages/home.php';
include $path;
$html = ob_get_clean();
echo 'home_body=' . round( microtime( true ) - $s, 3 ) . 's bytes=' . strlen( $html ) . "\n";
