<?php
/**
 * Invoice generation.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Invoice management.
 */
class NGC_Invoices {

	/**
	 * Generate invoice from WooCommerce order.
	 *
	 * @param WC_Order $order Order object.
	 * @return int|WP_Error Invoice ID.
	 */
	public static function generate_from_order( $order ) {
		global $wpdb;
		if ( is_numeric( $order ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( (int) $order );
		}
		if ( ! $order || ! is_object( $order ) || ! method_exists( $order, 'get_id' ) ) {
			return new WP_Error( 'ngc_invoice_order', __( 'Invalid order for invoice.', 'nextgencompanion' ) );
		}
		$table = NGC_Database::table( 'invoices' );

		$user_id    = (int) $order->get_user_id();
		$order_id   = (int) $order->get_id();
		$booking_id = (int) $order->get_meta( 'ngc_booking_id' );
		$amount     = (float) $order->get_total();
		$number     = self::next_invoice_number();

		$line_items = [];
		foreach ( $order->get_items() as $item ) {
			$line_items[] = [
				'name'   => $item->get_name(),
				'qty'    => $item->get_quantity(),
				'total'  => $item->get_total(),
			];
		}

		$row = [
			'invoice_number' => $number,
			'user_id'        => $user_id,
			'booking_id'     => $booking_id,
			'order_id'       => $order_id,
			'amount'         => $amount,
			'currency'       => $order->get_currency() ?: 'ZAR',
			'status'         => 'paid',
			'line_items'     => wp_json_encode( $line_items ),
			'issued_at'      => current_time( 'mysql', true ),
			'paid_at'        => current_time( 'mysql', true ),
			'meta'           => wp_json_encode( [ 'billing' => $order->get_address( 'billing' ) ] ),
		];
		if ( method_exists( 'NGC_Database', 'ensure_row_uuid' ) ) {
			$row = NGC_Database::ensure_row_uuid( $table, $row );
		}
		$inserted = $wpdb->insert( $table, $row );

		if ( ! $inserted ) {
			return new WP_Error( 'ngc_invoice_failed', __( 'Could not create invoice.', 'nextgencompanion' ), [ 'db' => $wpdb->last_error ] );
		}

		$invoice_id = (int) $wpdb->insert_id;
		NGC_Audit::log( 'invoice_issued', 'invoice', $invoice_id, [ 'order_id' => $order_id ] );
		NGC_Workflows::dispatch(
			'invoice.issued',
			[
				'invoice_id'     => (string) $invoice_id,
				'invoice_number' => $number,
				'user_id'        => (string) $user_id,
				'order_id'       => (string) $order_id,
				'amount'         => (string) $amount,
			]
		);

		return $invoice_id;
	}

	/**
	 * @return string
	 */
	private static function next_invoice_number() {
		$seq = (int) get_option( 'ngc_invoice_seq', 1000 );
		++$seq;
		update_option( 'ngc_invoice_seq', $seq, false );
		return 'NGC-INV-' . gmdate( 'Y' ) . '-' . str_pad( (string) $seq, 5, '0', STR_PAD_LEFT );
	}

	/**
	 * @param int $user_id User ID.
	 * @return array<int, object>
	 */
	public static function for_user( $user_id, $limit = 10 ) {
		global $wpdb;
		$table = NGC_Database::table( 'invoices' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d ORDER BY id DESC LIMIT %d", $user_id, $limit ) );
	}

	/**
	 * @param int $invoice_id Invoice ID.
	 * @return object|null
	 */
	public static function get( $invoice_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'invoices' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $invoice_id ) );
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		// Called from payments.
	}
}
