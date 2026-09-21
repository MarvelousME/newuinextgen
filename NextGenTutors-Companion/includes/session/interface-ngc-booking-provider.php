<?php
/**
 * Booking provider contract — scheduling truth.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes booking records for the session orchestrator.
 */
interface NGC_Booking_Provider_Interface {

	/**
	 * @param int $booking_id Booking ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public function get_normalized( $booking_id );

	/**
	 * @param int    $booking_id Booking ID.
	 * @param string $status     Target booking status.
	 * @param int    $actor_id   Actor.
	 * @return true|WP_Error
	 */
	public function confirm( $booking_id, $status = 'confirmed', $actor_id = 0 );

	/**
	 * @param int $booking_id Booking ID.
	 * @return true|WP_Error
	 */
	public function cancel( $booking_id );
}
