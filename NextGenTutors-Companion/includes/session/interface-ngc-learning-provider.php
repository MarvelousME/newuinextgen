<?php
/**
 * Learning provider contract — MasterStudy is learning truth.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subject course + session lesson + enrollment.
 */
interface NGC_Learning_Provider_Interface {

	/**
	 * @return bool
	 */
	public function is_available();

	/**
	 * @param string $subject_id   Subject slug.
	 * @param string $subject_name Subject label.
	 * @param int    $tutor_id     Instructor user ID.
	 * @return array{course_id:int,created:bool}|WP_Error
	 */
	public function ensure_subject_course( $subject_id, $subject_name, $tutor_id = 0 );

	/**
	 * @param int $student_user_id Student.
	 * @param int $course_id       Course.
	 * @return array{enrolled:bool,created:bool}|WP_Error
	 */
	public function ensure_enrollment( $student_user_id, $course_id );

	/**
	 * @param int    $course_id     Course.
	 * @param int    $tutor_id      Instructor.
	 * @param string $session_uuid  Session UUID.
	 * @param string $lesson_title  Title.
	 * @return array{lesson_id:int,created:bool}|WP_Error
	 */
	public function ensure_session_lesson( $course_id, $tutor_id, $session_uuid, $lesson_title );

	/**
	 * @param int $course_id Course.
	 * @param int $lesson_id Lesson.
	 * @return string
	 */
	public function player_url( $course_id, $lesson_id );

	/**
	 * @param int $student_user_id Student.
	 * @param int $course_id       Course.
	 * @param int $lesson_id       Lesson.
	 * @return true|WP_Error
	 */
	public function mark_complete( $student_user_id, $course_id, $lesson_id );
}
