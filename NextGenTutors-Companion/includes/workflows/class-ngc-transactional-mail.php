<?php
/**
 * Sends operational HTML emails on booking confirm, tutor approval, and lesson complete.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transactional mail hooks using IMPORTANT HTML layouts.
 */
class NGC_Transactional_Mail {

	/** @var array<int, bool> */
	private static $sent_booking = [];

	/** @var array<int, bool> */
	private static $sent_rating = [];

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'ngc_booking_confirmed', [ __CLASS__, 'on_booking_confirmed' ], 20, 2 );
		add_action( 'ngc_lesson_completed', [ __CLASS__, 'on_lesson_completed' ], 20, 1 );
		add_action( 'ngc_tutor_approved', [ __CLASS__, 'on_tutor_approved' ], 20, 1 );
	}

	/**
	 * Welcome & booking confirmation to parent/student.
	 *
	 * @param int|array<string,mixed> $booking_id Booking ID or workflow vars.
	 * @param array<string, mixed>    $context    Context when called from bookings.
	 */
	public static function on_booking_confirmed( $booking_id, $context = [] ) {
		if ( is_array( $booking_id ) ) {
			$context    = $booking_id;
			$booking_id = (int) ( $context['booking_id'] ?? 0 );
		} else {
			$booking_id = (int) $booking_id;
			$context    = is_array( $context ) ? $context : [];
		}

		$booking = ( $booking_id && class_exists( 'NGC_Bookings' ) ) ? NGC_Bookings::get( $booking_id ) : null;
		if ( ! $booking ) {
			return;
		}
		if ( ! empty( self::$sent_booking[ $booking_id ] ) ) {
			return;
		}
		self::$sent_booking[ $booking_id ] = true;

		$student_id = (int) ( $booking->student_user_id ?? 0 );
		$tutor_id   = (int) ( $booking->tutor_user_id ?? 0 );
		$to_user    = self::notify_user_for_student( $student_id );
		if ( ! $to_user ) {
			return;
		}

		$scheduled = (string) ( $booking->scheduled_at ?? $context['session_start'] ?? '' );
		$ts        = $scheduled ? strtotime( $scheduled ) : false;
		$join_url  = (string) ( $context['join_url'] ?? '' );
		if ( ! $join_url && class_exists( 'NGC_Meetings' ) ) {
			$meeting  = NGC_Meetings::ensure_for_booking( $booking_id );
			$join_url = ( ! is_wp_error( $meeting ) && is_array( $meeting ) ) ? (string) ( $meeting['join_url'] ?? '' ) : '';
		}

		$merge = [
			'first_name'         => (string) ( $to_user->first_name ?: $to_user->display_name ),
			'student_name'       => self::display_name( $student_id ),
			'tutor_name'         => self::display_name( $tutor_id ),
			'booking_date'       => $ts ? wp_date( get_option( 'date_format' ), $ts ) : $scheduled,
			'booking_time'       => $ts ? wp_date( get_option( 'time_format' ), $ts ) : '',
			'join_url'           => $join_url ?: home_url( '/student-dashboard/' ),
			'dashboard_url'      => home_url( '/parent-dashboard/' ),
			'booking_id'         => (string) $booking_id,
			'session_start'      => $scheduled,
			'subjects'           => (string) ( $booking->subject ?? $context['subject'] ?? '' ),
			'email'              => (string) $to_user->user_email,
			'user_id'            => (int) $to_user->ID,
			'preferences_url'    => class_exists( 'NGC_Popia_Consent' ) ? NGC_Popia_Consent::preferences_url() : home_url( '/privacy-policy/' ),
			'unsubscribe_url'    => class_exists( 'NGC_Popia_Consent' ) ? NGC_Popia_Consent::withdraw_url( (int) $to_user->ID ) : home_url( '/privacy-policy/' ),
			'support_phone'      => self::support_phone(),
			'popia_consent_date' => wp_date( get_option( 'date_format' ) ),
		];

		self::send( 'booking_confirmed', (string) $to_user->user_email, $merge );
	}

	/**
	 * Session rating request after lesson completed.
	 *
	 * @param array<string, mixed>|int $payload Lesson context or booking id.
	 */
	public static function on_lesson_completed( $payload ) {
		$ctx        = is_array( $payload ) ? $payload : [ 'booking_id' => (int) $payload ];
		$booking_id = (int) ( $ctx['booking_id'] ?? 0 );
		$student_id = (int) ( $ctx['student_user_id'] ?? 0 );
		$tutor_id   = (int) ( $ctx['tutor_user_id'] ?? 0 );

		if ( $booking_id && class_exists( 'NGC_Bookings' ) ) {
			$booking = NGC_Bookings::get( $booking_id );
			if ( $booking ) {
				$student_id = $student_id ?: (int) $booking->student_user_id;
				$tutor_id   = $tutor_id ?: (int) $booking->tutor_user_id;
			}
		}

		$to_user = self::notify_user_for_student( $student_id );
		if ( ! $to_user ) {
			return;
		}
		if ( $booking_id && ! empty( self::$sent_rating[ $booking_id ] ) ) {
			return;
		}
		if ( $booking_id ) {
			self::$sent_rating[ $booking_id ] = true;
		}

		$rating_url = add_query_arg(
			[
				'ngc_rate'    => 1,
				'booking_id'  => $booking_id,
				'tutor_id'    => $tutor_id,
			],
			home_url( '/parent-dashboard/' )
		);

		$merge = [
			'first_name'      => (string) ( $to_user->first_name ?: $to_user->display_name ),
			'student_name'    => self::display_name( $student_id ),
			'tutor_name'      => self::display_name( $tutor_id ),
			'rating_url'      => $rating_url,
			'booking_id'      => (string) $booking_id,
			'dashboard_url'   => home_url( '/parent-dashboard/' ),
			'email'           => (string) $to_user->user_email,
			'user_id'         => (int) $to_user->ID,
			'unsubscribe_url' => class_exists( 'NGC_Popia_Consent' ) ? NGC_Popia_Consent::withdraw_url( (int) $to_user->ID ) : home_url( '/privacy-policy/' ),
		];

		self::send( 'session_rating_request', (string) $to_user->user_email, $merge );
	}

	/**
	 * Ensure tutor approval uses the HTML welcome layout (orchestrator also sends).
	 * This is a no-op duplicate guard — orchestrator remains primary.
	 *
	 * @param array<string, mixed> $vars Approval vars.
	 */
	public static function on_tutor_approved( $vars ) {
		// Orchestrator already sends tutor_approved; layout is supplied via defaults().
		unset( $vars );
	}

	/**
	 * @param string               $template_key Template key.
	 * @param string               $to           Recipient.
	 * @param array<string, mixed> $context      Merge context.
	 */
	private static function send( $template_key, $to, $context ) {
		if ( ! $to || ! is_email( $to ) || ! class_exists( 'NGC_Email_Adapter' ) ) {
			return;
		}
		$adapter = new NGC_Email_Adapter();
		$adapter->create_or_update(
			'send_template',
			[
				'template_key' => $template_key,
				'to'           => $to,
				'context'      => $context,
			]
		);
	}

	/**
	 * Prefer parent account email for a learner; fall back to student.
	 *
	 * @param int $student_id Student user ID.
	 * @return WP_User|null
	 */
	private static function notify_user_for_student( $student_id ) {
		$student_id = (int) $student_id;
		if ( ! $student_id ) {
			return null;
		}
		$parent_id = (int) get_user_meta( $student_id, 'ngc_parent_user_id', true );
		if ( ! $parent_id && class_exists( 'NGC_Child_Learners' ) && method_exists( 'NGC_Child_Learners', 'parent_for_student' ) ) {
			$parent_id = (int) NGC_Child_Learners::parent_for_student( $student_id );
		}
		$user = get_userdata( $parent_id ?: $student_id );
		return $user instanceof WP_User ? $user : null;
	}

	/**
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function display_name( $user_id ) {
		$user = get_userdata( (int) $user_id );
		if ( ! $user ) {
			return '';
		}
		$name = trim( (string) $user->first_name . ' ' . (string) $user->last_name );
		return $name !== '' ? $name : (string) $user->display_name;
	}

	/**
	 * @return string
	 */
	private static function support_phone() {
		if ( class_exists( 'NGC_Business_Profile' ) && method_exists( 'NGC_Business_Profile', 'get' ) ) {
			$profile = NGC_Business_Profile::get();
			$phone   = (string) ( $profile['contact']['phone'] ?? $profile['phone'] ?? '' );
			if ( $phone ) {
				return $phone;
			}
		}
		return (string) get_option( 'ngc_support_phone', '+27 81 334 0625' );
	}
}
