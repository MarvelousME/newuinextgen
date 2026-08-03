<?php
/**
 * Outreach engine unit tests.
 * Run: php NextGenTutors-Companion/tests/outreach-engine.php
 */
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
$root = dirname( __DIR__ );
$GLOBALS['ngc_opts'] = [];
if ( ! function_exists( '__' ) ) { function __( $t, $d = null ) { return $t; } }
if ( ! function_exists( 'sanitize_text_field' ) ) { function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); } }
if ( ! function_exists( 'sanitize_textarea_field' ) ) { function sanitize_textarea_field( $s ) { return trim( (string) $s ); } }
if ( ! function_exists( 'sanitize_key' ) ) { function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); } }
if ( ! function_exists( 'sanitize_email' ) ) { function sanitize_email( $s ) { return filter_var( (string) $s, FILTER_SANITIZE_EMAIL ) ?: ''; } }
if ( ! function_exists( 'is_email' ) ) { function is_email( $e ) { return (bool) filter_var( (string) $e, FILTER_VALIDATE_EMAIL ); } }
if ( ! function_exists( 'wp_generate_password' ) ) { function wp_generate_password( $l = 12, $s = true, $e = false ) { return substr( bin2hex( random_bytes( 8 ) ), 0, (int) $l ); } }
if ( ! function_exists( 'get_current_user_id' ) ) { function get_current_user_id() { return 1; } }
if ( ! function_exists( 'get_option' ) ) { function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['ngc_opts'] ) ? $GLOBALS['ngc_opts'][ $k ] : $d; } }
if ( ! function_exists( 'update_option' ) ) { function update_option( $k, $v, $a = null ) { $GLOBALS['ngc_opts'][ $k ] = $v; return true; } }
if ( ! function_exists( 'wp_html_excerpt' ) ) { function wp_html_excerpt( $s, $n, $m = '' ) { return substr( strip_tags( (string) $s ), 0, $n ) . $m; } }
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $c; private $m;
		public function __construct( $c = '', $m = '' ) { $this->c = $c; $this->m = $m; }
		public function get_error_code() { return $this->c; }
	}
}
if ( ! function_exists( 'is_wp_error' ) ) { function is_wp_error( $t ) { return $t instanceof WP_Error; } }

require_once $root . '/includes/agentic/leads/class-ngc-outreach-engine.php';

$pass = 0; $fail = 0;
function o_assert( $n, $c ) { global $pass, $fail; if ( $c ) { echo "PASS  $n\n"; ++$pass; } else { echo "FAIL  $n\n"; ++$fail; } }

$camp = NGC_Outreach_Engine::create_campaign( [ 'name' => 'Tutor Recruit Q3' ] );
o_assert( 'create campaign', ! is_wp_error( $camp ) );

$deny = NGC_Outreach_Engine::enroll( [ 'lead_id' => 'lead_1', 'campaign_id' => $camp['id'], 'email' => 'a@example.com' ] );
o_assert( 'enroll requires approval', is_wp_error( $deny ) );

$enr = NGC_Outreach_Engine::enroll( [ 'lead_id' => 'lead_1', 'campaign_id' => $camp['id'], 'email' => 'a@example.com', 'human_approved' => 1 ] );
o_assert( 'enroll with approval', ! is_wp_error( $enr ) );

$enr2 = NGC_Outreach_Engine::enroll( [ 'lead_id' => 'lead_1', 'campaign_id' => $camp['id'], 'email' => 'a@example.com', 'human_approved' => 1 ] );
o_assert( 'enroll idempotent', $enr['id'] === $enr2['id'] );

$adv_deny = NGC_Outreach_Engine::advance( $enr['id'], false );
o_assert( 'advance requires human', is_wp_error( $adv_deny ) );

$adv = NGC_Outreach_Engine::advance( $enr['id'], true );
o_assert( 'advance step 1', ! is_wp_error( $adv ) && 1 === (int) $adv['step'] );

$cls = NGC_Outreach_Engine::classify_reply( 'Please unsubscribe me' );
o_assert( 'classify unsubscribe', 'unsubscribe' === $cls['label'] && ! empty( $cls['stop'] ) );

$cls2 = NGC_Outreach_Engine::classify_reply( 'I am interested — tell me more' );
o_assert( 'classify interested', 'interested' === $cls2['label'] );

$cls3 = NGC_Outreach_Engine::classify_reply( 'What is the pay and contract?' );
o_assert( 'sensitive needs human', ! empty( $cls3['needs_human'] ) );

$rep = NGC_Outreach_Engine::ingest_reply( [ 'enrollment_id' => $enr['id'], 'body' => 'I am interested, sounds good' ] );
o_assert( 'ingest interested', ! is_wp_error( $rep ) && 'interested' === ( $rep['enrollment']['status'] ?? '' ) );

$hand = NGC_Outreach_Engine::recruitment_handoff( $enr['id'] );
o_assert( 'handoff', ! is_wp_error( $hand ) && 'handed_off' === ( $hand['status'] ?? '' ) );

$stop = NGC_Outreach_Engine::enroll( [ 'lead_id' => 'lead_2', 'campaign_id' => $camp['id'], 'email' => 'b@example.com', 'human_approved' => 1 ] );
NGC_Outreach_Engine::ingest_reply( [ 'enrollment_id' => $stop['id'], 'body' => 'unsubscribe please' ] );
$adv_stop = NGC_Outreach_Engine::advance( $stop['id'], true );
o_assert( 'stop blocks advance', is_wp_error( $adv_stop ) );

echo "\nSummary: $pass passed, $fail failed\n";
exit( $fail > 0 ? 1 : 0 );
