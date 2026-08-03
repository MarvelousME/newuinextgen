<?php
/**
 * Publish-worker restart recovery (durable options + lease expiry).
 *
 * Run: php NextGenTutors-Companion/tests/publish-worker-restart.php
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$root = dirname( __DIR__ );
$GLOBALS['ngc_opts'] = [];

if ( ! function_exists( '__' ) ) {
	function __( $t, $d = null ) { return $t; }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); }
}
if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( $len = 12, $special = true, $extra = false ) {
		return substr( bin2hex( random_bytes( 16 ) ), 0, (int) $len );
	}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $k, $default = false ) {
		return array_key_exists( $k, $GLOBALS['ngc_opts'] ) ? $GLOBALS['ngc_opts'][ $k ] : $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $k, $v, $autoload = null ) {
		$GLOBALS['ngc_opts'][ $k ] = $v;
		return true;
	}
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action( ...$a ) {}
}
if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $h ) { return false; }
}
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code; private $message; private $data;
		public function __construct( $c = '', $m = '', $d = '' ) { $this->code = $c; $this->message = $m; $this->data = $d; }
		public function get_error_code() { return $this->code; }
		public function get_error_message() { return $this->message; }
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $t ) { return $t instanceof WP_Error; }
}

require_once $root . '/includes/agentic/content/class-ngc-publish-worker.php';

$pass = 0; $fail = 0;
function rw_assert( $name, $cond ) {
	global $pass, $fail;
	if ( $cond ) { echo "PASS  $name\n"; ++$pass; }
	else { echo "FAIL  $name\n"; ++$fail; }
}

$now = 1_700_000_000;
$job = NGC_Publish_Worker::enqueue([
	'post_id' => 'post_rw',
	'run_at_utc' => gmdate( 'c', $now - 60 ),
	'idempotency_key' => 'idem_rw_1',
]);
rw_assert( 'enqueue', ! is_wp_error( $job ) );

$snap = NGC_Publish_Worker::snapshot_state();
$snap['jobs'][0]['status']      = 'leased';
$snap['jobs'][0]['lease_owner'] = 'worker_a';
$snap['jobs'][0]['lease_until'] = $now + NGC_Publish_Worker::LEASE_SECONDS;
$snap['jobs'][0]['attempts']    = 1;
NGC_Publish_Worker::restore_state( $snap );

// Options-only durability: clear any PHP locals by re-reading via get_option path.
$persisted = NGC_Publish_Worker::snapshot_state();
rw_assert( 'options persist leased job', 'leased' === ( $persisted['jobs'][0]['status'] ?? '' ) );

$blocked = NGC_Publish_Worker::process_due( 'worker_b', $now + 10 );
rw_assert( 'lease holder exclusive', 0 === (int) $blocked['processed'] );

$done = NGC_Publish_Worker::process_due( 'worker_b', $now + NGC_Publish_Worker::LEASE_SECONDS + 1 );
$published = array_values( array_filter( NGC_Publish_Worker::jobs(), fn( $j ) => ( $j['status'] ?? '' ) === 'published' ) );
rw_assert( 'after lease expiry single publish', 1 === (int) $done['processed'] && 1 === count( $published ) );

$dup = NGC_Publish_Worker::process_due( 'worker_c', $now + 200 );
rw_assert( 'no duplicate publish', 0 === (int) $dup['processed'] );

echo "\nSummary: $pass passed, $fail failed\n";
exit( $fail > 0 ? 1 : 0 );
