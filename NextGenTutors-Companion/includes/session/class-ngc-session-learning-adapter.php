<?php
/**
 * MasterStudy learning adapter — subject course + session lesson.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Learning truth. Does not fake enrollment when MasterStudy is absent.
 */
class NGC_Session_Learning_Adapter implements NGC_Learning_Provider_Interface {

	public const COURSE_META_SUBJECT = '_ngt_subject_id';
	public const LESSON_META_SESSION = '_ngt_session_uuid';

	/**
	 * @return bool
	 */
	public function is_available() {
		return defined( 'STM_LMS_VERSION' ) || class_exists( 'STM_LMS_Course' ) || post_type_exists( 'stm-courses' );
	}

	/**
	 * @param string $subject_id   Subject slug.
	 * @param string $subject_name Subject label.
	 * @param int    $tutor_id     Instructor.
	 * @return array{course_id:int,created:bool}|WP_Error
	 */
	public function ensure_subject_course( $subject_id, $subject_name, $tutor_id = 0 ) {
		$subject_id = sanitize_title( (string) $subject_id );
		if ( '' === $subject_id ) {
			return new WP_Error( 'ngc_lms_subject', __( 'Subject is required for the learning course.', 'nextgencompanion' ) );
		}
		if ( ! $this->is_available() ) {
			return new WP_Error(
				'ngc_lms_unavailable',
				__( 'MasterStudy LMS is not active.', 'nextgencompanion' ),
				[ 'retryable' => false, 'classification' => 'Configuration' ]
			);
		}

		$existing = $this->find_course_by_subject( $subject_id );
		if ( $existing ) {
			if ( $tutor_id ) {
				$this->ensure_instructor_on_course( $existing, (int) $tutor_id );
			}
			return [ 'course_id' => $existing, 'created' => false ];
		}

		$title = sprintf(
			/* translators: %s subject name */
			__( 'NextGen Tutors — %s', 'nextgencompanion' ),
			$subject_name ?: $subject_id
		);
		$course_id = wp_insert_post(
			[
				'post_type'   => post_type_exists( 'stm-courses' ) ? 'stm-courses' : 'course',
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_author' => $tutor_id ?: get_current_user_id(),
				'post_name'   => 'ngt-' . $subject_id,
			],
			true
		);
		if ( is_wp_error( $course_id ) || ! $course_id ) {
			return is_wp_error( $course_id ) ? $course_id : new WP_Error( 'ngc_lms_course_create', __( 'Could not create subject course.', 'nextgencompanion' ) );
		}
		update_post_meta( (int) $course_id, self::COURSE_META_SUBJECT, $subject_id );
		update_post_meta( (int) $course_id, '_ngt_product_owned', '1' );
		if ( $tutor_id ) {
			$this->ensure_instructor_on_course( (int) $course_id, (int) $tutor_id );
		}
		return [ 'course_id' => (int) $course_id, 'created' => true ];
	}

