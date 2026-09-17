<?php
/**
 * Meeting adapter — Jitsi (configured provider) via NGC_Meetings.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Realtime communication adapter. Idempotent: one meeting per session.
 */
class NGC_Session_Meeting_Adapter implements NGC_Meeting_Provider_Interface {

	/**
	 * @param array<string, mixed> $session Session.
	 * @param array<string, mixed> $context Context.
	 * @return array{provider:string,meeting_id:string,reference:string,join_url:string,created:bool}|WP_Error
	 */
	public function ensure_meeting( array $session, array $context = [] ) {
		if ( ! empty( $session['meeting_id'] ) && ! empty( $session['meeting_url_reference'] ) ) {
			$url = $this->join_url_for_user( $session, (int) ( $context['user_id'] ?? 0 ) );
			return [
				'provider'   => (string) ( $session['meeting_provider'] ?: 'jitsi' ),
				'meeting_id' => (string) $session['meeting_id'],
				'reference'  => (string) $session['meeting_url_reference'],
				'join_url'   => is_wp_error( $url ) ? '' : (string) $url,
				'created'    => false,
			];
		}

		$booking_id = (int) ( $session['booking_id'] ?? 0 );
		if ( $booking_id && class_exists( 'NGC_Meetings' ) ) {
			$result = NGC_Meetings::ensure_for_booking( $booking_id, $context );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$room = (string) ( $result['room'] ?? '' );
			return [
				'provider'   => (string) ( $result['provider'] ?? 'jitsi' ),
				'meeting_id' => $room,
				'reference'  => $room,
				'join_url'   => (string) ( $result['join_url'] ?? '' ),
				'created'    => ! empty( $result['created'] ),
			];
		}

		if ( class_exists( 'NGC_Jitsi_Meeting_Adapter' ) ) {
			$adapter = new NGC_Jitsi_Meeting_Adapter();
			$result  = $adapter->create_or_update(
				'create_lesson_room',
				[
					'booking_id' => $booking_id,
					'uuid'       => (string) ( $session['session_uuid'] ?? '' ),
					'user_id'    => (int) ( $context['user_id'] ?? 0 ),
				]
			);
			if ( empty( $result['ok'] ) ) {
				return new WP_Error( 'ngc_meeting_create_failed', (string) ( $result['message'] ?? 'Meeting create failed' ), $result );
			}
			$room = (string) ( $result['room'] ?? '' );
			return [
				'provider'   => 'jitsi',
				'meeting_id' => $room,
				'reference'  => $room,
				'join_url'   => (string) ( $result['join_url'] ?? '' ),
				'created'    => true,
			];
		}

		return new WP_Error( 'ngc_meeting_unavailable', __( 'No meeting provider is configured.', 'nextgencompanion' ) );
	}

	/**
	 * @param array<string, mixed> $session Session.
	 * @param int                  $user_id User.
	 * @return string|WP_Error
	 */
	public function join_url_for_user( array $session, $user_id ) {
		$room = (string) ( $session['meeting_url_reference'] ?: $session['meeting_id'] ?? '' );
		if ( $room && class_exists( 'NGC_Jitsi_Meeting_Adapter' ) ) {
			$display = '';
			if ( $user_id && function_exists( 'get_userdata' ) ) {
				$user = get_userdata( (int) $user_id );
				$display = $user ? $user->display_name : '';
			}
			return NGC_Jitsi_Meeting_Adapter::join_url_for_room( $room, $display );
		}
		if ( ! empty( $session['booking_id'] ) && class_exists( 'NGC_Meetings' ) ) {
			return NGC_Meetings::join_url_for_user( (int) $session['booking_id'], (int) $user_id );
		}
		return new WP_Error( 'ngc_meeting_missing', __( 'Meeting has not been provisioned.', 'nextgencompanion' ) );
	}

	/**
	 * @param array<string, mixed> $session Session.
	 * @return true|WP_Error
	 */
	public function revoke( array $session ) {
		$booking_id = (int) ( $session['booking_id'] ?? 0 );
		if ( $booking_id && class_exists( 'NGC_Bookings' ) ) {
			NGC_Bookings::update_meta(
				$booking_id,
				[
					'meeting' => array_merge(
						NGC_Bookings::get_meeting_meta( $booking_id ),
						[ 'revoked' => true, 'revoked_at' => gmdate( 'c' ) ]
					),
				]
			);
		}
		return true;
	}
}
