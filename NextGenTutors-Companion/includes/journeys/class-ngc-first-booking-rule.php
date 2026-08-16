<?php
/**
 * First-booking reward rule — awards once per user.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Idempotent first booking gamification.
 */
final class NGC_First_Booking_Rule {

	public const META_AWARDED = 'ngt_first_booking_reward_awarded';

	/**
	 * @param int $user_id    User ID.
	 * @param int $order_id   Order ID.
	 * @param int $booking_id Booking ID.
	 * @return string Step result code.
	 */
	public static function maybe_award( $user_id, $order_id = 0, $booking_id = 0 ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return 'first_booking_skipped_no_user';
		}
		if ( get_user_meta( $user_id, self::META_AWARDED, true ) ) {
			return 'first_booking_already_awarded';
		}

		$idem = 'first_booking_reward:' . $user_id;
		if ( class_exists( 'NGC_Idempotency' ) ) {
			$begun = NGC_Idempotency::begin( $idem, 'user:' . $user_id, 'journey' );
			if ( is_wp_error( $begun ) || 'replay' === ( $begun['status'] ?? '' ) ) {
				return 'first_booking_idempotent_replay';
			}
		}

		$points = (int) NGC_Business_Rules::get( 'ngt.booking.first_booking_reward' );
		if ( $points <= 0 ) {
			return 'first_booking_disabled';
		}

		// Single award path: scoring event (100 pts) + Beginner badge (no second point dump).
		if ( class_exists( 'NGC_Gamification' ) ) {
			NGC_Gamification::process_event(
				$user_id,
				'first_booking',
				[
					'order_id'   => (int) $order_id,
					'booking_id' => (int) $booking_id,
					'skip_points_on_achievement' => true,
				]
			);
		} elseif ( class_exists( 'NGC_Scoring_Engine' ) ) {
			NGC_Scoring_Engine::award_event( $user_id, 'first_booking', [ 'order_id' => (int) $order_id, 'booking_id' => (int) $booking_id ] );
			if ( class_exists( 'NGC_Achievement_Engine' ) ) {
				NGC_Achievement_Engine::award(
					$user_id,
					'beginner',
					[
						'order_id'                   => (int) $order_id,
						'booking_id'                 => (int) $booking_id,
						'skip_points_on_achievement' => true,
					]
				);
			}
		}

		update_user_meta( $user_id, self::META_AWARDED, gmdate( 'c' ) );
		update_user_meta( $user_id, self::META_AWARDED . '_order', (int) $order_id );

		if ( class_exists( 'NGC_Idempotency' ) ) {
			NGC_Idempotency::commit( $idem, [ 'user_id' => $user_id, 'points' => $points ] );
		}

		return 'first_booking_awarded_' . $points;
	}
}
