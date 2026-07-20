<?php
/**
 * CLI: install registry plugins from local zips and configure NextGenTutors stack.
 *
 * Usage (Docker):
 *   wp eval-file wp-content/plugins/NextGenTutors-Plugin-Manager/scripts/install-registry-zips.php --allow-root
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! class_exists( 'NGCPM_Registry' ) ) {
	fwrite( STDERR, "NextGenTutors-Plugin-Manager is not loaded.\n" );
	exit( 1 );
}

// WP-CLI runs without a logged-in user — elevate for install/activate caps.
if ( function_exists( 'wp_set_current_user' ) ) {
	$admin = get_users( [ 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ] );
	wp_set_current_user( ! empty( $admin[0] ) ? (int) $admin[0] : 1 );
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$out = [
	'installed'  => [],
	'activated'  => [],
	'configured' => [],
];

if ( class_exists( 'NGCPM_Local_Packages' ) ) {
	NGCPM_Local_Packages::mirror_bundled_zips();
	$out['installed'] = NGCPM_Local_Packages::install_pending( false );
}

foreach ( NGCPM_Registry::get_all() as $slug => $def ) {
	if ( empty( $def['required'] ) || empty( $def['main_file'] ) ) {
		continue;
	}
	// Amelia needs schema before activation — handled after the loop.
	if ( 'ameliabooking' === $slug ) {
		continue;
	}
	$file = (string) $def['main_file'];
	if ( is_plugin_active( $file ) ) {
		continue;
	}
	if ( ! file_exists( WP_PLUGIN_DIR . '/' . dirname( $file ) ) ) {
		$r = NGCPM_Installer::install( $slug );
		$out['installed'][] = array_merge( [ 'slug' => $slug ], $r );
	}
	if ( file_exists( WP_PLUGIN_DIR . '/' . $file ) && ! is_plugin_active( $file ) ) {
		$act = activate_plugin( $file, '', false, true );
		$out['activated'][] = [
			'slug'    => $slug,
			'success' => ! is_wp_error( $act ),
			'message' => is_wp_error( $act ) ? $act->get_error_message() : 'activated',
		];
	}
}

// Amelia: install zip, create tables, then activate (prevents fatal on missing wp_amelia_* tables).
$amelia_def = NGCPM_Registry::get( 'ameliabooking' );
if ( $amelia_def && ! empty( $amelia_def['main_file'] ) ) {
	$amelia_file = (string) $amelia_def['main_file'];
	if ( ! file_exists( WP_PLUGIN_DIR . '/' . dirname( $amelia_file ) ) ) {
		$r = NGCPM_Installer::install( 'ameliabooking' );
		$out['installed'][] = array_merge( [ 'slug' => 'ameliabooking' ], $r );
	}
	if ( class_exists( 'NGC_Amelia_Bootstrap' ) ) {
		$out['configured']['amelia'] = NGC_Amelia_Bootstrap::safe_install_and_activate();
	} elseif ( file_exists( WP_PLUGIN_DIR . '/' . $amelia_file ) && ! is_plugin_active( $amelia_file ) ) {
		$act = activate_plugin( $amelia_file, '', false, true );
		$out['activated'][] = [
			'slug'    => 'ameliabooking',
			'success' => ! is_wp_error( $act ),
			'message' => is_wp_error( $act ) ? $act->get_error_message() : 'activated',
		];
	}
}

if ( class_exists( 'NGC_Integrations_Bootstrap' ) ) {
	$out['configured']['integrations'] = NGC_Integrations_Bootstrap::configure_local_stack( true );
}

if ( class_exists( 'NGC_Content_Pack_Bridge' ) ) {
	$out['configured']['content_catalog'] = NGC_Content_Pack_Bridge::import_catalog_specs();
}

if ( class_exists( 'NGC_AutomatorWP_Importer' ) && function_exists( 'ct_get_objects' ) ) {
	$out['configured']['automatorwp'] = NGC_AutomatorWP_Importer::import_from_v2_catalog( false );
}

if ( class_exists( 'NGC_Amelia_Bootstrap' ) && empty( $out['configured']['amelia'] ) ) {
	$out['configured']['amelia'] = NGC_Amelia_Bootstrap::safe_install_and_activate();
}

if ( class_exists( 'NGC_Tutor_Seeder' ) && defined( 'NGC_ALLOW_DEMO_SEED' ) && NGC_ALLOW_DEMO_SEED ) {
	$out['configured']['tutor_seed'] = NGC_Tutor_Seeder::ensure_seeded( true );
}

if ( function_exists( 'bi_sync_launch_pages' ) ) {
	$r = bi_sync_launch_pages();
	$out['configured']['launch_pages'] = is_wp_error( $r ) ? $r->get_error_message() : $r;
}

echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
