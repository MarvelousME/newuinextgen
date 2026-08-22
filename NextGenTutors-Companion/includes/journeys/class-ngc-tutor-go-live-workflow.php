<?php
/**
 * Tutor go-live workflow — TutorVerified projections (CRM / LMS / gamification).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs after orchestrator approval; does not replace verification decision.
 */
final class NGC_Tutor_Go_Live_Workflow {

	public const ACTION = 'tutor_go_live_projections';

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'ngc_workflow_completed', [ __CLASS__, 'on_workflow_completed' ], 20, 3 );
		add_action( 'ngc_workflow_authority_execute_' . self::ACTION, [ __CLASS__, 'execute' ], 10, 1 );
	}

	/**
	 * @param string               $workflow Workflow key.
	 * @param array<string, mixed> $context  Context.
	 * @param array<string, mixed> $result   Result.
	 */
	public static function on_workflow_completed( $workflow, $context, $result ) {
		if ( ! class_exists( 'NGC_Business_Rules' ) || ! NGC_Business_Rules::journey_enabled( 'tutor' ) ) {
			return;
		}
		if ( 'TUTOR_APPROVED' !== strtoupper( (string) $workflow ) ) {
			return;
		}
		if ( empty( $result['ok'] ) ) {
			return;
		}
		$user_id = (int) ( $context['user_id'] ?? $context['tutor_user_id'] ?? 0 );
		if ( $user_id <= 0 ) {
			return;
		}
		$payload = [
			'user_id'         => $user_id,
			'idempotency_key' => 'tutor_go_live:' . $user_id,
		];
		if ( class_exists( 'NGC_Workflow_Authority' ) && class_exists( 'NGC_Platform' ) && NGC_Platform::authority_enabled() ) {
			NGC_Workflow_Authority::from_producer( 'journey', self::ACTION, $payload );
			return;
		}
		self::execute( $payload );
	}

	/**
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	public static function execute( $payload ) {
		$user_id = (int) ( $payload['user_id'] ?? 0 );
		$steps   = [];
		if ( $user_id <= 0 ) {
			return [ 'ok' => false, 'steps' => [ 'missing_user' ] ];
		}

		if ( class_exists( 'NGC_Journey_Events' ) ) {
			NGC_Journey_Events::emit(
				NGC_Journey_Events::TUTOR_VERIFIED,
				[ 'user_id' => $user_id ],
				false
			);
			$steps[] = 'event_TutorVerified';
		}

		$user = get_userdata( $user_id );
		if ( $user && class_exists( 'NGC_Fluentcrm_Adapter' ) ) {
			$crm = new NGC_Fluentcrm_Adapter();
			if ( $crm->is_available() ) {
				$crm->create_or_update(
					'upsert_contact',
					[
						'email'       => $user->user_email,
						'user_id'     => $user_id,
						'first_name'  => $user->first_name,
						'last_name'   => $user->last_name,
						'tags'        => [ 'Verified Tutor', 'Ready for Bookings' ],
						'detach_tags' => [ 'Tutor Applicant', 'Pending Review' ],
						'lists'       => [ 'Tutor' ],
						'workflow'    => 'TutorGoLive',
					]
				);
				$steps[] = 'crm_verified_projected';
			}
		}

		if ( class_exists( 'NGC_Gamification_Provider_Port' ) ) {
			NGC_Gamification_Provider_Port::award_achievement( $user_id, 'verified' );
			$steps[] = 'gamification_verified_badge';
		}

		if ( class_exists( 'NGC_Achievement_Engine' ) ) {
			NGC_Achievement_Engine::award( $user_id, 'tutor_approved' );
			$steps[] = 'achievement_tutor_approved';
		}

		// Listing eligibility is evaluated — publication still requires policy.
		$eligible = self::evaluate_listing_eligibility( $user_id );
		$steps[]  = $eligible ? 'listing_eligible' : 'listing_denied_or_pending';
		update_user_meta( $user_id, 'ngt_listing_eligibility', $eligible ? 'ALLOW_PUBLIC_LISTING' : 'REQUIRE_REVIEW' );

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'tutor_go_live', 'user', $user_id, [ 'steps' => $steps, 'eligible' => $eligible ] );
		}

		return [ 'ok' => true, 'steps' => $steps, 'listing_eligible' => $eligible ];
	}

	/**
	 * Listing gate — Match RC-02 mandatory checks; auto-publish OFF unless filtered.
	 *
	 * @param int $user_id Tutor user ID.
	 * @return bool
	 */
	public static function evaluate_listing_eligibility( $user_id ) {
		$user_id = (int) $user_id;
		$status  = (string) get_user_meta( $user_id, 'ngc_tutor_status', true );
		if ( ! in_array( $status, [ 'approved', 'verified', 'active' ], true ) ) {
			$user = get_userdata( $user_id );
			if ( ! $user || ! array_intersect( [ 'ngt_tutor', 'tutor', 'um_tutor' ], (array) $user->roles ) ) {
				return false;
			}
		}
		if ( class_exists( 'NGC_Tutor_Listing_Eligibility' ) ) {
			return NGC_Tutor_Listing_Eligibility::may_auto_publish( $user_id );
		}
		return (bool) apply_filters( 'ngt_tutor_listing_auto_publish', false, $user_id );
	}
}
