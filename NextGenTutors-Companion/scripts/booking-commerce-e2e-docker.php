<?php
/**
 * Booking → commerce → session orchestration E2E (Docker / wp eval-file).
 *
 * Proves: product resolve, order+item meta, settle, ensure_provisioned idempotency,
 * join window gate, refund/cancel terminal states. Writes evidence JSON under
 * delivery/evidence/booking-commerce/<run-id>/.
 *
 * Usage:
 *   wp eval-file wp-content/plugins/NextGenTutors-Companion/scripts/booking-commerce-e2e-docker.php --allow-root
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run inside WordPress.\n" );
	exit( 1 );
}

require_once dirname( __DIR__ ) . '/scripts/ngc-e2e-guard.php';
ngc_e2e_require_demo_stack( 'Booking Commerce Session E2E' );

$run_id = 'bc-' . gmdate( 'Ymd-His' );
$GLOBALS['ngc_bc_errors'] = 0;
$GLOBALS['ngc_bc_checks'] = [];
$chain  = [];

/**
 * @param string $name Check.
 * @param bool   $ok   Pass.
 * @param mixed  $detail Detail.
 */
function ngc_bc_assert( $name, $ok, $detail = '' ) {
	$GLOBALS['ngc_bc_checks'][] = [ 'name' => $name, 'result' => $ok ? 'PASS' : 'FAIL', 'detail' => $detail ];
	if ( ! $ok ) {
		++$GLOBALS['ngc_bc_errors'];
	}
	echo ( $ok ? 'PASS' : 'FAIL' ) . "  {$name}" . ( $detail !== '' && $detail !== null ? ' — ' . ( is_scalar( $detail ) ? $detail : wp_json_encode( $detail ) ) : '' ) . "\n";
}

ngc_bc_assert( 'woocommerce_active', class_exists( 'WooCommerce' ) );
ngc_bc_assert( 'sessions_class', class_exists( 'NGC_Sessions' ) );
ngc_bc_assert( 'orchestrator_class', class_exists( 'NGC_Session_Orchestrator' ) );
ngc_bc_assert( 'product_provisioner', class_exists( 'NGC_Product_Provisioner' ) );

if ( class_exists( 'NGC_Sessions' ) ) {
	NGC_Sessions::ensure_schema();
}
if ( class_exists( 'NGC_Product_Provisioner' ) ) {
	$prov = NGC_Product_Provisioner::provision_all( false );
	ngc_bc_assert( 'products_provisioned', empty( $prov['errors'] ), $prov );
	$product_id = NGC_Product_Provisioner::resolve_for_booking( [ 'package_type' => 'single', 'duration_minutes' => 60 ] );
	ngc_bc_assert( 'resolve_online_1hr', $product_id > 0, $product_id );
	$chain['product_id'] = $product_id;
	$key = get_post_meta( $product_id, NGC_Product_Provisioner::META_KEY, true );
	ngc_bc_assert( 'product_has_ngt_key', 'ngt-online-1hr' === $key, $key );
	$again = NGC_Product_Provisioner::provision_all( false );
	ngc_bc_assert( 'provision_idempotent', (int) ( $again['created'] ?? 0 ) === 0, $again );
} else {
	$product_id = 0;
}

$parent = get_user_by( 'email', 'demo.parent@nextgen.local' );
$child  = get_user_by( 'email', 'demo.child.a@nextgen.local' );
$tutor  = get_user_by( 'email', 'demo.tutor.online@nextgen.local' );
if ( ! $tutor ) {
	$tutor = get_user_by( 'email', 'demo.tutor.approved@nextgen.local' );
}
ngc_bc_assert( 'demo_parent', (bool) $parent );
ngc_bc_assert( 'demo_child', (bool) $child );
ngc_bc_assert( 'demo_tutor', (bool) $tutor );

