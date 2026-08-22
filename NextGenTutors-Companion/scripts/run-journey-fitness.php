<?php
/**
 * Standalone fitness runner (no WP bootstrap).
 *
 * Usage: php NextGenTutors-Companion/scripts/run-journey-fitness.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'NGC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value ) { // phpcs:ignore
		$args = func_get_args();
		return $args[1];
	}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $key, $default = false ) { // phpcs:ignore
		return $default;
	}
}
if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 604800 );
}

require_once NGC_PLUGIN_DIR . 'includes/journeys/class-ngc-journey-fitness-tests.php';
require_once NGC_PLUGIN_DIR . 'includes/journeys/class-ngc-journey-dual-fire-guard.php';
require_once NGC_PLUGIN_DIR . 'includes/journeys/class-ngc-business-rules.php';
require_once NGC_PLUGIN_DIR . 'includes/journeys/class-ngc-payout-business-rules.php';
require_once NGC_PLUGIN_DIR . 'includes/journeys/class-ngc-first-booking-rule.php';
require_once NGC_PLUGIN_DIR . 'includes/journeys/class-ngc-tutor-listing-eligibility.php';
require_once NGC_PLUGIN_DIR . 'includes/gamification/class-ngc-scoring-engine.php';
require_once NGC_PLUGIN_DIR . 'includes/gamification/class-ngc-gamification-milestones.php';

if ( ! class_exists( 'NGC_Workflow_Authority' ) ) {
	class NGC_Workflow_Authority {} // phpcs:ignore
}
if ( ! class_exists( 'NGC_Platform' ) ) {
	class NGC_Platform {} // phpcs:ignore
}

$failed = 0;
foreach ( NGC_Journey_Fitness_Tests::run_all() as $t ) {
	$line = ( $t['ok'] ? 'PASS' : 'FAIL' ) . ' ' . $t['name'] . ' — ' . $t['detail'];
	echo $line . PHP_EOL;
	if ( ! $t['ok'] ) {
		++$failed;
	}
}
exit( $failed > 0 ? 1 : 0 );
