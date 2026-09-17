<?php
/**
 * Session lifecycle unit tests (no WordPress I/O).
 *
 * @package NextGenCompanion
 */

use PHPUnit\Framework\TestCase;

/**
 * State machine, join window, catalogue, price integrity, correlation.
 */
class SessionLifecycleTest extends TestCase {

	public function test_happy_path_transitions() {
		$path = [
			NGC_Session_States::DRAFT,
			NGC_Session_States::AWAITING_PAYMENT,
			NGC_Session_States::PAID,
			NGC_Session_States::BOOKING_CONFIRMED,
			NGC_Session_States::PROVISIONING,
			NGC_Session_States::READY,
			NGC_Session_States::JOIN_WINDOW_OPEN,
			NGC_Session_States::IN_PROGRESS,
			NGC_Session_States::COMPLETED,
		];
		for ( $i = 0; $i < count( $path ) - 1; $i++ ) {
			$this->assertTrue(
				NGC_Session_State_Machine::can_transition( $path[ $i ], $path[ $i + 1 ] ),
				$path[ $i ] . ' → ' . $path[ $i + 1 ]
			);
		}
	}

	public function test_invalid_transition_rejected() {
		$this->assertFalse( NGC_Session_State_Machine::can_transition( NGC_Session_States::DRAFT, NGC_Session_States::COMPLETED ) );
		$this->expectException( NGC_Session_Transition_Exception::class );
		NGC_Session_State_Machine::assert( NGC_Session_States::READY, NGC_Session_States::PAID );
	}

	public function test_refund_and_cancel_paths() {
		$this->assertTrue( NGC_Session_State_Machine::can_transition( NGC_Session_States::AWAITING_PAYMENT, NGC_Session_States::CANCELLED ) );
		$this->assertTrue( NGC_Session_State_Machine::can_transition( NGC_Session_States::PAID, NGC_Session_States::REFUNDED ) );
		$this->assertTrue( NGC_Session_State_Machine::can_transition( NGC_Session_States::READY, NGC_Session_States::CANCELLED ) );
		$this->assertTrue( NGC_Session_State_Machine::can_transition( NGC_Session_States::PROVISIONING, NGC_Session_States::FAILED ) );
		$this->assertTrue( NGC_Session_State_Machine::can_transition( NGC_Session_States::PAID, NGC_Session_States::PROVISIONING ) );
		$this->assertTrue( NGC_Session_State_Machine::can_transition( NGC_Session_States::JOIN_WINDOW_OPEN, NGC_Session_States::REFUNDED ) );
		$this->assertTrue( NGC_Session_State_Machine::can_transition( NGC_Session_States::IN_PROGRESS, NGC_Session_States::REFUNDED ) );
		$this->assertFalse( NGC_Session_State_Machine::can_transition( NGC_Session_States::COMPLETED, NGC_Session_States::READY ) );
	}

	public function test_join_window_too_early() {
		$start = gmdate( 'Y-m-d H:i:s', time() + 8 * 60 );
		$end   = gmdate( 'Y-m-d H:i:s', time() + 68 * 60 );
		$out   = NGC_Session_Join_Policy::evaluate(
			[
				'status'          => NGC_Session_States::READY,
				'payment_status'  => 'paid',
				'scheduled_start' => $start,
				'scheduled_end'   => $end,
			],
			time()
		);
		$this->assertFalse( $out['allowed'] );
		$this->assertSame( 'too_early', $out['reason'] );
		$this->assertStringContainsString( 'Lesson starts in', $out['label'] );
	}

	public function test_join_window_open_within_five_minutes() {
		$start = gmdate( 'Y-m-d H:i:s', time() + 3 * 60 );
		$end   = gmdate( 'Y-m-d H:i:s', time() + 63 * 60 );
		$out   = NGC_Session_Join_Policy::evaluate(
			[
				'status'          => NGC_Session_States::READY,
				'payment_status'  => 'paid',
				'scheduled_start' => $start,
				'scheduled_end'   => $end,
			],
			time()
		);
		$this->assertTrue( $out['allowed'] );
		$this->assertSame( 'JOIN LESSON', $out['label'] );
	}

	public function test_join_denied_when_unpaid() {
		$out = NGC_Session_Join_Policy::evaluate(
			[
				'status'          => NGC_Session_States::AWAITING_PAYMENT,
				'payment_status'  => 'unpaid',
				'scheduled_start' => gmdate( 'Y-m-d H:i:s', time() + 60 ),
			],
			time()
		);
		$this->assertFalse( $out['allowed'] );
		$this->assertSame( 'payment_required', $out['reason'] );
	}

	public function test_join_closed_after_window() {
		$start = gmdate( 'Y-m-d H:i:s', time() - 90 * 60 );
		$end   = gmdate( 'Y-m-d H:i:s', time() - 30 * 60 );
		$out   = NGC_Session_Join_Policy::evaluate(
			[
				'status'          => NGC_Session_States::READY,
				'payment_status'  => 'paid',
				'scheduled_start' => $start,
				'scheduled_end'   => $end,
			],
			time()
		);
		$this->assertFalse( $out['allowed'] );
		$this->assertSame( 'too_late', $out['reason'] );
	}

	public function test_product_catalog_official_skus() {
		$defs = NGC_Product_Catalog::definitions();
		$this->assertArrayHasKey( 'NGT-ONLINE-1HR', $defs );
		$this->assertSame( '320', $defs['NGT-ONLINE-1HR']['price'] );
		$this->assertCount( 16, $defs );
		$this->assertSame( 'NGT-ONLINE-1HR', NGC_Product_Catalog::resolve_key( 'online', 1 ) );
		$this->assertTrue( NGC_Product_Catalog::price_matches( 'NGT-ONLINE-1HR', '320' ) );
		$this->assertFalse( NGC_Product_Catalog::price_matches( 'NGT-ONLINE-1HR', '1' ) );
	}

