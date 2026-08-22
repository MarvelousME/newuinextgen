<?php
/**
 * MasterStudy LMS adapter — instructor and student profiles.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MasterStudy integration via WP roles and user meta.
 */
class NGC_Masterstudy_Adapter extends NGC_Adapter_Base {

	/**
	 * @return string
	 */
	public function slug() {
		return 'masterstudy';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return defined( 'STM_LMS_VERSION' ) || class_exists( 'STM_LMS_Course' );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify() {
		$checks = [
			'active'              => $this->is_available(),
			'instructor_role'     => (bool) get_role( 'stm_lms_instructor' ),
			'student_role_exists' => (bool) get_role( 'subscriber' ) || (bool) get_role( 'stm_lms_student' ),
			'ok'                  => false,
		];
		if ( ! $checks['active'] ) {
			$checks['status'] = 'PARTIAL — plugin API unavailable';
			return $checks;
		}
		$checks['ok']     = true;
		$checks['status'] = 'VERIFIED — MasterStudy active';
		return $checks;
	}

	/**
	 * @param string               $action  create_instructor|create_student.
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	public function create_or_update( $action, $payload ) {
		if ( ! $this->is_available() ) {
			return $this->handle_error( 'masterstudy_unavailable', __( 'MasterStudy LMS is not active.', 'nextgencompanion' ) );
		}

		$user_id = (int) ( $payload['user_id'] ?? 0 );
		if ( ! $user_id ) {
			return $this->handle_error( 'masterstudy_missing_user', __( 'User ID required for LMS profile.', 'nextgencompanion' ) );
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return $this->handle_error( 'masterstudy_user_not_found', __( 'WordPress user not found.', 'nextgencompanion' ) );
		}

		if ( 'create_instructor' === $action ) {
			return $this->create_instructor( $user, $payload );
		}
		if ( 'create_student' === $action ) {
			return $this->create_student( $user, $payload );
		}

		return $this->handle_error( 'masterstudy_invalid_action', __( 'Unsupported MasterStudy action.', 'nextgencompanion' ) );
	}

	/**
	 * @param WP_User              $user    User.
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	private function create_instructor( $user, $payload ) {
		$existing = (int) get_user_meta( $user->ID, 'ngc_stm_instructor_id', true );
		if ( $existing ) {
			$result = $this->success( [ 'id' => $existing, 'event' => 'MASTERSTUDY_INSTRUCTOR_EXISTS' ] );
			$this->audit_result( 'MASTERSTUDY_INSTRUCTOR_EXISTS', $result, $user->ID );
			return $result;
		}

		if ( ! in_array( 'stm_lms_instructor', (array) $user->roles, true ) ) {
			$user->add_role( 'stm_lms_instructor' );
		}

		update_user_meta( $user->ID, 'ngc_stm_instructor_id', $user->ID );
		update_user_meta( $user->ID, 'ngc_stm_profile_type', 'instructor' );
		if ( ! empty( $payload['subjects'] ) ) {
			update_user_meta( $user->ID, 'ngc_stm_subjects', $payload['subjects'] );
		}

		/**
		 * Fires after MasterStudy instructor profile is provisioned.
		 *
		 * @param int                  $user_id User ID.
		 * @param array<string, mixed> $payload Context.
		 */
		do_action( 'ngc_masterstudy_instructor_created', $user->ID, $payload );

		$result = $this->success(
			[
				'id'    => $user->ID,
				'event' => 'MASTERSTUDY_INSTRUCTOR_CREATED',
			]
		);
		$this->audit_result( 'MASTERSTUDY_INSTRUCTOR_CREATED', $result, $user->ID );
		return $result;
	}

