<?php
/**
 * Workflow email template registry.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template definitions, merge fields, render + test send.
 */
class NGC_Workflow_Email_Templates {

	const OPTION_KEY = 'ngc_email_templates';

	/**
	 * @return array<string, array<string, string>>
	 */
	public static function defaults() {
		$site = get_bloginfo( 'name' );
		$support = get_option( 'admin_email' );
		$booking_html = class_exists( 'NGC_Operational_Layouts' ) ? NGC_Operational_Layouts::load( 'booking_confirmed' ) : '';
		$tutor_html   = class_exists( 'NGC_Operational_Layouts' ) ? NGC_Operational_Layouts::load( 'tutor_approved' ) : '';
		$rating_html  = class_exists( 'NGC_Operational_Layouts' ) ? NGC_Operational_Layouts::load( 'session_rating_request' ) : '';
		$popia_html   = class_exists( 'NGC_Operational_Layouts' ) ? NGC_Operational_Layouts::load( 'popia_shell' ) : '';

		return [
			'tutor_registration_received' => [
				'subject' => sprintf( __( '[%s] Tutor application received', 'nextgencompanion' ), $site ),
				'html'    => '<p>Hi {{first_name}},</p><p>Thank you for applying to tutor with {{site_name}}. We have received your application for {{subjects}}.</p><p>Status: {{tutor_status}}</p><p>We will review your application within 48 hours.</p>',
				'text'    => 'Hi {{first_name}}, your tutor application was received. Status: {{tutor_status}}.',
				'trigger' => 'WF-TUTOR-REGISTERED',
				'recipient' => 'tutor',
			],
			'admin_new_tutor_application' => [
				'subject' => sprintf( __( '[%s] New tutor application', 'nextgencompanion' ), $site ),
				'html'    => '<p>New tutor application from {{first_name}} {{last_name}} ({{email}}).</p><p>Subjects: {{subjects}}</p><p>Location: {{location}}</p>',
				'text'    => 'New tutor application: {{email}} — {{subjects}}',
				'trigger' => 'WF-TUTOR-REGISTERED',
				'recipient' => 'admin',
			],
			'tutor_approved' => [
				'subject' => class_exists( 'NGC_Operational_Layouts' ) ? NGC_Operational_Layouts::subject( 'tutor_approved' ) : sprintf( __( '[%s] Your tutor application is approved', 'nextgencompanion' ), $site ),
				'html'    => $tutor_html ?: '<p>Hi {{first_name}},</p><p>Congratulations! Your tutor application has been approved.</p><p><a href="{{dashboard_url}}">Open your tutor dashboard</a></p>',
				'text'    => $tutor_html ? NGC_Operational_Layouts::to_text( $tutor_html ) : 'Your tutor application is approved. Dashboard: {{dashboard_url}}',
				'trigger' => 'WF-TUTOR-APPROVED',
				'recipient' => 'tutor',
			],
			'tutor_onboarding_next_steps' => [
				'subject' => sprintf( __( '[%s] Tutor onboarding next steps', 'nextgencompanion' ), $site ),
				'html'    => '<p>Hi {{first_name}},</p><p>Complete your profile, set availability, and review our tutoring guidelines.</p><p>Login: <a href="{{login_url}}">{{login_url}}</a></p>',
				'text'    => 'Complete onboarding at {{login_url}}',
				'trigger' => 'WF-TUTOR-APPROVED',
				'recipient' => 'tutor',
			],
			'admin_tutor_approval_completed' => [
				'subject' => sprintf( __( '[%s] Tutor approval completed', 'nextgencompanion' ), $site ),
				'html'    => '<p>Tutor {{first_name}} {{last_name}} ({{email}}) approved. CRM/Amelia/LMS sync should be verified.</p>',
				'text'    => 'Tutor approved: {{email}}',
				'trigger' => 'WF-TUTOR-APPROVED',
				'recipient' => 'admin',
			],
			'tutor_application_not_approved' => [
				'subject' => sprintf( __( '[%s] Tutor application update', 'nextgencompanion' ), $site ),
				'html'    => '<p>Hi {{first_name}},</p><p>Thank you for your interest. We are unable to approve your application at this time.</p><p>Reason: {{rejection_reason}}</p>',
				'text'    => 'Application not approved. Reason: {{rejection_reason}}',
				'trigger' => 'WF-TUTOR-REJECTED',
				'recipient' => 'tutor',
			],
			'tutor_resubmission_invitation' => [
				'subject' => sprintf( __( '[%s] You may resubmit your tutor application', 'nextgencompanion' ), $site ),
				'html'    => '<p>Hi {{first_name}},</p><p>You may update and resubmit your application when ready.</p>',
				'text'    => 'You may resubmit your tutor application.',
				'trigger' => 'WF-TUTOR-REJECTED',
				'recipient' => 'tutor',
			],
			'tutor_resubmission_received' => [
				'subject' => sprintf( __( '[%s] Tutor resubmission received', 'nextgencompanion' ), $site ),
				'html'    => '<p>Hi {{first_name}},</p><p>We received your updated tutor application and will review it shortly.</p>',
				'text'    => 'Resubmission received.',
				'trigger' => 'WF-TUTOR-RESUBMITTED',
				'recipient' => 'tutor',
			],
			'admin_tutor_resubmission_review' => [
				'subject' => sprintf( __( '[%s] Tutor resubmission for review', 'nextgencompanion' ), $site ),
				'html'    => '<p>Tutor {{first_name}} {{last_name}} ({{email}}) resubmitted their application.</p>',
				'text'    => 'Tutor resubmission: {{email}}',
				'trigger' => 'WF-TUTOR-RESUBMITTED',
				'recipient' => 'admin',
			],
			'parent_welcome' => [
				'subject' => sprintf( __( '[%s] Welcome to NextGen Tutors', 'nextgencompanion' ), $site ),
				'html'    => '<p>Hi {{first_name}},</p><p>Welcome! Your parent account is ready.</p><p><a href="{{dashboard_url}}">Parent dashboard</a></p>',
				'text'    => 'Welcome! Dashboard: {{dashboard_url}}',
				'trigger' => 'WF-PARENT-REGISTERED',
				'recipient' => 'parent',
			],
			'admin_new_parent_registration' => [
				'subject' => sprintf( __( '[%s] New parent registration', 'nextgencompanion' ), $site ),
				'html'    => '<p>Parent {{first_name}} {{last_name}} ({{email}}) registered. Learner: {{student_name}} Grade: {{grade}}</p>',
				'text'    => 'New parent: {{email}}',
				'trigger' => 'WF-PARENT-REGISTERED',
				'recipient' => 'admin',
			],
			'parent_student_profile_created' => [
				'subject' => sprintf( __( '[%s] Student profile created', 'nextgencompanion' ), $site ),
				'html'    => '<p>Hi {{parent_name}},</p><p>A student profile was created for {{student_name}} (grade {{grade}}).</p>',
				'text'    => 'Student profile created: {{student_name}}',
				'trigger' => 'WF-STUDENT-REGISTERED',
				'recipient' => 'parent',
			],
			'student_welcome' => [
				'subject' => sprintf( __( '[%s] Welcome, student', 'nextgencompanion' ), $site ),
				'html'    => '<p>Hi {{first_name}},</p><p>Your student account is ready. <a href="{{dashboard_url}}">Student dashboard</a></p>',
				'text'    => 'Welcome student. Dashboard: {{dashboard_url}}',
				'trigger' => 'WF-STUDENT-REGISTERED',
				'recipient' => 'student',
			],
			'child_learner_profile_created' => [
				'subject' => sprintf( __( '[%s] Child learner profile created', 'nextgencompanion' ), $site ),
				'html'    => '<p>Hi {{parent_name}},</p><p>We created a learner profile for {{student_name}} (grade {{grade}}).</p>',
				'text'    => 'Child learner: {{student_name}}',
				'trigger' => 'WF-CHILD-REGISTERED',
				'recipient' => 'parent',
			],
			'admin_new_student_registration' => [
				'subject' => sprintf( __( '[%s] New student registration', 'nextgencompanion' ), $site ),
				'html'    => '<p>Student {{first_name}} {{last_name}} ({{email}}) registered. Grade: {{grade}}</p>',
				'text'    => 'New student: {{email}}',
				'trigger' => 'WF-STUDENT-REGISTERED',
				'recipient' => 'admin',
			],
			'admin_child_learner_created' => [
				'subject' => sprintf( __( '[%s] Child learner created', 'nextgencompanion' ), $site ),
				'html'    => '<p>Parent {{parent_name}} created child learner {{student_name}} (grade {{grade}}).</p>',
				'text'    => 'Child learner created for {{parent_name}}',
				'trigger' => 'WF-CHILD-REGISTERED',
				'recipient' => 'admin',
			],
			'booking_confirmed' => [
				'subject'   => class_exists( 'NGC_Operational_Layouts' ) ? NGC_Operational_Layouts::subject( 'booking_confirmed' ) : sprintf( __( '[%s] Booking confirmed', 'nextgencompanion' ), $site ),
				'html'      => $booking_html ?: '<p>Hi {{first_name}},</p><p>Your session with {{tutor_name}} is confirmed for {{booking_date}} {{booking_time}}.</p><p><a href="{{join_url}}">Join session</a></p>',
				'text'      => $booking_html ? NGC_Operational_Layouts::to_text( $booking_html ) : 'Booking confirmed. Join: {{join_url}}',
				'trigger'   => 'booking.confirmed',
				'recipient' => 'parent',
			],
			'session_rating_request' => [
				'subject'   => class_exists( 'NGC_Operational_Layouts' ) ? NGC_Operational_Layouts::subject( 'session_rating_request' ) : sprintf( __( '[%s] Rate your session', 'nextgencompanion' ), $site ),
				'html'      => $rating_html ?: '<p>Hi {{first_name}},</p><p>Please rate the session with {{tutor_name}}.</p><p><a href="{{rating_url}}">Rate session</a></p>',
				'text'      => $rating_html ? NGC_Operational_Layouts::to_text( $rating_html ) : 'Rate your session: {{rating_url}}',
				'trigger'   => 'lesson.completed',
				'recipient' => 'parent',
			],
			'popia_transactional' => [
				'subject'   => class_exists( 'NGC_Operational_Layouts' ) ? NGC_Operational_Layouts::subject( 'popia_shell' ) : sprintf( __( '[%s] {{subject}}', 'nextgencompanion' ), $site ),
				'html'      => $popia_html ?: '<p>Hi {{first_name}},</p><p>{{body_content}}</p><p><a href="{{action_url}}">{{cta_text}}</a></p>',
				'text'      => $popia_html ? NGC_Operational_Layouts::to_text( $popia_html ) : '{{body_content}} {{action_url}}',
				'trigger'   => 'SYSTEM',
				'recipient' => 'user',
			],
			'crm_sync_failed' => [
				'subject' => sprintf( __( '[%s] CRM sync failed', 'nextgencompanion' ), $site ),
				'html'    => '<p>CRM sync failed for workflow {{workflow_status}}. User: {{email}}. Contact support: {{support_email}}</p>',
				'text'    => 'CRM sync failed for {{email}}',
				'trigger' => 'SYSTEM',
				'recipient' => 'admin',
			],
			'amelia_sync_failed' => [
				'subject' => sprintf( __( '[%s] Amelia sync failed', 'nextgencompanion' ), $site ),
				'html'    => '<p>Amelia employee sync failed for tutor {{email}}.</p>',
				'text'    => 'Amelia sync failed: {{email}}',
				'trigger' => 'SYSTEM',
				'recipient' => 'admin',
			],
			'masterstudy_sync_failed' => [
				'subject' => sprintf( __( '[%s] MasterStudy sync failed', 'nextgencompanion' ), $site ),
				'html'    => '<p>MasterStudy profile sync failed for {{email}}.</p>',
				'text'    => 'MasterStudy sync failed: {{email}}',
				'trigger' => 'SYSTEM',
				'recipient' => 'admin',
			],
			'workflow_verification_failed' => [
				'subject' => sprintf( __( '[%s] Workflow verification failed', 'nextgencompanion' ), $site ),
				'html'    => '<p>Post-workflow verification failed for {{workflow_status}}. User: {{email}}</p>',
				'text'    => 'Workflow verification failed.',
				'trigger' => 'SYSTEM',
				'recipient' => 'admin',
			],
			'session_reminder_24h' => [
				'subject' => sprintf( __( '[%s] Session tomorrow — reminder', 'nextgencompanion' ), $site ),
				'html'    => '<p>Hi,</p><p>Your tutoring session is in 24 hours.</p><p>Booking: {{booking_id}}</p><p>Start: {{session_start}}</p><p><a href="{{join_url}}">Join lesson (audio + video)</a></p>',
				'text'    => 'Session in 24 hours. Booking {{booking_id}}. Join: {{join_url}}',
				'trigger' => 'WF-03',
				'recipient' => 'student',
			],
			'session_reminder_1h' => [
				'subject' => sprintf( __( '[%s] Session in 1 hour', 'nextgencompanion' ), $site ),
				'html'    => '<p>Your session starts in one hour. Booking {{booking_id}}.</p><p><a href="{{join_url}}">Join lesson</a></p>',
				'text'    => 'Session in 1 hour. Join: {{join_url}}',
				'trigger' => 'WF-03',
				'recipient' => 'student',
			],
			'session_reminder_15m' => [
				'subject' => sprintf( __( '[%s] Join your session now', 'nextgencompanion' ), $site ),
				'html'    => '<p>Your session starts in 15 minutes.</p><p><a href="{{join_url}}">Join your audio + video lesson now</a></p>',
				'text'    => 'Session in 15 minutes. Join: {{join_url}}',
				'trigger' => 'WF-03',
				'recipient' => 'student',
			],
		];
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	public static function all() {
		$stored = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}
		return array_merge( self::defaults(), $stored );
	}

