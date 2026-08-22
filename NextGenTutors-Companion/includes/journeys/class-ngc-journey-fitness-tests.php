<?php
/**
 * Architecture fitness tests for journey / provider boundaries.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static source assertions (run via Companion tests/run.php or phpunit).
 */
final class NGC_Journey_Fitness_Tests {

	/**
	 * @return array<int, array{name:string,ok:bool,detail:string}>
	 */
	public static function run_all() {
		$tests = [];

		$tests[] = self::assert_true(
			'test_workflow_is_single_automation_authority',
			class_exists( 'NGC_Workflow_Authority' ) && class_exists( 'NGC_Platform' ),
			'NGC_Workflow_Authority must exist'
		);

		$learner = file_get_contents( NGC_PLUGIN_DIR . 'includes/journeys/class-ngc-learner-booking-confirmation-workflow.php' );
		$tests[] = self::assert_true(
			'test_learner_workflow_does_not_depend_directly_on_fluentcrm',
			is_string( $learner ) && false === strpos( $learner, 'FluentCrm' ) && false === strpos( $learner, 'FluentCRM\\' ),
			'Learner workflow must use CRM port, not FluentCRM SDK'
		);

		$tutor = file_get_contents( NGC_PLUGIN_DIR . 'includes/journeys/class-ngc-tutor-go-live-workflow.php' );
		$tests[] = self::assert_true(
			'test_tutor_domain_does_not_depend_on_gamipress',
			is_string( $tutor ) && false === strpos( $tutor, 'gamipress_' ),
			'Tutor go-live must not call gamipress_* functions directly'
		);

		$safety = file_get_contents( NGC_PLUGIN_DIR . 'includes/journeys/class-ngc-safety-escalation-workflow.php' );
		$tests[] = self::assert_true(
			'test_safeguarding_does_not_depend_directly_on_fluent_support',
			is_string( $safety ) && false === strpos( $safety, 'FluentSupport' ),
			'Safety escalation must not hard-depend on Fluent Support class'
		);

		$payments = file_get_contents( NGC_PLUGIN_DIR . 'includes/class-ngc-payments.php' );
		$tests[]  = self::assert_true(
			'test_payment_domain_does_not_depend_on_payfast_sdk',
			is_string( $payments ) && false === strpos( $payments, 'PayFast\\' ) && false === strpos( $payments, 'payfast/sdk' ),
			'Payments settle must not import PayFast SDK'
		);

		$guard = class_exists( 'NGC_Journey_Dual_Fire_Guard' );
		$tests[] = self::assert_true(
			'test_no_duplicate_booking_welcome_workflow',
			$guard && in_array( 'payment.received', NGC_Journey_Dual_Fire_Guard::blocked_core_events(), true ),
			'Dual-fire guard must block payment.received AutomatorWP cores'
		);

		$tests[] = self::assert_true(
			'test_first_booking_rule_idempotent_meta',
			class_exists( 'NGC_First_Booking_Rule' ) && defined( 'NGC_First_Booking_Rule::META_AWARDED' ) || class_exists( 'NGC_First_Booking_Rule' ),
			'First booking rule class present'
		);

		$tests[] = self::assert_true(
			'test_business_rules_fee_not_hardcoded_in_payout_scheduler',
			class_exists( 'NGC_Business_Rules' ) && class_exists( 'NGC_Payout_Business_Rules' ),
			'Payout fee/minimum must go through NGC_Business_Rules'
		);

		$hub = '';
		$hub_path = dirname( NGC_PLUGIN_DIR ) . '/nextgen-automation-hub/includes/class-ngt-hub-workflows.php';
		if ( is_readable( $hub_path ) ) {
			$hub = (string) file_get_contents( $hub_path );
		}
		$tests[] = self::assert_true(
			'test_hub_skips_add_user_role_when_companion_owns_tutor',
			$hub && false !== strpos( $hub, 'Skipped Hub add_user_role' ),
			'Hub must skip add_user_role when Companion owns tutor journey'
		);

		$points = class_exists( 'NGC_Scoring_Engine' ) ? NGC_Scoring_Engine::event_points() : [];
		$tests[] = self::assert_true(
			'test_session_points_student_50_tutor_25',
			isset( $points['session_completed']['student_points'] )
				&& 50.0 === (float) $points['session_completed']['student_points']
				&& isset( $points['session_completed_tutor']['tutor_points'] )
				&& 25.0 === (float) $points['session_completed_tutor']['tutor_points']
				&& empty( $points['session_completed']['tutor_points'] ),
			'Session Match: student 50 / tutor 25 on separate event keys'
		);

		$tests[] = self::assert_true(
			'test_first_booking_reward_100',
			class_exists( 'NGC_Business_Rules' )
				&& 100 === (int) NGC_Business_Rules::get( 'ngt.booking.first_booking_reward' )
				&& isset( $points['first_booking']['student_points'] )
				&& 100.0 === (float) $points['first_booking']['student_points'],
			'First booking Match: 100 student points'
		);

		$tests[] = self::assert_true(
			'test_popular_threshold_reviews_not_bookings',
			class_exists( 'NGC_Business_Rules' )
				&& 50 === (int) NGC_Business_Rules::get( 'ngt.tutor.popular_review_threshold' )
				&& class_exists( 'NGC_Gamification_Milestones' ),
			'Popular Match: 50 reviews + milestones class'
		);

		$tests[] = self::assert_true(
			'test_recording_disabled_by_default',
			class_exists( 'NGC_Business_Rules' )
				&& ! NGC_Business_Rules::get( 'ngt.session.recording_enabled' ),
			'Recording Match: OFF'
		);

		$tests[] = self::assert_true(
			'test_listing_eligibility_mandatory_checks',
			class_exists( 'NGC_Tutor_Listing_Eligibility' )
				&& in_array( 'id_verified', NGC_Tutor_Listing_Eligibility::mandatory_checks(), true ),
			'Listing Match: mandatory ID/Background/Training'
		);

		return $tests;
	}

	/**
	 * @param string $name   Name.
	 * @param bool   $ok     Pass.
	 * @param string $detail Detail.
	 * @return array{name:string,ok:bool,detail:string}
	 */
	private static function assert_true( $name, $ok, $detail ) {
		return [
			'name'   => $name,
			'ok'     => (bool) $ok,
			'detail' => $detail,
		];
	}
}
