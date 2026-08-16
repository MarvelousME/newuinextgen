<?php
/**
 * Journey registry for Workflow Studio / discovery (VIEW graphs, not auto-publish).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical journey definitions with provenance.
 */
final class NGC_Journey_Registry {

	/**
	 * Init — expose to studio importer as journey source.
	 */
	public static function init() {
		add_filter( 'ngc_studio_journey_definitions', [ __CLASS__, 'definitions' ] );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function definitions() {
		return [
			[
				'id'          => 'journey-learner',
				'name'        => 'Learner Journey',
				'authority'   => 'NGC_Workflow_Authority',
				'workflow'    => 'NGC_Learner_Booking_Confirmation_Workflow',
				'events'      => [
					NGC_Journey_Events::PAYMENT_CAPTURED,
					NGC_Journey_Events::BOOKING_CONFIRMED,
					NGC_Journey_Events::SESSION_REMINDER_24H,
					NGC_Journey_Events::SESSION_REMINDER_1H,
					NGC_Journey_Events::SESSION_COMPLETED,
					NGC_Journey_Events::RATING_SUBMITTED,
				],
				'states'      => NGC_Journey_State_Machines::all()['learner_booking'],
				'evidence'    => 'delivery/NGT-LEARNER-JOURNEY-MAP.md',
				'status'      => 'MIGRATE',
			],
			[
				'id'          => 'journey-tutor',
				'name'        => 'Tutor Journey',
				'authority'   => 'NGC_Workflow_Authority',
				'workflow'    => 'NGC_Tutor_Go_Live_Workflow',
				'events'      => [
					NGC_Journey_Events::TUTOR_APPLICATION_SUBMITTED,
					NGC_Journey_Events::TUTOR_VERIFIED,
					NGC_Journey_Events::TUTOR_REJECTED,
					NGC_Journey_Events::TUTOR_PAYOUT_CYCLE_STARTED,
				],
				'states'      => NGC_Journey_State_Machines::all()['tutor_lifecycle'],
				'evidence'    => 'delivery/NGT-TUTOR-JOURNEY-MAP.md',
				'status'      => 'MIGRATE',
			],
			[
				'id'          => 'journey-safety',
				'name'        => 'Safety & Compliance Journey',
				'authority'   => 'NGC_Safeguarding',
				'workflow'    => 'NGC_Safety_Escalation_Workflow',
				'events'      => [
					NGC_Journey_Events::SAFETY_FLAG_RAISED,
					NGC_Journey_Events::SAFETY_CASE_CREATED,
					NGC_Journey_Events::SAFETY_SLA_BREACHED,
				],
				'states'      => NGC_Journey_State_Machines::all()['safety_case'],
				'evidence'    => 'delivery/NGT-SAFETY-JOURNEY-MAP.md',
				'status'      => 'MIGRATE',
			],
		];
	}
}
