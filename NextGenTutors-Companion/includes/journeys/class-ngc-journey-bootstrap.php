<?php
/**
 * Journey package bootstrap — ecosystem-workflow authority for blueprints.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Boots learner / tutor / safety journey modules.
 */
final class NGC_Journey_Bootstrap {

	/**
	 * Init.
	 */
	public static function init() {
		if ( class_exists( 'NGC_Business_Rules' ) ) {
			NGC_Business_Rules::maybe_seed();
		}
		if ( class_exists( 'NGC_Journey_Dual_Fire_Guard' ) ) {
			NGC_Journey_Dual_Fire_Guard::init();
		}
		if ( class_exists( 'NGC_Learner_Booking_Confirmation_Workflow' ) ) {
			NGC_Learner_Booking_Confirmation_Workflow::init();
		}
		if ( class_exists( 'NGC_Tutor_Go_Live_Workflow' ) ) {
			NGC_Tutor_Go_Live_Workflow::init();
		}
		if ( class_exists( 'NGC_Safety_Escalation_Workflow' ) ) {
			NGC_Safety_Escalation_Workflow::init();
		}
		if ( class_exists( 'NGC_Journey_Registry' ) ) {
			NGC_Journey_Registry::init();
		}
		if ( class_exists( 'NGC_Payout_Business_Rules' ) ) {
			NGC_Payout_Business_Rules::init();
		}

		add_filter( 'ngc_referral_reward_amount', [ __CLASS__, 'filter_referral_amount' ] );
	}

	/**
	 * @param float $amount Default.
	 * @return float
	 */
	public static function filter_referral_amount( $amount ) {
		if ( class_exists( 'NGC_Business_Rules' ) ) {
			return (float) NGC_Business_Rules::get( 'ngt.referral.reward_amount' );
		}
		return (float) $amount;
	}
}