	/**
	 * @param WP_User              $user    User.
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	private function create_student( $user, $payload ) {
		$existing = (int) get_user_meta( $user->ID, 'ngc_stm_student_id', true );
		if ( $existing ) {
			$result = $this->success( [ 'id' => $existing, 'event' => 'MASTERSTUDY_STUDENT_EXISTS' ] );
			$this->audit_result( 'MASTERSTUDY_STUDENT_EXISTS', $result, $user->ID );
			return $result;
		}

		if ( get_role( 'stm_lms_student' ) && ! in_array( 'stm_lms_student', (array) $user->roles, true ) ) {
			$user->add_role( 'stm_lms_student' );
		} elseif ( ! in_array( 'student', (array) $user->roles, true ) ) {
			$user->add_role( 'student' );
		}

		update_user_meta( $user->ID, 'ngc_stm_student_id', $user->ID );
		update_user_meta( $user->ID, 'ngc_stm_profile_type', 'student' );
		if ( ! empty( $payload['grade'] ) ) {
			update_user_meta( $user->ID, 'ngc_grade', $payload['grade'] );
		}

		/**
		 * Fires after MasterStudy student profile is provisioned.
		 *
		 * @param int                  $user_id User ID.
		 * @param array<string, mixed> $payload Context.
		 */
		do_action( 'ngc_masterstudy_student_created', $user->ID, $payload );

