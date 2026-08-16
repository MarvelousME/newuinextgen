<?php
/**
 * Journey state machine definitions (documentation + transition helpers).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Explicit states for learner booking, tutor lifecycle, safety cases.
 */
final class NGC_Journey_State_Machines {

	/**
	 * @return array<string, array{states:array<int,string>,transitions:array<string,array<int,string>>}>
	 */
	public static function all() {
		return [
			'learner_booking' => [
				'states'      => [
					'DISCOVERED',
					'SELECTING_TUTOR',
					'BOOKING_REQUESTED',
					'PAYMENT_PENDING',
					'PAYMENT_CAPTURED',
					'BOOKING_CONFIRMED',
					'SESSION_SCHEDULED',
					'SESSION_READY',
					'SESSION_ACTIVE',
					'SESSION_COMPLETED',
					'RATING_PENDING',
					'COMPLETED',
				],
				'transitions' => [
					'DISCOVERED'         => [ 'SELECTING_TUTOR' ],
					'SELECTING_TUTOR'    => [ 'BOOKING_REQUESTED' ],
					'BOOKING_REQUESTED'  => [ 'PAYMENT_PENDING' ],
					'PAYMENT_PENDING'    => [ 'PAYMENT_CAPTURED', 'DISCOVERED' ],
					'PAYMENT_CAPTURED'   => [ 'BOOKING_CONFIRMED' ],
					'BOOKING_CONFIRMED'  => [ 'SESSION_SCHEDULED' ],
					'SESSION_SCHEDULED'  => [ 'SESSION_READY' ],
					'SESSION_READY'      => [ 'SESSION_ACTIVE' ],
					'SESSION_ACTIVE'     => [ 'SESSION_COMPLETED' ],
					'SESSION_COMPLETED'  => [ 'RATING_PENDING', 'COMPLETED' ],
					'RATING_PENDING'     => [ 'COMPLETED' ],
				],
			],
			'tutor_lifecycle' => [
				'states'      => [
					'APPLICATION_DRAFT',
					'SUBMITTED',
					'UNDER_REVIEW',
					'VERIFIED',
					'REJECTED',
					'RESUBMISSION_REQUIRED',
					'ONBOARDING',
					'READY_FOR_LISTING',
					'LIVE',
					'SUSPENDED',
					'INACTIVE',
				],
				'transitions' => [
					'APPLICATION_DRAFT'       => [ 'SUBMITTED' ],
					'SUBMITTED'               => [ 'UNDER_REVIEW' ],
					'UNDER_REVIEW'            => [ 'VERIFIED', 'REJECTED', 'RESUBMISSION_REQUIRED' ],
					'REJECTED'                => [ 'RESUBMISSION_REQUIRED', 'INACTIVE' ],
					'RESUBMISSION_REQUIRED'   => [ 'SUBMITTED' ],
					'VERIFIED'                => [ 'ONBOARDING' ],
					'ONBOARDING'              => [ 'READY_FOR_LISTING' ],
					'READY_FOR_LISTING'       => [ 'LIVE', 'ONBOARDING' ],
					'LIVE'                    => [ 'SUSPENDED', 'INACTIVE' ],
					'SUSPENDED'               => [ 'LIVE', 'INACTIVE' ],
				],
			],
			'safety_case'     => [
				'states'      => [
					'FLAGGED',
					'TRIAGED',
					'UNDER_REVIEW',
					'ACTION_REQUIRED',
					'RESTRICTED',
					'RESOLVED',
					'CLOSED',
				],
				'transitions' => [
					'FLAGGED'         => [ 'TRIAGED' ],
					'TRIAGED'         => [ 'UNDER_REVIEW', 'ACTION_REQUIRED' ],
					'UNDER_REVIEW'    => [ 'ACTION_REQUIRED', 'RESTRICTED', 'RESOLVED' ],
					'ACTION_REQUIRED' => [ 'RESTRICTED', 'RESOLVED' ],
					'RESTRICTED'      => [ 'UNDER_REVIEW', 'RESOLVED' ],
					'RESOLVED'        => [ 'CLOSED' ],
				],
			],
		];
	}

	/**
	 * @param string $machine Machine key.
	 * @param string $from    From state.
	 * @param string $to      To state.
	 * @return bool
	 */
	public static function can_transition( $machine, $from, $to ) {
		$all = self::all();
		if ( empty( $all[ $machine ] ) ) {
			return false;
		}
		$from = strtoupper( (string) $from );
		$to   = strtoupper( (string) $to );
		$allowed = $all[ $machine ]['transitions'][ $from ] ?? [];
		return in_array( $to, $allowed, true );
	}
}
