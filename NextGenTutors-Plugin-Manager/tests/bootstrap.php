<?php
/**
 * Test bootstrap — minimal WordPress stubs.
 *
 * @package NextGenCorePluginManager
 */

$root = dirname( __DIR__ );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/tests/stub/' );
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}
if ( ! defined( 'NGCPM_LOG_LIMIT' ) ) {
	define( 'NGCPM_LOG_LIMIT', 500 );
}

$options = [];

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value ) {
		return $value;
	}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $key, $default = false ) {
		global $options;
		return $options[ $key ] ?? $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $key, $value, $autoload = false ) {
		global $options;
		$options[ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $key ) {
		global $options;
		unset( $options[ $key ] );
		return true;
	}
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = '' ) {
		return $text;
	}
}
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'http://example.test/wp-admin/' . ltrim( $path, '/' );
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}
if ( ! function_exists( 'wp_generate_uuid4' ) ) {
	function wp_generate_uuid4() {
		return 'test-uuid';
	}
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 1;
	}
}

require_once $root . '/includes/class-ngcpm-logger.php';
require_once $root . '/includes/class-ngcpm-registry.php';
require_once $root . '/includes/class-ngcpm-settings.php';
require_once $root . '/includes/class-ngcpm-queue.php';
require_once $root . '/includes/class-ngcpm-repair.php';

/**
 * @return array<string, array<string, mixed>>
 */
function ngcpm_stub_scan() {
	$stub = [];
	foreach ( NGCPM_Registry::sorted() as $slug => $def ) {
		$stub[ $slug ] = array_merge(
			$def,
			[
				'installed'     => true,
				'active'        => true,
				'health_status' => 'READY',
			]
		);
	}
	return $stub;
}
