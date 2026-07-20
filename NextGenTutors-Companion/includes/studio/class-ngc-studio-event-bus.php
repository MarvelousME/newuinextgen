<?php
/**
 * Studio event bus — routes events to runtime without restart.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central event bus for studio triggers and custom events.
 */
class NGC_Studio_Event_Bus {

	/**
	 * @param string               $event   Trigger/event key.
	 * @param array<string, mixed> $context Payload.
	 */
	public static function emit( $event, $context = [] ) {
		$event   = sanitize_key( (string) $event );
		$context = is_array( $context ) ? $context : [];

		/**
		 * Fires before studio event dispatch.
		 *
		 * @param string               $event   Event key.
		 * @param array<string, mixed> $context Context.
		 */
		do_action( 'ngc_studio_before_event', $event, $context );

		NGC_Studio_Runtime::dispatch_event( $event, $context );

		// Bridge to legacy orchestrator when mapped.
		$legacy_map = [
			'TUTOR_REGISTERED'   => 'tutor.application.submitted',
			'TUTOR_APPROVED'     => 'tutor.approved',
			'TUTOR_REJECTED'     => 'tutor.rejected',
			'TUTOR_RESUBMITTED'  => 'tutor.more_info_requested',
			'PARENT_REGISTERED'  => 'parent_register.submitted',
			'STUDENT_REGISTERED' => 'student_register.submitted',
			'PAYMENT_COMPLETED'  => 'payment.completed',
			'BOOKING_CREATED'    => 'booking.created',
			'REVIEW_CREATED'     => 'review.submitted',
		];
		if ( isset( $legacy_map[ $event ] ) && class_exists( 'NGC_Workflow_Orchestrator' ) ) {
			NGC_Workflow_Orchestrator::execute_integrate_event( $legacy_map[ $event ], $context );
		}

		do_action( 'ngc_studio_event', $event, $context );
	}
}
