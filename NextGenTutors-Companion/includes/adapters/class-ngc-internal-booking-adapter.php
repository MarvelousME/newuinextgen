<?php
/**
 * Internal booking adapter for tutor calendar fallback.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads internal booking table and maps to public-safe slot states.
 */
class NGC_Internal_Booking_Adapter {

	/**
	 * @param int    $tutor_user_id Tutor user ID.
	 * @param string $from          Y-m-d.
	 * @param string $to            Y-m-d.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_busy_slots( $tutor_user_id, $from, $to ) {
		$tutor_user_id = (int) $tutor_user_id;
		if ( $tutor_user_id <= 0 ) {
			return [];
		}
		$rows = NGC_Bookings::query(
			[
				'tutor_user_id' => $tutor_user_id,
				'limit'         => 1000,
			]
		);
		$out = [];
		foreach ( (array) $rows as $row ) {
			$date = gmdate( 'Y-m-d', strtotime( (string) $row->scheduled_at ) );
			if ( $date < $from || $date > $to ) {
				continue;
			}
			$start = gmdate( 'H:i', strtotime( (string) $row->scheduled_at ) );
			$end   = gmdate( 'H:i', strtotime( (string) $row->scheduled_at . ' +' . (int) $row->duration_minutes . ' minutes' ) );
			$slot_status = self::normalize_status( (string) $row->status );
			$out[] = [
				'date'          => $date,
				'start_time'    => $start,
				'end_time'      => $end,
				'slot_status'   => $slot_status,
				'delivery_mode' => 'hybrid',
				'location_type' => 'online',
				'subject'       => sanitize_text_field( (string) $row->subject ),
				'booking_id'    => (int) $row->id,
				'source'        => 'internal',
				'is_available'  => false,
				'is_booked'     => in_array( $slot_status, [ 'booked', 'pending', 'completed' ], true ),
				'is_blocked'    => 'blocked' === $slot_status,
			];
		}
		return $out;
	}

	/**
	 * @param string $status Raw status.
	 * @return string
	 */
	private static function normalize_status( $status ) {
		$status = sanitize_key( $status );
		$map    = [
			'requested' => 'pending',
			'confirmed' => 'booked',
			'cancelled' => 'cancelled',
			'completed' => 'completed',
		];
		return $map[ $status ] ?? 'unavailable';
	}
}

