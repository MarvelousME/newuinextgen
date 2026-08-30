<?php
/**
 * Session orchestrator — coordinates bookings, reminders, and classroom state.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Session lifecycle orchestration (not a full video classroom).
 */
class NGC_Session_Orchestrator {

	/**
	 * @return void
	 */
	public static function init() {
		add_action( 'ngc_booking_confirmed', [ __CLASS__, 'on_booking_confirmed' ], 10, 2 );
		add_action( 'ngc_booking_status_changed', [ __CLASS__, 'on_status_changed' ], 10, 3 );
	}

	/**
	 * @param int   $booking_id
	 * @param array $context
	 * @return void
	 */
	public static function on_booking_confirmed( $booking_id, $context = [] ) {
		if ( class_exists( 'NGC_Session_Reminders' ) ) {
			NGC_Session_Reminders::on_booking_created( array_merge( $context, [ 'booking_id' => (int) $booking_id ] ) );
		}
		if ( class_exists( 'NGC_Meetings' ) ) {
			NGC_Meetings::ensure_for_booking( (int) $booking_id, $context );
		}
		do_action( 'ngc_session_orchestrated', (int) $booking_id, $context );
	}

	/**
	 * @param int    $booking_id
	 * @param string $from
	 * @param string $to
	 * @return void
	 */
	public static function on_status_changed( $booking_id, $from, $to ) {
		if ( 'confirmed' === $to ) {
			self::on_booking_confirmed( $booking_id, [ 'from_status' => $from ] );
		}
	}
}