	/**
	 * @param int $student_user_id Student.
	 * @param int $course_id       Course.
	 * @return array{enrolled:bool,created:bool}|WP_Error
	 */
	public function ensure_enrollment( $student_user_id, $course_id ) {
		$student_user_id = (int) $student_user_id;
		$course_id       = (int) $course_id;
		if ( ! $student_user_id || ! $course_id ) {
			return new WP_Error( 'ngc_lms_enroll_args', __( 'Student and course are required.', 'nextgencompanion' ) );
		}
		if ( ! $this->is_available() ) {
			return new WP_Error( 'ngc_lms_unavailable', __( 'MasterStudy LMS is not active.', 'nextgencompanion' ) );
		}

		if ( class_exists( 'STM_LMS_Course' ) && method_exists( 'STM_LMS_Course', 'add_user_course' ) ) {
			$already = false;
			if ( method_exists( 'STM_LMS_Course', 'check_course_author' ) ) {
				$already = (bool) get_user_meta( $student_user_id, 'stm_lms_course_enrolled_' . $course_id, true );
			}
			$courses = get_user_meta( $student_user_id, 'stm_lms_courses', true );
			if ( is_array( $courses ) ) {
				foreach ( $courses as $row ) {
					if ( (int) ( $row['course_id'] ?? 0 ) === $course_id ) {
						$already = true;
						break;
					}
				}
			}
			if ( $already ) {
				return [ 'enrolled' => true, 'created' => false ];
			}
			STM_LMS_Course::add_user_course( $student_user_id, $course_id, [], 0, false );
			update_user_meta( $student_user_id, 'stm_lms_course_enrolled_' . $course_id, 1 );
			return [ 'enrolled' => true, 'created' => true ];
		}

		$key     = 'ngt_enrolled_courses';
		$enrolled = get_user_meta( $student_user_id, $key, true );
		$enrolled = is_array( $enrolled ) ? $enrolled : [];
		if ( in_array( $course_id, array_map( 'intval', $enrolled ), true ) ) {
			return [ 'enrolled' => true, 'created' => false ];
		}
		$enrolled[] = $course_id;
		update_user_meta( $student_user_id, $key, array_values( array_unique( $enrolled ) ) );
		return [ 'enrolled' => true, 'created' => true ];
	}

	/**
	 * @param int    $course_id    Course.
	 * @param int    $tutor_id     Instructor.
	 * @param string $session_uuid Session UUID.
	 * @param string $lesson_title Title.
	 * @return array{lesson_id:int,created:bool}|WP_Error
	 */
	public function ensure_session_lesson( $course_id, $tutor_id, $session_uuid, $lesson_title ) {
		$course_id    = (int) $course_id;
		$session_uuid = (string) $session_uuid;
		if ( ! $course_id || '' === $session_uuid ) {
			return new WP_Error( 'ngc_lms_lesson_args', __( 'Course and session UUID are required.', 'nextgencompanion' ) );
		}
		if ( ! $this->is_available() ) {
			return new WP_Error( 'ngc_lms_unavailable', __( 'MasterStudy LMS is not active.', 'nextgencompanion' ) );
		}

		$found = $this->find_lesson_by_session( $session_uuid );
		if ( $found ) {
			return [ 'lesson_id' => $found, 'created' => false ];
		}

		$type      = post_type_exists( 'stm-lessons' ) ? 'stm-lessons' : ( post_type_exists( 'stm-lesson' ) ? 'stm-lesson' : 'lesson' );
		$lesson_id = wp_insert_post(
			[
				'post_type'   => $type,
				'post_status' => 'publish',
				'post_title'  => $lesson_title ?: ( 'Session ' . $session_uuid ),
				'post_author' => $tutor_id ?: get_current_user_id(),
				'post_parent' => $course_id,
			],
			true
		);
		if ( is_wp_error( $lesson_id ) || ! $lesson_id ) {
			return is_wp_error( $lesson_id ) ? $lesson_id : new WP_Error( 'ngc_lms_lesson_create', __( 'Could not create session lesson.', 'nextgencompanion' ) );
		}
		update_post_meta( (int) $lesson_id, self::LESSON_META_SESSION, $session_uuid );
		update_post_meta( (int) $lesson_id, '_ngt_course_id', $course_id );
		$this->attach_lesson_to_curriculum( $course_id, (int) $lesson_id );
		return [ 'lesson_id' => (int) $lesson_id, 'created' => true ];
	}

	/**
	 * @param int $course_id Course.
	 * @param int $lesson_id Lesson.
	 * @return string
	 */
	public function player_url( $course_id, $lesson_id ) {
		$course_id = (int) $course_id;
		$lesson_id = (int) $lesson_id;
		if ( class_exists( 'STM_LMS_Lesson' ) && method_exists( 'STM_LMS_Lesson', 'get_lesson_url' ) ) {
			$url = STM_LMS_Lesson::get_lesson_url( $course_id, $lesson_id );
			if ( is_string( $url ) && $url ) {
				return $url;
			}
		}
		$permalink = $lesson_id ? get_permalink( $lesson_id ) : '';
		if ( $permalink ) {
			return $permalink;
		}
		return $course_id ? (string) get_permalink( $course_id ) : '';
	}

