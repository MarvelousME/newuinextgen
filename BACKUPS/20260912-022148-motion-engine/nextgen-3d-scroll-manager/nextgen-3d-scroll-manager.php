<?php
/**
 * Plugin Name:       NextGen 3D Scroll Manager
 * Plugin URI:        https://nextgentutors.co.za/
 * Description:       Admin-configurable 3D Scrolling Engine for NextGen Tutors. Manages scroll-linked 3D transforms, depth parallax, perspective reveals, WebGL effects and GSAP/ScrollTrigger orchestration — all configurable without touching PHP or JS.
 * Version:           1.1.0
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            NextGen Tutors / BeyondInfinity
 * Text Domain:       ngt-3d-scroll
 * Domain Path:       /languages
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Constants ──────────────────────────────────────────────────────────────────
define( 'NGT3D_VERSION',      '1.1.0' );
define( 'NGT3D_PLUGIN_FILE',  __FILE__ );
define( 'NGT3D_PLUGIN_DIR',   plugin_dir_path( __FILE__ ) );
define( 'NGT3D_PLUGIN_URL',   plugin_dir_url( __FILE__ ) );
define( 'NGT3D_PLUGIN_BASE',  plugin_basename( __FILE__ ) );
define( 'NGT3D_SCHEMA_VERSION', 1 );

// ── Autoloader ─────────────────────────────────────────────────────────────────
spl_autoload_register( function ( string $class ): void {
	if ( 0 !== strpos( $class, 'NGT3D_' ) ) {
		return;
	}

	// NGT3D_Rule_Repository → class-ngt3d-rule-repository.php
	$file = 'class-' . strtolower( str_replace( [ 'NGT3D_', '_' ], [ 'ngt3d-', '-' ], $class ) ) . '.php';

	$dirs = [
		NGT3D_PLUGIN_DIR . 'includes/',
		NGT3D_PLUGIN_DIR . 'admin/',
	];

	foreach ( $dirs as $dir ) {
		$path = $dir . $file;
		if ( file_exists( $path ) ) {
			require_once $path;
			return;
		}
	}
} );

// ── Activation / Deactivation ──────────────────────────────────────────────────
register_activation_hook( __FILE__, function (): void {
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-schema.php';
	NGT3D_Schema::install();

	// Seed default front-page rules (runs only when table is empty).
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-rule-repository.php';
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-animation-registry.php';
	NGT3D_Rule_Repository::seed_defaults();

	// Flush rewrite rules to be safe.
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function (): void {
	flush_rewrite_rules();
} );

// ── Boot ───────────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', function (): void {
	// Load text domain.
	load_plugin_textdomain( 'ngt-3d-scroll', false, NGT3D_PLUGIN_DIR . 'languages/' );

	// Core includes (order matters).
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-schema.php';
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-animation-registry.php';
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-validator.php';
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-rule-repository.php';
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-page-resolver.php';
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-dependency-resolver.php';
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-runtime-config.php';
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-asset-loader.php';
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-diagnostics.php';
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-rest-api.php';
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-demo-page.php';
	require_once NGT3D_PLUGIN_DIR . 'includes/class-ngt3d-home-3d-page.php';

	// Admin.
	if ( is_admin() ) {
		require_once NGT3D_PLUGIN_DIR . 'admin/class-ngt3d-admin.php';
		NGT3D_Admin::boot();
	}

	// Schema upgrade check (cheap option read).
	NGT3D_Schema::maybe_upgrade();

	// One-time homepage showcase map migration (idempotent).
	NGT3D_Rule_Repository::migrate_showcase_home_map();

	// Demo lab page + rules.
	NGT3D_Demo_Page::boot();

	// Kinetic home copy with curated 3D presets.
	NGT3D_Home_3d_Page::boot();

	// Enqueue frontend assets.
	NGT3D_Asset_Loader::boot();

	// REST API.
	NGT3D_Rest_Api::boot();
}, 5 );
