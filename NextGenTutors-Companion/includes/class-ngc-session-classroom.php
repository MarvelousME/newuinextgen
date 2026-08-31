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
		$join = apply_filters( 'ngc_session_join_url', '', $booking_id );
		if ( ! $join ) {
			return '<p class="ngc-session-classroom">' . esc_html__( 'Session link will appear when the lesson is confirmed.', 'nextgencompanion' ) . '</p>';
		}
		return sprintf(
			'<p class="ngc-session-classroom"><a class="ngc-btn ngc-btn--primary" href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
			esc_url( $join ),
			esc_html__( 'Join session', 'nextgencompanion' )
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
