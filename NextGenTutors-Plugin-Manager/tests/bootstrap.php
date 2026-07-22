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
if ( ! function_exists( 'do_action' ) ) {
	function do_action( $tag, ...$args ) {
		return;
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
if ( ! function_exists( 'sanitize_file_name' ) ) {
	function sanitize_file_name( $filename ) {
		$filename = (string) $filename;
		$filename = preg_replace( '/[^A-Za-z0-9._-]/', '', $filename );
		return $filename ?: 'file';
	}
}
if ( ! function_exists( 'wp_normalize_path' ) ) {
	function wp_normalize_path( $path ) {
		$path = str_replace( '\\', '/', (string) $path );
		$path = preg_replace( '#/+#', '/', $path );
		return $path;
	}
}
if ( ! function_exists( 'wp_upload_dir' ) ) {
	function wp_upload_dir( $time = null, $create_dir = true, $refresh_cache = false ) {
		$base = trailingslashit( WP_CONTENT_DIR ) . 'uploads';
		return [
			'path'    => $base,
			'url'     => 'http://example.test/wp-content/uploads',
			'subdir'  => '',
			'basedir' => $base,
			'baseurl' => 'http://example.test/wp-content/uploads',
			'error'   => false,
		];
	}
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return trailingslashit( dirname( $file ) );
	}
}
if ( ! defined( 'NGCPM_PLUGIN_FILE' ) ) {
	define( 'NGCPM_PLUGIN_FILE', dirname( __DIR__ ) . '/NextGenTutors-Plugin-Manager.php' );
}
if ( ! defined( 'NGCPM_PLUGIN_DIR' ) ) {
	define( 'NGCPM_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
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
if ( ! class_exists( 'NGCPM_Installer' ) ) {
	/**
	 * Minimal installer stub for offline unit tests.
	 */
	class NGCPM_Installer {
		/**
		 * @param array<string,mixed> $def Plugin definition.
		 * @return string|false
		 */
		public static function resolve_local_path( $def ) {
			return false;
		}
	}
}
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
