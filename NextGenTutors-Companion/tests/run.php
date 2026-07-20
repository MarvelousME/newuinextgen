<?php
/**
 * Lightweight unit tests (no PHPUnit) for NextGen Companion.
 *
 * Usage: php tests/run.php
 *
 * @package NextGenCompanion
 */

$root   = dirname( __DIR__ );
$errors = 0;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/tests-stub/' );
}

function ngc_test_assert( $label, $ok ) {
	global $errors;
	if ( ! $ok ) {
		echo "FAIL: {$label}\n";
		++$errors;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

require_once $root . '/includes/class-ngc-uuid.php';
require_once $root . '/includes/integrations/class-ngc-woocommerce-catalog.php';
require_once $root . '/includes/integrations/class-ngc-payout-export.php';

if ( ! defined( 'NGC_ALLOW_DEMO_SEED' ) ) {
	define( 'NGC_ALLOW_DEMO_SEED', true );
}

$ngc_test_options = [];
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		global $ngc_test_options;
		return array_key_exists( $name, $ngc_test_options ) ? $ngc_test_options[ $name ] : $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $name, $value, $autoload = null ) {
		global $ngc_test_options;
		$ngc_test_options[ $name ] = $value;
		return true;
	}
}
if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $name ) {
		global $ngc_test_options;
		unset( $ngc_test_options[ $name ] );
		return true;
	}
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value ) {
		unset( $hook );
		return $value;
	}
}

require_once $root . '/includes/integrations/class-ngc-amelia-bootstrap.php';

ngc_test_assert( 'amelia local stack elevated sync', NGC_Amelia_Bootstrap::allows_elevated_sync() );
ngc_test_assert( 'amelia direct mode flag', NGC_Amelia_Bootstrap::uses_direct_mode() === false );
$ngc_test_options['ngc_amelia_api_key'] = NGC_Amelia_Bootstrap::DIRECT_MODE_KEY;
$ngc_test_options['ngc_amelia_direct_mode'] = '1';
ngc_test_assert( 'amelia direct mode detect', NGC_Amelia_Bootstrap::uses_direct_mode() );
ngc_test_assert( 'amelia table_name sanitizes', NGC_Amelia_Bootstrap::table_name( 'amelia_users' ) !== NGC_Amelia_Bootstrap::table_name( 'amelia_users;drop' ) );
NGC_Amelia_Bootstrap::begin_trusted_sync();
ngc_test_assert( 'amelia trusted sync depth', NGC_Amelia_Bootstrap::allows_elevated_sync() );
NGC_Amelia_Bootstrap::end_trusted_sync();

ngc_test_assert( 'uuid valid format', NGC_Uuid::is_valid( NGC_Uuid::generate() ) );
ngc_test_assert( 'uuid rejects junk', ! NGC_Uuid::is_valid( 'not-a-uuid' ) );

$cats = NGC_WooCommerce_Catalog::parse_category_names( 'Online Tutoring, In-Person Tutoring' );
ngc_test_assert( 'parse_category_names splits CSV', 2 === count( $cats ) && 'Online Tutoring' === $cats[0] );
ngc_test_assert( 'parse_category_names empty', [] === NGC_WooCommerce_Catalog::parse_category_names( '' ) );

$csv = NGC_Payout_Export::to_csv(
	[
		[
			'recipient_email' => 'tutor@example.com',
			'recipient_name'  => 'Test Tutor',
			'amount'          => '150.00',
			'currency'        => 'ZAR',
			'reference'       => 'NGC-PAYOUT-1',
			'payout_id'       => 1,
		],
	]
);
ngc_test_assert( 'payfast csv header', 0 === strpos( $csv, 'recipient_email,recipient_name,amount,currency,reference,payout_id' ) );
ngc_test_assert( 'payfast csv row', false !== strpos( $csv, 'tutor@example.com' ) );
ngc_test_assert( 'csv_cell quotes comma', '"a,b"' === NGC_Payout_Export::csv_cell( 'a,b' ) );

require_once $root . '/includes/diagnostics/class-ngc-legacy-plugin-guard.php';

