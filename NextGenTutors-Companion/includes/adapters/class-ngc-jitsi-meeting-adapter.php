<?php
/**
 * Jitsi Meet adapter — online audio/video lesson rooms for bookings.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates deterministic public Jitsi rooms bound to bookings.
 * No API key required for meet.jit.si; custom base URL supported via option.
 */
class NGC_Jitsi_Meeting_Adapter extends NGC_Adapter_Base {

	const OPTION_BASE = 'ngc_jitsi_base_url';

	/**
	 * @return string
	 */
	public function slug() {
		return 'jitsi';
	}

	/**
	 * Always available — public Jitsi (or self-hosted) needs no WP plugin.
	 *
	 * @return bool
	 */
	public function is_available() {
		return true;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify() {
		$base = self::base_url();
		return [
			'active'   => true,
			'ok'       => (bool) $base,
			'base_url' => $base,
			'status'   => $base ? 'VERIFIED — Jitsi lesson rooms ready' : 'PARTIAL — invalid base URL',
		];
	}

	/**
	 * @return string
	 */
	public static function base_url() {
		$stored = (string) get_option( self::OPTION_BASE, '' );
		$base   = $stored !== '' ? $stored : 'https://meet.jit.si';
		$base   = untrailingslashit( esc_url_raw( $base ) );
		/**
		 * Filter Jitsi Meet base URL (no trailing slash).
		 *
		 * @param string $base Base URL.
		 */
		return (string) apply_filters( 'ngc_jitsi_base_url', $base );
	}

	/**
	 * @param string               $action  create_lesson_room|get_join_url.
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	public function create_or_update( $action, $payload ) {
		$action = sanitize_key( $action );
		if ( ! in_array( $action, [ 'create_lesson_room', 'create_jitsi_lesson_room', 'get_join_url' ], true ) ) {
			return $this->handle_error( 'jitsi_invalid_action', __( 'Unsupported Jitsi action.', 'nextgencompanion' ) );
		}

		$booking_id = (int) ( $payload['booking_id'] ?? 0 );
		$uuid       = sanitize_text_field( (string) ( $payload['uuid'] ?? '' ) );
		$display    = sanitize_text_field( (string) ( $payload['display_name'] ?? '' ) );

		if ( $booking_id <= 0 && $uuid === '' ) {
			return $this->handle_error( 'jitsi_missing_booking', __( 'Booking ID or UUID required.', 'nextgencompanion' ) );
		}

		$room = self::room_name( $booking_id, $uuid );
		$url  = self::join_url_for_room( $room, $display );

		$result = $this->success(
			[
				'event'       => 'JITSI_ROOM_READY',
				'provider'    => 'jitsi',
				'booking_id'  => $booking_id,
				'room'        => $room,
				'join_url'    => $url,
				'audio_video' => true,
			]
		);
		$this->audit_result( 'JITSI_ROOM_READY', $result, (int) ( $payload['user_id'] ?? 0 ) );
		return $result;
	}

	/**
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>|null
	 */
	public function get_existing( $payload ) {
		$booking_id = (int) ( $payload['booking_id'] ?? 0 );
		if ( $booking_id <= 0 || ! class_exists( 'NGC_Bookings' ) ) {
			return null;
		}
		$meeting = NGC_Bookings::get_meeting_meta( $booking_id );
		if ( empty( $meeting['join_url'] ) ) {
			return null;
		}
		return [
			'provider' => (string) ( $meeting['provider'] ?? 'jitsi' ),
			'room'     => (string) ( $meeting['room'] ?? '' ),
			'join_url' => (string) $meeting['join_url'],
		];
	}

	/**
	 * @param int    $booking_id Booking ID.
	 * @param string $uuid       Booking UUID.
	 * @return string
	 */
	public static function room_name( $booking_id, $uuid = '' ) {
		$token = $uuid !== '' ? preg_replace( '/[^a-zA-Z0-9\-]/', '', $uuid ) : '';
		if ( ! $token ) {
			$token = 'b' . (int) $booking_id;
		}
		$name = 'NextGenTutors-Lesson-' . $token;
		/**
		 * Filter Jitsi room name for a booking.
		 *
		 * @param string $name       Room name.
		 * @param int    $booking_id Booking ID.
		 * @param string $uuid       UUID.
		 */
		return (string) apply_filters( 'ngc_jitsi_room_name', $name, (int) $booking_id, (string) $uuid );
	}

	/**
	 * @param string $room         Room slug.
	 * @param string $display_name Optional display name hash fragment.
	 * @return string
	 */
	public static function join_url_for_room( $room, $display_name = '' ) {
		$url = self::base_url() . '/' . rawurlencode( (string) $room );
		if ( $display_name !== '' ) {
			$url .= '#userInfo.displayName="' . rawurlencode( $display_name ) . '"';
		}
		return $url;
	}
}
