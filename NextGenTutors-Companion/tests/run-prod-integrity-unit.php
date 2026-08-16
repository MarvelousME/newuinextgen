<?php
/**
 * Demo-env + Hub authority characterization (no WP DB required).
 *
 * Usage: php NextGenTutors-Companion/tests/run-prod-integrity-unit.php
 */

define( 'ABSPATH', __DIR__ . '/' );

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value ) { // phpcs:ignore
		$args = func_get_args();
		return $args[1];
	}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $k, $d = false ) { // phpcs:ignore
		return $d;
	}
}
if ( ! function_exists( 'home_url' ) ) {
	function home_url() { // phpcs:ignore
		return 'https://example.invalid';
	}
}
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) { // phpcs:ignore
		$p = parse_url( $url );
		if ( -1 === $component ) {
			return $p;
		}
		$map = [
			PHP_URL_HOST => 'host',
		];
		$key = $map[ $component ] ?? null;
		return $key ? ( $p[ $key ] ?? null ) : null;
	}
}

require_once dirname( __DIR__ ) . '/includes/demo/class-ngc-demo-env.php';

$failed = 0;
function expect( $name, $ok, $detail = '' ) {
	global $failed;
	echo ( $ok ? 'PASS' : 'FAIL' ) . " $name" . ( $detail ? " — $detail" : '' ) . PHP_EOL;
	if ( ! $ok ) {
		++$failed;
	}
}

$GLOBALS['ngc_test_environment'] = 'production';
expect( 'seed_denied_in_production', false === NGC_Demo_Env::seed_allowed(), 'production must deny seed' );

$GLOBALS['ngc_test_environment'] = 'local';
putenv( 'NGC_ALLOW_DEMO_SEED=0' );
expect( 'seed_denied_when_env_zero', false === NGC_Demo_Env::seed_allowed(), 'env=0 must deny even on local' );

putenv( 'NGC_ALLOW_DEMO_SEED=1' );
if ( ! defined( 'NGC_ALLOW_DEMO_SEED' ) ) {
	define( 'NGC_ALLOW_DEMO_SEED', true );
}
expect( 'seed_allowed_local_with_flag', true === NGC_Demo_Env::seed_allowed(), 'local + flag allows seed' );

$hub = dirname( __DIR__, 2 ) . '/nextgen-automation-hub/includes/class-ngt-hub-companion-delegate.php';
expect( 'hub_delegate_file', is_readable( $hub ), $hub );
$src = is_readable( $hub ) ? file_get_contents( $hub ) : '';
expect( 'hub_domain_writes_blocked_method', false !== strpos( (string) $src, 'domain_writes_blocked' ), 'method present' );

$match = dirname( __DIR__, 2 ) . '/nextgen-automation-hub/includes/class-ngt-hub-matching.php';
$msrc  = is_readable( $match ) ? file_get_contents( $match ) : '';
expect( 'hub_match_blocks_companion_authority', false !== strpos( (string) $msrc, 'ngt_match_companion_authority' ), 'error code present' );

exit( $failed > 0 ? 1 : 0 );
