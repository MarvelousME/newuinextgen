<?php
/**
 * Amelia E2E — plugin active, bootstrap, tutor employee sync.
 *
 * Usage (Docker):
 *   wp eval-file wp-content/plugins/NextGenTutors-Companion/scripts/amelia-e2e-docker.php --allow-root
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run inside WordPress.\n" );
	exit( 1 );
}

require_once dirname( __DIR__ ) . '/scripts/ngc-e2e-guard.php';
ngc_e2e_require_demo_stack( 'NextGen Amelia E2E' );

$errors = 0;
$checks = [];

/**
 * @param string $name   Check name.
 * @param bool   $ok     Pass.
 * @param string $detail Detail.
 */
function ngc_am_assert( $name, $ok, $detail = '' ) {
	global $checks, $errors;
	$checks[] = [ 'name' => $name, 'ok' => $ok, 'detail' => $detail ];
	if ( ! $ok ) {
		++$errors;
	}
}

$amelia_active = defined( 'AMELIA_VERSION' ) || class_exists( '\AmeliaBooking\Plugin' );
ngc_am_assert( 'amelia_plugin', $amelia_active, $amelia_active ? AMELIA_VERSION ?? 'active' : 'not installed' );

if ( ! $amelia_active ) {
	echo "NextGen Amelia E2E\nFAIL — Amelia plugin not active\n";
	exit( 1 );
}

ngc_am_assert( 'amelia_bootstrap_class', class_exists( 'NGC_Amelia_Bootstrap' ), '' );
ngc_am_assert( 'amelia_adapter_class', class_exists( 'NGC_Amelia_Adapter' ), '' );

$bootstrap = class_exists( 'NGC_Amelia_Bootstrap' ) ? NGC_Amelia_Bootstrap::bootstrap( true ) : [ 'ok' => false ];
ngc_am_assert( 'amelia_tables', ! empty( $bootstrap['tables']['ok'] ), $bootstrap['tables']['status'] ?? '' );
ngc_am_assert( 'amelia_service', ! empty( $bootstrap['service_id'] ), (string) ( $bootstrap['service_id'] ?? 0 ) );
ngc_am_assert( 'amelia_api_mode', ! empty( $bootstrap['api']['ok'] ), $bootstrap['api']['status'] ?? '' );

$adapter = new NGC_Amelia_Adapter();
$verify  = $adapter->verify();
ngc_am_assert( 'amelia_verify', ! empty( $verify['ok'] ), $verify['status'] ?? '' );

$test_email = 'amelia.e2e.tutor@test.local';
$test_user  = get_user_by( 'email', $test_email );

if ( ! $test_user ) {
	$user_id = wp_insert_user(
		[
			'user_login'   => 'amelia_e2e_tutor',
			'user_email'   => $test_email,
			'user_pass'    => wp_generate_password( 16, true ),
			'first_name'   => 'Amelia',
			'last_name'    => 'E2E',
			'display_name' => 'Amelia E2E Tutor',
			'role'         => 'tutor',
		]
	);
	ngc_am_assert( 'test_user_created', ! is_wp_error( $user_id ) && $user_id > 0, is_wp_error( $user_id ) ? $user_id->get_error_message() : (string) $user_id );
} else {
	$user_id = (int) $test_user->ID;
	delete_user_meta( $user_id, 'ngc_amelia_employee_id' );
	ngc_am_assert( 'test_user_ready', true, (string) $user_id );
}

if ( ! empty( $user_id ) && ! is_wp_error( $user_id ) ) {
	$payload = [
		'user_id'    => (int) $user_id,
		'email'      => $test_email,
		'first_name' => 'Amelia',
		'last_name'  => 'E2E',
		'phone'      => '+27123456789',
		'subjects'   => 'Mathematics',
		'bio'        => 'E2E tutor for Amelia sync.',
	];

	$result = $adapter->create_or_update( 'create_employee', $payload );
	ngc_am_assert( 'employee_sync', ! empty( $result['ok'] ), $result['message'] ?? (string) ( $result['id'] ?? '' ) );

	$employee_id = (int) get_user_meta( $user_id, 'ngc_amelia_employee_id', true );
	ngc_am_assert( 'employee_meta', $employee_id > 0, (string) $employee_id );

	if ( $employee_id > 0 && class_exists( 'NGC_Amelia_Bootstrap' ) && NGC_Amelia_Bootstrap::table_exists( 'amelia_users' ) ) {
		global $wpdb;
		$table = $wpdb->prefix . 'amelia_users';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT id, type, email FROM {$table} WHERE id = %d", $employee_id ), ARRAY_A );
		ngc_am_assert( 'amelia_user_row', ! empty( $row ) && 'provider' === ( $row['type'] ?? '' ), $row['email'] ?? 'missing' );
	}
}

echo "NextGen Amelia E2E\n";
echo str_repeat( '-', 44 ) . "\n";
foreach ( $checks as $c ) {
	echo ( $c['ok'] ? 'OK  ' : 'FAIL' ) . ' ' . $c['name'];
	if ( $c['detail'] ) {
		echo ' — ' . $c['detail'];
	}
	echo "\n";
}
echo str_repeat( '-', 44 ) . "\n";
echo $errors ? "FAILED with {$errors} error(s)\n" : "OK — Amelia E2E passed\n";
exit( $errors ? 1 : 0 );
