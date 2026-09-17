<?php
/**
 * Session domain unit smoke (no PHPUnit binary required).
 *
 * @package NextGenCompanion
 */

require_once __DIR__ . '/phpunit/bootstrap.php';

$fail = 0;
$ok   = 0;

/**
 * @param string $label Label.
 * @param bool   $cond  Condition.
 */
function uassert( $label, $cond ) {
	global $fail, $ok;
	if ( $cond ) {
		echo "PASS $label\n";
		++$ok;
	} else {
		echo "FAIL $label\n";
		++$fail;
	}
}

uassert( 'draft→awaiting_payment', NGC_Session_State_Machine::can_transition( NGC_Session_States::DRAFT, NGC_Session_States::AWAITING_PAYMENT ) );
uassert( 'awaiting→paid', NGC_Session_State_Machine::can_transition( NGC_Session_States::AWAITING_PAYMENT, NGC_Session_States::PAID ) );
uassert( 'paid→booking_confirmed', NGC_Session_State_Machine::can_transition( NGC_Session_States::PAID, NGC_Session_States::BOOKING_CONFIRMED ) );
uassert( 'confirmed→provisioning', NGC_Session_State_Machine::can_transition( NGC_Session_States::BOOKING_CONFIRMED, NGC_Session_States::PROVISIONING ) );
uassert( 'provisioning→ready', NGC_Session_State_Machine::can_transition( NGC_Session_States::PROVISIONING, NGC_Session_States::READY ) );
uassert( 'ready→in_progress', NGC_Session_State_Machine::can_transition( NGC_Session_States::READY, NGC_Session_States::IN_PROGRESS ) );
uassert( 'in_progress→completed', NGC_Session_State_Machine::can_transition( NGC_Session_States::IN_PROGRESS, NGC_Session_States::COMPLETED ) );
uassert( 'draft↛completed', ! NGC_Session_State_Machine::can_transition( NGC_Session_States::DRAFT, NGC_Session_States::COMPLETED ) );
uassert( 'paid→provisioning', NGC_Session_State_Machine::can_transition( NGC_Session_States::PAID, NGC_Session_States::PROVISIONING ) );
uassert( 'join_window→refunded', NGC_Session_State_Machine::can_transition( NGC_Session_States::JOIN_WINDOW_OPEN, NGC_Session_States::REFUNDED ) );
uassert( 'in_progress→refunded', NGC_Session_State_Machine::can_transition( NGC_Session_States::IN_PROGRESS, NGC_Session_States::REFUNDED ) );
uassert( 'paid→refunded', NGC_Session_State_Machine::can_transition( NGC_Session_States::PAID, NGC_Session_States::REFUNDED ) );
uassert( 'provisioning→failed', NGC_Session_State_Machine::can_transition( NGC_Session_States::PROVISIONING, NGC_Session_States::FAILED ) );

$threw = false;
try {
	NGC_Session_State_Machine::assert( NGC_Session_States::READY, NGC_Session_States::PAID );
} catch ( NGC_Session_Transition_Exception $e ) {
	$threw = true;
}
uassert( 'invalid transition throws', $threw );

$early = NGC_Session_Join_Policy::evaluate(
	[
		'status'          => NGC_Session_States::READY,
		'payment_status'  => 'paid',
		'scheduled_start' => gmdate( 'Y-m-d H:i:s', time() + 8 * 60 ),
		'scheduled_end'   => gmdate( 'Y-m-d H:i:s', time() + 68 * 60 ),
	],
	time()
);
uassert( 'too_early denied', false === $early['allowed'] && 'too_early' === $early['reason'] );

$open = NGC_Session_Join_Policy::evaluate(
	[
		'status'          => NGC_Session_States::READY,
		'payment_status'  => 'paid',
		'scheduled_start' => gmdate( 'Y-m-d H:i:s', time() + 3 * 60 ),
		'scheduled_end'   => gmdate( 'Y-m-d H:i:s', time() + 63 * 60 ),
	],
	time()
);
uassert( 'join open within 5 min', ! empty( $open['allowed'] ) );

$unpaid = NGC_Session_Join_Policy::evaluate(
	[
		'status'         => NGC_Session_States::AWAITING_PAYMENT,
		'payment_status' => 'unpaid',
		'scheduled_start'=> gmdate( 'Y-m-d H:i:s', time() + 60 ),
	],
	time()
);
uassert( 'unpaid denied', 'payment_required' === $unpaid['reason'] );

$late = NGC_Session_Join_Policy::evaluate(
	[
		'status'          => NGC_Session_States::READY,
		'payment_status'  => 'paid',
		'scheduled_start' => gmdate( 'Y-m-d H:i:s', time() - 90 * 60 ),
		'scheduled_end'   => gmdate( 'Y-m-d H:i:s', time() - 30 * 60 ),
	],
	time()
);
uassert( 'too_late denied', 'too_late' === $late['reason'] );

