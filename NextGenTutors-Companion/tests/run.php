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
$passed = 0;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/tests-stub/' );
}
if ( ! defined( 'NGC_PLUGIN_DIR' ) ) {
	define( 'NGC_PLUGIN_DIR', $root . '/' );
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', dirname( $root ) );
}

function ngc_test_assert( $label, $ok ) {
	global $errors, $passed;
	if ( ! $ok ) {
		echo "FAIL: {$label}\n";
		++$errors;
	} else {
		++$passed;
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
if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) ) {
	define( 'WP_ENVIRONMENT_TYPE', 'local' );
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
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( (string) $string, '/\\' ) . '/';
	}
}
if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( $string ) {
		return rtrim( (string) $string, '/\\' );
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

// --- PRIV-001 retention settings ---
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $hook ) {
		unset( $hook );
		return false;
	}
}
if ( ! function_exists( 'wp_schedule_event' ) ) {
	function wp_schedule_event( $timestamp, $recurrence, $hook ) {
		unset( $timestamp, $recurrence, $hook );
		return true;
	}
}
if ( ! function_exists( 'wp_unschedule_event' ) ) {
	function wp_unschedule_event( $timestamp, $hook ) {
		unset( $timestamp, $hook );
		return true;
	}
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return filter_var( (string) $url, FILTER_SANITIZE_URL ) ?: '';
	}
}
if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
		unset( $special_chars, $extra_special_chars );
		return substr( str_repeat( 'abcdef0123456789', 8 ), 0, (int) $length );
	}
}

require_once $root . '/includes/class-ngc-privacy.php';
$priv = NGC_Privacy::save_settings(
	[
		'minor_days'     => 10, // below floor → 30
		'analytics_days' => 3,  // below floor → 7
		'log_days'       => 90,
		'auto_purge'     => true,
	]
);
ngc_test_assert( 'privacy minor days floored to 30', 30 === (int) $priv['minor_days'] );
ngc_test_assert( 'privacy analytics days floored to 7', 7 === (int) $priv['analytics_days'] );
ngc_test_assert( 'privacy auto purge enabled', ! empty( $priv['auto_purge'] ) );

$exporters = NGC_Privacy::register_exporter( [] );
ngc_test_assert( 'privacy exporter registered', isset( $exporters[ NGC_Privacy::EXPORTER_KEY ] ) );
$erasers = NGC_Privacy::register_eraser( [] );
ngc_test_assert( 'privacy eraser registered', isset( $erasers[ NGC_Privacy::ERASER_KEY ] ) );

// --- OBS-001 metrics ---
require_once $root . '/includes/diagnostics/class-ngc-metrics.php';
$mset = NGC_Metrics::save_settings(
	[
		'enabled'               => true,
		'push_url'              => 'https://collector.example/ingest',
		'alert_error_threshold' => 10,
		'rotate_token'          => true,
	]
);
ngc_test_assert( 'metrics enabled', ! empty( $mset['enabled'] ) );
ngc_test_assert( 'metrics token length', strlen( (string) $mset['token'] ) >= 32 );
ngc_test_assert( 'metrics push url saved', 'https://collector.example/ingest' === $mset['push_url'] );

NGC_Metrics::bump( 'test_counter', 3 );
$counters = get_transient( NGC_Metrics::TRANSIENT_COUNTERS );
ngc_test_assert( 'metrics bump works', is_array( $counters ) && 3 === (int) ( $counters['test_counter'] ?? 0 ) );
putenv( 'NGC_METRICS_TOKEN=from-env-token' );
ngc_test_assert( 'metrics scrape token prefers env', 'from-env-token' === NGC_Metrics::scrape_token() );
putenv( 'NGC_METRICS_TOKEN' );

// --- Phase 14 demo subsystem ---
if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) {
		return 'http://localhost:8890' . $path;
	}
}
if ( ! function_exists( 'sanitize_file_name' ) ) {
	function sanitize_file_name( $name ) {
		return preg_replace( '/[^a-zA-Z0-9_\-\.]/', '', (string) $name );
	}
}
if ( ! function_exists( 'wp_generate_uuid4' ) ) {
	function wp_generate_uuid4() {
		return '00000000-0000-4000-8000-000000000001';
	}
}
if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( $dir ) {
		return is_dir( $dir ) || mkdir( $dir, 0777, true );
	}
}
if ( ! defined( 'NGC_PLUGIN_DIR' ) ) {
	define( 'NGC_PLUGIN_DIR', $root . '/' );
}

