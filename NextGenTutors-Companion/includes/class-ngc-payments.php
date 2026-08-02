<?php
/**
 * WooCommerce payment hooks.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payment integration.
 */
class NGC_Payments {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'woocommerce_payment_complete', [ __CLASS__, 'on_payment_complete' ], 30 );
		add_action( 'woocommerce_order_status_completed', [ __CLASS__, 'on_order_completed' ], 30 );
		add_action( 'woocommerce_order_status_failed', [ __CLASS__, 'on_order_failed' ], 30 );
		add_action( 'woocommerce_order_status_refunded', [ __CLASS__, 'on_order_refunded' ], 30 );
		add_action( 'woocommerce_order_partially_refunded', [ __CLASS__, 'on_order_refunded' ], 30 );
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', [ __CLASS__, 'missing_wc_notice' ] );
		}
	}

	/**
	 * Admin notice when WooCommerce is absent.
	 */
	public static function missing_wc_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>';
		esc_html_e( 'NextGen Companion: WooCommerce is not active. Payments, invoices, wallet credits, and refunds require WooCommerce.', 'nextgencompanion' );
		echo '</p></div>';
	}

	/**
	 * @param int $order_id Order ID.
	 */
	public static function on_payment_complete( $order_id ) {
		self::settle_order( (int) $order_id );
	}

	/**
	 * @param int $order_id Order ID.
	 */
	public static function on_order_completed( $order_id ) {
		self::settle_order( (int) $order_id );
	}

	/**
	 * Idempotent payment settlement — wallet, invoice, booking, workflows.
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	public static function settle_order( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return false;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->is_paid() ) {
			return false;
		}
		if ( $order->get_meta( 'ngc_payment_settled' ) ) {
			return true;
		}

		$idem_key = 'payment-settle:' . (int) $order_id;
		if ( class_exists( 'NGC_Idempotency' ) ) {
			$begun = NGC_Idempotency::begin( $idem_key, 'order:' . (int) $order_id, 'payments' );
			if ( is_wp_error( $begun ) || 'replay' === ( $begun['status'] ?? '' ) ) {
				return true;
			}
		}

		$user_id = (int) $order->get_user_id();
		// Guest / PayFast ITN orders often have user_id 0 — resolve billing email so
		// each paid order still lands a per-order wallet ledger row on the parent.
		if ( $user_id <= 0 ) {
			$email = (string) $order->get_billing_email();
			if ( $email && is_email( $email ) ) {
				$by_email = get_user_by( 'email', $email );
				if ( $by_email ) {
					$user_id = (int) $by_email->ID;
					if ( ! $order->get_user_id() ) {
						$order->set_customer_id( $user_id );
						$order->save();
					}
				}
			}
		}
		$amount = (float) $order->get_total();

		if ( $user_id > 0 && $amount > 0 ) {
			NGC_Wallet::credit( $user_id, $amount, 'order:' . $order_id, __( 'Payment received', 'nextgencompanion' ) );
		}
		NGC_Invoices::generate_from_order( $order );

		$booking_id = (int) $order->get_meta( 'ngc_booking_id' );
		if ( $booking_id && class_exists( 'NGC_Bookings' ) ) {
			NGC_Bookings::transition( $booking_id, 'confirmed' );
		}

		NGC_Workflows::dispatch(
			'order.completed',
			[
				'order_id' => (string) $order_id,
				'user_id'  => (string) $user_id,
			]
		);

		NGC_Workflows::dispatch(
			'payment.received',
			[
				'order_id' => (string) $order_id,
				'user_id'  => (string) $user_id,
				'amount'   => (string) $amount,
			]
		);

		do_action(
			'ngc_payment_received',
			[
				'order_id'   => $order_id,
				'user_id'    => $user_id,
				'parent_user_id' => $user_id,
				'amount'     => $amount,
				'booking_id' => $booking_id,
			]
		);

		if ( class_exists( 'NGC_Platform_Repository' ) ) {
			NGC_Platform_Repository::create(
				'conversions',
				[
					'event_key'   => 'payment_completed',
					'user_id'     => $user_id,
					'visitor_id'  => sanitize_text_field( wp_unslash( $_COOKIE['visitor_id'] ?? '' ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					'object_type' => 'order',
					'object_id'   => (int) $order_id,
					'value'       => (float) $amount,
					'currency'    => 'ZAR',
					'attribution' => sanitize_text_field( wp_unslash( $_COOKIE['last_touch_source'] ?? '' ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				]
			);
		}

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'payment_completed', 'order', $order_id, [ 'amount' => $amount ] );
		}

		do_action(
			'ngc_payment_settled',
			(int) $order_id,
			[
				'amount'   => $amount,
				'user_id'  => $user_id,
				'currency' => method_exists( $order, 'get_currency' ) ? (string) $order->get_currency() : 'ZAR',
			]
		);

		$order->update_meta_data( 'ngc_payment_settled', gmdate( 'c' ) );
		$order->save();

		if ( class_exists( 'NGC_Idempotency' ) ) {
			NGC_Idempotency::commit( $idem_key, [ 'order_id' => (int) $order_id, 'amount' => $amount ] );
		}

		return true;
	}

	/**
	 * @param int $order_id Order ID.
	 */
	public static function on_order_failed( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		NGC_Workflows::dispatch(
			'payment.failed',
			[
				'order_id' => (string) $order_id,
				'user_id'  => (string) $order->get_user_id(),
				'amount'   => (string) $order->get_total(),
			]
		);

		NGC_Audit::log( 'payment_failed', 'order', $order_id );
	}

	/**
	 * @param int $order_id Order ID.
	 */
	public static function on_order_refunded( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$user_id = (int) $order->get_user_id();
		$amount  = (float) $order->get_total_refunded();
		if ( $amount > 0 ) {
			NGC_Wallet::debit( $user_id, $amount, 'refund:' . $order_id, __( 'Order refunded', 'nextgencompanion' ) );
		}

		NGC_Workflows::dispatch(
			'payment.refunded',
			[
				'order_id' => (string) $order_id,
				'user_id'  => (string) $user_id,
				'amount'   => (string) $amount,
			]
		);

		NGC_Audit::log( 'payment_refunded', 'order', $order_id, [ 'amount' => $amount ] );
		do_action( 'ngc_payment_refunded', (int) $order_id, [ 'amount' => $amount, 'user_id' => $user_id ] );
	}
}
