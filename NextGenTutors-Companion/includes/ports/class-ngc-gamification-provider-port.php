<?php
/**
 * Gamification provider port — GamiPress adapter.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Commands gamification; never owns ratings or listing.
 */
final class NGC_Gamification_Provider_Port {

	/**
	 * @param int    $user_id User.
	 * @param string $type    Point type.
	 * @param float  $amount  Amount.
	 * @param string $reason  Reason.
	 */
	public static function award_points( $user_id, $type, $amount, $reason = '' ) {
		if ( class_exists( 'NGC_Gamipress_Adapter' ) ) {
			NGC_Gamipress_Adapter::award_points( (int) $user_id, (string) $type, (float) $amount, (string) $reason );
		}
	}

	/**
	 * @param int    $user_id User.
	 * @param string $slug    Achievement slug.
	 */
	public static function award_achievement( $user_id, $slug ) {
		if ( class_exists( 'NGC_Gamipress_Adapter' ) ) {
			NGC_Gamipress_Adapter::award_achievement( (int) $user_id, (string) $slug );
		}
	}
}
