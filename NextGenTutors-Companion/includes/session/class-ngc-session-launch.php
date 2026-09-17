<?php
/**
 * Authoritative JOIN LESSON launch.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side authorization + join window + player/meeting URL issuance.
 */
class NGC_Session_Launch {

	/**
	 * @param int $session_id Session ID.
	 * @param int $user_id    Actor.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function launch( $session_id, $user_id = 0 ) {
		$user_id = $user_id ?: get_current_user_id();
		$session = NGC_Session_Repository::get( (int) $session_id );
		if ( ! $session ) {
			return new WP_Error( 'ngc_session_not_found', __( 'Session not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
		}
		if ( ! self::can_participate( $session, $user_id ) ) {
			NGC_Session_Observability::success( 'join_denied_total', $session + [ 'user_id' => $user_id, 'operation' => 'launch' ] );
			$ex = new NGC_Session_Authorization_Exception(
				__( 'You are not a participant in this lesson.', 'nextgencompanion' ),
				'ngc_session_forbidden',
				[ 'session_id' => (int) $session_id ]
			);
			return $ex->to_wp_error();
		}

		$window = NGC_Session_Join_Policy::evaluate( $session );
		if ( empty( $window['allowed'] ) ) {
			NGC_Session_Observability::success( 'join_denied_total', $session + [ 'user_id' => $user_id, 'reason' => $window['reason'] ] );
			$err = new WP_Error(
				'ngc_join_denied',
				$window['label'] ?: __( 'This lesson is not available to join right now.', 'nextgencompanion' ),
				[ 'status' => 409, 'reason' => $window['reason'], 'window' => $window ]
			);
			if ( class_exists( 'NGC_Session_Audit_Adapter' ) ) {
				( new NGC_Session_Audit_Adapter() )->record(
					'join_authorized',
					'session',
					(int) $session_id,
					'denied',
					[ 'correlation_id' => $session['correlation_id'], 'error_code' => $window['reason'], 'actor' => $user_id ]
				);
			}
			return $err;
		}

		$now   = function_exists( 'current_time' ) ? current_time( 'mysql', true ) : gmdate( 'Y-m-d H:i:s' );
		$roles = [];
		if ( function_exists( 'get_userdata' ) ) {
			$user  = get_userdata( $user_id );
			$roles = $user ? (array) $user->roles : [];
		}
		$is_tutor   = (int) $session['tutor_user_id'] === (int) $user_id;
		$is_student = (int) $session['student_user_id'] === (int) $user_id;
		$patch      = [];
		if ( $is_student && empty( $session['student_joined_at'] ) ) {
			$patch['student_joined_at'] = $now;
		}
		if ( $is_tutor && empty( $session['tutor_joined_at'] ) ) {
			$patch['tutor_joined_at'] = $now;
		}
		if ( NGC_Session_State_Machine::can_transition( (string) $session['status'], NGC_Session_States::IN_PROGRESS ) ) {
			$trans = NGC_Session_Repository::transition( (int) $session_id, NGC_Session_States::IN_PROGRESS, $patch );
			if ( ! is_wp_error( $trans ) ) {
				$session = $trans;
			}
		} elseif ( $patch ) {
			$updated = NGC_Session_Repository::update( (int) $session_id, $patch );
			if ( ! is_wp_error( $updated ) ) {
				$session = $updated;
			}
		}

		$player  = '';
		$meeting = '';
		if ( (int) $session['masterstudy_course_id'] && (int) $session['masterstudy_lesson_id'] ) {
			$learning = new NGC_Session_Learning_Adapter();
			$player   = $learning->player_url( (int) $session['masterstudy_course_id'], (int) $session['masterstudy_lesson_id'] );
		}
		$meet_adapter = new NGC_Session_Meeting_Adapter();
		$meet_url     = $meet_adapter->join_url_for_user( $session, $user_id );
		if ( ! is_wp_error( $meet_url ) ) {
			$meeting = (string) $meet_url;
		}

		$launch_url = $player ?: $meeting;
		if ( ! $launch_url ) {
			return new WP_Error( 'ngc_launch_missing', __( 'Lesson destination is not provisioned.', 'nextgencompanion' ), [ 'status' => 409 ] );
		}

		$event = $is_tutor ? 'tutor_joined' : 'student_joined';
		( new NGC_Session_Audit_Adapter() )->record(
			'join_authorized',
			'session',
			(int) $session_id,
			'success',
			[ 'correlation_id' => $session['correlation_id'], 'actor' => $user_id ]
		);
		( new NGC_Session_Audit_Adapter() )->record(
			$event,
			'session',
			(int) $session_id,
			'success',
			[ 'correlation_id' => $session['correlation_id'], 'actor' => $user_id ]
		);
		if ( NGC_Session_States::IN_PROGRESS === $session['status'] ) {
			( new NGC_Session_Audit_Adapter() )->record(
				'session_started',
				'session',
				(int) $session_id,
				'success',
				[ 'correlation_id' => $session['correlation_id'] ]
			);
		}
		NGC_Session_Observability::success( 'join_authorized_total', $session + [ 'user_id' => $user_id ] );

		unset( $roles );
		return [
			'session_id'     => (int) $session['id'],
			'correlation_id' => (string) $session['correlation_id'],
			'launch_url'     => $launch_url,
			'player_url'     => $player,
			'classroom_url'  => $player ?: $meeting,
			'meeting_url'    => $meeting,
			'join_url'       => $launch_url,
			'provider'       => (string) $session['meeting_provider'],
			'window'         => $window,
		];
	}

	/**
	 * Launch by booking ID (legacy dashboard path).
	 *
	 * @param int $booking_id Booking.
	 * @param int $user_id    User.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function launch_booking( $booking_id, $user_id = 0 ) {
		$session = NGC_Session_Repository::get_by_booking_id( (int) $booking_id );
		if ( ! $session ) {
			$provisioned = NGC_Ensure_Session_Provisioned::run( 0, (int) $booking_id );
			if ( is_wp_error( $provisioned ) ) {
				return $provisioned;
			}
			$session = $provisioned;
		}
		return self::launch( (int) $session['id'], $user_id );
	}

	/**
	 * @param array<string, mixed> $session Session.
	 * @param int                  $user_id User.
	 * @return bool
	 */
	public static function can_participate( array $session, $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}
		if ( class_exists( 'NGC_Access' ) && NGC_Access::is_ops( $user_id ) ) {
			return true;
		}
		$parties = [
			(int) $session['student_user_id'],
			(int) $session['tutor_user_id'],
			(int) $session['parent_user_id'],
		];
		if ( in_array( $user_id, $parties, true ) ) {
			return true;
		}
		$student = (int) $session['student_user_id'];
		if ( $student && function_exists( 'get_user_meta' ) ) {
			$parent = (int) get_user_meta( $student, 'ngc_parent_user_id', true );
			if ( ! $parent ) {
				$parent = (int) get_user_meta( $student, 'ngt_parent_user_id', true );
			}
			if ( $parent === $user_id ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Complete a lesson (tutor/ops).
	 *
	 * @param int $session_id Session.
	 * @param int $user_id    Actor.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function complete( $session_id, $user_id = 0 ) {
		$user_id = $user_id ?: get_current_user_id();
		$session = NGC_Session_Repository::get( (int) $session_id );
		if ( ! $session ) {
			return new WP_Error( 'ngc_session_not_found', __( 'Session not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
		}
		if ( ! self::can_participate( $session, $user_id ) ) {
			return ( new NGC_Session_Authorization_Exception( __( 'You cannot complete this lesson.', 'nextgencompanion' ) ) )->to_wp_error();
		}
		$is_tutor = (int) $session['tutor_user_id'] === (int) $user_id;
		if ( ! $is_tutor && class_exists( 'NGC_Access' ) && ! NGC_Access::is_ops( $user_id ) ) {
			return ( new NGC_Session_Authorization_Exception( __( 'Only the tutor can complete this lesson.', 'nextgencompanion' ) ) )->to_wp_error();
		}

		$completed = NGC_Session_Repository::transition( (int) $session_id, NGC_Session_States::COMPLETED );
		if ( is_wp_error( $completed ) ) {
			return $completed;
		}
		if ( class_exists( 'NGC_Bookings' ) && ! empty( $session['booking_id'] ) && class_exists( 'NGC_Database' ) ) {
			global $wpdb;
			$wpdb->update(
				NGC_Database::table( 'bookings' ),
				[
					'status'     => 'completed',
					'updated_at' => current_time( 'mysql', true ),
				],
				[ 'id' => (int) $session['booking_id'] ]
			);
		}
		if ( (int) $session['masterstudy_lesson_id'] && class_exists( 'NGC_Session_Learning_Adapter' ) ) {
			$learning = new NGC_Session_Learning_Adapter();
			if ( $learning->is_available() ) {
				$learning->mark_complete(
					(int) $session['student_user_id'],
					(int) $session['masterstudy_course_id'],
					(int) $session['masterstudy_lesson_id']
				);
			}
		}
		( new NGC_Session_Audit_Adapter() )->record(
			'session_completed',
			'session',
			(int) $session_id,
			'success',
			[ 'correlation_id' => $session['correlation_id'], 'actor' => $user_id ]
		);
		NGC_Session_Observability::success( 'session_completion_total', $completed );
		( new NGC_Session_Notification_Adapter() )->notify( 'session.completed', $completed );
		return $completed;
	}

	/**
	 * @param int    $session_id Session.
	 * @param string $attendance present|absent|late.
	 * @param int    $user_id    Actor.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function attendance( $session_id, $attendance, $user_id = 0 ) {
		$user_id    = $user_id ?: get_current_user_id();
		$attendance = sanitize_key( $attendance );
		if ( ! in_array( $attendance, [ 'present', 'absent', 'late' ], true ) ) {
			return new WP_Error( 'ngc_attendance_invalid', __( 'Invalid attendance value.', 'nextgencompanion' ) );
		}
		$session = NGC_Session_Repository::get( (int) $session_id );
		if ( ! $session ) {
			return new WP_Error( 'ngc_session_not_found', __( 'Session not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
		}
		if ( (int) $session['tutor_user_id'] !== (int) $user_id && ! ( class_exists( 'NGC_Access' ) && NGC_Access::is_ops( $user_id ) ) ) {
			return ( new NGC_Session_Authorization_Exception( __( 'Only the tutor can record attendance.', 'nextgencompanion' ) ) )->to_wp_error();
		}
		$updated = NGC_Session_Repository::update( (int) $session_id, [ 'meta' => [ 'attendance' => $attendance ] ] );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}
		if ( class_exists( 'NGC_Database' ) ) {
			global $wpdb;
			$table = NGC_Database::table( 'session_logs' );
			$wpdb->insert(
				$table,
				[
					'booking_id'      => (int) $session['booking_id'],
					'student_user_id' => (int) $session['student_user_id'],
					'tutor_user_id'   => (int) $session['tutor_user_id'],
					'attendance'      => $attendance,
					'created_at'      => current_time( 'mysql', true ),
				]
			);
		}
		return $updated;
	}
}
