<?php
/**
 * Parent/student registration and form intake orchestration.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User provisioning from registration forms.
 */
class NGC_Registration {

	/**
	 * @param array<string, mixed> $payload Form fields.
	 * @return int|WP_Error User ID.
	 */
	public static function register_parent( $payload ) {
		$email = sanitize_email( $payload['email'] ?? '' );
		$name  = sanitize_text_field( $payload['parent_name'] ?? $payload['name'] ?? '' );
		if ( ! $email || ! is_email( $email ) ) {
			return new WP_Error( 'ngc_invalid_email', __( 'A valid parent email is required.', 'nextgencompanion' ) );
		}

		$user_id = self::ensure_user( $email, $name, 'parent' );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$child = sanitize_text_field( $payload['child_name'] ?? '' );
		$grade = sanitize_text_field( $payload['grade'] ?? '' );
		if ( $child ) {
			update_user_meta( $user_id, 'ngc_child_name', $child );
			self::add_learner_profile( $user_id, $child, $grade );
		}
		if ( $grade ) {
			update_user_meta( $user_id, 'ngc_child_grade', $grade );
		}

		NGC_Workflow_Orchestrator::run(
			'PARENT_REGISTERED',
			array_merge( $payload, [
				'user_id'     => $user_id,
				'email'       => $email,
				'parent_name' => $name,
				'child_name'  => $child,
				'grade'       => $grade,
			] )
		);

		return $user_id;
	}

	/**
	 * Store a learner profile under a parent account.
	 *
	 * @param int    $parent_id Parent user ID.
	 * @param string $name      Learner name.
	 * @param string $grade     Grade.
	 * @param int    $student_id Optional linked WP user ID.
	 */
	public static function add_learner_profile( $parent_id, $name, $grade = '', $student_id = 0 ) {
		$learners = get_user_meta( $parent_id, 'ngc_learners', true );
		if ( ! is_array( $learners ) ) {
			$learners = [];
		}
		$learners[] = [
			'name'       => $name,
			'grade'      => $grade,
			'student_id' => (int) $student_id,
			'created'    => gmdate( 'c' ),
		];
		update_user_meta( $parent_id, 'ngc_learners', $learners );

		if ( class_exists( 'NGC_Child_Learners' ) ) {
			$child_id = NGC_Child_Learners::create(
				[
					'parent_user_id'  => (int) $parent_id,
					'display_name'    => $name,
					'grade'           => $grade,
					'student_user_id' => (int) $student_id,
				]
			);
			if ( ! is_wp_error( $child_id ) && $student_id ) {
				NGC_Child_Learners::link_student( $child_id, (int) $student_id );
			}
		}

		if ( $student_id ) {
			$linked = get_user_meta( $parent_id, 'ngc_linked_students', true );
			if ( ! is_array( $linked ) ) {
				$linked = [];
			}
			if ( ! in_array( $student_id, $linked, true ) ) {
				$linked[] = (int) $student_id;
				update_user_meta( $parent_id, 'ngc_linked_students', $linked );
			}
			update_user_meta( $student_id, 'ngc_parent_user_id', (int) $parent_id );
		}
	}

	/**
	 * @param array<string, mixed> $payload Form fields.
	 * @return int|WP_Error User ID.
	 */
	public static function register_student( $payload ) {
		$email = sanitize_email( $payload['email'] ?? '' );
		$name  = sanitize_text_field( $payload['full_name'] ?? $payload['name'] ?? '' );
		if ( ! $email || ! is_email( $email ) ) {
			return new WP_Error( 'ngc_invalid_email', __( 'A valid student email is required.', 'nextgencompanion' ) );
		}

		$user_id = self::ensure_user( $email, $name, 'student' );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$grade = sanitize_text_field( $payload['grade'] ?? '' );
		if ( $grade ) {
			update_user_meta( $user_id, 'ngc_grade', $grade );
		}

		$parent_email = sanitize_email( $payload['parent_email'] ?? '' );
		if ( $parent_email && is_email( $parent_email ) ) {
			$parent = get_user_by( 'email', $parent_email );
			if ( $parent ) {
				self::add_learner_profile( (int) $parent->ID, $name, $grade, $user_id );
			}
		}

		NGC_Workflow_Orchestrator::run(
			'STUDENT_REGISTERED',
			array_merge( $payload, [
				'user_id'   => $user_id,
				'email'     => $email,
				'full_name' => $name,
				'grade'     => $grade,
			] )
		);

		return $user_id;
	}

	/**
	 * @param string $email Email.
	 * @param string $name  Display name.
	 * @param string $role  Role slug.
	 * @return int|WP_Error
	 */
	private static function ensure_user( $email, $name, $role ) {
		$existing = get_user_by( 'email', $email );
		if ( $existing ) {
			if ( ! in_array( $role, (array) $existing->roles, true ) ) {
				$existing->add_role( $role );
			}
			return (int) $existing->ID;
		}

		$username = sanitize_user( current( explode( '@', $email ) ), true );
		if ( username_exists( $username ) ) {
			$username = sanitize_user( $username . wp_rand( 100, 999 ), true );
		}

		$password = wp_generate_password( 16, true, true );
		$user_id  = wp_create_user( $username, $password, $email );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		wp_update_user(
			[
				'ID'           => $user_id,
				'display_name' => $name ?: $username,
				'first_name'   => $name,
			]
		);

		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			$user->set_role( $role );
		}

		wp_new_user_notification( $user_id, null, 'user' );

		return (int) $user_id;
	}

	/**
	 * Business logic on form intake (before workflow dispatch).
	 *
	 * @param string               $form_id Form slug.
	 * @param array<string, mixed> $payload Fields.
	 */
	public static function on_form_intake( $form_id, $payload ) {
		switch ( $form_id ) {
			case 'find_tutor':
				NGC_Matching::create_from_find_tutor( $payload );
				break;
			case 'become_tutor':
				NGC_Tutor_Lifecycle::apply( $payload );
				break;
			case 'parent_register':
				self::register_parent( $payload );
				break;
			case 'student_register':
				self::register_student( $payload );
				break;
		}
		// Registration workflows are executed inside register_* / tutor lifecycle.
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'ngc_form_submitted', [ __CLASS__, 'on_form_intake' ], 5, 2 );
	}
}
