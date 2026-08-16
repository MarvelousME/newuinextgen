<?php
/**
 * Learning provider port — MasterStudy adapter.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensures LMS student projection from WP identity (no independent MS identity).
 */
final class NGC_Learning_Provider_Port {

	/**
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function ensure_student( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 || ! class_exists( 'NGC_Masterstudy_Adapter' ) ) {
			return 'learning_skipped';
		}
		$adapter = new NGC_Masterstudy_Adapter();
		if ( ! $adapter->is_available() ) {
			return 'learning_unavailable';
		}
		$result = $adapter->create_or_update(
			'create_student',
			[
				'user_id'  => $user_id,
				'workflow' => 'LearnerBookingConfirmation',
			]
		);
		return ! empty( $result['ok'] ) || ! empty( $result['success'] ) || empty( $result['error'] )
			? 'learning_student_ensured'
			: 'learning_ensure_failed';
	}
}
