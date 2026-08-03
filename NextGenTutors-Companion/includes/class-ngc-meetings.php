<?php
/**
 * Lesson meeting orchestration (Jitsi A/V rooms bound to bookings).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensures join URLs exist for confirmed online lessons.
 */
class NGC_Meetings {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'ngc_booking_confirmed', [ __CLASS__, 'on_booking_confirmed' ], 10, 2 );
	}

	/**
	 * @param int                  $booking_id Booking ID.
	 * @param array<string, mixed> $context    Context.
	 */
	public static function on_booking_confirmed( $booking_id, $context = [] ) {
		self::ensure_for_booking( (int) $booking_id, $context );
	}

	/**
	 * Create or return the meeting join payload for a booking.
	 *
	 * @param int                  $booking_id Booking ID.
	 * @param array<string, mixed> $context    Optional display_name / user_id.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function ensure_for_booking( $booking_id, $context = [] ) {
		$booking_id = (int) $booking_id;
		if ( $booking_id <= 0 || ! class_exists( 'NGC_Bookings' ) ) {
			return new WP_Error( 'ngc_meeting_invalid', __( 'Invalid booking for meeting.', 'nextgencompanion' ) );
		}

		$existing = NGC_Bookings::get_meeting_meta( $booking_id );
		if ( ! empty( $existing['join_url'] ) ) {
			return [
				'ok'       => true,
				'provider' => (string) ( $existing['provider'] ?? 'jitsi' ),
				'room'     => (string) ( $existing['room'] ?? '' ),
				'join_url' => (string) $existing['join_url'],
				'created'  => false,
			];
		}

		$booking = NGC_Bookings::get( $booking_id );
		if ( ! $booking ) {
			return new WP_Error( 'ngc_meeting_not_found', __( 'Booking not found.', 'nextgencompanion' ) );
		}

		$adapter = new NGC_Jitsi_Meeting_Adapter();
		$result  = $adapter->create_or_update(
			'create_lesson_room',
			[
				'booking_id'   => $booking_id,
				'uuid'         => (string) ( $booking->uuid ?? '' ),
				'display_name' => (string) ( $context['display_name'] ?? '' ),
				'user_id'      => (int) ( $context['user_id'] ?? 0 ),
			]
		);

		if ( empty( $result['ok'] ) || empty( $result['join_url'] ) ) {
			return new WP_Error(
				'ngc_meeting_create_failed',
				(string) ( $result['message'] ?? __( 'Could not create lesson room.', 'nextgencompanion' ) ),
				$result
			);
		}

		$meeting = [
			'provider'    => 'jitsi',
			'room'        => (string) $result['room'],
			'join_url'    => (string) $result['join_url'],
			'audio_video' => true,
			'created_at'  => current_time( 'mysql', true ),
		];
		NGC_Bookings::set_meeting_meta( $booking_id, $meeting );

		return [
			'ok'       => true,
			'provider' => 'jitsi',
			'room'     => $meeting['room'],
			'join_url' => $meeting['join_url'],
			'created'  => true,
		];
	}

	/**
	 * Personalized join URL for a viewer (display name in Jitsi hash).
	 *
	 * @param int $booking_id Booking ID.
	 * @param int $user_id    Viewer.
	 * @return string|WP_Error
	 */
	public static function join_url_for_user( $booking_id, $user_id = 0 ) {
		$user_id = $user_id ?: get_current_user_id();
		$user    = get_userdata( (int) $user_id );
		$display = $user ? $user->display_name : '';

		$ensured = self::ensure_for_booking(
			(int) $booking_id,
			[
				'display_name' => $display,
				'user_id'      => (int) $user_id,
			]
		);
		if ( is_wp_error( $ensured ) ) {
			return $ensured;
		}

		$room = (string) ( $ensured['room'] ?? '' );
		if ( $room === '' && ! empty( $ensured['join_url'] ) ) {
			return (string) $ensured['join_url'];
		}
		return NGC_Jitsi_Meeting_Adapter::join_url_for_room( $room, $display );
	}

	/**
	 * Whether the booking is in a joinable lifecycle state.
	 *
	 * @param object|null $booking Booking row.
	 * @return bool
	 */
	public static function can_join_status( $booking ) {
		if ( ! $booking || empty( $booking->status ) ) {
			return false;
		}
		$status = sanitize_key( (string) $booking->status );
		return in_array( $status, [ 'requested', 'confirmed' ], true );
	}
}
