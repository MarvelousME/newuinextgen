<?php
/**
 * Offline smoke for NGSW_Catalog::parse_lines / guess_icon (no WordPress bootstrap).
 *
 * Run: php nextgen-subjects-widget/tests/smoke-catalog.php
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// Minimal WordPress stubs.
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
	}
}
if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title ) {
		$title = strtolower( trim( (string) $title ) );
		$title = preg_replace( '/[^a-z0-9]+/', '-', $title );
		return trim( (string) $title, '-' );
	}
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
	}
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return (string) $url;
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return false;
	}
}
if ( ! function_exists( 'taxonomy_exists' ) ) {
	function taxonomy_exists( $tax ) {
		return false;
	}
}
if ( ! function_exists( 'get_page_by_path' ) ) {
	function get_page_by_path( $path ) {
		return null;
	}
}
if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) {
		return 'https://example.test' . $path;
	}
}
if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $key, $value, $url ) {
		$sep = str_contains( $url, '?' ) ? '&' : '?';
		return $url . $sep . rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value );
	}
}

require_once dirname( __DIR__ ) . '/includes/class-ngsw-catalog.php';

$parsed = NGSW_Catalog::parse_lines( "Mathematics|CAPS maths|calculator\nEnglish||book\n\n|skip-me" );
assert( count( $parsed ) === 2, 'expected 2 subjects, got ' . count( $parsed ) );
assert( $parsed[0]['name'] === 'Mathematics', 'math name' );
assert( $parsed[0]['icon'] === 'calculator', 'math icon' );
assert( $parsed[0]['slug'] === 'mathematics', 'math slug' );
assert( str_contains( $parsed[0]['url'], 'subject=mathematics' ), 'math url' );
assert( $parsed[1]['icon'] === 'book', 'english icon' );

$defaults = NGSW_Catalog::defaults();
assert( count( $defaults ) >= 6, 'defaults count' );

$auto = NGSW_Catalog::resolve( '', true );
assert( count( $auto ) >= 6, 'auto fallback defaults' );

$manual = NGSW_Catalog::resolve( 'Physics|Atoms|atom', false );
assert( count( $manual ) === 1 && $manual[0]['name'] === 'Physics', 'manual override' );

echo "PASS ngsw catalog smoke (" . count( $defaults ) . " defaults)\n";
