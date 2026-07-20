<?php
/**
 * Amelia booking integration with internal ngc_bookings sync.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Amelia hooks — sync to ngc_bookings and emit workflow events.
 */
class NGC_Amelia {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'AmeliaBookingAddedBeforeNotify', [ __CLASS__, 'on_amelia_booking' ], 10, 1 );
		add_action( 'amelia_booking_added', [ __CLASS__, 'on_amelia_booking' ], 10, 1 );
		add_action( 'amelia_booking_status_changed', [ __CLASS__, 'on_amelia_status_changed' ], 10, 3 );
	}

	/**
	 * @param mixed $booking Amelia booking entity/array.
	 */
	public static function on_amelia_booking( $booking ) {
		$data = is_object( $booking ) ? (array) $booking : (array) $booking;
		$amelia_id   = (int) ( $data['id'] ?? $data['bookingId'] ?? 0 );
		$internal_id = 0;
		if ( class_exists( 'NGC_Bookings' ) ) {
			$internal_id = NGC_Bookings::sync_from_amelia( $data );
		}
		$context = [
			'booking_id'         => (string) ( $internal_id ?: $amelia_id ),
			'internal_booking_id'=> $internal_id,
			'amelia_booking_id'  => (string) $amelia_id,
			'service_id'         => (string) ( $data['serviceId'] ?? '' ),
			'employee_id'        => (string) ( $data['providerId'] ?? $data['employeeId'] ?? '' ),
			'starts_at'          => (string) ( $data['bookingStart'] ?? $data['start'] ?? '' ),
			'student_email'      => (string) ( $data['customerEmail'] ?? $data['email'] ?? '' ),
		];
		NGC_Workflows::dispatch( 'booking.created', $context );
		NGC_Workflows::dispatch( 'session.scheduled', $context );
		do_action( 'ngc_booking_created', $context );
	}

	/**
	 * @param mixed  $booking   Booking entity.
	 * @param string $old_status Previous status.
	 * @param string $new_status New status.
	 */
	public static function on_amelia_status_changed( $booking, $old_status, $new_status ) {
		$data       = is_object( $booking ) ? (array) $booking : (array) $booking;
		$amelia_id  = (int) ( $data['id'] ?? $data['bookingId'] ?? 0 );
		if ( ! $amelia_id || ! class_exists( 'NGC_Bookings' ) ) {
			return;
		}
		$map = [
			'approved'  => 'confirmed',
			'canceled'  => 'cancelled',
			'rejected'  => 'cancelled',
			'completed' => 'completed',
		];
		$status = $map[ sanitize_key( (string) $new_status ) ] ?? '';
		if ( $status ) {
			NGC_Bookings::update_status_by_amelia_id( $amelia_id, $status );
		}
	}
}