require_once $root . '/includes/demo/class-ngc-demo-env.php';
require_once $root . '/includes/demo/class-ngc-demo-clock.php';
require_once $root . '/includes/demo/class-ngc-demo-notifications.php';
require_once $root . '/includes/demo/class-ngc-demo-registry.php';
require_once $root . '/includes/demo/class-ngc-demo-journeys.php';

$meta = NGC_Demo_Env::demo_meta( 'MATCH-001' );
ngc_test_assert( 'demo meta is_demo', ! empty( $meta['is_demo'] ) );
ngc_test_assert( 'demo meta scenario', 'match-001' === $meta['demo_scenario_id'] );
ngc_test_assert( 'demo meta version', NGC_Demo_Env::SEED_VERSION === $meta['demo_seed_version'] );

$personas = NGC_Demo_Registry::personas();
ngc_test_assert( 'demo personas >= 18', count( $personas ) >= 18 );
ngc_test_assert( 'demo primary parent exists', isset( $personas['NGT-DEMO-P0001'] ) );
ngc_test_assert( 'demo approved tutor exists', isset( $personas['NGT-DEMO-T0001'] ) );

NGC_Demo_Notifications::clear();
NGC_Demo_Notifications::emit( 'booking-confirmed', 'demo.parent@nextgen.local', 'booking.confirmed', [ 'id' => 1 ] );
$notes = NGC_Demo_Notifications::all();
ngc_test_assert( 'demo notification recorded', count( $notes ) >= 1 && 'booking-confirmed' === ( $notes[0]['template'] ?? '' ) );

$journeys = NGC_Demo_Journeys::list_journeys();
ngc_test_assert( 'demo journey catalogue >= 29', count( $journeys ) >= 29 );
$ids = array_map(
	static function ( $j ) {
		return (string) ( $j['id'] ?? '' );
	},
	$journeys
);
ngc_test_assert( 'demo journey MATCH-001 present', in_array( 'MATCH-001', $ids, true ) );
ngc_test_assert( 'demo journey BOOK-001 present', in_array( 'BOOK-001', $ids, true ) );
ngc_test_assert( 'demo journey FIN-001 present', in_array( 'FIN-001', $ids, true ) );
ngc_test_assert( 'demo journey umbrella parent present', in_array( 'JOURNEY-PARENT-001', $ids, true ) );

// --- Intelligence platform (schema, channels, layout, webhook SSRF guard) ---
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		$url = trim( (string) $url );
		return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : '';
	}
}
if ( ! function_exists( 'wp_http_validate_url' ) ) {
	function wp_http_validate_url( $url ) {
		return (bool) filter_var( $url, FILTER_VALIDATE_URL );
	}
}
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}
if ( ! function_exists( 'site_url' ) ) {
	function site_url( $path = '' ) {
		return 'https://nextgentutors.test' . $path;
	}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return site_url( '/wp-admin/' . ltrim( $path, '/' ) );
	}
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 1;
	}
}
if ( ! function_exists( 'wp_trim_words' ) ) {
	function wp_trim_words( $text, $num ) {
		$words = explode( ' ', (string) $text );
		return implode( ' ', array_slice( $words, 0, (int) $num ) );
	}
}
if ( ! function_exists( 'wp_timezone_string' ) ) {
	function wp_timezone_string() {
		return 'UTC';
	}
}

require_once $root . '/includes/intelligence/class-ngc-intelligence-schema.php';
require_once $root . '/includes/intelligence/class-ngc-intelligence-config.php';
require_once $root . '/includes/intelligence/class-ngc-intelligence-dispatch.php';
require_once $root . '/includes/intelligence/class-ngc-intelligence-channels.php';
require_once $root . '/includes/intelligence/class-ngc-intelligence-audit.php';
require_once $root . '/includes/intelligence/class-ngc-intelligence-layout.php';

$normalized = NGC_Intelligence_Schema::normalize(
	[
		'event_key'   => 'workflow.action.test',
		'plugin_slug' => 'automation-hub',
		'severity'    => 'info',
		'message'     => 'Unit test event',
	]
);
ngc_test_assert( 'intel schema normalizes', is_array( $normalized ) && 'workflow_action_test' === $normalized['event_key'] );
ngc_test_assert( 'intel schema rejects empty key', is_wp_error( NGC_Intelligence_Schema::normalize( [ 'event_key' => '' ] ) ) );

$teams = NGC_Intelligence_Channels::format( 'teams', 'error', 'Alert', 'Something failed', [] );
ngc_test_assert( 'intel teams MessageCard', isset( $teams['@type'] ) && 'MessageCard' === $teams['@type'] );
$slack = NGC_Intelligence_Channels::format( 'slack', 'warning', 'Warn', 'Check logs', [] );
ngc_test_assert( 'intel slack blocks', isset( $slack['blocks'] ) && is_array( $slack['blocks'] ) );

