<?php
/**
 * PayFast sandbox E2E — configure gateway, checkout redirect, ITN completion,
 * amount-tamper rejection, and replay idempotency.
 *
 * Usage (Docker):
 *   wp eval-file wp-content/plugins/NextGenTutors-Companion/scripts/payfast-e2e-docker.php --allow-root
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run inside WordPress.\n" );
	exit( 1 );
}

require_once dirname( __DIR__ ) . '/scripts/ngc-e2e-guard.php';
ngc_e2e_require_demo_stack( 'NextGen PayFast E2E' );

$GLOBALS['ngc_pf_errors'] = 0;
$GLOBALS['ngc_pf_checks'] = [];

/**
 * @param string $name   Check name.
 * @param bool   $ok     Pass.
 * @param string $detail Detail.
 */
function ngc_pf_assert( $name, $ok, $detail = '' ) {
	$GLOBALS['ngc_pf_checks'][] = [ 'name' => $name, 'ok' => (bool) $ok, 'detail' => $detail ];
	if ( ! $ok ) {
		++$GLOBALS['ngc_pf_errors'];
	}
}

/**
 * Sign ITN payload with gateway passphrase via public ITN helper.
 *
 * @param array<string, string> $payload Fields (no signature).
 * @param string                $passphrase Passphrase.
 * @return string
 */
function ngc_pf_sign( array $payload, $passphrase ) {
	return NGC_PayFast_Itn::generate_signature( $payload, $passphrase );
}

$sandbox = [
	'enabled'      => 'yes',
	'title'        => 'PayFast',
	'description'  => 'Pay securely with PayFast (sandbox).',
	'merchant_id'  => '10000100',
	'merchant_key' => '46f0cd694581a',
	'passphrase'   => 'jt7NOE43FZPn',
	'sandbox'      => 'yes',
];

ngc_pf_assert( 'woocommerce_active', class_exists( 'WooCommerce' ), class_exists( 'WooCommerce' ) ? 'ok' : 'install WooCommerce' );
ngc_pf_assert( 'payfast_itn_helper', class_exists( 'NGC_PayFast_Itn' ), '' );

update_option( 'woocommerce_ngc_payfast_settings', $sandbox );
update_option( 'woocommerce_default_gateway', 'ngc_payfast' );

ngc_pf_assert( 'ngc_payfast_class', class_exists( 'NGC_PayFast_Gateway' ), '' );

if ( ! class_exists( 'NGC_PayFast_Gateway' ) || ! class_exists( 'NGC_PayFast_Itn' ) ) {
	echo "NextGen PayFast E2E\nFAIL — PayFast classes missing\n";
	exit( 1 );
}

$gateway = new NGC_PayFast_Gateway();
ngc_pf_assert( 'gateway_enabled', 'yes' === $gateway->enabled, (string) $gateway->enabled );

$product_id = (int) get_option( 'ngc_payfast_e2e_product_id', 0 );
if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
	$product_id = wp_insert_post(
		[
			'post_type'   => 'product',
			'post_status' => 'publish',
			'post_title'  => 'E2E Lesson Credit',
		],
		true
	);
	if ( ! is_wp_error( $product_id ) && $product_id ) {
		wp_set_object_terms( $product_id, 'simple', 'product_type' );
		update_post_meta( $product_id, '_regular_price', '320' );
		update_post_meta( $product_id, '_price', '320' );
		update_option( 'ngc_payfast_e2e_product_id', $product_id );
	}
}

ngc_pf_assert( 'product_ready', $product_id > 0 && 'product' === get_post_type( $product_id ), (string) $product_id );

/**
 * @param int $product_id Product ID.
 * @return WC_Order|null
 */
function ngc_pf_create_order( $product_id ) {
	$order = wc_create_order();
	if ( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			$order->add_product( $product, 1 );
		}
	}
	$order->set_billing_first_name( 'E2E' );
	$order->set_billing_last_name( 'Parent' );
	$order->set_billing_email( 'payfast.e2e@test.local' );
	$order->set_currency( 'ZAR' );
	$order->calculate_totals();
	$order->save();
	return $order;
}

$order = ngc_pf_create_order( $product_id );
ngc_pf_assert( 'order_created', (bool) $order->get_id(), (string) $order->get_id() );

$result   = $gateway->process_payment( $order->get_id() );
$redirect = (string) ( $result['redirect'] ?? '' );
ngc_pf_assert( 'checkout_redirect', 'success' === ( $result['result'] ?? '' ), $result['result'] ?? '' );
ngc_pf_assert( 'sandbox_url', false !== strpos( $redirect, 'sandbox.payfast.co.za' ), $redirect );

$amount = number_format( (float) $order->get_total(), 2, '.', '' );
$itn    = [
	'm_payment_id'   => (string) $order->get_id(),
	'pf_payment_id'  => 'pf_e2e_' . wp_generate_password( 8, false ),
	'payment_status' => 'COMPLETE',
	'item_name'      => 'Order ' . $order->get_order_number(),
	'amount_gross'   => $amount,
	'merchant_id'    => $sandbox['merchant_id'],
];
$itn['signature'] = ngc_pf_sign( $itn, $sandbox['passphrase'] );