	/**
	 * @param string               $key     Template key.
	 * @param array<string, mixed> $context Merge context.
	 * @return array<string, string>|WP_Error
	 */
	public static function render( $key, $context ) {
		$templates = self::all();
		if ( empty( $templates[ $key ] ) ) {
			return new WP_Error( 'ngc_template_missing', __( 'Email template not found.', 'nextgencompanion' ) );
		}
		$merge = self::merge_context( $context );
		$tpl   = $templates[ $key ];
		$override = apply_filters( 'ngc_email_template_override', null, $key );
		if ( is_array( $override ) ) {
			$tpl = array_merge( $tpl, $override );
		}
		return [
			'subject' => self::apply_merge( $tpl['subject'], $merge ),
			'html'    => self::apply_merge( $tpl['html'], $merge ),
			'text'    => self::apply_merge( $tpl['text'] ?? '', $merge ),
		];
	}

	/**
	 * @param array<string, mixed> $context Context.
	 * @return array<string, string>
	 */
	public static function merge_context( $context ) {
		$defaults = [
			'first_name'         => (string) ( $context['first_name'] ?? '' ),
			'last_name'          => (string) ( $context['last_name'] ?? '' ),
			'email'              => (string) ( $context['email'] ?? '' ),
			'phone'              => (string) ( $context['phone'] ?? '' ),
			'role'               => (string) ( $context['role'] ?? '' ),
			'workflow_status'    => (string) ( $context['workflow_status'] ?? $context['workflow'] ?? '' ),
			'tutor_status'       => (string) ( $context['tutor_status'] ?? '' ),
			'student_name'       => (string) ( $context['student_name'] ?? $context['child_name'] ?? '' ),
			'parent_name'        => (string) ( $context['parent_name'] ?? '' ),
			'subjects'           => (string) ( $context['subjects'] ?? $context['subject'] ?? '' ),
			'grades'             => (string) ( $context['grade'] ?? $context['grades'] ?? '' ),
			'grade'              => (string) ( $context['grade'] ?? $context['grades'] ?? '' ),
			'location'           => (string) ( $context['location'] ?? '' ),
			'approval_status'    => (string) ( $context['approval_status'] ?? '' ),
			'rejection_reason'   => (string) ( $context['rejection_reason'] ?? '' ),
			'dashboard_url'      => (string) ( $context['dashboard_url'] ?? home_url( '/student-dashboard' ) ),
			'login_url'          => (string) ( $context['login_url'] ?? wp_login_url() ),
			'support_email'      => (string) ( $context['support_email'] ?? get_option( 'admin_email' ) ),
			'support_phone'      => (string) ( $context['support_phone'] ?? get_option( 'ngc_support_phone', '+27 81 334 0625' ) ),
			'site_name'          => (string) ( $context['site_name'] ?? get_bloginfo( 'name' ) ),
			'booking_id'         => (string) ( $context['booking_id'] ?? '' ),
			'booking_date'       => (string) ( $context['booking_date'] ?? '' ),
			'booking_time'       => (string) ( $context['booking_time'] ?? '' ),
			'session_start'      => (string) ( $context['session_start'] ?? $context['starts_at'] ?? '' ),
			'join_url'           => (string) ( $context['join_url'] ?? $context['joinUrl'] ?? '' ),
			'tutor_name'         => (string) ( $context['tutor_name'] ?? '' ),
			'payout_rate'        => (string) ( $context['payout_rate'] ?? 'R320' ),
			'kb_url'             => (string) ( $context['kb_url'] ?? home_url( '/support/' ) ),
			'rating_url'         => (string) ( $context['rating_url'] ?? home_url( '/parent-dashboard/' ) ),
			'preferences_url'    => (string) ( $context['preferences_url'] ?? home_url( '/privacy-policy/' ) ),
			'unsubscribe_url'    => (string) ( $context['unsubscribe_url'] ?? home_url( '/privacy-policy/' ) ),
			'popia_consent_date' => (string) ( $context['popia_consent_date'] ?? wp_date( get_option( 'date_format' ) ) ),
			'body_content'       => (string) ( $context['body_content'] ?? '' ),
			'action_url'         => (string) ( $context['action_url'] ?? home_url( '/' ) ),
			'cta_text'           => (string) ( $context['cta_text'] ?? __( 'Continue', 'nextgencompanion' ) ),
			'subject'            => (string) ( $context['subject'] ?? '' ),
			'year'               => (string) ( $context['year'] ?? gmdate( 'Y' ) ),
		];
		return $defaults;
	}