$stub_plugins = $root . '/tests-stub/wp-content/plugins';
foreach ( array( 'custom-folder/custom.php', 'legacy/legacy.php', 'vendor/ngt-core.php' ) as $rel ) {
	$path = $stub_plugins . '/' . $rel;
	if ( ! is_dir( dirname( $path ) ) ) {
		mkdir( dirname( $path ), 0777, true );
	}
	if ( ! is_file( $path ) ) {
		file_put_contents( $path, "<?php\n" );
	}
}
if ( ! function_exists( 'get_plugin_data' ) ) {
	function get_plugin_data( $file, $markup = true, $translate = true ) {
		unset( $markup, $translate );
		$relative = str_replace( '\\', '/', str_replace( WP_PLUGIN_DIR . '/', '', $file ) );
		$map      = array(
			'custom-folder/custom.php' => array( 'Name' => 'Custom Legacy', 'TextDomain' => 'nextgen-tutors-core' ),
			'legacy/legacy.php'        => array( 'Name' => 'NextGen Tutors', 'TextDomain' => 'nextgen-tutors' ),
			'vendor/ngt-core.php'      => array( 'Name' => 'NextGen Tutors Core', 'TextDomain' => 'vendor-core' ),
		);
		return $map[ $relative ] ?? array();
	}
}
if ( ! function_exists( 'wp_normalize_path' ) ) {
	function wp_normalize_path( $path ) {
		return str_replace( '\\', '/', $path );
	}
}
if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', $root . '/tests-stub/wp-content/plugins' );
}

ngc_test_assert( 'guard denies core basename', NGC_Legacy_Plugin_Guard::is_denied( 'nextgen-tutors-core/nextgen-tutors-core.php' ) );
ngc_test_assert( 'guard denies importer folder', NGC_Legacy_Plugin_Guard::is_denied( 'nextgen-tutors-importer/nextgen-tutors-importer.php' ) );
ngc_test_assert( 'guard allows companion path', ! NGC_Legacy_Plugin_Guard::is_denied( 'nextgentutors-companion/nextgencompanion.php' ) );
ngc_test_assert( 'guard allows unrelated plugin', ! NGC_Legacy_Plugin_Guard::is_denied( 'woocommerce/woocommerce.php' ) );
ngc_test_assert( 'guard denies folder prefix only', NGC_Legacy_Plugin_Guard::is_denied( 'nextgen-tutors-plugin/nextgen-tutors.php' ) );
ngc_test_assert( 'guard denies text domain header', NGC_Legacy_Plugin_Guard::is_denied( 'custom-folder/custom.php' ) );
ngc_test_assert( 'guard denies legacy plugin name', NGC_Legacy_Plugin_Guard::is_denied( 'legacy/legacy.php' ) );
ngc_test_assert( 'guard denies exact core name header', NGC_Legacy_Plugin_Guard::is_denied( 'vendor/ngt-core.php' ) );

// --- Agent policy engine (governed autonomy) ---
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $key ) );
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		global $ngc_test_user_id;
		return isset( $ngc_test_user_id ) ? (int) $ngc_test_user_id : 0;
	}
}
if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) {
		unset( $type, $gmt );
		return gmdate( 'Y-m-d H:i:s' );
	}
}

require_once $root . '/includes/agents/class-ngc-agent-policy-engine.php';
require_once $root . '/includes/agents/class-ngc-agent-control-plane.php';

$ngc_test_options['ngc_agent_global_pause'] = false;
$ngc_test_options['ngc_agent_paused_ids']   = [];

$deny = NGC_Agent_Policy_Engine::evaluate( 'agent.secret.exfiltrate', [ 'agent_id' => 'system-audit', 'autonomy_level' => 0 ] );
ngc_test_assert( 'policy denies secret exfiltration', ( $deny['decision'] ?? '' ) === NGC_Agent_Policy_Engine::DENY );

$allow = NGC_Agent_Policy_Engine::evaluate( 'agent.observe', [ 'agent_id' => 'system-audit', 'autonomy_level' => 0 ] );
ngc_test_assert( 'policy allows observe at L0', ( $allow['decision'] ?? '' ) === NGC_Agent_Policy_Engine::ALLOW );