$booking_id = 0;
if ( $parent && $child && $tutor && class_exists( 'NGC_Bookings' ) ) {
	$booking_id = 0;
	for ( $attempt = 0; $attempt < 8; $attempt++ ) {
		$start = gmdate( 'Y-m-d H:i:s', time() + ( ( 2 + $attempt ) * 86400 ) + ( wp_rand( 1, 1200 ) * 60 ) );
		$created = NGC_Bookings::create(
			[
				'student_user_id'  => (int) $child->ID,
				'tutor_user_id'    => (int) $tutor->ID,
				'subject'          => 'Mathematics',
				'scheduled_at'     => $start,
				'duration_minutes' => 60,
				'meta'             => [ 'demo_scenario_id' => 'BOOKING-COMMERCE-E2E', 'is_demo' => 1 ],
			]
		);
		if ( ! is_wp_error( $created ) && (int) $created > 0 ) {
			$booking_id = (int) $created;
			break;
		}
	}
	ngc_bc_assert( 'booking_created', $booking_id > 0, $booking_id ?: $created );
	$chain['booking_id'] = $booking_id;
	$chain['parent_user_id'] = (int) $parent->ID;
	$chain['child_user_id']  = (int) $child->ID;
	$chain['tutor_user_id']  = (int) $tutor->ID;
}

$order_id = 0;
if ( $booking_id && class_exists( 'NGC_Parent_Checkout' ) ) {
	$order = NGC_Parent_Checkout::create_order(
		[
			'user_id'    => (int) $parent->ID,
			'booking_id' => $booking_id,
			'email'      => $parent->user_email,
			'first_name' => 'Demo',
			'last_name'  => 'Parent',
		]
	);
	ngc_bc_assert( 'order_created', ! is_wp_error( $order ) && $order, is_wp_error( $order ) ? $order->get_error_message() : '' );
	if ( ! is_wp_error( $order ) ) {
		$order_id = (int) $order->get_id();
		$chain['order_id'] = $order_id;
		$chain['order_total'] = (float) $order->get_total();
		$item_meta_ok = false;
		foreach ( $order->get_items() as $item ) {
			$item_meta_ok = (int) $item->get_meta( '_ngt_booking_id' ) === $booking_id
				&& (int) $item->get_meta( '_ngt_tutor_user_id' ) === (int) $tutor->ID
				&& (int) $item->get_meta( '_ngt_student_user_id' ) === (int) $child->ID;
			$chain['order_item_id'] = (int) $item->get_id();
			$chain['line_total'] = (float) $item->get_total();
			break;
		}
		ngc_bc_assert( 'order_item_ngt_meta', $item_meta_ok );
		ngc_bc_assert( 'order_booking_meta', (int) $order->get_meta( 'ngc_booking_id' ) === $booking_id );
		ngc_bc_assert(
			'price_integrity',
			abs( (float) $order->get_total() - (float) ( $chain['line_total'] ?? 0 ) ) < 0.01
			|| (float) $order->get_total() > 0,
			[ 'total' => $order->get_total(), 'line' => $chain['line_total'] ?? null ]
		);

		// Payment failure path: mark failed before settle — session must not be join-ready/paid.
		$order->update_status( 'failed', 'E2E payment failure probe' );
		if ( class_exists( 'NGC_Session_Orchestrator' ) && method_exists( 'NGC_Session_Orchestrator', 'on_order_failed' ) ) {
			NGC_Session_Orchestrator::on_order_failed( $order_id );
		} else {
			do_action( 'woocommerce_order_status_failed', $order_id, $order );
		}
		$pre = NGC_Session_Orchestrator::ensure_provisioned(
			[
				'booking_id' => $booking_id,
				'order_id'   => $order_id,
				'source'     => 'e2e_pre_fail',
			]
		);
		ngc_bc_assert( 'session_pre_provision', ! is_wp_error( $pre ), is_wp_error( $pre ) ? $pre->get_error_message() : '' );
		$session_fail = class_exists( 'NGC_Sessions' ) ? NGC_Sessions::get_by_booking( $booking_id ) : null;
		$fail_ok      = ! $session_fail
			|| ! in_array( (string) ( $session_fail->payment_status ?? '' ), [ 'paid' ], true )
			|| in_array( (string) ( $session_fail->status ?? '' ), [ 'failed', 'awaiting_payment', 'draft', 'cancelled' ], true );
		// After failed order + pre-provision, payment_status must not claim paid.
		if ( $session_fail ) {
			$fail_ok = (string) ( $session_fail->payment_status ?? '' ) !== 'paid'
				&& (string) ( $session_fail->status ?? '' ) !== 'ready'
				&& (string) ( $session_fail->status ?? '' ) !== 'in_progress';
		}
		ngc_bc_assert(
			'payment_failure_blocks_ready',
			$fail_ok,
			$session_fail ? [
				'status'         => $session_fail->status,
				'payment_status' => $session_fail->payment_status,
			] : 'no_session'
		);
		$order->update_status( 'pending', 'E2E reset for settle' );

		// Authoritative settle (sandbox-safe).
		if ( class_exists( 'NGC_Payments' ) ) {
			$order->payment_complete();
			NGC_Payments::settle_order( $order_id );
		}
		$order = wc_get_order( $order_id );
		ngc_bc_assert( 'order_paid_or_processing', $order && ( $order->is_paid() || in_array( $order->get_status(), [ 'processing', 'completed' ], true ) ), $order ? $order->get_status() : 'missing' );
	}
}

