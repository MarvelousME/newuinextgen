<?php
/**
 * Commerce provider contract — product/payment/invoice truth.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce-facing commerce operations for NGT sessions.
 */
interface NGC_Commerce_Provider_Interface {

	/**
	 * @param int $order_id Order ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public function get_order_snapshot( $order_id );

	/**
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	public function is_paid( $order_id );

	/**
	 * @param int $order_id Order ID.
	 * @return int|WP_Error Invoice ID.
	 */
	public function ensure_invoice( $order_id );
}
