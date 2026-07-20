<?php
/**
 * Studio trigger catalog and dynamic registration.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical trigger definitions for the visual trigger builder.
 */
class NGC_Studio_Triggers {

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function catalog() {
		$triggers = [
			'USER_REGISTERED'        => [ 'group' => 'user', 'label' => 'User Registered' ],
			'USER_UPDATED'           => [ 'group' => 'user', 'label' => 'User Updated' ],
			'USER_DELETED'           => [ 'group' => 'user', 'label' => 'User Deleted' ],
			'PARENT_REGISTERED'      => [ 'group' => 'registration', 'label' => 'Parent Registered' ],
			'STUDENT_REGISTERED'     => [ 'group' => 'registration', 'label' => 'Student Registered' ],
			'CHILD_REGISTERED'       => [ 'group' => 'registration', 'label' => 'Child Registered' ],
			'TUTOR_REGISTERED'       => [ 'group' => 'tutor', 'label' => 'Tutor Registered' ],
			'TUTOR_APPROVED'         => [ 'group' => 'tutor', 'label' => 'Tutor Approved' ],
			'TUTOR_REJECTED'         => [ 'group' => 'tutor', 'label' => 'Tutor Rejected' ],
			'TUTOR_RESUBMITTED'      => [ 'group' => 'tutor', 'label' => 'Tutor Resubmitted' ],
			'MATCH_CREATED'          => [ 'group' => 'matching', 'label' => 'Match Created' ],
			'MATCH_ACCEPTED'         => [ 'group' => 'matching', 'label' => 'Match Accepted' ],
			'MATCH_REJECTED'         => [ 'group' => 'matching', 'label' => 'Match Rejected' ],
			'BOOKING_CREATED'        => [ 'group' => 'booking', 'label' => 'Booking Created' ],
			'BOOKING_UPDATED'        => [ 'group' => 'booking', 'label' => 'Booking Updated' ],
			'BOOKING_CANCELLED'      => [ 'group' => 'booking', 'label' => 'Booking Cancelled' ],
			'BOOKING_COMPLETED'      => [ 'group' => 'booking', 'label' => 'Booking Completed' ],
			'PAYMENT_INITIATED'      => [ 'group' => 'payment', 'label' => 'Payment Initiated' ],
			'PAYMENT_COMPLETED'      => [ 'group' => 'payment', 'label' => 'Payment Completed' ],
			'PAYMENT_FAILED'         => [ 'group' => 'payment', 'label' => 'Payment Failed' ],
			'PAYMENT_REFUNDED'       => [ 'group' => 'payment', 'label' => 'Payment Refunded' ],
			'INVOICE_CREATED'        => [ 'group' => 'finance', 'label' => 'Invoice Created' ],
			'INVOICE_PAID'           => [ 'group' => 'finance', 'label' => 'Invoice Paid' ],
			'LESSON_STARTED'         => [ 'group' => 'lesson', 'label' => 'Lesson Started' ],
			'LESSON_COMPLETED'       => [ 'group' => 'lesson', 'label' => 'Lesson Completed' ],
			'REVIEW_CREATED'         => [ 'group' => 'review', 'label' => 'Review Created' ],
			'RATING_CREATED'         => [ 'group' => 'review', 'label' => 'Rating Created' ],
			'EMAIL_SENT'             => [ 'group' => 'email', 'label' => 'Email Sent' ],
			'EMAIL_FAILED'           => [ 'group' => 'email', 'label' => 'Email Failed' ],
			'CRM_CONTACT_CREATED'    => [ 'group' => 'crm', 'label' => 'CRM Contact Created' ],
			'CRM_CONTACT_UPDATED'    => [ 'group' => 'crm', 'label' => 'CRM Contact Updated' ],
			'LMS_STUDENT_CREATED'    => [ 'group' => 'lms', 'label' => 'LMS Student Created' ],
			'LMS_INSTRUCTOR_CREATED' => [ 'group' => 'lms', 'label' => 'LMS Instructor Created' ],
			'LOGIN'                  => [ 'group' => 'auth', 'label' => 'Login' ],
			'LOGOUT'                 => [ 'group' => 'auth', 'label' => 'Logout' ],
			'CRON'                   => [ 'group' => 'system', 'label' => 'Cron' ],
			'WEBHOOK'                => [ 'group' => 'system', 'label' => 'Webhook' ],
			'REST_API'               => [ 'group' => 'system', 'label' => 'REST API' ],
			'CUSTOM_EVENT'           => [ 'group' => 'custom', 'label' => 'Custom Event' ],
		];

		return apply_filters( 'ngc_studio_trigger_catalog', $triggers );
	}

	/**
	 * Map studio trigger keys to WordPress / companion hooks.
	 *
	 * @return array<string, string>
	 */
	public static function hook_map() {
		return apply_filters(
			'ngc_studio_trigger_hook_map',
			[
				'TUTOR_REGISTERED'   => 'ngc_form_submitted_become_tutor',
				'PARENT_REGISTERED'  => 'ngc_form_submitted_parent_register',
				'STUDENT_REGISTERED' => 'ngc_form_submitted_student_register',
				'TUTOR_APPROVED'     => 'ngc_tutor_approved',
				'TUTOR_REJECTED'     => 'ngc_tutor_rejected',
				'MATCH_ACCEPTED'     => 'ngc_match_accepted',
				'BOOKING_CREATED'    => 'ngc_booking_created',
				'BOOKING_CANCELLED'  => 'ngc_booking_cancelled',
				'PAYMENT_COMPLETED'  => 'ngc_payment_received',
				'PAYMENT_FAILED'     => 'ngc_payment_failed',
				'LESSON_COMPLETED'   => 'ngc_lesson_completed',
				'REVIEW_CREATED'     => 'ngc_review_submitted',
				'LOGIN'              => 'wp_login',
				'LOGOUT'             => 'wp_logout',
				'CRON'               => 'ngc_studio_cron_tick',
				'WEBHOOK'            => 'ngc_studio_webhook_received',
				'REST_API'           => 'ngc_studio_rest_triggered',
				'CUSTOM_EVENT'       => 'ngc_studio_custom_event',
			]
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function node_types() {
		return apply_filters(
			'ngc_studio_node_types',
			[
				'START', 'EVENT', 'FORM', 'CONDITION', 'DECISION', 'APPROVAL',
				'EMAIL', 'NOTIFICATION', 'ROLE', 'CREATE_USER', 'UPDATE_USER',
				'CREATE_CONTACT', 'UPDATE_CONTACT', 'BOOKING', 'PAYMENT',
				'CRM', 'LMS', 'WEBHOOK', 'SCRIPT', 'API', 'WAIT', 'DELAY',
				'BRANCH', 'LOOP', 'AUDIT', 'EXPORT', 'AI_ACTION', 'END',
			]
		);
	}
}