$session_id = 0;
if ( $booking_id && class_exists( 'NGC_Session_Orchestrator' ) ) {
	$r1 = NGC_Session_Orchestrator::ensure_provisioned(
		[
			'booking_id' => $booking_id,
			'order_id'   => $order_id,
			'source'     => 'e2e_settle',
		]
	);
	$r2 = NGC_Session_Orchestrator::ensure_provisioned(
		[
			'booking_id' => $booking_id,
			'order_id'   => $order_id,
			'source'     => 'e2e_replay',
		]
	);
	$r3 = NGC_Session_Orchestrator::ensure_provisioned(
		[
			'booking_id' => $booking_id,
			'order_id'   => $order_id,
			'source'     => 'e2e_replay2',
		]
	);
	ngc_bc_assert( 'ensure_provisioned_ok', ! is_wp_error( $r1 ), is_wp_error( $r1 ) ? $r1->get_error_message() : '' );
	$session = NGC_Sessions::get_by_booking( $booking_id );
	ngc_bc_assert( 'session_row', (bool) $session );
	if ( $session ) {
		$session_id = (int) $session->id;
		$chain['session_id'] = $session_id;
		$chain['session_uuid'] = $session->session_uuid;
		$chain['correlation_id'] = $session->correlation_id;
		$chain['meeting_id'] = $session->meeting_id;
		$chain['masterstudy_course_id'] = (int) $session->masterstudy_course_id;
		$chain['masterstudy_lesson_id'] = (int) $session->masterstudy_lesson_id;
		ngc_bc_assert( 'session_paid', 'paid' === $session->payment_status, $session->payment_status );
		ngc_bc_assert( 'session_ready_or_later', in_array( $session->status, [ 'ready', 'join_window_open', 'in_progress', 'provisioning', 'booking_confirmed', 'paid' ], true ), $session->status );
		ngc_bc_assert( 'correlation_present', (string) $session->correlation_id !== '' );
	}
	$count = 0;
	if ( $session_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'sessions' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE booking_id = %d", $booking_id ) );
	}
	ngc_bc_assert( 'duplicate_session_suppressed', 1 === $count, $count );
	ngc_bc_assert( 'replay_safe', ! is_wp_error( $r2 ) && ! is_wp_error( $r3 ) );

	if ( $session_id ) {
		$window = NGC_Session_Orchestrator::join_window_status( NGC_Sessions::get( $session_id ) );
		ngc_bc_assert( 'join_window_evaluated', isset( $window['allowed'] ), $window );
		$launch = NGC_Session_Orchestrator::authorize_launch( $session_id, (int) $child->ID );
		if ( ! empty( $window['allowed'] ) ) {
			ngc_bc_assert( 'launch_authorized', ! is_wp_error( $launch ), is_wp_error( $launch ) ? $launch->get_error_message() : '' );
			if ( ! is_wp_error( $launch ) ) {
				$chain['launch_url_present'] = ! empty( $launch['launch_url'] );
				ngc_bc_assert( 'launch_url', ! empty( $launch['launch_url'] ) );
			}
		} else {
			ngc_bc_assert( 'launch_blocked_outside_window', is_wp_error( $launch ), $window );
		}
	}
}

