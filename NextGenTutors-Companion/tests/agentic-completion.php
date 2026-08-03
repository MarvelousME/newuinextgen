<?php
/**
 * Extended agentic + publish-worker governance tests (standalone stubs).
 *
 * Run: php NextGenTutors-Companion/tests/agentic-completion.php
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
if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $s ) { return filter_var( (string) $s, FILTER_SANITIZE_EMAIL ) ?: ''; }
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $s ) { return trim( (string) $s ); }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $u ) { return filter_var( (string) $u, FILTER_SANITIZE_URL ) ?: ''; }
}
if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( $len = 12, $special = true, $extra = false ) {
		return substr( bin2hex( random_bytes( 16 ) ), 0, (int) $len );
	}
}
if ( ! function_exists( 'gmdate' ) ) {
	// native
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
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $u, $c = -1 ) {
		return -1 === $c ? parse_url( $u ) : parse_url( $u, $c );
	}
}
if ( ! function_exists( 'is_email' ) ) {
	function is_email( $e ) { return (bool) filter_var( (string) $e, FILTER_VALIDATE_EMAIL ); }
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

require_once $root . '/includes/agentic/leads/class-ngc-lead-criteria.php';
require_once $root . '/includes/agentic/leads/class-ngc-tutor-leads.php';
require_once $root . '/includes/agentic/content/class-ngc-publish-worker.php';
require_once $root . '/includes/agentic/mcp/class-ngc-mcp-ssrf.php';

$pass = 0; $fail = 0;
function c_assert( $name, $cond ) {
	global $pass, $fail;
	if ( $cond ) { echo "PASS  $name\n"; ++$pass; }
	else { echo "FAIL  $name\n"; ++$fail; }
}

// Import boundary rejects protected traits.
$imp = NGC_Tutor_Leads::create([
	'source' => 'consented_import',
	'subject' => 'Physics',
	'display_name' => 'Test Tutor',
	'public_email' => 'tutor@example.com',
	'ethnicity' => 'x',
	'discovery_query' => [ 'subject' => 'Physics', 'gender' => 'f' ],
]);
c_assert( 'import/API rejects gender in discovery_query', is_wp_error( $imp ) );

$ok = NGC_Tutor_Leads::create([
	'source' => 'manual_entry',
	'subject' => 'Physics',
	'display_name' => 'Test Tutor',
	'public_email' => 'tutor2@example.com',
	'discovery_query' => [ 'subject' => 'Physics', 'service_area' => 'Gauteng' ],
]);
c_assert( 'manual lead create ok', ! is_wp_error( $ok ) );

$scrape = NGC_Tutor_Leads::create([
	'source' => 'linkedin_scrape',
	'subject' => 'Maths',
	'discovery_query' => [ 'subject' => 'Maths' ],
]);
c_assert( 'linkedin scrape source blocked', is_wp_error( $scrape ) );

$bing = NGC_Tutor_Leads::create([
	'source' => 'bing_search_api',
	'subject' => 'Maths',
	'discovery_query' => [ 'subject' => 'Maths' ],
]);
c_assert( 'retired bing search source blocked', is_wp_error( $bing ) );

// Worker: enqueue idempotent + dual worker single publish.
$GLOBALS['ngc_opts'] = [];
$j1 = NGC_Publish_Worker::enqueue([
	'post_id' => 'post_test',
	'run_at_utc' => gmdate( 'c', time() - 10 ),
	'idempotency_key' => 'idem_worker_1',
]);
$j2 = NGC_Publish_Worker::enqueue([
	'post_id' => 'post_test',
	'run_at_utc' => gmdate( 'c', time() - 10 ),
	'idempotency_key' => 'idem_worker_1',
]);
c_assert( 'enqueue idempotent', ! is_wp_error( $j1 ) && ( $j1['id'] === $j2['id'] ) );

$r1 = NGC_Publish_Worker::process_due( 'worker_a', time() );
c_assert( 'worker processes due job', ! empty( $r1['processed'] ) && $r1['processed'] >= 1 );

$r2 = NGC_Publish_Worker::process_due( 'worker_b', time() );
$published = array_filter( NGC_Publish_Worker::jobs(), fn( $j ) => ( $j['status'] ?? '' ) === 'published' );
c_assert( 'second worker does not duplicate publish', count( $published ) === 1 );
c_assert( 'second pass processed 0 new', (int) $r2['processed'] === 0 );

// SSRF hex/octal/dword host forms (host-level, before DNS).
$meta = NGC_Mcp_Ssrf::assert_safe_url( 'http://169.254.169.254/', false );
c_assert( 'php ssrf metadata blocked', is_wp_error( $meta ) );
$redir_style = NGC_Mcp_Ssrf::assert_safe_url( 'https://127.0.0.1/mcp', false );
c_assert( 'php ssrf loopback IP blocked', is_wp_error( $redir_style ) );
$dword = NGC_Mcp_Ssrf::assert_safe_url( 'https://2130706433/mcp', false );
c_assert( 'php ssrf dword blocked', is_wp_error( $dword ) );
$hex = NGC_Mcp_Ssrf::assert_safe_url( 'https://0x7f000001/mcp', false );
c_assert( 'php ssrf hex dword blocked', is_wp_error( $hex ) );
$loc = NGC_Mcp_Ssrf::assert_safe_url_no_redirect( 'https://example.com/mcp', false, 'http://127.0.0.1/' );
c_assert( 'php ssrf Location header rejected', is_wp_error( $loc ) );

// Restart recovery: lease persists in options; after expiry another worker publishes once.
$GLOBALS['ngc_opts'] = [];
$now = time();
$job = NGC_Publish_Worker::enqueue([
	'post_id' => 'post_restart',
	'run_at_utc' => gmdate( 'c', $now - 10 ),
	'idempotency_key' => 'idem_restart_1',
]);
c_assert( 'restart harness enqueue ok', ! is_wp_error( $job ) );

// Simulate partial progress: worker crashed while holding a lease (options durable).
$snap = NGC_Publish_Worker::snapshot_state();
$snap['jobs'][0]['status']      = 'leased';
$snap['jobs'][0]['lease_owner'] = 'worker_crashed';
$snap['jobs'][0]['lease_until'] = $now + 30;
$snap['jobs'][0]['attempts']    = 1;
NGC_Publish_Worker::restore_state( $snap );
c_assert( 'leased state restored from options snapshot', 'leased' === ( NGC_Publish_Worker::jobs()[0]['status'] ?? '' ) );

// Active lease: different worker must skip.
$during = NGC_Publish_Worker::process_due( 'worker_b', $now + 5 );
c_assert( 'active lease skips other worker', (int) $during['processed'] === 0 );

// Simulate process restart: nothing in-memory; options still hold the job. Lease expired.
$after = NGC_Publish_Worker::process_due( 'worker_after_restart', $now + 61 );
$published_restart = array_filter( NGC_Publish_Worker::jobs(), fn( $j ) => ( $j['status'] ?? '' ) === 'published' );
c_assert( 'post-lease-expiry worker publishes once', (int) $after['processed'] === 1 );
c_assert( 'restart recovery single publish', count( $published_restart ) === 1 );

$again = NGC_Publish_Worker::process_due( 'worker_c', $now + 120 );
c_assert( 'no duplicate after restart publish', (int) $again['processed'] === 0 );

echo "\nSummary: $pass passed, $fail failed\n";
exit( $fail > 0 ? 1 : 0 );
