<?php
/**
 * PHPUnit bootstrap for NextGen Companion.
 *
 * @package NextGenCompanion
 */

$root = dirname( __DIR__, 2 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/tests-stub/' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins' );
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value ) {
		unset( $hook );
		return $value;
	}
}

if ( ! function_exists( 'get_plugin_data' ) ) {
	/**
	 * @param string $file Plugin path.
	 * @return array<string, string>
	 */
	function get_plugin_data( $file, $markup = true, $translate = true ) {
		unset( $markup, $translate );
		$normalized_file = wp_normalize_path( (string) $file );
		$plugin_root     = rtrim( wp_normalize_path( WP_PLUGIN_DIR ), '/' ) . '/';
		$relative        = str_replace( $plugin_root, '', $normalized_file );
		$map      = array(
			'custom-folder/custom.php' => array(
				'Name'       => 'Custom Legacy',
				'TextDomain' => 'nextgen-tutors-core',
			),
			'legacy/legacy.php' => array(
				'Name'       => 'NextGen Tutors',
				'TextDomain' => 'nextgen-tutors',
			),
			'vendor/ngt-core.php' => array(
				'Name'       => 'NextGen Tutors Core',
				'TextDomain' => 'vendor-core',
			),
		);
		return $map[ $relative ] ?? array();
	}
}

if ( ! function_exists( 'wp_normalize_path' ) ) {
	function wp_normalize_path( $path ) {
		return str_replace( '\\', '/', $path );
	}
}

$stub_plugins = WP_PLUGIN_DIR;
foreach ( array( 'custom-folder', 'legacy', 'vendor' ) as $dir ) {
	if ( ! is_dir( $stub_plugins . '/' . $dir ) ) {
		mkdir( $stub_plugins . '/' . $dir, 0777, true );
	}
}
foreach ( array( 'custom-folder/custom.php', 'legacy/legacy.php', 'vendor/ngt-core.php' ) as $rel ) {
	$file = $stub_plugins . '/' . $rel;
	if ( ! is_file( $file ) ) {
		file_put_contents( $file, "<?php\n/**\n * Plugin Name: Stub\n */\n" );
	}
}

require_once $root . '/includes/diagnostics/class-ngc-legacy-plugin-guard.php';
require_once $root . '/includes/session/class-ngc-session-states.php';
require_once $root . '/includes/integrations/class-ngc-product-provisioner.php';
require_once $root . '/includes/integrations/class-ngc-woocommerce-catalog.php';
require_once $root . '/includes/integrations/class-ngc-payout-export.php';
require_once $root . '/includes/memory/interface-ngc-memory-provider.php';
require_once $root . '/includes/memory/class-ngc-memory-settings.php';
require_once $root . '/includes/memory/class-ngc-memory-noop-provider.php';
require_once $root . '/includes/memory/class-ngc-memory-service.php';

if ( ! isset( $GLOBALS['ngc_test_options'] ) ) {
	$GLOBALS['ngc_test_options'] = [];
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * @param string $key Option key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	function get_option( $key, $default = false ) {
		return array_key_exists( $key, $GLOBALS['ngc_test_options'] ) ? $GLOBALS['ngc_test_options'][ $key ] : $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * @param string $key Option key.
	 * @param mixed  $value Value.
	 * @param bool   $autoload Autoload.
	 * @return bool
	 */
	function update_option( $key, $value, $autoload = true ) {
		unset( $autoload );
		$GLOBALS['ngc_test_options'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		unset( $domain );
		return $text;
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return $value;
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) {
		unset( $type, $gmt );
		return '2026-08-13 18:00:00';
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		$key = strtolower( (string) $key );
		return preg_replace( '/[^a-z0-9_\-]/', '', $key );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title ) {
		$title = strtolower( (string) $title );
		$title = preg_replace( '/[^a-z0-9]+/', '-', $title );
		return trim( $title, '-' );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( (string) $string, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error stub for unit tests.
	 */
	class WP_Error {
		/** @var string */
		private $code;
		/** @var string */
		private $message;
		/** @var mixed */
		private $data;
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = (string) $code;
			$this->message = (string) $message;
			$this->data    = $data;
		}
		public function get_error_message() {
			return $this->message;
		}
		public function get_error_code() {
			return $this->code;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! defined( 'NGC_PLUGIN_DIR' ) ) {
	define( 'NGC_PLUGIN_DIR', $root . '/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', dirname( $root ) );
}
