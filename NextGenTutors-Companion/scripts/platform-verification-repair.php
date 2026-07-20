<?php
/**
 * Repair local stack gaps and print NGC_Verification results (Docker / WP-CLI).
 *
 * Usage:
 *   docker compose --profile setup run --rm --entrypoint wp wpcli eval-file \
 *     wp-content/plugins/NextGenTutors-Companion/scripts/platform-verification-repair.php --allow-root
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( class_exists( 'NGC_Core_Loader' ) ) {
	NGC_Core_Loader::repair_local_stack();
}

if ( class_exists( 'NGC_Integrations_Bootstrap' ) ) {
	NGC_Integrations_Bootstrap::configure_local_stack( true );
}

if ( class_exists( 'NGC_Tutor_Cpt_Source' ) ) {
	NGC_Tutor_Cpt_Source::ensure_showcase_tutor();
}

if ( class_exists( 'NGC_Platform_Tracking' ) ) {
	NGC_Platform_Tracking::ensure_demo_attribution();
	NGC_Platform_Tracking::seed_local_consent_bootstrap();
}

if ( class_exists( 'NGC_Amelia_Bootstrap' ) ) {
	if ( ! NGC_Amelia_Bootstrap::is_active() ) {
		NGC_Amelia_Bootstrap::safe_install_and_activate();
	}
	NGC_Amelia_Bootstrap::bootstrap( false );
	NGC_Amelia_Bootstrap::ensure_api_key();
}

if ( ! class_exists( 'NGC_Verification' ) ) {
	WP_CLI::error( 'NGC_Verification not loaded.' );
}

$checks = NGC_Verification::run_checks();
$skip   = [ 'ok', 'version', 'tutor_counts' ];

WP_CLI::line( '=== NextGen Platform Verification ===' );
foreach ( $checks as $key => $item ) {
	if ( in_array( $key, $skip, true ) ) {
		continue;
	}
	if ( ! is_array( $item ) ) {
		continue;
	}
	$status  = $item['status'] ?? 'UNKNOWN';
	$message = $item['message'] ?? '';
	WP_CLI::line( sprintf( '%-28s %s — %s', $key, $status, $message ) );
}

if ( ! empty( $checks['tutor_counts'] ) ) {
	WP_CLI::line( '' );
	WP_CLI::line( 'Tutor counts: ' . wp_json_encode( $checks['tutor_counts'] ) );
}

if ( function_exists( 'bi_get_live_tutors' ) ) {
	$live = bi_get_live_tutors( 1 );
	WP_CLI::line( 'Theme live CPT helper: ' . ( ! empty( $live ) ? 'READY' : 'NOT READY' ) );
}

WP_CLI::success( 'Aggregate ok: ' . ( ! empty( $checks['ok'] ) ? 'true' : 'false' ) );
