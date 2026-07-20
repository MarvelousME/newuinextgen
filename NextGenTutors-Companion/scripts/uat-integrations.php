<?php
/**
 * Amelia / PayFast integration UAT smoke checks (Docker-friendly).
 *
 * Usage:
 *   docker exec nextgentutors-wordpress-1 php /var/www/html/wp-content/plugins/NextGenTutors-Companion/scripts/uat-integrations.php
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run inside WordPress (wp eval-file).\n" );
	exit( 1 );
}

$errors = 0;
$checks = [];

/**
 * @param string $name   Check name.
 * @param bool   $ok     Pass/fail.
 * @param string $detail Detail.
 */
function ngc_uat_assert( $name, $ok, $detail = '' ) {
	global $checks, $errors;
	$checks[] = [ 'name' => $name, 'ok' => $ok, 'detail' => $detail ];
	if ( ! $ok ) {
		++$errors;
	}
}

// Amelia adapter / booking bridge
ngc_uat_assert( 'amelia_class', class_exists( 'NGC_Amelia' ), '' );
ngc_uat_assert( 'amelia_sync', method_exists( 'NGC_Bookings', 'sync_from_amelia' ), '' );
ngc_uat_assert( 'amelia_column', (bool) $GLOBALS['wpdb']->get_results(
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	"SHOW COLUMNS FROM " . NGC_Database::table( 'bookings' ) . " LIKE 'amelia_booking_id'"
), '' );

$amelia_active = is_plugin_active( 'ameliabooking/ameliabooking.php' )
	|| class_exists( 'AmeliaBooking\\Plugin', false )
	|| defined( 'AMELIA_VERSION' );
ngc_uat_assert( 'amelia_plugin', $amelia_active, $amelia_active ? 'detected' : 'not installed — UAT partial' );

// PayFast / WooCommerce payout path
ngc_uat_assert( 'payout_export', class_exists( 'NGC_Payout_Export' ), '' );
ngc_uat_assert( 'payout_scheduler', class_exists( 'NGC_Payout_Scheduler' ), '' );
ngc_uat_assert( 'biweekly_cron_hook', class_exists( 'NGC_Payout_Scheduler' ) && NGC_Payout_Scheduler::CRON_HOOK_BIWEEKLY === 'ngc_biweekly_payout_batch', '' );

$payfast_active = is_plugin_active( 'woocommerce-payfast-gateway/woocommerce-payfast-gateway.php' )
	|| is_plugin_active( 'payfast/payfast.php' )
	|| is_plugin_active( 'paygate-payweb-for-woocommerce/paygate-payweb-for-woocommerce.php' )
	|| class_exists( 'WC_Gateway_PayFast', false );
ngc_uat_assert( 'payfast_gateway', $payfast_active, $payfast_active ? 'detected' : 'not installed — UAT partial' );

$wc_active = class_exists( 'WooCommerce' );
ngc_uat_assert( 'woocommerce', $wc_active, $wc_active ? 'active' : 'optional' );

if ( $wc_active && class_exists( 'NGC_WooCommerce_Catalog' ) ) {
	ngc_uat_assert( 'wc_catalog_csv', file_exists( NGC_WooCommerce_Catalog::csv_path() ), NGC_WooCommerce_Catalog::csv_path() );
}

echo "NextGen Companion — Integration UAT\n";
echo str_repeat( '-', 40 ) . "\n";
foreach ( $checks as $c ) {
	echo ( $c['ok'] ? 'OK  ' : 'WARN' ) . ' ' . $c['name'];
	if ( $c['detail'] ) {
		echo ' — ' . $c['detail'];
	}
	echo "\n";
}
echo str_repeat( '-', 40 ) . "\n";

// Soft-fail when third-party plugins absent; hard-fail on companion gaps.
$hard = array_filter(
	$checks,
	static function ( $c ) {
		return ! $c['ok'] && ! in_array( $c['name'], [ 'amelia_plugin', 'payfast_gateway', 'woocommerce' ], true );
	}
);

if ( $hard ) {
	echo 'FAILED — companion integration gaps: ' . count( $hard ) . "\n";
	exit( 1 );
}

echo "OK — UAT passed (third-party plugins may be absent in dev)\n";
exit( 0 );