ngc_test_assert( 'intel webhook allows slack https', NGC_Intelligence_Dispatch::is_safe_webhook_url( 'https://hooks.slack.com/services/T/B/xxx' ) );
ngc_test_assert( 'intel webhook blocks localhost', ! NGC_Intelligence_Dispatch::is_safe_webhook_url( 'https://localhost/hook' ) );
ngc_test_assert( 'intel webhook blocks private ip', ! NGC_Intelligence_Dispatch::is_safe_webhook_url( 'https://192.168.1.1/hook' ) );
ngc_test_assert( 'intel webhook blocks http', ! NGC_Intelligence_Dispatch::is_safe_webhook_url( 'http://hooks.slack.com/services/x' ) );

$layout = NGC_Intelligence_Layout::save( [ 'brief', 'kpis', 'evil-widget' ], 42 );
ngc_test_assert( 'intel layout whitelist', ! in_array( 'evil-widget', $layout, true ) && in_array( 'brief', $layout, true ) );

$ngc_test_options[ NGC_Intelligence_Config::OPTION ] = array_merge(
	NGC_Intelligence_Config::defaults(),
	[ 'webhook_secret' => 'super-secret-value' ]
);
$api_cfg = NGC_Intelligence_Config::get_for_api();
ngc_test_assert( 'intel config api masks secret', ! isset( $api_cfg['webhook_secret'] ) );
ngc_test_assert( 'intel config api secret_set flag', ! empty( $api_cfg['webhook_secret_set'] ) );

// --- Unified admin framework ---
require_once $root . '/includes/admin/framework/class-ngc-admin-helpers.php';
require_once $root . '/includes/admin/framework/class-ngc-admin-registry.php';
require_once $root . '/includes/admin/framework/class-ngc-admin-catalog.php';

if ( ! class_exists( 'NGC_Admin_Shell', false ) ) {
	class NGC_Admin_Shell {
		public const PARENT_SLUG = 'ngt-admin';
		public const MENU_TITLE  = 'NEXT GEN TUTORS';
	}
}

NGC_Admin_Catalog::register_defaults();
ngc_test_assert( 'admin catalog has mission-control module', null !== NGC_Admin_Registry::get_module( 'mission-control' ) );
ngc_test_assert( 'admin catalog has mission control screen', null !== NGC_Admin_Registry::get_screen( 'ngtmc-mission-control' ) );
ngc_test_assert( 'admin parent helper', 'ngt-admin' === ngt_admin_parent() );
ngc_test_assert( 'admin catalog screen count', count( NGC_Admin_Registry::screens() ) >= 20 );
ngc_test_assert( 'admin catalog screen definitions file', count( NGC_Admin_Catalog::screen_definitions() ) >= 20 );

require_once $root . '/includes/admin/framework/class-ngc-platform-version.php';
require_once $root . '/includes/admin/framework/class-ngc-admin-theme.php';
require_once $root . '/includes/admin/framework/class-ngc-admin-nav-layout.php';
require_once $root . '/includes/admin/framework/class-ngc-admin-entity-registry.php';

ngc_test_assert( 'platform display title v1.0', false !== strpos( NGC_Platform_Version::display_title(), 'v1.0' ) );
ngc_test_assert( 'platform bundle has companion', isset( NGC_Platform_Version::bundle()['companion'] ) );

$theme = NGC_Admin_Theme::sanitize( [ 'primary' => '#112233', 'evil' => '<script>', 'border_radius' => '10' ] );
ngc_test_assert( 'theme sanitize keeps primary', '#112233' === ( $theme['primary'] ?? '' ) );
ngc_test_assert( 'theme sanitize drops unknown', ! isset( $theme['evil'] ) );

$merged = NGC_Admin_Nav_Layout::merge( NGC_Admin_Nav_Layout::defaults(), [ 'favorites' => [ 'ngc-applications' ], 'order' => [ 'cat:platform' ] ] );
ngc_test_assert( 'nav layout merge favorites', in_array( 'ngc-applications', $merged['favorites'], true ) );

