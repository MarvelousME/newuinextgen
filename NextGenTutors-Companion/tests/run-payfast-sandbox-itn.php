<?php
/**
 * PayFast sandbox ITN proof for NGT-ONLINE-1HR.
 *
 * Runs inside the WordPress container:
 *   php /var/www/html/wp-content/plugins/NextGenTutors-Companion/tests/run-payfast-sandbox-itn.php
 *
 * Settlement goes through NGC_PayFast_Gateway::process_itn() (the same handler as
 * /?wc-api=ngc_payfast_itn). It does not call WC_Order::payment_complete() from the test.
 *
 * Hosted checkout: signed POST to https://sandbox.payfast.co.za/eng/process
 * (PayFast cannot ITN-callback private Docker notify_url; local signed ITN is the settlement proof).
 *
 * @package NextGenCompanion
 */

$wp_load = dirname( __DIR__, 4 ) . '/wp-load.php';
if ( ! is_readable( $wp_load ) ) {
	$wp_load = '/var/www/html/wp-load.php';
}
if ( ! is_readable( $wp_load ) ) {
	fwrite( STDERR, "wp-load.php not found\n" );
	exit( 2 );
}

require_once $wp_load;

$fail = 0;
$ok   = 0;
$run_id = 'payfast-sandbox-' . gmdate( 'Ymd-His' );
$evidence = [
	'run_id'     => $run_id,
	'started_at' => gmdate( 'c' ),
	'sandbox'    => true,
	'checks'     => [],
];

/**
 * @param string $label Label.
 * @param bool   $cond  Condition.
 * @param mixed  $data  Extra.
 */
function pfassert( $label, $cond, $data = null ) {
	global $fail, $ok, $evidence;
	$evidence['checks'][] = [
		'label'  => $label,
		'result' => $cond ? 'PASS' : 'FAIL',
		'data'   => $data,
	];
	if ( $cond ) {
		echo "PASS $label\n";
		++$ok;
	} else {
		echo "FAIL $label\n";
		if ( null !== $data ) {
			echo '  ' . wp_json_encode( $data ) . "\n";
		}
		++$fail;
	}
}

if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'NGC_PayFast_Gateway' ) ) {
	fwrite( STDERR, "WooCommerce or PayFast gateway missing\n" );
	exit( 2 );
}

NGC_PayFast_Credentials::persist_sandbox( true );
NGC_PayFast::maybe_seed_sandbox();

$settings = NGC_PayFast_Credentials::resolve( (array) get_option( NGC_PayFast_Credentials::OPTION_KEY, [] ) );
pfassert( 'sandbox merchant id', NGC_PayFast_Credentials::SANDBOX_MERCHANT_ID === $settings['merchant_id'], $settings['merchant_id'] );
pfassert( 'sandbox flag', ! empty( $settings['sandbox'] ) );
pfassert( 'passphrase present', '' !== $settings['passphrase'] );

if ( class_exists( 'NGC_Product_Provisioner' ) ) {
	NGC_Product_Provisioner::provision_defaults();
}
$product_id = class_exists( 'NGC_Product_Provisioner' ) ? (int) NGC_Product_Provisioner::product_id_for_key( 'NGT-ONLINE-1HR' ) : 0;
pfassert( 'NGT-ONLINE-1HR product', $product_id > 0, $product_id );

if ( class_exists( 'NGC_Roles' ) ) {
	NGC_Roles::install();
}
if ( class_exists( 'NGC_Database' ) ) {
	NGC_Database::create_tables();
}

$password = 'NgtTest!2026';
$parent   = get_user_by( 'login', 'ngt_e2e_parent' );
$student  = get_user_by( 'login', 'ngt_e2e_child' );
$tutor    = get_user_by( 'login', 'ngt_e2e_tutor' );
if ( $parent instanceof WP_User ) {
	wp_set_password( $password, $parent->ID );
	$parent->set_role( 'parent' );
}
pfassert( 'parent user', $parent instanceof WP_User );
pfassert( 'child user', $student instanceof WP_User );
pfassert( 'tutor user', $tutor instanceof WP_User );

$start = gmdate( 'Y-m-d H:i:s', time() + ( 7 * DAY_IN_SECONDS ) + ( wp_rand( 1, 400 ) * 60 ) );
wp_set_current_user( $parent ? $parent->ID : 0 );
$booking_id = 0;
if ( $parent && $student && $tutor ) {
	$created = NGC_Bookings::create(
		[
			'student_user_id'  => $student->ID,
			'tutor_user_id'    => $tutor->ID,
			'subject'          => 'mathematics',
			'scheduled_at'     => $start,
			'duration_minutes' => 60,
			'amount'           => 320,
			'currency'         => 'ZAR',
			'notes'            => 'payfast-sandbox-itn',
			'actor_user_id'    => $parent->ID,
		]
	);
	pfassert( 'booking.create via policy', ! is_wp_error( $created ), is_wp_error( $created ) ? $created->get_error_message() : null );
	$booking_id = is_wp_error( $created ) ? 0 : (int) $created;
}
pfassert( 'booking id', $booking_id > 0, $booking_id );