	/**
	 * @param string                $content Content.
	 * @param array<string, string> $merge   Fields.
	 * @return string
	 */
	private static function apply_merge( $content, $merge ) {
		$url_keys = [
			'dashboard_url', 'login_url', 'join_url', 'kb_url', 'rating_url',
			'preferences_url', 'unsubscribe_url', 'action_url',
		];
		foreach ( $merge as $key => $value ) {
			$safe = in_array( $key, $url_keys, true ) ? esc_url( $value ) : esc_html( $value );
			// Allow raw HTML snippets only for body_content (POPIA shell).
			if ( 'body_content' === $key ) {
				$safe = wp_kses_post( $value );
			}
			$content = str_replace( '{{' . $key . '}}', $safe, $content );
		}
		return $content;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function verify_all() {
		$defaults = self::defaults();
		$all      = self::all();
		$missing  = [];
		foreach ( array_keys( $defaults ) as $key ) {
			if ( empty( $all[ $key ]['subject'] ) || empty( $all[ $key ]['html'] ) ) {
				$missing[] = $key;
			}
		}
		return [
			'ok'      => empty( $missing ),
			'count'   => count( $defaults ),
			'missing' => $missing,
		];
	}

	/**
	 * Install defaults on activation.
	 */
	public static function install_defaults() {
		$current = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $current ) || empty( $current ) ) {
			update_option( self::OPTION_KEY, [], false );
		}
	}
}
