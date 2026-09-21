<?php
/**
 * Session correlation / idempotency identifiers.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates NGT-SES-YYYYMMDD-XXXXXXXX correlation IDs.
 */
class NGC_Session_Correlation {

	/**
	 * @param int|null $now Timestamp.
	 * @return string
	 */
	public static function generate( $now = null ) {
		$ts   = $now ? (int) $now : time();
		$date = gmdate( 'Ymd', $ts );
		$rand = strtoupper( bin2hex( random_bytes( 4 ) ) );
		return 'NGT-SES-' . $date . '-' . $rand;
	}

	/**
	 * Stable idempotency key for order+booking pair.
	 *
	 * @param int $order_id   Order ID.
	 * @param int $booking_id Booking ID.
	 * @return string
	 */
	public static function idempotency_key( $order_id, $booking_id ) {
		$order_id   = (int) $order_id;
		$booking_id = (int) $booking_id;
		if ( $order_id > 0 && $booking_id > 0 ) {
			return 'session:order:' . $order_id . ':booking:' . $booking_id;
		}
		if ( $order_id > 0 ) {
			return 'session:order:' . $order_id;
		}
		if ( $booking_id > 0 ) {
			return 'session:booking:' . $booking_id;
		}
		return 'session:draft:' . self::generate();
	}

	/**
	 * @param string $value Candidate.
	 * @return bool
	 */
	public static function is_valid( $value ) {
		return (bool) preg_match( '/^NGT-SES-\d{8}-[A-F0-9]{8}$/', (string) $value );
	}
}
