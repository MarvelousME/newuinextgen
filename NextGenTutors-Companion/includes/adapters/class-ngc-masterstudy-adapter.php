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
}
