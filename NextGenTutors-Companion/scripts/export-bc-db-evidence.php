<?php
/**
 * Export DB evidence for booking-commerce PASS run bc-20260809-104545.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$dir = NGC_PLUGIN_DIR . 'evidence/booking-commerce/bc-20260809-104545/db';
if ( ! is_dir( $dir ) ) {
	wp_mkdir_p( $dir );
}

global $wpdb;

$ids = [
	'run_id'         => 'bc-20260809-104545',
	'booking'        => 176,
	'session'        => 17,
	'order'          => 57718,
	'adult_booking'  => 177,
	'adult_session'  => 18,
	'adult_order'    => 57720,
	'invoice'        => 39,
	'product'        => 57658,
	'correlation_id' => 'NGT-SES-20260809-41CE5DF5',
];
file_put_contents( $dir . '/ids.json', wp_json_encode( $ids, JSON_PRETTY_PRINT ) );

$bookings = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT id, student_user_id, tutor_user_id, subject, scheduled_at, status, order_id FROM {$wpdb->prefix}ngc_bookings WHERE id IN (%d,%d)",
		176,
		177
	),
	ARRAY_A
);
file_put_contents( $dir . '/bookings.json', wp_json_encode( $bookings, JSON_PRETTY_PRINT ) );

$sessions = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT id, session_uuid, correlation_id, booking_id, order_id, product_id, student_user_id, parent_user_id, tutor_user_id, subject_name, masterstudy_course_id, masterstudy_lesson_id, meeting_provider, meeting_id, status, payment_status, scheduled_start FROM {$wpdb->prefix}ngc_sessions WHERE id IN (%d,%d)",
		17,
		18
	),
	ARRAY_A
);
file_put_contents( $dir . '/sessions.json', wp_json_encode( $sessions, JSON_PRETTY_PRINT ) );

$invoices = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT id, invoice_number, user_id, booking_id, order_id, amount, currency, status FROM {$wpdb->prefix}ngc_invoices WHERE id = %d OR order_id IN (%d,%d)",
		39,
		57718,
		57720
	),
	ARRAY_A
);
file_put_contents( $dir . '/invoices.json', wp_json_encode( $invoices, JSON_PRETTY_PRINT ) );

$users = $wpdb->get_results(
	"SELECT ID, user_login, user_email FROM {$wpdb->users} WHERE ID IN (243,246,247,250)",
	ARRAY_A
);
file_put_contents( $dir . '/users.json', wp_json_encode( $users, JSON_PRETTY_PRINT ) );

$order_items = $wpdb->get_results(
	'SELECT order_item_id, order_id, order_item_name, order_item_type FROM ' . $wpdb->prefix . 'woocommerce_order_items WHERE order_id IN (57718,57720)',
	ARRAY_A
);
file_put_contents( $dir . '/woocommerce-order-items.json', wp_json_encode( $order_items, JSON_PRETTY_PRINT ) );

$meta_out = [];
foreach ( (array) $order_items as $row ) {
	$meta = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT meta_key, meta_value FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta WHERE order_item_id = %d AND meta_key LIKE %s',
			(int) $row['order_item_id'],
			$wpdb->esc_like( '_ngt_' ) . '%'
		),
		ARRAY_A
	);
	$meta_out[ (int) $row['order_item_id'] ] = $meta;
}
file_put_contents( $dir . '/woocommerce-order-item-meta-ngt.json', wp_json_encode( $meta_out, JSON_PRETTY_PRINT ) );

$orders = [];
foreach ( [ 57718, 57720 ] as $oid ) {
	$o = function_exists( 'wc_get_order' ) ? wc_get_order( $oid ) : null;
	if ( $o ) {
		$orders[] = [
			'id'             => $o->get_id(),
			'status'         => $o->get_status(),
			'total'          => $o->get_total(),
			'currency'       => $o->get_currency(),
			'customer_id'    => $o->get_user_id(),
			'payment_method' => $o->get_payment_method(),
		];
	}
}
file_put_contents( $dir . '/woocommerce-orders.json', wp_json_encode( $orders, JSON_PRETTY_PRINT ) );

// Simple CSV mirrors for auditor tooling.
$csv_sessions = "id,session_uuid,correlation_id,booking_id,order_id,product_id,status,payment_status,ms_course,ms_lesson,meeting_id\n";
foreach ( (array) $sessions as $row ) {
	$csv_sessions .= sprintf(
		"%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
		$row['id'],
		$row['session_uuid'],
		$row['correlation_id'],
		$row['booking_id'],
		$row['order_id'],
		$row['product_id'],
		$row['status'],
		$row['payment_status'],
		$row['masterstudy_course_id'],
		$row['masterstudy_lesson_id'],
		$row['meeting_id']
	);
}
file_put_contents( $dir . '/sessions.csv', $csv_sessions );

echo 'DB_EVIDENCE ' . $dir . PHP_EOL;