$refund = NGC_Agent_Policy_Engine::evaluate( 'finance.refund.execute', [ 'agent_id' => 'financial-reconciliation', 'autonomy_level' => 1 ] );
ngc_test_assert( 'policy requires approval for refund', ( $refund['decision'] ?? '' ) === NGC_Agent_Policy_Engine::REQUIRE_APPROVAL );

$ngc_test_options['ngc_agent_global_pause'] = true;
$paused = NGC_Agent_Policy_Engine::evaluate( 'agent.observe', [ 'agent_id' => 'system-audit', 'autonomy_level' => 0 ] );
ngc_test_assert( 'global kill switch denies observe', ( $paused['decision'] ?? '' ) === NGC_Agent_Policy_Engine::DENY );
$ngc_test_options['ngc_agent_global_pause'] = false;

$over = NGC_Agent_Policy_Engine::evaluate( 'agent.observe', [ 'agent_id' => 'system-audit', 'autonomy_level' => 4 ] );
ngc_test_assert( 'autonomy over policy max requires approval', ( $over['decision'] ?? '' ) === NGC_Agent_Policy_Engine::REQUIRE_APPROVAL );

// --- PayFast ITN security ---
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
$ngc_test_transients = [];
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) {
		global $ngc_test_transients;
		return array_key_exists( $key, $ngc_test_transients ) ? $ngc_test_transients[ $key ] : false;
	}
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $ttl = 0 ) {
		global $ngc_test_transients;
		$ngc_test_transients[ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}
if ( ! class_exists( 'WP_Error', false ) ) {
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
		public function get_error_data() {
			return $this->data;
		}
	}
}

require_once $root . '/includes/integrations/class-ngc-payfast-itn.php';

$pf_pass = 'unit-test-pass';
$pf_body = [
	'merchant_id'  => '10000100',
	'm_payment_id' => '99',
	'amount_gross' => '250.00',
	'pf_payment_id'=> 'PF-UNIT-1',
];
$pf_body['signature'] = NGC_PayFast_Itn::generate_signature( $pf_body, $pf_pass );
ngc_test_assert(
	'payfast ITN valid payload',
	true === NGC_PayFast_Itn::validate_notification(
		$pf_body,
		[ 'merchant_id' => '10000100', 'passphrase' => $pf_pass, 'sandbox' => true ],
		250.00
	)
);
$pf_tamper = $pf_body;
$pf_tamper['amount_gross'] = '1.00';
$pf_tamper['signature']    = NGC_PayFast_Itn::generate_signature( $pf_tamper, $pf_pass );
$pf_err = NGC_PayFast_Itn::validate_notification(
	$pf_tamper,
	[ 'merchant_id' => '10000100', 'passphrase' => $pf_pass, 'sandbox' => true ],
	250.00
);
ngc_test_assert( 'payfast ITN rejects amount tamper', is_wp_error( $pf_err ) && 'ngc_pf_amount' === $pf_err->get_error_code() );

NGC_PayFast_Itn::mark_processed( 'PF-UNIT-1', 99 );
$pf_replay = NGC_PayFast_Itn::validate_notification(
	$pf_body,
	[ 'merchant_id' => '10000100', 'passphrase' => $pf_pass, 'sandbox' => true ],
	250.00
);
ngc_test_assert( 'payfast ITN detects replay', is_wp_error( $pf_replay ) );

// --- NGC_Access object-level helpers (IDOR) ---
$ngc_test_user_id   = 10;
$ngc_test_user_meta = [
	20 => [ 'ngt_parent_user_id' => 10 ],
	21 => [ 'ngt_parent_user_id' => 99 ],
];
$ngc_test_caps = [];
$ngc_test_user = (object) [ 'roles' => [ 'parent' ] ];