	public function test_price_integrity_rejects_tamper() {
		$this->expectException( NGC_Session_Validation_Exception::class );
		NGC_Session_Price_Integrity::validate(
			[
				'product_key'      => 'NGT-ONLINE-1HR',
				'price'            => '1.00',
				'tutor_user_id'    => 1,
				'student_user_id'  => 2,
				'subject_id'       => 'mathematics',
				'duration_minutes' => 60,
				'session_count'    => 1,
			]
		);
	}

	public function test_price_integrity_rejects_tutor_mismatch() {
		$this->expectException( NGC_Session_Validation_Exception::class );
		NGC_Session_Price_Integrity::validate(
			[
				'product_key'           => 'NGT-ONLINE-1HR',
				'tutor_user_id'         => 10,
				'booking_tutor_user_id' => 11,
				'student_user_id'       => 2,
				'subject_id'            => 'mathematics',
			]
		);
	}

	public function test_price_integrity_rejects_subject_mismatch() {
		$this->expectException( NGC_Session_Validation_Exception::class );
		NGC_Session_Price_Integrity::validate(
			[
				'product_key'      => 'NGT-ONLINE-1HR',
				'tutor_user_id'    => 10,
				'student_user_id'  => 2,
				'subject_id'       => 'mathematics',
				'booking_subject'  => 'english',
			]
		);
	}

	public function test_correlation_format() {
		$id = NGC_Session_Correlation::generate( strtotime( '2026-08-09 12:00:00 UTC' ) );
		$this->assertTrue( NGC_Session_Correlation::is_valid( $id ) );
		$this->assertStringStartsWith( 'NGT-SES-20260809-', $id );
		$this->assertSame( 'session:order:9:booking:4', NGC_Session_Correlation::idempotency_key( 9, 4 ) );
	}

	public function test_identity_adult_self_purchase() {
		$parties = NGC_Session_Identity::resolve_parties( 50, 50 );
		$this->assertSame( 'adult_student_self_purchase', $parties['mode'] );
		$this->assertSame( 50, $parties['customer_user_id'] );
	}

	public function test_idempotent_product_keys_stable() {
		$a = array_keys( NGC_Product_Catalog::definitions() );
		$b = array_keys( NGC_Product_Catalog::definitions() );
		$this->assertSame( $a, $b );
	}

	public function test_booking_rest_payload_redacts_join_url() {
		$booking = (object) [
			'id'   => 12,
			'meta' => wp_json_encode(
				[
					'meeting' => [
						'provider' => 'jitsi',
						'room'     => 'ngt-secret-room',
						'join_url' => 'https://meet.jit.si/ngt-secret-room',
					],
					'source'  => 'woocommerce',
				]
			),
			'join_url' => 'https://meet.jit.si/leaked',
		];
		$safe = NGC_Session_Presenter::sanitize_booking_for_rest( $booking );
		$json = wp_json_encode( $safe );
		$this->assertStringNotContainsString( 'meet.jit.si', $json );
		$this->assertStringNotContainsString( 'ngt-secret-room', $json );
		$this->assertArrayNotHasKey( 'join_url', $safe->meta['meeting'] );
		$this->assertSame( 'jitsi', $safe->meta['meeting']['provider'] );
		$this->assertSame( 'woocommerce', $safe->meta['source'] );
		$array_safe = NGC_Session_Presenter::sanitize_booking_for_rest(
			[
				'id'       => 12,
				'meta'     => [ 'meeting' => [ 'join_url' => 'https://meet.jit.si/array-leak' ] ],
				'join_url' => 'https://meet.jit.si/leaked',
			]
		);
		$this->assertArrayNotHasKey( 'join_url', $array_safe );
		$this->assertArrayNotHasKey( 'join_url', $array_safe['meta']['meeting'] );
		$this->assertSame( '', NGC_Product_Catalog::key_for_product( 0 ) );
	}

	public function test_cart_collect_drops_unknown_fields() {
		$args = NGC_Session_Checkout::collect_untrusted_context(
			[
				'tutor_user_id'   => '10',
				'student_user_id' => '2',
				'subject_id'      => 'mathematics',
				'price'           => '1.00',
				'join_url'        => 'https://evil.example/join',
				'ngt_product_key' => 'NGT-ONLINE-1HR',
			]
		);
		$this->assertSame( '10', $args['tutor_user_id'] );
		$this->assertSame( 'NGT-ONLINE-1HR', $args['product_key'] );
		$this->assertArrayNotHasKey( 'price', $args );
		$this->assertArrayNotHasKey( 'join_url', $args );
		$validated = NGC_Session_Price_Integrity::validate( $args );
		$this->assertSame( '320', $validated['price'] );
	}

	public function test_cart_item_snapshot_only_stores_validated_fields() {
		$snap = NGC_Session_Checkout::to_cart_item_data(
			[
				'booking_id'      => 9,
				'tutor_user_id'   => 10,
				'student_user_id' => 2,
				'product_key'     => 'NGT-ONLINE-1HR',
				'join_url'        => 'https://meet.jit.si/nope',
				'price'           => '1.00',
			]
		);
		$this->assertSame( 1, $snap['_ngt_validated'] );
		$this->assertSame( 10, $snap['ngt_tutor_user_id'] );
		$this->assertArrayNotHasKey( 'ngt_join_url', $snap );
		$this->assertArrayNotHasKey( 'ngt_price', $snap );
	}
}