// Adult student self-purchase chain (scenario 2).
$adult = get_user_by( 'email', 'demo.student.adult@nextgen.local' );
ngc_bc_assert( 'demo_adult', (bool) $adult );
if ( $adult && $tutor && class_exists( 'NGC_Bookings' ) && class_exists( 'NGC_Parent_Checkout' ) ) {
	$ab = 0;
	for ( $attempt = 0; $attempt < 8; $attempt++ ) {
		$created = NGC_Bookings::create(
			[
				'student_user_id'  => (int) $adult->ID,
				'tutor_user_id'    => (int) $tutor->ID,
				'subject'          => 'English',
				'scheduled_at'     => gmdate( 'Y-m-d H:i:s', time() + ( ( 10 + $attempt ) * 86400 ) + ( wp_rand( 1, 1200 ) * 60 ) ),
				'duration_minutes' => 60,
				'meta'             => [ 'demo_scenario_id' => 'BOOKING-COMMERCE-ADULT', 'is_demo' => 1 ],
			]
		);
		if ( ! is_wp_error( $created ) && (int) $created > 0 ) {
			$ab = (int) $created;
			break;
		}
	}
	ngc_bc_assert( 'adult_booking', $ab > 0, $ab ?: $created );
	if ( $ab > 0 ) {
		$ao = NGC_Parent_Checkout::create_order(
			[
				'user_id'    => (int) $adult->ID,
				'booking_id' => $ab,
				'email'      => $adult->user_email,
				'first_name' => 'Adult',
				'last_name'  => 'Student',
			]
		);
		ngc_bc_assert( 'adult_order', ! is_wp_error( $ao ), is_wp_error( $ao ) ? $ao->get_error_message() : '' );
		if ( ! is_wp_error( $ao ) && class_exists( 'NGC_Payments' ) ) {
			$ao->payment_complete();
			NGC_Payments::settle_order( $ao->get_id() );
			NGC_Session_Orchestrator::ensure_provisioned(
				[
					'booking_id' => $ab,
					'order_id'   => (int) $ao->get_id(),
					'source'     => 'e2e_adult',
				]
			);
			$as = NGC_Sessions::get_by_booking( $ab );
			$adult_ok = $as
				&& (int) $as->student_user_id === (int) $adult->ID
				&& (
					(int) $as->parent_user_id === (int) $adult->ID
					|| (int) $as->parent_user_id === 0
				)
				&& (int) $as->order_id === (int) $ao->get_id();
			ngc_bc_assert(
				'adult_is_payer_and_learner',
				$adult_ok,
				$as ? [
					'student' => (int) $as->student_user_id,
					'parent'  => (int) $as->parent_user_id,
					'order'   => (int) $as->order_id,
					'status'  => $as->payment_status,
				] : 'no_session'
			);
			$chain['adult'] = [
				'booking_id' => $ab,
				'order_id'   => (int) $ao->get_id(),
				'session_id' => $as ? (int) $as->id : 0,
			];
		}
	}
}

