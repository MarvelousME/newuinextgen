<?php
/**
 * MasterStudy LMS integration (safe if plugin absent).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LMS hooks.
 */
class NGC_Lms {

	/**
	 * Hook registration.
	 */
	public static function init() {
		if ( ! defined( 'STM_LMS_VERSION' ) && ! class_exists( 'STM_LMS_Course' ) ) {
			add_action( 'admin_notices', [ __CLASS__, 'missing_notice' ] );
			return;
		}

		add_action( 'ngc_lesson_completed', [ __CLASS__, 'on_lesson_completed' ], 10, 1 );
		add_action( 'stm_lms_lesson_passed', [ __CLASS__, 'on_stm_lesson_passed' ], 10, 2 );
	}

	/**
	 * @param array<string, mixed> $context Context.
	 */
	public static function on_lesson_completed( $context ) {
		// Already handling ngc_lesson_completed — do not re-dispatch lesson.completed
		// (that would recurse via NGC_Workflows → ngc_lesson_completed).
		unset( $context );
	}

	/**
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 */
	public static function on_stm_lesson_passed( $user_id, $course_id ) {
		NGC_Workflows::dispatch(
			'lesson.completed',
			[
				'student_user_id' => (string) $user_id,
				'course_id'       => (string) $course_id,
				'progress_note'   => __( 'MasterStudy lesson passed', 'nextgencompanion' ),
			]
		);
	}

	/**
	 * Admin notice when MasterStudy LMS is not installed.
	 */
	public static function missing_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-info is-dismissible"><p>';
		esc_html_e( 'NextGen Companion: MasterStudy LMS is not active. LMS progress hooks are disabled.', 'nextgencompanion' );
		echo '</p></div>';
	}
}
