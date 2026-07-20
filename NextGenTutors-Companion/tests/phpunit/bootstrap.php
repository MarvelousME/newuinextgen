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
		$relative = str_replace( '\\', '/', str_replace( WP_PLUGIN_DIR . '/', '', wp_normalize_path( $file ) ) );
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