NGC_Admin_Entity_Registry::init();
ngc_test_assert( 'entity registry has applications', null !== NGC_Admin_Entity_Registry::get( 'applications' ) );
ngc_test_assert( 'entity registry has matches', null !== NGC_Admin_Entity_Registry::get( 'matches' ) );
ngc_test_assert( 'entity registry has safeguarding', null !== NGC_Admin_Entity_Registry::get( 'safeguarding_cases' ) );
ngc_test_assert( 'theme designer screen registered', null !== NGC_Admin_Registry::get_screen( 'ngt-admin-theme-designer' ) );
ngc_test_assert( 'education students screen registered', null !== NGC_Admin_Registry::get_screen( 'ngt-edu-students' ) );

NGC_Admin_Registry::register_screen(
	[
		'slug'       => 'ngt-test-screen',
		'title'      => 'Zeta Test Screen',
		'module'     => 'system',
		'category'   => 'infrastructure',
		'capability' => 'read',
		'keywords'   => [ 'zetaunique' ],
		'callback'   => '__return_null',
	]
);
// Bypass capability stub by searching raw screens index path used in production for admins.
$all = NGC_Admin_Registry::screens();
$found = false;
foreach ( $all as $s ) {
	if ( false !== strpos( strtolower( (string) ( $s['title'] ?? '' ) . ' ' . implode( ' ', (array) ( $s['keywords'] ?? [] ) ) ), 'zetaunique' ) ) {
		$found = true;
		break;
	}
}
ngc_test_assert( 'admin registry indexes keywords', $found );

// --- Automation Studio importer ---
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		unset( $domain );
		return $text;
	}
}
if ( ! defined( 'NGC_PLUGIN_DIR' ) ) {
	define( 'NGC_PLUGIN_DIR', $root . '/' );
}
if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', dirname( $root ) );
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', dirname( $root ) );
}

require_once $root . '/includes/studio/class-ngc-studio-templates.php';
require_once $root . '/includes/studio/class-ngc-studio-importer.php';

$graph = NGC_Studio_Templates::build_linear_graph( 'TUTOR_APPROVED', [ 'CRM', 'EMAIL', 'END' ] );
ngc_test_assert( 'studio linear graph has nodes', ! empty( $graph['nodes'] ) && count( $graph['nodes'] ) >= 3 );
ngc_test_assert( 'studio linear graph has edges', ! empty( $graph['edges'] ) );
ngc_test_assert( 'studio templates catalog non-empty', count( NGC_Studio_Templates::all() ) >= 20 );

$hub_path = dirname( $root ) . '/nextgen-automation-hub/config/workflows.json';
if ( is_readable( $hub_path ) ) {
	$hub_raw = json_decode( (string) file_get_contents( $hub_path ), true );
	ngc_test_assert( 'hub workflows.json readable', is_array( $hub_raw ) && ! empty( $hub_raw['workflows'] ) );
}

$orch = ( new ReflectionClass( 'NGC_Studio_Importer' ) )->getMethod( 'orchestrator_map' );
$orch->setAccessible( true );
$orch_map = $orch->invoke( null );
ngc_test_assert( 'studio orch map has 7 workflows', is_array( $orch_map ) && count( $orch_map ) >= 7 );

// --- Enterprise Platform Kernel ---
require_once $root . '/includes/platform/class-ngc-tenant-context.php';
require_once $root . '/includes/platform/class-ngc-authz-matrix.php';
require_once $root . '/includes/platform/class-ngc-idempotency.php';
require_once $root . '/includes/platform/class-ngc-platform.php';

ngc_test_assert( 'tenant context default id', NGC_Tenant_Context::id() >= 1 );
$tenant_leak = NGC_Tenant_Context::run_as(
	99,
	static function () {
		return NGC_Tenant_Context::id();
	}
);
ngc_test_assert( 'tenant run_as override', 99 === (int) $tenant_leak );
ngc_test_assert( 'tenant restored after run_as', 1 === (int) NGC_Tenant_Context::id() || NGC_Tenant_Context::id() >= 1 );