		$result = $this->success(
			[
				'id'    => $user->ID,
				'event' => 'MASTERSTUDY_STUDENT_CREATED',
			]
		);
		$this->audit_result( 'MASTERSTUDY_STUDENT_CREATED', $result, $user->ID );
		return $result;
	}

	/**
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>|null
	 */
	public function get_existing( $payload ) {
		$user_id = (int) ( $payload['user_id'] ?? 0 );
		if ( ! $user_id ) {
			return null;
		}
		$type = get_user_meta( $user_id, 'ngc_stm_profile_type', true );
		if ( ! $type ) {
			return null;
		}
		return [
			'type' => $type,
			'id'   => (int) get_user_meta( $user_id, 'ngc_stm_' . $type . '_id', true ),
		];
	}

	/**
	 * Ensure subject course + student enrollment + session lesson association.
	 *
	 * @param array<string, mixed> $ctx Context.
	 * @return array<string, mixed>
	 */
	public function ensure_session_learning( $ctx ) {
		if ( ! $this->is_available() ) {
			return $this->handle_error( 'masterstudy_unavailable', __( 'MasterStudy LMS is not active.', 'nextgencompanion' ) );
		}

		$student = (int) ( $ctx['student_user_id'] ?? 0 );
		$tutor   = (int) ( $ctx['tutor_user_id'] ?? 0 );
		$subject = sanitize_text_field( (string) ( $ctx['subject'] ?? 'General' ) );
		$session = (int) ( $ctx['session_id'] ?? 0 );

		if ( $student ) {
			$this->create_or_update( 'create_student', [ 'user_id' => $student ] );
		}
		if ( $tutor ) {
			$this->create_or_update( 'create_instructor', [ 'user_id' => $tutor ] );
		}

		$course_id = $this->resolve_or_create_subject_course( $subject, $tutor );
		$lesson_id = 0;
		if ( $course_id && $session ) {
			$lesson_id = $this->resolve_or_create_session_lesson( $course_id, $session, $subject, (string) ( $ctx['correlation_id'] ?? '' ) );
		}

		if ( $course_id && $student && function_exists( 'stm_lms_add_user_course' ) ) {
			stm_lms_add_user_course(
				[
					'user_id'   => $student,
					'course_id' => $course_id,
				]
			);
		} elseif ( $course_id && $student ) {
			// Meta enrollment marker when API absent.
			$enrolled = get_user_meta( $student, 'ngc_stm_enrolled_courses', true );
			if ( ! is_array( $enrolled ) ) {
				$enrolled = [];
			}
			if ( ! in_array( $course_id, $enrolled, true ) ) {
				$enrolled[] = $course_id;
				update_user_meta( $student, 'ngc_stm_enrolled_courses', $enrolled );
			}
		}

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log(
				'masterstudy_enrolled',
				'session',
				$session,
				[
					'course_id' => $course_id,
					'lesson_id' => $lesson_id,
					'student'   => $student,
				]
			);
		}

		return [
			'ok'            => true,
			'course_id'     => $course_id,
			'lesson_id'     => $lesson_id,
			'lesson_status' => $course_id ? 'linked' : 'unresolved',
		];
	}

	/**
	 * @param string $subject Subject.
	 * @param int    $tutor   Tutor user ID.
	 * @return int
	 */
	private function resolve_or_create_subject_course( $subject, $tutor ) {
		$key = 'ngt-subject-' . sanitize_title( $subject );
		$existing = get_posts(
			[
				'post_type'      => 'stm-courses',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_ngt_subject_course_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);
		if ( $existing ) {
			return (int) $existing[0];
		}

		// Fallback CPT names used by some MasterStudy installs.
		$post_type = post_type_exists( 'stm-courses' ) ? 'stm-courses' : ( post_type_exists( 'stm_lms_course' ) ? 'stm_lms_course' : '' );
		if ( ! $post_type ) {
			return 0;
		}

		$course_id = wp_insert_post(
			[
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'post_title'  => sprintf(
					/* translators: %s: subject */
					__( 'NextGen Tutors — %s', 'nextgencompanion' ),
					$subject
				),
				'post_author' => $tutor > 0 ? $tutor : get_current_user_id(),
			],
			true
		);
		if ( is_wp_error( $course_id ) || ! $course_id ) {
			return 0;
		}
		update_post_meta( (int) $course_id, '_ngt_subject_course_key', $key );
		return (int) $course_id;
	}

	/**
	 * @param int    $course_id Course.
	 * @param int    $session_id Session.
	 * @param string $subject Subject.
	 * @param string $correlation Correlation.
	 * @return int
	 */
	private function resolve_or_create_session_lesson( $course_id, $session_id, $subject, $correlation ) {
		$key = 'ngt-session-lesson-' . (int) $session_id;
		$existing = get_posts(
			[
				'post_type'      => [ 'stm-lessons', 'stm_lms_lesson', 'lesson' ],
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_ngt_session_lesson_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);
		if ( $existing ) {
			return (int) $existing[0];
		}

		$lesson_type = post_type_exists( 'stm-lessons' ) ? 'stm-lessons' : ( post_type_exists( 'stm_lms_lesson' ) ? 'stm_lms_lesson' : '' );
		if ( ! $lesson_type ) {
			// Persist association on course meta when lesson CPT missing.
			update_post_meta( $course_id, $key, $correlation );
			return 0;
		}

		$lesson_id = wp_insert_post(
			[
				'post_type'   => $lesson_type,
				'post_status' => 'publish',
				'post_title'  => sprintf(
					/* translators: 1: subject 2: session id */
					__( '%1$s — Session #%2$d', 'nextgencompanion' ),
					$subject,
					$session_id
				),
				'post_parent' => $course_id,
			],
			true
		);
		if ( is_wp_error( $lesson_id ) || ! $lesson_id ) {
			return 0;
		}
		update_post_meta( (int) $lesson_id, '_ngt_session_lesson_key', $key );
		update_post_meta( (int) $lesson_id, '_ngt_session_id', (int) $session_id );
		update_post_meta( (int) $lesson_id, '_ngt_correlation_id', sanitize_text_field( $correlation ) );
		update_post_meta( (int) $lesson_id, 'course_id', (int) $course_id );
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'lesson_resolved', 'session', $session_id, [ 'lesson_id' => (int) $lesson_id, 'course_id' => $course_id ] );
		}
		return (int) $lesson_id;
	}

	/**
	 * @param int $course_id Course ID.
	 * @param int $lesson_id Lesson ID.
	 * @return string
	 */
	public function course_player_url( $course_id, $lesson_id = 0 ) {
		$course_id = (int) $course_id;
		if ( $course_id <= 0 ) {
			return '';
		}
		$url = get_permalink( $course_id );
		if ( ! $url ) {
			return '';
		}
		if ( (int) $lesson_id > 0 ) {
			$url = add_query_arg( [ 'lesson_id' => (int) $lesson_id ], $url );
		}
		return $url;
	}
}
