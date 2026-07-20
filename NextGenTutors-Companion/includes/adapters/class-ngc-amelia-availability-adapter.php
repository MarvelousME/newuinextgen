<?php
/**
 * Amelia availability adapter for tutor calendars.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads availability and appointments from Amelia.
 */
class NGC_Amelia_Availability_Adapter {

	/**
	 * @return bool
	 */
	public function is_available() {
		$plugin_active = class_exists( 'NGC_Amelia_Bootstrap' )
			? NGC_Amelia_Bootstrap::is_active()
			: ( defined( 'AMELIA_VERSION' ) || class_exists( '\AmeliaBooking\Plugin' ) );

		if ( ! $plugin_active ) {
			return false;
		}

		if ( ! empty( get_option( 'ngc_amelia_api_key', '' ) ) ) {
			return true;
		}

		return class_exists( 'NGC_Amelia_Bootstrap' ) && NGC_Amelia_Bootstrap::table_exists( 'amelia_appointments' );
	}

	/**
	 * @param int    $employee_id Amelia employee ID.
	 * @param string $from        Y-m-d.
	 * @param string $to          Y-m-d.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_busy_slots( $employee_id, $from, $to ) {
		$employee_id = (int) $employee_id;
		if ( $employee_id <= 0 ) {
			return [];
		}

		$slots = $this->busy_slots_from_tables( $employee_id, $from, $to );
		if ( ! empty( $slots ) ) {
			return $slots;
		}

		$api = $this->busy_slots_from_api( $employee_id, $from, $to );
		return ! empty( $api ) ? $api : [];
	}

	/**
	 * @param int    $employee_id Employee ID.
	 * @param string $from        From date.
	 * @param string $to          To date.
	 * @return array<int, array<string, mixed>>
	 */
	private function busy_slots_from_tables( $employee_id, $from, $to ) {
		global $wpdb;
		$appointments = $wpdb->prefix . 'amelia_appointments';
		$bookings     = $wpdb->prefix . 'amelia_customer_bookings';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$appointments_exists = (bool) $wpdb->get_var( "SHOW TABLES LIKE '{$appointments}'" );
		if ( ! $appointments_exists ) {
			return [];
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = "SELECT a.id, a.bookingStart, a.bookingEnd, a.status FROM {$appointments} a";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$bookings_exists = (bool) $wpdb->get_var( "SHOW TABLES LIKE '{$bookings}'" );
		if ( $bookings_exists ) {
			$query .= " INNER JOIN {$bookings} cb ON cb.appointmentId = a.id";
		}
		$query .= ' WHERE a.providerId = %d AND DATE(a.bookingStart) >= %s AND DATE(a.bookingStart) <= %s';
		if ( $bookings_exists ) {
			$query .= ' AND (cb.status IS NULL OR cb.status <> %s)';
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( $query, $employee_id, $from, $to, 'canceled' ), ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( $query, $employee_id, $from, $to ), ARRAY_A );
		}

		$slots = [];
		foreach ( (array) $rows as $row ) {
			$status   = sanitize_key( (string) ( $row['status'] ?? 'booked' ) );
			$slot_status = in_array( $status, [ 'canceled', 'cancelled' ], true ) ? 'cancelled' : 'booked';
			$slots[] = [
				'date'         => gmdate( 'Y-m-d', strtotime( (string) $row['bookingStart'] ) ),
				'start_time'   => gmdate( 'H:i', strtotime( (string) $row['bookingStart'] ) ),
				'end_time'     => gmdate( 'H:i', strtotime( (string) $row['bookingEnd'] ) ),
				'slot_status'  => $slot_status,
				'booking_id'   => (int) ( $row['id'] ?? 0 ),
				'source'       => 'amelia',
				'is_available' => false,
				'is_booked'    => 'booked' === $slot_status,
				'is_blocked'   => 'blocked' === $slot_status,
			];
		}
		return $slots;
	}

	/**
	 * @param int    $employee_id Employee ID.
	 * @param string $from        From date.
	 * @param string $to          To date.
	 * @return array<int, array<string, mixed>>
	 */
	private function busy_slots_from_api( $employee_id, $from, $to ) {
		$path = sprintf(
			'/appointments?providers[]=%d&page=1&dates[]=%s&dates[]=%s',
			$employee_id,
			rawurlencode( $from ),
			rawurlencode( $to )
		);
		$response = $this->api_get( $path );
		if ( empty( $response['ok'] ) || empty( $response['data'] ) ) {
			return [];
		}

		$slots = [];
		$items = isset( $response['data']['appointments'] ) && is_array( $response['data']['appointments'] ) ? $response['data']['appointments'] : (array) $response['data'];
		foreach ( $items as $item ) {
			$start = (string) ( $item['bookingStart'] ?? '' );
			$end   = (string) ( $item['bookingEnd'] ?? '' );
			if ( ! $start || ! $end ) {
				continue;
			}
			$status = sanitize_key( (string) ( $item['status'] ?? 'booked' ) );
			$slots[] = [
				'date'         => gmdate( 'Y-m-d', strtotime( $start ) ),
				'start_time'   => gmdate( 'H:i', strtotime( $start ) ),
				'end_time'     => gmdate( 'H:i', strtotime( $end ) ),
				'slot_status'  => in_array( $status, [ 'approved', 'booked' ], true ) ? 'booked' : ( 'pending' === $status ? 'pending' : 'unavailable' ),
				'booking_id'   => 0,
				'source'       => 'amelia',
				'is_available' => false,
				'is_booked'    => true,
				'is_blocked'   => false,
			];
		}
		return $slots;
	}

	/**
	 * @param string $path Path.
	 * @return array<string, mixed>
	 */
	private function api_get( $path ) {
		$key = (string) get_option( 'ngc_amelia_api_key', '' );
		if ( ! $key ) {
			return [ 'ok' => false ];
		}
		$url = admin_url( 'admin-ajax.php?action=wpamelia_api&call=/api/v1' . $path );
		$res = wp_remote_get(
			$url,
			[
				'timeout' => 20,
				'headers' => [ 'Amelia' => $key ],
			]
		);
		if ( is_wp_error( $res ) ) {
			return [ 'ok' => false ];
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		return [ 'ok' => true, 'data' => $data['data'] ?? $data ];
	}
}