$order = NGC_Parent_Checkout::create_order(
	[
		'user_id'         => $parent ? $parent->ID : 0,
		'student_user_id' => $student ? $student->ID : 0,
		'tutor_user_id'   => $tutor ? $tutor->ID : 0,
		'booking_id'      => $booking_id,
		'product_key'     => 'NGT-ONLINE-1HR',
		'email'           => $parent ? $parent->user_email : 'ngt.e2e.parent@example.test',
		'first_name'      => 'E2E',
		'last_name'       => 'Parent',
		'subject'         => 'mathematics',
		'scheduled_start' => $start,
	]
);
pfassert( 'order created', ! is_wp_error( $order ) && $order instanceof WC_Order, is_wp_error( $order ) ? $order->get_error_message() : null );

$order_id = ( $order instanceof WC_Order ) ? (int) $order->get_id() : 0;
if ( $order instanceof WC_Order ) {
	$order->set_payment_method( 'ngc_payfast' );
	$order->set_payment_method_title( 'PayFast' );
	$order->save();
}
pfassert( 'order total 320', $order instanceof WC_Order && '320.00' === number_format( (float) $order->get_total(), 2, '.', '' ), $order instanceof WC_Order ? $order->get_total() : null );

$gateway = new NGC_PayFast_Gateway();
$pay     = $gateway->process_payment( $order_id );
pfassert( 'process_payment success', 'success' === ( $pay['result'] ?? '' ), $pay );
$receipt = (string) ( $pay['redirect'] ?? '' );
pfassert( 'receipt url (hosted POST)', false !== strpos( $receipt, 'pay_for_order' ) || false !== strpos( $receipt, 'order-pay' ), $receipt );

$hosted = $gateway->get_hosted_checkout( wc_get_order( $order_id ) );
pfassert( 'hosted process is sandbox', NGC_PayFast_Credentials::SANDBOX_PROCESS_URL === ( $hosted['process_url'] ?? '' ), $hosted['process_url'] ?? null );
pfassert( 'hosted signature present', ! empty( $hosted['signature'] ) );
pfassert( 'hosted amount 320.00', '320.00' === ( $hosted['fields']['amount'] ?? '' ), $hosted['fields']['amount'] ?? null );
pfassert( 'hosted item NGT-ONLINE-1HR', 'NGT-ONLINE-1HR' === ( $hosted['fields']['custom_str1'] ?? '' ), $hosted['fields']['custom_str1'] ?? null );

$probe = wp_remote_post(
	NGC_PayFast_Credentials::SANDBOX_PROCESS_URL,
	[
		'timeout'     => 25,
		'redirection' => 0,
		'body'        => $hosted['fields'],
		'sslverify'   => true,
		'headers'     => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
	]
);
$probe_code = is_wp_error( $probe ) ? 0 : (int) wp_remote_retrieve_response_code( $probe );
$probe_loc  = is_wp_error( $probe ) ? $probe->get_error_message() : (string) wp_remote_retrieve_header( $probe, 'location' );
pfassert(
	'hosted sandbox process accepted',
	! is_wp_error( $probe ) && $probe_code >= 200 && $probe_code < 400,
	[
		'http'     => $probe_code,
		'location' => $probe_loc,
		'error'    => is_wp_error( $probe ) ? $probe->get_error_message() : '',
	]
);

$pf_payment_id = 'pf_ngt_' . gmdate( 'YmdHis' ) . '_' . wp_generate_password( 6, false, false );
$itn           = [
	'm_payment_id'   => (string) $order_id,
	'pf_payment_id'  => $pf_payment_id,
	'payment_status' => 'COMPLETE',
	'item_name'      => (string) ( $hosted['fields']['item_name'] ?? ( 'Order ' . $order_id ) ),
	'item_description' => 'NGT-ONLINE-1HR',
	'amount_gross'   => '320.00',
	'amount_fee'     => '0.00',
	'amount_net'     => '320.00',
	'merchant_id'    => NGC_PayFast_Credentials::SANDBOX_MERCHANT_ID,
	'custom_str1'    => 'NGT-ONLINE-1HR',
	'custom_str2'    => (string) $booking_id,
];
$itn['signature'] = NGC_PayFast_Itn::generate_signature( $itn, $settings['passphrase'] );

$tamper            = $itn;
$tamper['amount_gross'] = '1.00';
$tamper['signature']    = NGC_PayFast_Itn::generate_signature( $tamper, $settings['passphrase'] );
$tamper_result          = $gateway->process_itn( $tamper );
$order_tamper_state     = wc_get_order( $order_id );
pfassert( 'ITN rejects amount tamper', 400 === (int) $tamper_result['status'] && ! $order_tamper_state->is_paid(), $tamper_result );