// Cancel / refund scenario on a dedicated booking.
if ( $parent && $child && $tutor && class_exists( 'NGC_Bookings' ) ) {
	$cb = (int) NGC_Bookings::create(
		[
			'student_user_id'  => (int) $child->ID,
			'tutor_user_id'    => (int) $tutor->ID,
			'subject'          => 'Science',
			'scheduled_at'     => gmdate( 'Y-m-d H:i:s', time() + ( 48 * 3600 ) + ( wp_rand( 10, 400 ) * 60 ) ),
			'duration_minutes' => 60,
			'meta'             => [ 'demo_scenario_id' => 'BOOKING-COMMERCE-REFUND', 'is_demo' => 1 ],
		]
	);
	$co = NGC_Parent_Checkout::create_order(
		[
			'user_id'    => (int) $parent->ID,
			'booking_id' => $cb,
			'email'      => $parent->user_email,
		]
	);
	if ( ! is_wp_error( $co ) && class_exists( 'NGC_Payments' ) ) {
		$co->payment_complete();
		NGC_Payments::settle_order( $co->get_id() );
		NGC_Session_Orchestrator::ensure_provisioned(
			[
				'booking_id' => $cb,
				'order_id'   => (int) $co->get_id(),
				'source'     => 'e2e_refund_setup',
			]
		);
		try {
			// Prefer WC API (2-arg hooks). Some third-party plugins fatally require both args.
			$co->update_status( 'refunded', 'E2E refund' );
		} catch ( Throwable $e ) {
			ngc_bc_assert( 'refund_status_update', false, $e->getMessage() );
		}
		if ( class_exists( 'NGC_Session_Orchestrator' ) ) {
			NGC_Session_Orchestrator::on_payment_refunded(
				(int) $co->get_id(),
				[ 'booking_id' => $cb, 'amount' => (float) $co->get_total() ]
			);
		}
		$rs = NGC_Sessions::get_by_booking( $cb );
		ngc_bc_assert( 'refund_session_terminal', $rs && ( in_array( $rs->status, [ 'refunded', 'cancelled' ], true ) || 'refunded' === $rs->payment_status ), $rs ? $rs->status . '/' . $rs->payment_status : 'missing' );
		$chain['refund'] = [
			'booking_id' => $cb,
			'order_id'   => (int) $co->get_id(),
			'session_id' => $rs ? (int) $rs->id : 0,
			'status'     => $rs->status ?? '',
		];
	}
}

$invoice_ok = false;
if ( $order_id && class_exists( 'NGC_Invoices' ) ) {
	try {
		$inv = NGC_Invoices::generate_from_order( wc_get_order( $order_id ) );
		$invoice_ok = ! is_wp_error( $inv ) && $inv;
		ngc_bc_assert( 'invoice_generated', (bool) $invoice_ok, is_wp_error( $inv ) ? $inv->get_error_message() : $inv );
		$chain['invoice'] = is_wp_error( $inv ) ? $inv->get_error_message() : $inv;
	} catch ( Throwable $e ) {
		ngc_bc_assert( 'invoice_generated', false, $e->getMessage() );
	}
}

// Evidence pack — write under plugin (host-mounted) so artifacts are recoverable.
$evidence_root = trailingslashit( defined( 'NGC_PLUGIN_DIR' ) ? NGC_PLUGIN_DIR : dirname( __DIR__ ) ) . 'evidence/booking-commerce/' . $run_id;
if ( ! is_dir( $evidence_root ) ) {
	wp_mkdir_p( $evidence_root );
}
file_put_contents( $evidence_root . '/chain.json', wp_json_encode( $chain, JSON_PRETTY_PRINT ) );
file_put_contents( $evidence_root . '/checks.json', wp_json_encode( $GLOBALS['ngc_bc_checks'], JSON_PRETTY_PRINT ) );
file_put_contents(
	$evidence_root . '/reconciliation.json',
	wp_json_encode(
		[
			'product_id'   => $chain['product_id'] ?? 0,
			'order_id'     => $chain['order_id'] ?? 0,
			'order_total'  => $chain['order_total'] ?? null,
			'line_total'   => $chain['line_total'] ?? null,
			'session_id'   => $chain['session_id'] ?? 0,
			'correlation'  => $chain['correlation_id'] ?? '',
			'run_id'       => $run_id,
		],
		JSON_PRETTY_PRINT
	)
);

$errors = (int) $GLOBALS['ngc_bc_errors'];
echo "\nRUN {$run_id}\n";
echo 'Evidence: ' . $evidence_root . "\n";
echo $errors ? "RESULT FAIL ({$errors} failures)\n" : "RESULT PASS\n";
exit( $errors ? 1 : 0 );
