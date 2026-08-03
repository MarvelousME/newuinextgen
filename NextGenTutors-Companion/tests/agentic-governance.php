<?php
/**
 * Agentic governance unit checks (no WordPress bootstrap required for criteria/schedule/ssrf).
 *
 * Run: php NextGenTutors-Companion/tests/agentic-governance.php
 *
 * @package NextGenCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$root = dirname( __DIR__ );
require_once $root . '/includes/agentic/leads/class-ngc-lead-criteria.php';
require_once $root . '/includes/agentic/content/class-ngc-schedule-rrule.php';
require_once $root . '/includes/agentic/mcp/class-ngc-mcp-ssrf.php';

// Minimal WP stubs used by classes under test.
if ( ! function_exists( '__' ) ) {
	function __( $t, $d = null ) { // phpcs:ignore
		return $t;
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $s ) {
		return trim( strip_tags( (string) $s ) );
	}
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $s ) {
		return trim( (string) $s );
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $s ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) );
	}
}
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;
		private $data;
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}
		public function get_error_code() {
			return $this->code;
		}
		public function get_error_message() {
			return $this->message;
		}
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $t ) {
		return $t instanceof WP_Error;
	}
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $u ) {
		return filter_var( (string) $u, FILTER_SANITIZE_URL ) ?: '';
	}
}
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $u, $c = -1 ) {
		return parse_url( $u, $c );
	}
}

$pass = 0;
$fail = 0;
function ag_assert( $name, $cond ) {
	global $pass, $fail;
	if ( $cond ) {
		echo "PASS  $name\n";
		++$pass;
	} else {
		echo "FAIL  $name\n";
		++$fail;
	}
}

// Protected-trait exclusion.
$bad = NGC_Lead_Criteria::sanitize( [ 'subject' => 'Maths', 'ethnicity' => 'x' ] );
ag_assert( 'rejects ethnicity filter', is_wp_error( $bad ) && 'ngc_lead_protected_trait' === $bad->get_error_code() );

$bad_age = NGC_Lead_Criteria::sanitize( [ 'subject' => 'Maths', 'age_min' => 25 ] );
ag_assert( 'rejects age_min filter', is_wp_error( $bad_age ) );

$bad_gender = NGC_Lead_Criteria::sanitize( [ 'subject' => 'Maths', 'gender' => 'f' ] );
ag_assert( 'rejects gender filter', is_wp_error( $bad_gender ) );

$ok = NGC_Lead_Criteria::sanitize( [ 'subject' => 'Mathematics', 'service_area' => 'Gauteng', 'delivery_mode' => 'online' ] );
ag_assert( 'allows job-relevant criteria', ! is_wp_error( $ok ) && 'Mathematics' === $ok['subject'] );

$score_bad = NGC_Lead_Criteria::assert_explanation_clean( 'Strong candidate due to ethnicity match' );
ag_assert( 'rejects protected language in score explanation', is_wp_error( $score_bad ) );

$score_ok = NGC_Lead_Criteria::assert_explanation_clean( 'Strong Maths teaching experience in Gauteng' );
ag_assert( 'allows job-relevant score explanation', true === $score_ok );

// Schedule multi-time ambiguity.
$preview = NGC_Schedule_Rrule::preview(
	[
		'timezone' => 'Africa/Johannesburg',
		'dtstart'  => '2026-12-25 09:00:00',
		'rrule'    => 'FREQ=DAILY;COUNT=2',
		'times'    => [ '09:00', '16:00' ],
		'limit'    => 20,
	]
);
ag_assert( 'schedule preview ok', ! is_wp_error( $preview ) && ! empty( $preview['ok'] ) );
ag_assert( 'multi-time yields >= 2 occurrences', ! is_wp_error( $preview ) && count( $preview['occurrences'] ) >= 2 );
ag_assert( 'first occurrence is 09:00 local', ! is_wp_error( $preview ) && false !== strpos( $preview['occurrences'][0]['local'], '09:00' ) );

// SSRF guards.
$meta = NGC_Mcp_Ssrf::assert_safe_url( 'http://169.254.169.254/latest/meta-data/', false );
ag_assert( 'blocks metadata IP', is_wp_error( $meta ) );

$http = NGC_Mcp_Ssrf::assert_safe_url( 'http://example.com/mcp', false );
ag_assert( 'blocks non-HTTPS remote', is_wp_error( $http ) );

$https = NGC_Mcp_Ssrf::assert_safe_url( 'https://example.com/mcp', false );
ag_assert( 'allows HTTPS public host', ! is_wp_error( $https ) );

$dword = NGC_Mcp_Ssrf::assert_safe_url( 'https://2130706433/mcp', false ); // 127.0.0.1 as dword
ag_assert( 'blocks dword IP encoding', is_wp_error( $dword ) && 'ngc_mcp_ssrf_encoded' === $dword->get_error_code() );

$hex = NGC_Mcp_Ssrf::assert_safe_url( 'https://0x7f000001/mcp', false );
ag_assert( 'blocks hex dword IP encoding', is_wp_error( $hex ) && 'ngc_mcp_ssrf_encoded' === $hex->get_error_code() );

$oct = NGC_Mcp_Ssrf::assert_safe_url( 'https://017700000001/mcp', false );
ag_assert( 'blocks octal IP encoding', is_wp_error( $oct ) && 'ngc_mcp_ssrf_encoded' === $oct->get_error_code() );

$dotted_hex = NGC_Mcp_Ssrf::assert_safe_url( 'https://0x7f.0x0.0x0.0x1/mcp', false );
ag_assert( 'blocks dotted hex IP encoding', is_wp_error( $dotted_hex ) );

$userinfo = NGC_Mcp_Ssrf::assert_safe_url( 'https://user:pass@example.com/mcp', false );
ag_assert( 'blocks credentials in URL', is_wp_error( $userinfo ) );

$redir = NGC_Mcp_Ssrf::assert_safe_url_no_redirect( 'https://example.com/mcp', false, 'https://169.254.169.254/' );
ag_assert( 'rejects Location header (no redirect follow)', is_wp_error( $redir ) && 'ngc_mcp_redirect' === $redir->get_error_code() );

$no_loc = NGC_Mcp_Ssrf::assert_safe_url_no_redirect( 'https://example.com/mcp', false, null );
ag_assert( 'allows safe URL when Location absent', true === $no_loc );

echo "\nSummary: $pass passed, $fail failed\n";
exit( $fail > 0 ? 1 : 0 );
