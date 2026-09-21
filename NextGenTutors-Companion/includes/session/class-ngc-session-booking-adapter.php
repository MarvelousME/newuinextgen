<?php
/**
 * Booking adapter — NGC bookings (+ Amelia IDs when present).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scheduling truth adapter.
 */
class NGC_Session_Booking_Adapter implements NGC_Booking_Provider_Interface {

	/**
	 * @param int $booking_id Booking ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public function get_normalized( $booking_id ) {
		if ( ! class_exists( 'NGC_Bookings' ) ) {
			return new WP_Error( 'ngc_booking_unavailable', __( 'Booking service unavailable.', 'nextgencompanion' ) );
		}
		$booking = NGC_Bookings::get( (int) $booking_id );
		if ( ! $booking ) {
			return new WP_Error( 'ngc_booking_not_found', __( 'Booking not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
		}
		$start = (string) ( $booking->scheduled_at ?? '' );
		$mins  = max( 1, (int) ( $booking->duration_minutes ?? 60 ) );
		$end   = $start ? gmdate( 'Y-m-d H:i:s', strtotime( $start . ' UTC' ) + ( $mins * 60 ) ) : '';
		$meta  = NGC_Bookings::get_meta( $booking );
		return [
			'booking_id'       => (int) $booking->id,
			'booking_provider' => ! empty( $booking->amelia_booking_id ) ? 'amelia' : 'ngc',
			'amelia_booking_id'=> (int) ( $booking->amelia_booking_id ?? 0 ),
			'tutor_user_id'    => (int) $booking->tutor_user_id,
			'student_user_id'  => (int) $booking->student_user_id,
			'subject'          => (string) $booking->subject,
			'subject_id'       => sanitize_title( (string) $booking->subject ),
			'start'            => $start,
			'end'              => $end,
			'duration_minutes' => $mins,
			'timezone'         => (string) ( $meta['timezone'] ?? 'Africa/Johannesburg' ),
			'status'           => (string) $booking->status,
			'order_id'         => (int) ( $booking->order_id ?? 0 ),
			'amount'           => (float) ( $booking->amount ?? 0 ),
			'uuid'             => (string) ( $booking->uuid ?? '' ),
		];
	}

	/**
	 * @param int    $booking_id Booking ID.
	 * @param string $status     Status.
	 * @param int    $actor_id   Actor.
	 * @return true|WP_Error
	 */
	public function confirm( $booking_id, $status = 'confirmed', $actor_id = 0 ) {
		if ( ! class_exists( 'NGC_Bookings' ) ) {
			return new WP_Error( 'ngc_booking_unavailable', __( 'Booking service unavailable.', 'nextgencompanion' ) );
		}
		$booking = NGC_Bookings::get( (int) $booking_id );
		if ( $booking && (string) $booking->status === $status ) {
			return true;
		}
		return NGC_Bookings::transition( (int) $booking_id, $status, (int) $actor_id );
	}

	/**
	 * @param int $booking_id Booking ID.
	 * @return true|WP_Error
	 */
	public function cancel( $booking_id ) {
		return $this->confirm( $booking_id, 'cancelled' );
	}
}
