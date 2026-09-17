<?php
/**
 * Commerce adapter — WooCommerce order/invoice truth.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product/payment/invoice adapter.
 */
class NGC_Session_Commerce_Adapter implements NGC_Commerce_Provider_Interface {

	/**
	 * @param int $order_id Order ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public function get_order_snapshot( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return new WP_Error( 'ngc_commerce_unavailable', __( 'WooCommerce is not active.', 'nextgencompanion' ) );
		}
		$order = wc_get_order( (int) $order_id );
		if ( ! $order ) {
			return new WP_Error( 'ngc_order_not_found', __( 'Order not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
		}
		$items = [];
		foreach ( $order->get_items() as $item_id => $item ) {
			$items[] = [
				'order_item_id' => (int) $item_id,
				'product_id'    => (int) $item->get_product_id(),
				'name'          => $item->get_name(),
				'qty'           => (int) $item->get_quantity(),
				'subtotal'      => (float) $item->get_subtotal(),
				'total'         => (float) $item->get_total(),
				'meta'          => self::item_meta( $item ),
			];
		}
		return [
			'order_id'       => (int) $order->get_id(),
			'status'         => $order->get_status(),
			'paid'           => $order->is_paid(),
			'currency'       => $order->get_currency() ?: 'ZAR',
			'total'          => (float) $order->get_total(),
			'subtotal'       => (float) $order->get_subtotal(),
			'tax'            => (float) $order->get_total_tax(),
			'customer_id'    => (int) $order->get_user_id(),
			'billing_email'  => $order->get_billing_email(),
			'payment_method' => $order->get_payment_method(),
			'booking_id'     => (int) $order->get_meta( 'ngc_booking_id' ),
			'items'          => $items,
		];
	}

	/**
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	public function is_paid( $order_id ) {
		$snap = $this->get_order_snapshot( $order_id );
		return ! is_wp_error( $snap ) && ! empty( $snap['paid'] );
	}

	/**
	 * @param int $order_id Order ID.
	 * @return int|WP_Error
	 */
	public function ensure_invoice( $order_id ) {
		if ( ! class_exists( 'NGC_Invoices' ) || ! function_exists( 'wc_get_order' ) ) {
			return new WP_Error( 'ngc_invoice_unavailable', __( 'Invoicing is unavailable.', 'nextgencompanion' ) );
		}
		$order = wc_get_order( (int) $order_id );
		if ( ! $order ) {
			return new WP_Error( 'ngc_order_not_found', __( 'Order not found.', 'nextgencompanion' ) );
		}
		return NGC_Invoices::generate_from_order( $order );
	}

	/**
	 * @param WC_Order_Item_Product $item Item.
	 * @return array<string, mixed>
	 */
	private static function item_meta( $item ) {
		$keys = [
			'_ngt_session_uuid',
			'_ngt_booking_id',
			'_ngt_tutor_id',
			'_ngt_tutor_name',
			'_ngt_student_id',
			'_ngt_student_name',
			'_ngt_parent_id',
			'_ngt_subject_id',
			'_ngt_subject_name',
			'_ngt_duration_minutes',
			'_ngt_session_count',
			'_ngt_scheduled_start',
			'_ngt_scheduled_end',
			'_ngt_timezone',
			'_ngt_product_key',
			'_ngt_pricing_rule',
		];
		$out = [];
		foreach ( $keys as $key ) {
			$out[ $key ] = $item->get_meta( $key );
		}
		return $out;
	}
}