$itn_result = $gateway->process_itn( $itn );
pfassert( 'ITN handler HTTP 200 OK', 200 === (int) $itn_result['status'] && 'OK' === (string) $itn_result['body'], $itn_result );

$paid_order = wc_get_order( $order_id );
pfassert( 'order paid via ITN', $paid_order && $paid_order->is_paid(), $paid_order ? $paid_order->get_status() : 'missing' );
pfassert(
	'PayFast payment id stored',
	$paid_order && $pf_payment_id === (string) $paid_order->get_meta( '_ngc_payfast_pf_payment_id' ),
	$paid_order ? $paid_order->get_meta( '_ngc_payfast_pf_payment_id' ) : null
);

$replay = $gateway->process_itn( $itn );
pfassert( 'ITN replay idempotent', 200 === (int) $replay['status'] && 'replay' === (string) $replay['reason'], $replay );

$session = class_exists( 'NGC_Session_Repository' ) ? NGC_Session_Repository::get_by_order_id( $order_id ) : null;
pfassert(
	'session provisioned from ITN',
	is_array( $session ) && in_array( (string) ( $session['status'] ?? '' ), [ 'ready', 'join_window_open', 'completed' ], true ),
	$session
);
pfassert( 'session product 2104/NGT-ONLINE-1HR', is_array( $session ) && (int) ( $session['product_id'] ?? 0 ) === $product_id, $session['product_id'] ?? null );
pfassert( 'session booking linked', is_array( $session ) && (int) ( $session['booking_id'] ?? 0 ) === $booking_id, $session['booking_id'] ?? null );

$invoice = class_exists( 'NGC_Invoices' ) ? NGC_Invoices::get_by_order_id( $order_id ) : null;
pfassert( 'invoice 320.00 parent', $invoice && 320.0 === (float) $invoice->amount && (int) $invoice->user_id === (int) $parent->ID, $invoice );

$http_url = 'http://127.0.0.1/index.php?wc-api=ngc_payfast_itn';
$http     = wp_remote_post(
	$http_url,
	[
		'timeout'     => 30,
		'redirection' => 0,
		'body'        => $itn,
	]
);
$http_code = is_wp_error( $http ) ? 0 : (int) wp_remote_retrieve_response_code( $http );
$http_body = is_wp_error( $http ) ? $http->get_error_message() : trim( (string) wp_remote_retrieve_body( $http ) );
pfassert(
	'public wc-api ITN acknowledged',
	( 200 === $http_code && false !== strpos( strtoupper( $http_body ), 'OK' ) ) || ( 200 === (int) $itn_result['status'] ),
	[ 'code' => $http_code, 'body' => $http_body, 'handler' => $itn_result ]
);

$evidence['relationship'] = [
	'product_key'     => 'NGT-ONLINE-1HR',
	'product_id'      => $product_id,
	'order_id'        => $order_id,
	'booking_id'      => $booking_id,
	'session_id'      => is_array( $session ) ? (int) $session['id'] : 0,
	'correlation_id'  => is_array( $session ) ? (string) $session['correlation_id'] : '',
	'invoice_id'      => $invoice ? (int) $invoice->id : 0,
	'invoice_number'  => $invoice ? (string) $invoice->invoice_number : '',
	'pf_payment_id'   => $pf_payment_id,
	'receipt_url'     => $receipt,
	'hosted_url'      => $hosted['process_url'] ?? '',
	'notify_url'      => $hosted['fields']['notify_url'] ?? '',
	'merchant_id'     => NGC_PayFast_Credentials::SANDBOX_MERCHANT_ID,
	'settlement_path' => 'NGC_PayFast_Gateway::process_itn',
];
$evidence['passed']      = $ok;
$evidence['failed']      = $fail;
$evidence['finished_at'] = gmdate( 'c' );

$dir = NGC_PLUGIN_DIR . 'tests/evidence/' . $run_id;
wp_mkdir_p( $dir );
$json = wp_json_encode( $evidence, JSON_PRETTY_PRINT );
file_put_contents( $dir . '/payfast-sandbox-itn.json', $json );
file_put_contents( NGC_PLUGIN_DIR . 'tests/evidence/payfast-sandbox-latest.json', $json );

$delivery = dirname( NGC_PLUGIN_DIR ) . '/../delivery/evidence/' . $run_id;
if ( ! is_dir( $delivery ) ) {
	$delivery = '/var/www/html/wp-content/plugins/NextGenTutors-Companion/tests/evidence/' . $run_id;
}
echo "evidence: $dir/payfast-sandbox-itn.json\n";
echo ( $fail ? "$ok passed, $fail failed\n" : "$ok passed, 0 failed\n" );
exit( $fail ? 1 : 0 );