	/**
	 * @param int $student_user_id Student.
	 * @param int $course_id       Course.
	 * @param int $lesson_id       Lesson.
	 * @return true|WP_Error
	 */
	public function mark_complete( $student_user_id, $course_id, $lesson_id ) {
		if ( ! $this->is_available() ) {
			return new WP_Error( 'ngc_lms_unavailable', __( 'MasterStudy LMS is not active.', 'nextgencompanion' ) );
		}
		$student_user_id = (int) $student_user_id;
		$course_id       = (int) $course_id;
		$lesson_id       = (int) $lesson_id;
		$done            = get_user_meta( $student_user_id, 'ngt_completed_lessons', true );
		$done            = is_array( $done ) ? $done : [];
		$key             = $course_id . ':' . $lesson_id;
		if ( ! in_array( $key, $done, true ) ) {
			$done[] = $key;
			update_user_meta( $student_user_id, 'ngt_completed_lessons', $done );
		}
		if ( class_exists( 'STM_LMS_Lesson' ) && method_exists( 'STM_LMS_Lesson', 'complete_lesson' ) === false ) {
			update_user_meta( $student_user_id, 'stm_lms_lesson_completed_' . $lesson_id, 1 );
		}
		return true;
	}

	/**
	 * @param string $subject_id Subject.
	 * @return int
	 */
	private function find_course_by_subject( $subject_id ) {
		$q = new WP_Query(
			[
				'post_type'      => post_type_exists( 'stm-courses' ) ? 'stm-courses' : 'any',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::COURSE_META_SUBJECT,
				'meta_value'     => $subject_id,
				'no_found_rows'  => true,
			]
		);
		return $q->posts ? (int) $q->posts[0] : 0;
	}

	/**
	 * @param string $session_uuid UUID.
	 * @return int
	 */
	private function find_lesson_by_session( $session_uuid ) {
		$q = new WP_Query(
			[
				'post_type'      => post_type_exists( 'stm-lessons' ) ? [ 'stm-lessons', 'stm-lesson' ] : 'any',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::LESSON_META_SESSION,
				'meta_value'     => $session_uuid,
				'no_found_rows'  => true,
			]
		);
		return $q->posts ? (int) $q->posts[0] : 0;
	}

	/**
	 * @param int $course_id Course.
	 * @param int $tutor_id  Tutor.
	 * @return void
	 */
	private function ensure_instructor_on_course( $course_id, $tutor_id ) {
		if ( class_exists( 'NGC_Masterstudy_Adapter' ) ) {
			$adapter = new NGC_Masterstudy_Adapter();
			$adapter->create_or_update( 'create_instructor', [ 'user_id' => $tutor_id ] );
		}
		$post = get_post( $course_id );
		if ( $post && (int) $post->post_author !== (int) $tutor_id ) {
			wp_update_post( [ 'ID' => $course_id, 'post_author' => $tutor_id ] );
		}
	}

	/**
	 * Attach lesson id to MasterStudy curriculum meta when present.
	 *
	 * @param int $course_id Course.
	 * @param int $lesson_id Lesson.
	 * @return void
	 */
	private function attach_lesson_to_curriculum( $course_id, $lesson_id ) {
		$curriculum = get_post_meta( $course_id, 'curriculum', true );
		if ( ! is_array( $curriculum ) ) {
			$curriculum = [];
		}
		if ( ! in_array( (int) $lesson_id, array_map( 'intval', $curriculum ), true ) ) {
			$curriculum[] = (int) $lesson_id;
			update_post_meta( $course_id, 'curriculum', $curriculum );
		}
	}
}
