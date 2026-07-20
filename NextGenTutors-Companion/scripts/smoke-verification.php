<?php
/**
 * Verification aggregate smoke test (no WordPress required).
 *
 * Usage: php scripts/smoke-verification.php
 *
 * @package NextGenCompanion
 */

$root = dirname( __DIR__ );

// Minimal stub of aggregate logic mirrored from NGC_Verification.
function ngc_smoke_aggregate_ok( $checks ) {
	$exclude = [ 'ok', 'version', 'tutor_counts' ];
	foreach ( $checks as $key => $check ) {
		if ( in_array( $key, $exclude, true ) ) {
			continue;
		}
		if ( is_array( $check ) ) {
			$required = ! isset( $check['required'] ) || $check['required'];
			if ( $required && 'FAIL' === ( $check['status'] ?? '' ) ) {
				return false;
			}
		} elseif ( false === $check ) {
			return false;
		}
	}
	return true;
}

$errors = 0;

// All PASS required checks => ok true.
$all_pass = [
	'tables' => [ 'status' => 'PASS', 'required' => true ],
	'cookies' => [ 'status' => 'NOT_VERIFIED', 'required' => false ],
];
$ok = ngc_smoke_aggregate_ok( $all_pass );
if ( ! $ok ) {
	echo "FAIL: all PASS checks should produce ok=true\n";
	++$errors;
}

// One FAIL required => ok false.
$one_fail = [
	'tables' => [ 'status' => 'PASS', 'required' => true ],
	'roles'  => [ 'status' => 'FAIL', 'required' => true ],
];
$ok = ngc_smoke_aggregate_ok( $one_fail );
if ( $ok ) {
	echo "FAIL: one FAIL required check should produce ok=false\n";
	++$errors;
}

// WARNING only => ok true.
$warning_only = [
	'tables' => [ 'status' => 'WARNING', 'required' => false ],
];
$ok = ngc_smoke_aggregate_ok( $warning_only );
if ( ! $ok ) {
	echo "FAIL: WARNING-only checks should not fail aggregate\n";
	++$errors;
}

// Missing ok key simulation — isset guard.
$checks_without_ok = [ 'tables' => [ 'status' => 'PASS', 'required' => true ] ];
$checks_without_ok['ok'] = ngc_smoke_aggregate_ok( $checks_without_ok );
if ( ! isset( $checks_without_ok['ok'] ) ) {
	echo "FAIL: ok key must always be set\n";
	++$errors;
}

// Duplicate-init guard in widget JS.
$widget_js = file_get_contents( $root . '/assets/js/ngc-match-widget.js' );
if ( false === strpos( $widget_js, 'NGCMatchWidgetInitialized' ) ) {
	echo "FAIL: ngc-match-widget.js missing init guard\n";
	++$errors;
}
if ( false !== strpos( $widget_js, "match-dock-btn').addEventListener('click'" ) || false !== strpos( $widget_js, 'match-dock-btn' ) && false !== strpos( $widget_js, 'togglePanel' ) ) {
	if ( preg_match( '/match-dock-btn.*togglePanel/s', $widget_js ) ) {
		echo "FAIL: ngc-match-widget.js must not toggle on #match-dock-btn\n";
		++$errors;
	}
}

// Bridge dispatches event only.
$bridge_js = @file_get_contents( dirname( $root ) . '/beyondinfinity/assets/js/ngt-wp-bridge.js' );
if ( $bridge_js && false === strpos( $bridge_js, 'ngc:open-match-widget' ) ) {
	echo "FAIL: ngt-wp-bridge.js must dispatch ngc:open-match-widget\n";
	++$errors;
}

// run_checks sets ok in PHP source.
$verification = file_get_contents( $root . '/includes/class-ngc-verification.php' );
if ( false === strpos( $verification, "\$checks['ok']" ) ) {
	echo "FAIL: class-ngc-verification.php must set \$checks['ok']\n";
	++$errors;
}

// Marketplace shortcodes registered.
$marketplace = file_get_contents( $root . '/includes/class-ngc-marketplace.php' );
if ( false === strpos( $marketplace, 'ngc_tutor_carousel' ) ) {
	echo "FAIL: NGC_Marketplace must register ngc_tutor_carousel\n";
	++$errors;
}
if ( false === strpos( $marketplace, 'ngc_tutor_marketplace' ) ) {
	echo "FAIL: NGC_Marketplace must register ngc_tutor_marketplace\n";
	++$errors;
}
if ( false === strpos( $marketplace, 'query_tutors' ) ) {
	echo "FAIL: NGC_Marketplace must implement query_tutors\n";
	++$errors;
}

// PageFormsRegistry wired.
$bootstrap = file_get_contents( $root . '/includes/class-ngc-plugin.php' );
if ( false === strpos( $bootstrap, 'NGC_Page_Forms_Registry' ) ) {
	echo "FAIL: bootstrap must load NGC_Page_Forms_Registry\n";
	++$errors;
}
$rest = file_get_contents( $root . '/includes/rest/class-ngc-rest.php' );
if ( false === strpos( $rest, 'NGC_Rest_Page_Forms_Registry' ) ) {
	echo "FAIL: NGC_Rest must register Page_Forms_Registry routes\n";
	++$errors;
}
if ( false === strpos( $rest, 'NGC_Rest_Marketplace' ) ) {
	echo "FAIL: NGC_Rest must register Marketplace routes\n";
	++$errors;
}

// Dashboard analytics charts in REST.
$dash_rest = file_get_contents( $root . '/includes/rest/class-ngc-rest-dashboard.php' );
if ( false === strpos( $dash_rest, 'NGC_Dashboard_Analytics' ) ) {
	echo "FAIL: dashboard REST must include NGC_Dashboard_Analytics charts\n";
	++$errors;
}

// Rate limiter on AJAX.
$matching = file_get_contents( $root . '/includes/matching/class-ngc-smart-matching.php' );
if ( false === strpos( $matching, 'NGC_Rate_Limiter::check' ) ) {
	echo "FAIL: ajax_match must use NGC_Rate_Limiter\n";
	++$errors;
}

// AI suite ported from unified companion.
$ai_files = [
	'includes/ai/class-ngc-ai-models.php',
	'includes/ai/class-ngc-ai-agents.php',
	'includes/rest/class-ngc-rest-ai.php',
	'assets/js/ngc-ai.js',
];
foreach ( $ai_files as $rel ) {
	if ( ! file_exists( $root . '/' . $rel ) ) {
		echo "FAIL: missing AI suite file {$rel}\n";
		++$errors;
	}
}
$ai_js = file_get_contents( $root . '/assets/js/ngc-ai.js' );
if ( false === strpos( $ai_js, '/ai/models' ) || false === strpos( $ai_js, '/ai/agents/delete' ) ) {
	echo "FAIL: ngc-ai.js must call model and agent REST endpoints\n";
	++$errors;
}

echo $errors ? "FAILED with {$errors} error(s)\n" : "OK — verification smoke tests passed\n";
exit( $errors ? 1 : 0 );
