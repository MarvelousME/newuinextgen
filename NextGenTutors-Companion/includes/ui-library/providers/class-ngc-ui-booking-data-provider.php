<?php
/**
 * Booking + Amelia integration provider.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upcoming lessons and booking controls.
 */
class NGC_UI_Booking_Data_Provider extends NGC_UI_Data_Provider {

	/**
	 * @return string
	 */
	public function get_key() {
		return 'booking';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return class_exists( 'NGC_Bookings' ) || class_exists( 'NGC_Amelia' );
	}

	/**
	 * @param array<string, mixed> $args Args.
	 * @return array<int, array<string, mixed>>
	 */
	public function list( $args = [] ) {
		$user_id = (int) ( $args['user_id'] ?? get_current_user_id() );
		if ( ! $user_id || ! class_exists( 'NGC_Bookings' ) ) {
			return [];
		}

		if ( method_exists( 'NGC_Bookings', 'list_for_user' ) ) {
			return (array) NGC_Bookings::list_for_user( $user_id, $args );
		}

		$user = get_userdata( $user_id );
		$roles = $user ? (array) $user->roles : [];
		if ( in_array( 'tutor', $roles, true ) || user_can( $user_id, 'ngc_tutor' ) ) {
			$rows = NGC_Bookings::query( array_merge( $args, [ 'tutor_user_id' => $user_id ] ) );
		} elseif ( in_array( 'parent', $roles, true ) || user_can( $user_id, 'ngc_parent' ) ) {
			$rows = NGC_Bookings::query_for_parent( $user_id, (int) ( $args['limit'] ?? 10 ) );
		} else {
			$rows = NGC_Bookings::query( array_merge( $args, [ 'student_user_id' => $user_id ] ) );
		}

		$viewer = $user_id;
		return array_map(
			static function ( $booking ) use ( $viewer ) {
				return NGC_Bookings::format_session_row( $booking, $viewer );
			},
			$rows ?: []
		);
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @param string               $component Component.
	 * @return array<string, mixed>
	 */
	public function map_to_component( $row, $component ) {
		if ( 'booking-list' !== $component ) {
			return $row;
		}
		return [
			'peerName'    => $row['peerName'] ?? '',
			'peerImage'   => $row['peerImage'] ?? '',
			'subject'     => $row['subject'] ?? '',
			'createdAt'   => $row['createdAt'] ?? '',
			'status'      => $row['status'] ?? '',
			'statusLabel' => $row['statusLabel'] ?? '',
			'bookingId'   => $row['bookingId'] ?? $row['id'] ?? 0,
			'joinUrl'     => $row['joinUrl'] ?? '',
			'canJoin'     => ! empty( $row['canJoin'] ),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify_source() {
		return [
			'provider' => $this->get_key(),
			'class'    => 'NGC_Bookings',
			'integr'   => 'NGC_Amelia',
		];
	}
}
