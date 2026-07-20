<?php
/**
 * Docker E2E smoke runner for NextGen Companion.
 *
 * Usage (from host):
 *   docker exec nextgentutors-wordpress-1 php /var/www/html/wp-content/plugins/NextGenTutors-Companion/scripts/e2e-docker.php --allow-root
 *
 * Or via wp-cli:
 *   docker exec nextgentutors-wordpress-1 php /var/www/html/wp-cli.phar eval-file \
 *     /var/www/html/wp-content/plugins/NextGenTutors-Companion/scripts/e2e-docker.php --allow-root
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run inside WordPress (wp eval-file or wp-cli).\n" );
	exit( 1 );
}

$errors = 0;
$checks = [];

/**
 * @param string $name   Check name.
 * @param bool   $ok     Result.
 * @param string $detail Detail.
 */
function ngc_e2e_assert( $name, $ok, $detail = '' ) {
	global $checks, $errors;
	$checks[] = [ 'name' => $name, 'ok' => $ok, 'detail' => $detail ];
	if ( ! $ok ) {
		++$errors;
	}
}

// 1. Tables + verification
NGC_Database::create_tables();
$verify = NGC_Studio_Verification::run();
ngc_e2e_assert( 'studio_verification', ! empty( $verify['ok'] ), wp_json_encode( $verify ) );

// 2. POPIA consent helpers
ngc_e2e_assert( 'consent_granted_method', method_exists( 'NGC_Platform_Tracking', 'consent_granted' ), '' );
ngc_e2e_assert( 'marketing_capture_method', method_exists( 'NGC_Platform_Tracking', 'marketing_capture_allowed' ), '' );

// 3. Amelia booking sync API
ngc_e2e_assert( 'amelia_sync_method', method_exists( 'NGC_Bookings', 'sync_from_amelia' ), '' );
ngc_e2e_assert( 'amelia_lookup_method', method_exists( 'NGC_Bookings', 'get_by_amelia_id' ), '' );

// 4. REST routes registered
$routes = rest_get_server()->get_routes();
ngc_e2e_assert( 'rest_wallet', isset( $routes['/ngc/v1/wallet'] ), '' );
ngc_e2e_assert( 'rest_invoices', isset( $routes['/ngc/v1/invoices'] ), '' );
ngc_e2e_assert( 'rest_matches_list', isset( $routes['/ngc/v1/matches'] ), '' );
ngc_e2e_assert( 'rest_matches_get', isset( $routes['/ngc/v1/matches/(?P<id>\\d+)'] ), '' );

// 5. Integrate runtime
$integrate = NGC_Integrate_Runtime::status();
ngc_e2e_assert( 'integrate_specs', ! empty( $integrate['ok'] ), wp_json_encode( $integrate ) );

// 6. Studio simulate (dry path)
$workflows = NGC_Studio_Repository::list_workflows();
if ( $workflows ) {
	$wf     = $workflows[0];
	$result = NGC_Studio_Simulator::dry_run( $wf, [] );
	ngc_e2e_assert( 'studio_simulate', ! empty( $result['ok'] ) && ! empty( $result['path'] ), (string) ( $result['message'] ?? '' ) );
} else {
	ngc_e2e_assert( 'studio_simulate', true, 'skipped — no workflows' );
}

// 7. Reminder idempotency schema
global $wpdb;
$reminder_table = NGC_Database::table( 'reminder_schedules' );
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$indexes = $wpdb->get_results( "SHOW INDEX FROM {$reminder_table} WHERE Key_name = 'booking_reminder'" );
ngc_e2e_assert( 'reminder_unique_index', ! empty( $indexes ), 'booking_reminder unique key' );

// 8. Amelia column on bookings
$bookings_table = NGC_Database::table( 'bookings' );
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$cols = $wpdb->get_results( "SHOW COLUMNS FROM {$bookings_table} LIKE 'amelia_booking_id'" );
ngc_e2e_assert( 'bookings_amelia_column', ! empty( $cols ), '' );
ngc_e2e_assert( 'payout_export_class', class_exists( 'NGC_Payout_Export' ), '' );
ngc_e2e_assert( 'payout_create_method', method_exists( 'NGC_Reviews', 'create_payout' ), '' );
ngc_e2e_assert( 'studio_sse_method', method_exists( 'NGC_Studio_Stream', 'render_sse' ), '' );

// 9. Phase 1 — uuid columns, child learners, section CMS, bi-weekly payouts
NGC_Database::ensure_uuid_columns();
$matches_table = NGC_Database::table( 'matches' );
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$uuid_col = $wpdb->get_results( "SHOW COLUMNS FROM {$matches_table} LIKE 'uuid'" );
ngc_e2e_assert( 'uuid_column_matches', ! empty( $uuid_col ), '' );

$child_table = NGC_Database::table( 'child_learners' );
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
ngc_e2e_assert( 'child_learners_table', (bool) $wpdb->get_var( "SHOW TABLES LIKE '{$child_table}'" ), '' );

$sections_table = NGC_Database::table( 'page_sections' );
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
ngc_e2e_assert( 'page_sections_table', (bool) $wpdb->get_var( "SHOW TABLES LIKE '{$sections_table}'" ), '' );

NGC_Section_CMS::install_defaults();
ngc_e2e_assert( 'section_cms_keys', 11 === count( NGC_Section_CMS::section_keys() ), '' );
ngc_e2e_assert( 'section_cms_hero', ! empty( NGC_Section_CMS::get_section( 'home', 'hero' )['title'] ), '' );

ngc_e2e_assert( 'child_learner_role', (bool) get_role( 'child_learner' ), '' );
ngc_e2e_assert( 'biweekly_payout_hook', class_exists( 'NGC_Payout_Scheduler' ) && NGC_Payout_Scheduler::CRON_HOOK_BIWEEKLY === 'ngc_biweekly_payout_batch', '' );
NGC_Payout_Scheduler::ensure_cron();
ngc_e2e_assert( 'biweekly_payout_scheduled', (bool) wp_next_scheduled( NGC_Payout_Scheduler::CRON_HOOK_BIWEEKLY ), '' );

$routes = rest_get_server()->get_routes();
ngc_e2e_assert( 'rest_sections', isset( $routes['/ngc/v1/sections/home'] ), '' );

echo "NextGen Companion — Docker E2E\n";
echo str_repeat( '-', 40 ) . "\n";
foreach ( $checks as $c ) {
	echo ( $c['ok'] ? 'OK  ' : 'FAIL' ) . ' ' . $c['name'];
	if ( $c['detail'] ) {
		echo ' — ' . $c['detail'];
	}
	echo "\n";
}
echo str_repeat( '-', 40 ) . "\n";
echo $errors ? "FAILED with {$errors} error(s)\n" : "OK — E2E checks passed\n";
exit( $errors ? 1 : 0 );
