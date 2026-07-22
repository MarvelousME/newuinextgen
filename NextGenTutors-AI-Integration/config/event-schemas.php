<?php
/**
 * External event schema registry.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$event = static function ( $required, $optional, $allowed, $classification = 'confidential', $consent = false, $external = true, $policy = false, $profile = 'default' ) {
	return [
		'schema_version'            => 1,
		'required_fields'           => $required,
		'optional_fields'           => $optional,
		'allowed_payload_fields'    => $allowed,
		'data_classification'       => $classification,
		'consent_required'          => (bool) $consent,
		'external_delivery_allowed' => (bool) $external,
		'policy_required'           => (bool) $policy,
		'redaction_profile'         => $profile,
	];
};

$standard = [ 'id', 'user_id', 'status', 'reason', 'metadata' ];

return [
	'schema_version' => 1,
	'events'         => [
		'parent.registered'            => $event( [ 'subject_id' ], [], [ 'user_id', 'email', 'first_name', 'last_name', 'roles' ] ),
		'student.registered'           => $event( [ 'subject_id' ], [], [ 'user_id', 'email', 'first_name', 'last_name', 'grade', 'guardian_id' ], 'restricted', true, true, false, 'minor' ),
		'child_learner.created'        => $event( [ 'subject_id' ], [], [ 'learner_id', 'guardian_id', 'grade', 'subjects', 'learning_goals' ], 'restricted', true, true, false, 'minor' ),
		'consent.recorded'             => $event( [ 'subject_id' ], [], [ 'consent_type', 'consent_version', 'granted', 'recorded_at', 'guardian_id' ], 'confidential' ),
		'tutor.application.submitted'  => $event( [ 'subject_id' ], [], [ 'tutor_id', 'application_id', 'subjects', 'grades', 'status', 'submitted_at' ] ),
		'tutor.approved'               => $event( [ 'subject_id' ], [], $standard ),
		'tutor.rejected'               => $event( [ 'subject_id' ], [], $standard ),
		'tutor.resubmission.requested' => $event( [ 'subject_id' ], [], $standard ),
		'tutor.suspended'              => $event( [ 'subject_id' ], [], $standard ),
		'match.requested'              => $event( [ 'subject_id' ], [], [ 'match_id', 'learner_id', 'subjects', 'grade', 'province', 'modality', 'budget', 'candidates' ], 'restricted', true, true, false, 'minor' ),
		'match.proposed'               => $event( [ 'subject_id' ], [], [ 'match_id', 'tutor_id', 'score', 'explanation', 'status' ] ),
		'match.accepted'               => $event( [ 'subject_id' ], [], [ 'match_id', 'tutor_id', 'accepted_by', 'status' ] ),
		'match.rejected'               => $event( [ 'subject_id' ], [], [ 'match_id', 'tutor_id', 'reason', 'status' ] ),
		'booking.created'              => $event( [ 'subject_id' ], [], [ 'booking_id', 'tutor_id', 'learner_id', 'starts_at', 'modality', 'status' ], 'confidential', true ),
		'booking.confirmed'            => $event( [ 'subject_id' ], [], [ 'booking_id', 'starts_at', 'status', 'payment_id' ], 'confidential', true ),
		'booking.rescheduled'          => $event( [ 'subject_id' ], [], [ 'booking_id', 'old_starts_at', 'starts_at', 'reason', 'status' ], 'confidential', true ),
		'booking.cancelled'            => $event( [ 'subject_id' ], [], [ 'booking_id', 'reason', 'cancelled_by', 'status' ], 'confidential', true ),
		'payment.succeeded'            => $event( [ 'subject_id' ], [], [ 'payment_id', 'order_id', 'amount', 'currency', 'provider_reference', 'status' ], 'restricted', false, true, false, 'finance' ),
		'payment.failed'               => $event( [ 'subject_id' ], [], [ 'payment_id', 'order_id', 'amount', 'currency', 'failure_code', 'status' ], 'restricted', false, true, false, 'finance' ),
		'refund.completed'             => $event( [ 'subject_id' ], [], [ 'refund_id', 'payment_id', 'amount', 'currency', 'provider_reference', 'status' ], 'restricted', false, true, false, 'finance' ),
		'lesson.completed'             => $event( [ 'subject_id' ], [], [ 'lesson_id', 'booking_id', 'tutor_id', 'learner_id', 'completed_at', 'status' ], 'confidential', true ),
		'review.submitted'             => $event( [ 'subject_id' ], [], [ 'review_id', 'booking_id', 'rating', 'comment', 'status' ], 'internal' ),
		'progress_report.submitted'    => $event( [ 'subject_id' ], [], [ 'report_id', 'learner_id', 'tutor_id', 'summary', 'subjects', 'submitted_at' ], 'confidential', true ),
		'fraud.signal.raised'          => $event( [ 'subject_id' ], [], [ 'signal_id', 'signal_type', 'risk_score', 'severity', 'evidence', 'status' ], 'restricted', false, true, true, 'safeguarding' ),
		'security.alert.raised'        => $event( [ 'subject_id' ], [], [ 'alert_id', 'alert_type', 'severity', 'evidence', 'status' ], 'restricted', false, true, true, 'safeguarding' ),
		'compliance.case.opened'       => $event( [ 'subject_id' ], [], [ 'case_id', 'case_type', 'risk', 'status', 'evidence' ], 'restricted', false, true, true, 'safeguarding' ),
		'safeguarding.alert.raised'    => $event( [ 'subject_id' ], [], [ 'alert_id', 'alert_type', 'severity', 'evidence', 'status' ], 'restricted', true, true, true, 'safeguarding' ),
	],
];
