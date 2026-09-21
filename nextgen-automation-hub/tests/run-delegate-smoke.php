<?php
/**
 * Smoke: Hub quiet-domain deferral when Companion (NGC_Plugin) is active (TD-RAD-004).
 *
 * Usage: php tests/run-delegate-smoke.php
 *
 * @package NextGenAutomationHub
 */

$root   = dirname( __DIR__ );
$errors = 0;
$passed = 0;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/tests-stub/' );
}

function ngt_hub_test_assert( $label, $ok ) {
	global $errors, $passed;
	if ( ! $ok ) {
		echo "FAIL: {$label}\n";
		++$errors;
	} else {
		echo "OK:   {$label}\n";
		++$passed;
	}
}

if ( ! function_exists( 'rest_url' ) ) {
	function rest_url( $path = '' ) {
		return 'https://example.test/wp-json/' . ltrim( (string) $path, '/' );
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook, ...$args ) {
		unset( $hook, $args );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}

if ( ! class_exists( 'WP_REST_Server', false ) ) {
	class WP_REST_Server {
		const READABLE  = 'GET';
		const CREATABLE = 'POST';
	}
}

$GLOBALS['ngt_hub_registered_routes'] = [];

if ( ! function_exists( 'register_rest_route' ) ) {
	function register_rest_route( $namespace, $route, $args = [], $override = false ) {
		unset( $args, $override );
		$GLOBALS['ngt_hub_registered_routes'][] = rtrim( (string) $namespace, '/' ) . '/' . ltrim( (string) $route, '/' );
		return true;
	}
}

require_once $root . '/includes/class-ngt-hub-companion-delegate.php';

// --- Standalone (no Companion) ---
NGT_Hub_Companion_Delegate::reset_detection_cache();
ngt_hub_test_assert( 'standalone: not quiet domain', ! NGT_Hub_Companion_Delegate::is_quiet_domain() );
ngt_hub_test_assert( 'standalone: may register matching', NGT_Hub_Companion_Delegate::should_register_matching() );
ngt_hub_test_assert( 'standalone: may register finance', NGT_Hub_Companion_Delegate::should_register_finance() );
ngt_hub_test_assert( 'standalone: REST ns ngt/v1', 'ngt/v1' === NGT_Hub_Companion_Delegate::rest_namespace() );

// --- Companion active via NGC_Plugin ---
if ( ! class_exists( 'NGC_Plugin', false ) ) {
	class NGC_Plugin {}
}
NGT_Hub_Companion_Delegate::reset_detection_cache();

ngt_hub_test_assert( 'companion: quiet domain', NGT_Hub_Companion_Delegate::is_quiet_domain() );
ngt_hub_test_assert( 'companion: delegate-only', NGT_Hub_Companion_Delegate::is_delegate_only() );
ngt_hub_test_assert( 'companion: skip matching registration', ! NGT_Hub_Companion_Delegate::should_register_matching() );
ngt_hub_test_assert( 'companion: skip finance registration', ! NGT_Hub_Companion_Delegate::should_register_finance() );
ngt_hub_test_assert( 'companion: REST ns ngt-hub/v1', 'ngt-hub/v1' === NGT_Hub_Companion_Delegate::rest_namespace() );
ngt_hub_test_assert( 'companion: finance CPT includes ngt_payout', in_array( 'ngt_payout', NGT_Hub_Companion_Delegate::finance_post_types(), true ) );

// Minimal stubs so NGT_Hub_REST can load without full Hub bootstrap.
if ( ! defined( 'NGT_HUB_VERSION' ) ) {
	define( 'NGT_HUB_VERSION', '2.0.0-test' );
}
if ( ! defined( 'NGT_HUB_DIR' ) ) {
	define( 'NGT_HUB_DIR', $root . '/' );
}
if ( ! defined( 'NGT_HUB_URL' ) ) {
	define( 'NGT_HUB_URL', 'https://example.test/hub/' );
}
if ( ! defined( 'NGT_HUB_FILE' ) ) {
	define( 'NGT_HUB_FILE', $root . '/nextgen-automation-hub.php' );
}

require_once $root . '/includes/class-ngt-hub-rest.php';

$GLOBALS['ngt_hub_registered_routes'] = [];
NGT_Hub_REST::register_routes();

$routes = $GLOBALS['ngt_hub_registered_routes'];
$has_matches = false;
foreach ( $routes as $r ) {
	if ( false !== strpos( $r, '/matches/pending' ) ) {
		$has_matches = true;
		break;
	}
}

ngt_hub_test_assert( 'companion: matching endpoint NOT registered', ! $has_matches );
ngt_hub_test_assert( 'companion: non-domain routes still register', count( $routes ) >= 5 );

$ns_ok = true;
foreach ( $routes as $r ) {
	if ( 0 !== strpos( $r, 'ngt-hub/v1/' ) ) {
		$ns_ok = false;
		break;
	}
}
ngt_hub_test_assert( 'companion: routes under ngt-hub/v1', $ns_ok && count( $routes ) > 0 );

echo "\n{$passed} passed, {$errors} failed\n";
exit( $errors > 0 ? 1 : 0 );