$itn_url = home_url( '/?wc-api=ngc_payfast_itn' );
// From WP-CLI / Docker network, localhost is not the web container.
if ( ( defined( 'WP_CLI' ) && WP_CLI ) || file_exists( '/.dockerenv' ) ) {
	$parsed = wp_parse_url( $itn_url );
	$path   = isset( $parsed['path'] ) ? $parsed['path'] : '/';
	$query  = isset( $parsed['query'] ) ? '?' . $parsed['query'] : '';
	$itn_url = 'http://wordpress' . $path . $query;
}

/**
 * @param string                $itn_url Endpoint.
 * @param array<string, string> $body    ITN payload.
 * @param int                   $retries Extra attempts after first failure.
 * @return array{code:int, body:string}
 */
function ngc_pf_post_itn( $itn_url, $body, $retries = 2 ) {
	$attempt = 0;
	$last    = [ 'code' => 0, 'body' => 'no attempt' ];
	while ( $attempt <= $retries ) {
		$http = wp_remote_post(
			$itn_url,
			[
				'timeout' => 45,
				'body'    => $body,
			]
		);
		if ( is_wp_error( $http ) ) {
			$last = [ 'code' => 0, 'body' => $http->get_error_message() ];
		} else {
			$last = [
				'code' => (int) wp_remote_retrieve_response_code( $http ),
				'body' => trim( wp_remote_retrieve_body( $http ) ),
			];
			if ( 200 === $last['code'] ) {
				return $last;
			}
		}
		++$attempt;
		if ( $attempt <= $retries ) {
			usleep( 500000 ); // 0.5s backoff between ITN posts under Docker load.
		}
	}
	return $last;
}

// --- Amount tamper: valid signature for wrong amount must not pay order ---
$order_tamper = ngc_pf_create_order( $product_id );
$itn_tamper   = [
	'm_payment_id'   => (string) $order_tamper->get_id(),
	'pf_payment_id'  => 'pf_tamper_' . wp_generate_password( 8, false ),
	'payment_status' => 'COMPLETE',
	'item_name'      => 'Order ' . $order_tamper->get_order_number(),
	'amount_gross'   => '1.00',
	'merchant_id'    => $sandbox['merchant_id'],
];
$itn_tamper['signature'] = ngc_pf_sign( $itn_tamper, $sandbox['passphrase'] );
$resp_tamper             = ngc_pf_post_itn( $itn_url, $itn_tamper );
$order_tamper            = wc_get_order( $order_tamper->get_id() );
ngc_pf_assert(
	'itn_rejects_amount_tamper',
	! ( $order_tamper && $order_tamper->is_paid() ),
	$resp_tamper['code'] . ' ' . $resp_tamper['body'] . ' status=' . ( $order_tamper ? $order_tamper->get_status() : 'missing' )
);

$resp1 = ngc_pf_post_itn( $itn_url, $itn );
ngc_pf_assert( 'itn_http_200', 200 === $resp1['code'] && 'OK' === $resp1['body'], $resp1['code'] . ' ' . $resp1['body'] );

$order = wc_get_order( $order->get_id() );
ngc_pf_assert( 'order_paid', $order && $order->is_paid(), $order ? $order->get_status() : 'missing' );

// Replay same pf_payment_id — must stay OK/idempotent, not double-apply side effects.
$resp_replay = ngc_pf_post_itn( $itn_url, $itn );
ngc_pf_assert(
	'itn_replay_idempotent',
	200 === $resp_replay['code'] && in_array( $resp_replay['body'], [ 'OK', 'DUPLICATE' ], true ),
	$resp_replay['code'] . ' ' . $resp_replay['body']
);

// Second order — confirms fresh notification still completes.
$order2  = ngc_pf_create_order( $product_id );
$amount2 = number_format( (float) $order2->get_total(), 2, '.', '' );
$itn2    = [
	'm_payment_id'   => (string) $order2->get_id(),
	'pf_payment_id'  => 'pf_http_' . wp_generate_password( 8, false ),
	'payment_status' => 'COMPLETE',
	'item_name'      => 'Order ' . $order2->get_order_number(),
	'amount_gross'   => $amount2,
	'merchant_id'    => $sandbox['merchant_id'],
];
$itn2['signature'] = ngc_pf_sign( $itn2, $sandbox['passphrase'] );

$resp2 = ngc_pf_post_itn( $itn_url, $itn2 );
ngc_pf_assert( 'itn_http_second', 200 === $resp2['code'] && 'OK' === $resp2['body'], $resp2['code'] . ' ' . $resp2['body'] );

$order2 = wc_get_order( $order2->get_id() );
ngc_pf_assert( 'itn_http_paid', $order2 && $order2->is_paid(), $order2 ? $order2->get_status() : '' );

echo "NextGen PayFast E2E\n";
echo str_repeat( '-', 44 ) . "\n";
foreach ( $GLOBALS['ngc_pf_checks'] as $c ) {
	echo ( $c['ok'] ? 'OK  ' : 'FAIL' ) . ' ' . $c['name'];
	if ( $c['detail'] ) {
		echo ' — ' . $c['detail'];
	}
	echo "\n";
}
echo str_repeat( '-', 44 ) . "\n";
$errors = (int) $GLOBALS['ngc_pf_errors'];
echo $errors ? "FAILED with {$errors} error(s)\n" : "OK — PayFast sandbox E2E passed\n";
exit( $errors ? 1 : 0 );
