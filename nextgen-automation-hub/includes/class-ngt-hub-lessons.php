<?php
/**
 * Lesson completion detection and event firing.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Lessons {

	public static function register_hooks(): void {
		add_action( 'save_post_ngt_lesson', [ __CLASS__, 'on_lesson_save' ], 20, 3 );
		add_action( 'acf/save_post', [ __CLASS__, 'on_acf_save' ], 20 );
	}

	/**
	 * @param WP_Post $post Post object.
	 */
	public static function on_lesson_save( int $post_id, $post, bool $update ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		self::maybe_complete( $post_id );
	}

	public static function on_acf_save( $post_id ): void {
		if ( 'ngt_lesson' !== get_post_type( $post_id ) ) {
			return;
		}
		self::maybe_complete( (int) $post_id );
	}

	private static function maybe_complete( int $post_id ): void {
		$status = get_post_meta( $post_id, 'ngt_lesson_status', true );
		if ( 'completed' !== $status ) {
			return;
		}

		if ( get_post_meta( $post_id, '_ngt_completion_fired', true ) ) {
			return;
		}

		$student_id = (int) get_post_meta( $post_id, 'ngt_student_user_id', true );
		$tutor_id   = (int) get_post_meta( $post_id, 'ngt_tutor_user_id', true );
		$parent_id  = (int) get_post_meta( $post_id, 'ngt_parent_user_id', true );
		$note       = (string) get_post_meta( $post_id, 'ngt_progress_note', true );

		if ( ! $parent_id && $student_id ) {
			$parent_id = (int) get_user_meta( $student_id, 'ngt_parent_user_id', true );
		}

		NGT_Hub::fire_event(
			'ngt.lesson.completed',
			'lesson',
			$tutor_id,
			$post_id,
			[
				'lesson_id'       => $post_id,
				'student_user_id' => $student_id,
				'tutor_user_id'   => $tutor_id,
				'parent_user_id'  => $parent_id,
				'progress_note'   => $note,
			]
		);

		update_post_meta( $post_id, '_ngt_completion_fired', 1 );
	}

	/**
	 * Mark lesson complete via REST or admin action.
	 * Object-level check: tutor/student/parent on the lesson, or admin.
	 */
	public static function mark_complete( int $lesson_id, string $note = '' ): bool {
		if ( 'ngt_lesson' !== get_post_type( $lesson_id ) ) {
			return false;
		}
		if ( ! self::user_can_complete( $lesson_id ) ) {
			return false;
		}
		update_post_meta( $lesson_id, 'ngt_lesson_status', 'completed' );
		if ( $note ) {
			update_post_meta( $lesson_id, 'ngt_progress_note', sanitize_textarea_field( $note ) );
		}
		delete_post_meta( $lesson_id, '_ngt_completion_fired' );
		self::maybe_complete( $lesson_id );
		return true;
	}

	/**
	 * @param int $lesson_id Lesson post ID.
	 * @param int $user_id   Actor (0 = current).
	 */
	public static function user_can_complete( int $lesson_id, int $user_id = 0 ): bool {
		$user_id = $user_id ?: get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'ngt_manage_hub' ) ) {
			return true;
		}
		$parties = [
			(int) get_post_meta( $lesson_id, 'ngt_student_user_id', true ),
			(int) get_post_meta( $lesson_id, 'ngt_tutor_user_id', true ),
			(int) get_post_meta( $lesson_id, 'ngt_parent_user_id', true ),
		];
		$student = (int) get_post_meta( $lesson_id, 'ngt_student_user_id', true );
		if ( $student ) {
			$linked = (int) get_user_meta( $student, 'ngt_parent_user_id', true );
			if ( $linked ) {
				$parties[] = $linked;
			}
		}
		return in_array( $user_id, $parties, true );
	}
}