if ( ! function_exists( 'user_can' ) ) {
	function user_can( $user_id, $cap ) {
		global $ngc_test_caps;
		return ! empty( $ngc_test_caps[ (int) $user_id ][ $cap ] );
	}
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap ) {
		return user_can( get_current_user_id(), $cap );
	}
}
if ( ! function_exists( 'get_user_meta' ) ) {
	function get_user_meta( $user_id, $key, $single = false ) {
		global $ngc_test_user_meta;
		$val = $ngc_test_user_meta[ (int) $user_id ][ $key ] ?? '';
		return $single ? $val : [ $val ];
	}
}
if ( ! function_exists( 'get_userdata' ) ) {
	function get_userdata( $user_id ) {
		global $ngc_test_user;
		unset( $user_id );
		return $ngc_test_user;
	}
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		unset( $domain );
		return $text;
	}
}

require_once $root . '/includes/class-ngc-access.php';

$booking_owned = (object) [ 'student_user_id' => 20, 'tutor_user_id' => 30, 'parent_user_id' => 10 ];
$booking_other = (object) [ 'student_user_id' => 21, 'tutor_user_id' => 30, 'parent_user_id' => 99 ];
ngc_test_assert( 'access parent can view child booking', NGC_Access::can_view_booking( $booking_owned, 10 ) );
ngc_test_assert( 'access stranger cannot view booking', ! NGC_Access::can_view_booking( $booking_other, 10 ) );

$create_ok = NGC_Access::sanitize_booking_create_payload( [ 'student_user_id' => 20, 'amount' => 100 ], 10 );
ngc_test_assert( 'access create allows linked child', is_array( $create_ok ) && 20 === (int) $create_ok['student_user_id'] );
$create_bad = NGC_Access::sanitize_booking_create_payload( [ 'student_user_id' => 21 ], 10 );
ngc_test_assert( 'access create blocks foreign student', is_wp_error( $create_bad ) );

$upd = NGC_Access::sanitize_booking_update_payload( [ 'amount' => 999, 'status' => 'confirmed', 'tutor_user_id' => 55 ], 10 );
ngc_test_assert( 'access update strips amount/tutor for non-ops', ! isset( $upd['amount'] ) && ! isset( $upd['tutor_user_id'] ) && 'confirmed' === ( $upd['status'] ?? '' ) );

$match = (object) [ 'parent_user_id' => 10, 'student_user_id' => 20, 'tutor_user_id' => 30 ];
ngc_test_assert( 'access can act on own match', NGC_Access::can_act_on_match( $match, 10 ) );
ngc_test_assert( 'access cannot act on foreign match', ! NGC_Access::can_act_on_match( $match, 77 ) );

// --- Safeguarding SLA helpers ---
require_once $root . '/includes/agents/class-ngc-safeguarding.php';
ngc_test_assert( 'sfg sla high is 4h', 4 === NGC_Safeguarding::sla_hours_for( 'high' ) );
ngc_test_assert( 'sfg sla critical is 2h', 2 === NGC_Safeguarding::sla_hours_for( 'critical' ) );
$sla_ok = NGC_Safeguarding::sla_status( (object) [ 'due_at' => gmdate( 'Y-m-d H:i:s', time() + 3600 ), 'status' => 'open', 'escalated_at' => null ] );
ngc_test_assert( 'sfg sla not breached when future due', ! $sla_ok['breached'] );
$sla_bad = NGC_Safeguarding::sla_status( (object) [ 'due_at' => gmdate( 'Y-m-d H:i:s', time() - 60 ), 'status' => 'open', 'escalated_at' => null ] );
ngc_test_assert( 'sfg sla breached when past due', $sla_bad['breached'] );

// --- Fraud default rules coverage ---
require_once $root . '/includes/agents/class-ngc-fraud-engine.php';
$fr = NGC_Fraud_Engine::default_rules();
ngc_test_assert( 'fraud has payout_detail_change', isset( $fr['payout_detail_change'] ) );
ngc_test_assert( 'fraud has booking_velocity', isset( $fr['booking_velocity'] ) );
ngc_test_assert( 'fraud has harassment_signal', isset( $fr['harassment_signal'] ) );
ngc_test_assert( 'fraud rule count >= 12', count( $fr ) >= 12 );

if ( $errors > 0 ) {
	echo "\n{$errors} test(s) failed\n";
	exit( 1 );
}

echo "OK — 43 unit tests passed\n";
exit( 0 );
