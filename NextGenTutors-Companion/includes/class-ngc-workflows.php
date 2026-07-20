<?php
/**
 * Workflow event dispatcher — bridges to theme bi_workflow_dispatch.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Workflow integration.
 */
class NGC_Workflows {

	/**
	 * @var array<string, string>
	 */
	private static $event_map = [
		'find_tutor.submitted'       => 'ngt.find_tutor.submitted',
		'tutor_application.submitted'=> 'ngt.tutor_application.submitted',
		'parent_register.submitted'  => 'ngt.parent_register.submitted',
		'student_register.submitted' => 'ngt.student_register.submitted',
		'support.escalated'          => 'ngt.support.escalated',
		'tutor.approved'             => 'ngt.tutor.approved',
		'tutor.rejected'             => 'ngt.tutor.rejected',
		'match.accepted'             => 'ngt.match.accepted',
		'match.proposed'             => 'ngt.match.proposed',
		'match.auto_assigned'        => 'ngt.match.auto_assigned',
		'lesson.completed'           => 'ngt.lesson.completed',
		'invoice.issued'             => 'ngt.invoice.issued',
		'payment.failed'             => 'ngt.payment.failed',
		'payment.refunded'           => 'ngt.payment.refunded',
		'payment.received'           => 'ngt.payment.received',
		'payout.processed'           => 'ngt.payout.processed',
		'booking.cancelled'          => 'ngt.booking.cancelled',
		'review.submitted'           => 'ngt.review.submitted',
		'order.completed'            => 'woocommerce.order.completed',
		'booking.created'            => 'amelia.booking.created',
		'session.scheduled'          => 'ngt.session.scheduled',
		'reminders.queued'           => 'ngt.reminders.queued',
		'reminder.24h.sent'          => 'ngt.reminder.24h.sent',
		'reminder.1h.sent'           => 'ngt.reminder.1h.sent',
		'reminder.15m.sent'          => 'ngt.reminder.15m.sent',
		'notification.failed'        => 'ngt.notification.failed',
		'referral.converted'         => 'ngt.referral.converted',
		'payout.calculated'          => 'ngt.payout.calculated',
		'daily.health_check'         => 'ngt.daily.health_check',
		'progress_report.submitted'  => 'ngt.progress_report.submitted',
		'lesson_note.created'        => 'ngt.lesson_note.created',
		'resource.recommended'       => 'ngt.resource.recommended',
	];

	/**
	 * Dispatch a companion event to the theme workflow pack.
	 *
	 * @param string               $event Companion event slug (without ngt. prefix).
	 * @param array<string, mixed> $vars  Template variables.
	 */
	public static function dispatch( $event, $vars = [] ) {
		$full = self::$event_map[ $event ] ?? $event;
		if ( 0 !== strpos( $full, 'ngt.' ) && 0 !== strpos( $full, 'woocommerce.' ) && 0 !== strpos( $full, 'amelia.' ) && 0 !== strpos( $full, 'wp.' ) ) {
			$full = 'ngt.' . ltrim( $full, '.' );
		}

		$vars = apply_filters( 'ngc_workflow_vars', $vars, $event, $full );

		if ( function_exists( 'bi_workflow_dispatch' ) ) {
			bi_workflow_dispatch( $full, $vars );
		}

		/**
		 * Fires after companion workflow dispatch.
		 *
		 * @param string               $full Full event key.
		 * @param array<string, mixed> $vars Variables.
		 */
		do_action( 'ngc_workflow_dispatched', $full, $vars );
		do_action( 'ngc_' . str_replace( '.', '_', $event ), $vars );
	}

	/**
	 * Map form payload to workflow vars (theme-compatible).
	 *
	 * @param array<string, mixed> $payload Form data.
	 * @return array<string, mixed>
	 */
	public static function vars_from_payload( $payload ) {
		if ( function_exists( 'bi_workflow_vars_from_payload' ) ) {
			return bi_workflow_vars_from_payload( $payload );
		}
		return [
			'name'       => $payload['parent_name'] ?? $payload['name'] ?? $payload['full_name'] ?? '',
			'child_name' => $payload['child_name'] ?? '',
			'email'      => $payload['email'] ?? '',
			'phone'      => $payload['phone'] ?? $payload['mobile'] ?? '',
			'grade'      => $payload['grade'] ?? '',
			'subject'    => $payload['subject'] ?? $payload['subjects'] ?? '',
			'area'       => $payload['area'] ?? $payload['province'] ?? '',
			'bio'        => $payload['bio'] ?? $payload['message'] ?? '',
			'subjects'   => $payload['subjects'] ?? $payload['subject'] ?? '',
			'summary'    => $payload['message'] ?? $payload['subject'] ?? '',
			'source'     => 'companion-form',
			'priority'   => 'normal',
		];
	}

	/**
	 * Handle companion form submissions.
	 *
	 * @param string               $form_id Form slug.
	 * @param array<string, mixed> $payload Fields.
	 */
	public static function on_form_submitted( $form_id, $payload ) {
		$events = [
			'find_tutor'       => 'find_tutor.submitted',
			'become_tutor'     => 'tutor_application.submitted',
			'contact_support'  => 'support.escalated',
			'parent_register'  => 'parent_register.submitted',
			'student_register' => 'student_register.submitted',
		];
		// Registration workflows dispatch via NGC_Workflow_Orchestrator.
		if ( in_array( $form_id, [ 'become_tutor', 'parent_register', 'student_register' ], true ) ) {
			return;
		}
		if ( empty( $events[ $form_id ] ) ) {
			return;
		}
		$vars = self::vars_from_payload( $payload );
		if ( 'support.escalated' === $events[ $form_id ] ) {
			$vars['summary']  = wp_json_encode( $payload, JSON_PRETTY_PRINT );
			$vars['priority'] = 'high';
		}
		self::dispatch( $events[ $form_id ], $vars );
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'ngc_form_submitted', [ __CLASS__, 'on_form_submitted' ], 10, 2 );
	}
}
