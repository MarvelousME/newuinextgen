<?php
/**
 * NGCPM unit tests (run outside WordPress).
 *
 * Usage: php tests/run.php
 *
 * @package NextGenCorePluginManager
 */

$root = dirname( __DIR__ );
require_once $root . '/tests/bootstrap.php';

$failures = 0;

function ngcpm_assert_true( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		echo "FAIL: {$message}\n";
		++$failures;
	}
}

function ngcpm_assert_equals( $expected, $actual, $message ) {
	global $failures;
	if ( $expected !== $actual ) {
		echo "FAIL: {$message} (expected " . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . ")\n";
		++$failures;
	}
}

echo "NGCPM unit tests\n";

// Queue plan actions.
$stub = ngcpm_stub_scan();
$stub['woocommerce'] = array_merge( $stub['woocommerce'], [
	'installed'        => false,
	'active'           => false,
	'can_auto_install' => true,
	'health_status'    => 'MISSING',
] );
$stub['elementor'] = array_merge( $stub['elementor'], [
	'installed'        => true,
	'active'           => false,
	'can_auto_install' => true,
	'health_status'    => 'INACTIVE',
] );
$stub['ameliabooking'] = array_merge( $stub['ameliabooking'], [
	'installed'        => false,
	'active'           => false,
	'can_auto_install' => false,
	'health_status'    => 'MANUAL_REQUIRED',
] );

$plan = NGCPM_Queue::build_plan( $stub );
ngcpm_assert_equals( 3, count( $plan ), 'queue plan item count' );
$actions = array_column( $plan, 'action' );
ngcpm_assert_true( in_array( 'install', $actions, true ), 'plan includes install' );
ngcpm_assert_true( in_array( 'activate', $actions, true ), 'plan includes activate' );
ngcpm_assert_true( in_array( 'manual', $actions, true ), 'plan includes manual' );

// Repair detection (requires can_auto_install on scan rows, as scanner provides).
$issues = NGCPM_Repair::detect_issues( $stub );
ngcpm_assert_equals( 2, count( $issues ), 'repair detects install + activate issues' );
$has_install = false;
$has_activate = false;
foreach ( $issues as $issue ) {
	if ( 'install' === ( $issue['strategy'] ?? '' ) ) {
		$has_install = true;
	}
	if ( 'activate' === ( $issue['strategy'] ?? '' ) ) {
		$has_activate = true;
	}
}
ngcpm_assert_true( $has_install, 'repair includes install strategy' );
ngcpm_assert_true( $has_activate, 'repair includes activate strategy' );

// Logger context sanitization.
NGCPM_Logger::log( 'test', 'msg', [ 'slug' => 'woocommerce', 'bad<script>' => 'x' ] );
$recent = NGCPM_Logger::recent( 1 );
ngcpm_assert_true( ! empty( $recent[0]['context']['slug'] ), 'logger stores slug' );
ngcpm_assert_equals( 'woocommerce', $recent[0]['context']['slug'], 'logger sanitizes slug value' );

// AJAX source assertions.
$ajax = file_get_contents( $root . '/includes/class-ngcpm-ajax.php' );
ngcpm_assert_true( false !== strpos( $ajax, 'reject_legacy_batch' ), 'legacy batch blocked' );
ngcpm_assert_true( false !== strpos( $ajax, "'activate' === \$strategy" ), 'repair capability branch' );

$view_model = file_get_contents( $root . '/includes/class-ngcpm-view-model.php' );
ngcpm_assert_true( false !== strpos( $view_model, 'for_app' ), 'view model exists' );

// Registry dependency edges.
$edges = NGCPM_Registry::dependency_edges();
$edge_pairs = array_map(
	static function ( $edge ) {
		return ( $edge['from'] ?? '' ) . '->' . ( $edge['to'] ?? '' );
	},
	$edges
);
ngcpm_assert_true( in_array( 'woocommerce->payfast-payment-gateway', $edge_pairs, true ), 'payfast depends on woocommerce' );
ngcpm_assert_true( in_array( 'elementor->ultimate-elementor', $edge_pairs, true ), 'ultimate elementor depends on elementor' );
ngcpm_assert_true( in_array( 'core', NGCPM_Registry::pipeline_slugs(), true ), 'pipeline includes core' );

$js_bundle = '';
$js_paths = [
	$root . '/assets/js/admin-ui.js',
	$root . '/assets/js/modules/ngcpm-core.js',
	$root . '/assets/js/modules/ngcpm-queue.js',
];
foreach ( $js_paths as $js_path ) {
	if ( is_file( $js_path ) ) {
		$js_bundle .= file_get_contents( $js_path );
	}
}
ngcpm_assert_true( false !== strpos( $js_bundle, 'window.NGCPM_UI' ), 'JS uses NGCPM_UI namespace' );
ngcpm_assert_true( false !== strpos( $js_bundle, 'ngcpm_queue_plan' ), 'JS uses queue plan endpoint' );

$installer_src = file_get_contents( $root . '/includes/class-ngcpm-installer.php' );
ngcpm_assert_true(
	false !== strpos( $installer_src, 'downloads.wordpress.org/plugin/' ) && false !== strpos( $installer_src, '.latest-stable.zip' ),
	'installer defines direct wp.org zip fallback'
);

ngcpm_assert_true( false !== strpos( $ajax, 'handle_cookie_probe' ), 'cookie probe handler exists' );
ngcpm_assert_true( false !== strpos( $ajax, 'handle_dismiss_notification' ), 'dismiss notification handler exists' );

$cookies_src = file_get_contents( $root . '/includes/class-ngcpm-cookies.php' );
ngcpm_assert_true( false !== strpos( $cookies_src, 'COOKIE_SYSTEM_AVAILABLE' ), 'cookie system check id' );
ngcpm_assert_true( false !== strpos( $cookies_src, 'NOT_CONFIGURED' ) || false !== strpos( $cookies_src, 'check_tracking_cookie' ), 'admin tracking not fail' );

$buttons_src = file_get_contents( $root . '/includes/class-ngcpm-buttons.php' );
ngcpm_assert_true( false !== strpos( $buttons_src, 'registry' ), 'button registry exists' );

if ( $failures > 0 ) {
	echo "\n{$failures} failure(s)\n";
	exit( 1 );
}

echo "OK - all unit tests passed\n";
exit( 0 );