$defs = NGC_Product_Catalog::definitions();
uassert( '16 official products', 16 === count( $defs ) );
uassert( 'online 1hr price 320', '320' === $defs['NGT-ONLINE-1HR']['price'] );
uassert( 'resolve online single', 'NGT-ONLINE-1HR' === NGC_Product_Catalog::resolve_key( 'online', 1 ) );
uassert( 'price match', NGC_Product_Catalog::price_matches( 'NGT-ONLINE-1HR', '320' ) );
uassert( 'price tamper rejected', ! NGC_Product_Catalog::price_matches( 'NGT-ONLINE-1HR', '1' ) );

$tamper = false;
try {
	NGC_Session_Price_Integrity::validate(
		[
			'product_key'     => 'NGT-ONLINE-1HR',
			'price'           => '1.00',
			'tutor_user_id'   => 1,
			'student_user_id' => 2,
			'subject_id'      => 'mathematics',
		]
	);
} catch ( NGC_Session_Validation_Exception $e ) {
	$tamper = 'ngc_price_tamper' === $e->get_error_code();
}
uassert( 'price integrity tamper', $tamper );

$mismatch = false;
try {
	NGC_Session_Price_Integrity::validate(
		[
			'product_key'           => 'NGT-ONLINE-1HR',
			'tutor_user_id'         => 10,
			'booking_tutor_user_id' => 11,
			'student_user_id'       => 2,
			'subject_id'            => 'mathematics',
		]
	);
} catch ( NGC_Session_Validation_Exception $e ) {
	$mismatch = 'ngc_tutor_mismatch' === $e->get_error_code();
}
uassert( 'tutor mismatch', $mismatch );

$subj = false;
try {
	NGC_Session_Price_Integrity::validate(
		[
			'product_key'     => 'NGT-ONLINE-1HR',
			'tutor_user_id'   => 10,
			'student_user_id' => 2,
			'subject_id'      => 'mathematics',
			'booking_subject' => 'english',
		]
	);
} catch ( NGC_Session_Validation_Exception $e ) {
	$subj = 'ngc_subject_mismatch' === $e->get_error_code();
}
uassert( 'subject mismatch', $subj );

$id = NGC_Session_Correlation::generate( strtotime( '2026-08-09 12:00:00 UTC' ) );
uassert( 'correlation format', NGC_Session_Correlation::is_valid( $id ) && 0 === strpos( $id, 'NGT-SES-20260809-' ) );
uassert( 'idempotency key', 'session:order:9:booking:4' === NGC_Session_Correlation::idempotency_key( 9, 4 ) );

$parties = NGC_Session_Identity::resolve_parties( 50, 50 );
uassert( 'adult self purchase', 'adult_student_self_purchase' === $parties['mode'] && 50 === $parties['customer_user_id'] );

$leaky = (object) [
	'id'       => 12,
	'meta'     => wp_json_encode(
		[
			'meeting' => [
				'provider' => 'jitsi',
				'room'     => 'ngt-secret-room',
				'join_url' => 'https://meet.jit.si/ngt-secret-room',
			],
		]
	),
	'join_url' => 'https://meet.jit.si/leaked',
];
$safe = NGC_Session_Presenter::sanitize_booking_for_rest( $leaky );
$json = wp_json_encode( $safe );
uassert( 'rest booking redacts join url', false === strpos( $json, 'meet.jit.si' ) && false === strpos( $json, 'ngt-secret-room' ) );
uassert( 'rest booking keeps provider', isset( $safe->meta['meeting']['provider'] ) && 'jitsi' === $safe->meta['meeting']['provider'] );

$cart = NGC_Session_Checkout::collect_untrusted_context(
	[
		'tutor_user_id'   => '10',
		'student_user_id' => '2',
		'subject_id'      => 'mathematics',
		'price'           => '1.00',
		'join_url'        => 'https://evil.example/join',
		'ngt_product_key' => 'NGT-ONLINE-1HR',
	]
);
uassert( 'cart drops client price', ! isset( $cart['price'] ) && ! isset( $cart['join_url'] ) );
$cart_ok = NGC_Session_Price_Integrity::validate( $cart );
uassert( 'cart uses catalogue price', '320' === $cart_ok['price'] );

$snap = NGC_Session_Checkout::to_cart_item_data(
	[
		'tutor_user_id' => 10,
		'product_key'   => 'NGT-ONLINE-1HR',
		'join_url'      => 'https://meet.jit.si/nope',
		'price'         => '1.00',
	]
);
uassert( 'cart snapshot omits secrets', ! isset( $snap['ngt_join_url'] ) && ! isset( $snap['ngt_price'] ) && 1 === $snap['_ngt_validated'] );
uassert( 'non-catalogue product has empty key', '' === NGC_Product_Catalog::key_for_product( 0 ) );

echo "\n$ok passed, $fail failed\n";
exit( $fail ? 1 : 0 );