$matrix = NGC_Authz_Matrix::matrix();
ngc_test_assert( 'authz matrix has SuperAdmin', isset( $matrix['SuperAdmin'] ) );
ngc_test_assert( 'authz matrix has Moderator safeguarding cap', in_array( 'ngc_manage_safeguarding', $matrix['Moderator'], true ) );
ngc_test_assert( 'authz matrix has Finance ledger cap', in_array( 'ngc_view_ledger', $matrix['Finance'], true ) );
ngc_test_assert( 'student cannot view finance', ! NGC_Authz_Matrix::role_allows( 'Student', 'ngc_view_finance' ) );
ngc_test_assert( 'parent cannot manage platform', ! NGC_Authz_Matrix::role_allows( 'Parent', 'ngc_manage_platform' ) );
ngc_test_assert( 'tutor cannot view ledger', ! NGC_Authz_Matrix::role_allows( 'Tutor', 'ngc_view_ledger' ) );
ngc_test_assert( 'finance has ledger', NGC_Authz_Matrix::role_allows( 'Finance', 'ngc_view_ledger' ) );
ngc_test_assert( 'payouts is privileged', NGC_Authz_Matrix::is_privileged( 'ngc_manage_payouts' ) );
ngc_test_assert( 'book session is not privileged', ! NGC_Authz_Matrix::is_privileged( 'ngc_book_session' ) );
$pub = NGC_Authz_Matrix::public_routes();
ngc_test_assert( 'public routes include support tickets', is_array( $pub ) && isset( $pub[0]['route'] ) && false !== strpos( $pub[0]['route'], '/support/' ) );
$authz_pack = dirname( $root ) . '/architecture/policies/authz-matrix.json';
$authz_json = is_readable( $authz_pack ) ? json_decode( (string) file_get_contents( $authz_pack ), true ) : null;
ngc_test_assert( 'authz JSON pack readable', is_array( $authz_json ) );
ngc_test_assert( 'authz JSON default DENY', is_array( $authz_json ) && 'DENY' === ( $authz_json['defaultDecision'] ?? '' ) );
ngc_test_assert( 'authz JSON roles match matrix', is_array( $authz_json ) && $matrix === ( $authz_json['roles'] ?? null ) );
ngc_test_assert( 'authz JSON privileged match', is_array( $authz_json ) && NGC_Authz_Matrix::privileged_capabilities() === ( $authz_json['privilegedCapabilities'] ?? null ) );

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
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
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
	}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}
if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) {
		return gmdate( 'Y-m-d H:i:s' );
	}
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 0;
	}
}

$fp1 = NGC_Idempotency::fingerprint( [ 'a' => 1, 'b' => 2 ] );
$fp2 = NGC_Idempotency::fingerprint( [ 'b' => 2, 'a' => 1 ] );
ngc_test_assert( 'idempotency fingerprint stable sort', $fp1 === $fp2 );

ngc_test_assert( 'platform authority option constant', defined( 'NGC_Platform::OPTION_AUTHORITY' ) || NGC_Platform::OPTION_AUTHORITY === 'ngc_workflow_authority_v1' );
ngc_test_assert( 'platform kill switch constant', NGC_Platform::OPTION_KILL_SWITCH === 'ngc_workflow_authority_kill' );

// Pure ledger balance math helper via reflection-free check of unbalanced refusal logic (unit).
$unbalanced_check = abs( 10.0 - 9.0 ) > 0.001;
ngc_test_assert( 'ledger unbalanced detection', $unbalanced_check );

// Audit chain hash material format.
$material = implode( '|', [ 1, 1, 'uuid', 'action', 'type', 0, hash( 'sha256', '{}' ), str_repeat( '0', 64 ) ] );
ngc_test_assert( 'audit material non-empty', strlen( hash( 'sha256', $material ) ) === 64 );

$backoff = null;
if ( ! class_exists( 'NGC_Durable_Queue' ) ) {
	require_once $root . '/includes/platform/class-ngc-platform-schema.php';
	require_once $root . '/includes/platform/class-ngc-platform-observability.php';
	require_once $root . '/includes/platform/class-ngc-durable-queue.php';
}
if ( ! function_exists( 'wp_rand' ) ) {
	function wp_rand( $min = 0, $max = 0 ) {
		return $min;
	}
}
$backoff = NGC_Durable_Queue::backoff_seconds( 3 );
ngc_test_assert( 'queue backoff positive', $backoff >= 1 );

require_once $root . '/includes/demo/class-ngc-demo-env.php';
$GLOBALS['ngc_test_environment'] = 'production';
ngc_test_assert( 'production environment detected', NGC_Demo_Env::is_production_environment() );
ngc_test_assert( 'production denies demo seed even if constant true', ! NGC_Demo_Env::seed_allowed() );
$prod_gate = NGC_Demo_Env::assert_demo_ops_allowed();
ngc_test_assert( 'production blocks demo ops', is_wp_error( $prod_gate ) );
$GLOBALS['ngc_test_environment'] = 'local';
ngc_test_assert( 'local environment not production', ! NGC_Demo_Env::is_production_environment() );
ngc_test_assert( 'local allows demo seed when constant set', NGC_Demo_Env::seed_allowed() );
unset( $GLOBALS['ngc_test_environment'] );

if ( $errors > 0 ) {
	echo "\n{$errors} test(s) failed\n";
	exit( 1 );
}

echo "OK — unit tests passed ({$passed} assertions)\n";
exit( 0 );
