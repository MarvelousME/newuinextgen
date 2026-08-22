<?php
/**
 * Canonical journey event catalog + aliases to legacy ngc/ngt hooks.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single vocabulary for Learner / Tutor / Safety journeys.
 */
final class NGC_Journey_Events {

	public const PAYMENT_CAPTURED           = 'PaymentCaptured';
	public const BOOKING_CONFIRMED          = 'BookingConfirmed';
	public const BOOKING_REQUESTED          = 'BookingRequested';
	public const SESSION_COMPLETED          = 'SessionCompleted';
	public const SESSION_REMINDER_24H       = 'SessionReminderDue24Hours';
	public const SESSION_REMINDER_1H        = 'SessionReminderDue1Hour';
	public const RATING_SUBMITTED           = 'RatingSubmitted';
	public const PAYMENT_FAILED             = 'PaymentFailed';
	public const TUTOR_APPLICATION_SUBMITTED = 'TutorApplicationSubmitted';
	public const TUTOR_VERIFIED             = 'TutorVerified';
	public const TUTOR_REJECTED             = 'TutorRejected';
	public const TUTOR_ONBOARDING_COMPLETED = 'TutorOnboardingCompleted';
	public const TUTOR_PAYOUT_CYCLE_STARTED = 'TutorPayoutCycleStarted';
	public const SESSION_NO_SHOW_DETECTED   = 'SessionNoShowDetected';
	public const REFERRAL_REGISTERED        = 'ReferralRegistered';
	public const SAFETY_FLAG_RAISED         = 'SafetyFlagRaised';
	public const SAFETY_CASE_CREATED        = 'SafetyCaseCreated';
	public const SAFETY_SLA_BREACHED        = 'SafetySlaBreached';

	/**
	 * Canonical → legacy aliases (emit both when migrating).
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function aliases() {
		return apply_filters(
			'ngc_journey_event_aliases',
			[
				self::PAYMENT_CAPTURED            => [ 'ngc_payment_settled', 'ngt.payment.received', 'payment.received', 'payment.completed' ],
				self::BOOKING_CONFIRMED           => [ 'ngc_booking_confirmed', 'booking.created', 'amelia.booking.created' ],
				self::BOOKING_REQUESTED           => [ 'ngc_booking_created' ],
				self::SESSION_COMPLETED           => [ 'ngc_booking_completed', 'ngt.lesson.completed', 'lesson.completed' ],
				self::SESSION_REMINDER_24H        => [ 'ngt.reminder.24h.sent', 'reminder.24h.sent' ],
				self::SESSION_REMINDER_1H         => [ 'ngt.reminder.1h.sent', 'reminder.1h.sent' ],
				self::RATING_SUBMITTED            => [ 'ngc_review_submitted', 'ngt.review.submitted', 'review.submitted' ],
				self::PAYMENT_FAILED              => [ 'ngc_payment_failed', 'ngt.payment.failed', 'payment.failed' ],
				self::TUTOR_APPLICATION_SUBMITTED => [ 'TUTOR_REGISTERED', 'ngt.tutor_application.submitted', 'tutor_application.submitted' ],
				self::TUTOR_VERIFIED              => [ 'TUTOR_APPROVED', 'ngc_tutor_approved', 'ngt.tutor.approved', 'tutor.approved' ],
				self::TUTOR_REJECTED              => [ 'TUTOR_REJECTED', 'ngc_tutor_rejected', 'ngt.tutor.rejected', 'tutor.rejected' ],
				self::TUTOR_PAYOUT_CYCLE_STARTED  => [ 'ngc_monthly_payout_batch', 'payout.run.started' ],
				self::REFERRAL_REGISTERED         => [ 'ngc_referral_converted', 'ngt.referral.converted' ],
				self::SAFETY_FLAG_RAISED          => [ 'ngc_safeguarding_flag_raised' ],
				self::SAFETY_CASE_CREATED         => [ 'ngc_safeguarding_case_created' ],
				self::SAFETY_SLA_BREACHED         => [ 'ngc_safeguarding_sla_breached' ],
			]
		);
	}

	/**
	 * Emit a canonical journey event (and optional legacy aliases).
	 *
	 * @param string               $event   Canonical name.
	 * @param array<string, mixed> $payload Payload.
	 * @param bool                 $legacy  Also fire legacy aliases.
	 */
	public static function emit( $event, array $payload = [], $legacy = true ) {
		$event = (string) $event;
		$payload['canonical_event'] = $event;
		$payload['emitted_at']      = gmdate( 'c' );
		if ( empty( $payload['correlation_id'] ) && class_exists( 'NGC_Platform_Observability' ) ) {
			$payload['correlation_id'] = NGC_Platform_Observability::current_trace_id();
		}

		/**
		 * Canonical journey event.
		 *
		 * @param array  $payload Payload.
		 * @param string $event   Event name.
		 */
		do_action( 'ngc_journey_event', $payload, $event );
		do_action( 'ngc_journey_event_' . sanitize_key( $event ), $payload );

		if ( ! $legacy ) {
			return;
		}

		// Only re-dispatch dotted companion event slugs — never re-fire typed WP hooks
		// (e.g. ngc_payment_settled expects int $order_id) to avoid dual-fire / arity bugs.
		$map = self::aliases();
		foreach ( (array) ( $map[ $event ] ?? [] ) as $alias ) {
			$alias = (string) $alias;
			if ( '' === $alias || $alias === $event ) {
				continue;
			}
			if ( 0 === strpos( $alias, 'ngc_' ) || 0 === strpos( $alias, 'TUTOR_' ) || false === strpos( $alias, '.' ) ) {
				continue;
			}
			if ( class_exists( 'NGC_Workflows' ) ) {
				$slug = preg_replace( '/^ngt\./', '', $alias );
				NGC_Workflows::dispatch( (string) $slug, $payload );
			}
		}
	}

	/**
	 * Resolve a legacy hook/event string to a canonical name when known.
	 *
	 * @param string $legacy Legacy key.
	 * @return string Empty if unknown.
	 */
	public static function resolve( $legacy ) {
		$legacy = (string) $legacy;
		foreach ( self::aliases() as $canonical => $list ) {
			if ( $canonical === $legacy || in_array( $legacy, $list, true ) ) {
				return $canonical;
			}
		}
		return '';
	}
}
