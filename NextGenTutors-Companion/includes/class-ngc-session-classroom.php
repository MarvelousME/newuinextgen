<?php
/**
 * Session classroom surface — join links and session metadata for dashboards.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightweight classroom helper (Jitsi join URLs — not full LMS classroom).
 */
class NGC_Session_Classroom {

	/**
	 * @return void
	 */
	public static function init() {
		add_shortcode( 'ngc_session_classroom', [ __CLASS__, 'shortcode' ] );
	}

	/**
	 * @param array<string, mixed> $atts
	 * @return string
	 */
	public static function shortcode( $atts = [] ) {
		$atts = shortcode_atts(
			[
				'booking_id' => 0,
			],
			$atts,
			'ngc_session_classroom'
		);
		$booking_id = (int) $atts['booking_id'];
		if ( ! $booking_id || ! class_exists( 'NGC_Bookings' ) ) {
			return '';
		}
		$session = class_exists( 'NGC_Session_Repository' ) ? NGC_Session_Repository::get_by_booking_id( $booking_id ) : null;
		$sid     = $session ? (int) $session['id'] : 0;
		$window  = $session ? NGC_Session_Join_Policy::evaluate( $session ) : [ 'allowed' => false, 'label' => __( 'Session link will appear when the lesson is confirmed.', 'nextgencompanion' ) ];
		if ( empty( $window['allowed'] ) ) {
			return '<p class="ngc-session-classroom">' . esc_html( (string) $window['label'] ) . '</p>';
		}
		return sprintf(
			'<p class="ngc-session-classroom"><button type="button" class="ngc-btn ngc-btn--primary bi-dash-join-btn" data-session-id="%d" data-booking-id="%d">%s</button></p>',
			$sid,
			$booking_id,
			esc_html__( 'Join lesson', 'nextgencompanion' )
		);
	}

	/**
	 * @param int $booking_id
	 * @return array<string, mixed>
	 */
	public static function snapshot( $booking_id ) {
		return [
			'booking_id' => (int) $booking_id,
			'join_url'   => apply_filters( 'ngc_session_join_url', '', (int) $booking_id ),
			'status'     => 'scheduled',
		];
	}
}
