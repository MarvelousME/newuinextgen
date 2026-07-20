<?php
/**
 * Demo mode shims and demo user seeding.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Demo journey manager.
 */
class NGC_Platform_Demo {

	/**
	 * @return bool
	 */
	public static function is_enabled() {
		return '1' === (string) get_option( 'ngc_demo_mode_enabled', '0' );
	}

	/**
	 * @param bool $enabled Enabled state.
	 */
	public static function set_enabled( $enabled ) {
		update_option( 'ngc_demo_mode_enabled', $enabled ? '1' : '0', false );
	}

	/**
	 * @param string $key Demo payload key.
	 * @return array<string, mixed>
	 */
	public static function get_payload( $key ) {
		$file = NGC_PLUGIN_DIR . 'demo/' . sanitize_file_name( $key ) . '.json';
		if ( ! file_exists( $file ) ) {
			return [];
		}
		$data = json_decode( (string) file_get_contents( $file ), true );
		return is_array( $data ) ? $data : [];
	}

	/**
	 * @return string[]
	 */
	public static function required_payloads() {
		return [
			'demo_parent',
			'demo_student',
			'demo_tutor_applicant',
			'demo_approved_tutor',
			'demo_admin',
			'demo_finance',
			'demo_support',
			'demo_analytics',
			'demo_acquisition',
			'demo_tutor_calendar',
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function verify_payloads() {
		$missing = [];
		foreach ( self::required_payloads() as $name ) {
			if ( empty( self::get_payload( $name ) ) ) {
				$missing[] = $name;
			}
		}
		return [
			'ok'      => empty( $missing ),
			'missing' => $missing,
		];
	}

	/**
	 * Seed demo users.
	 *
	 * @return array<string, mixed>
	 */
	public static function seed_demo_users() {
		$seed_map = [
			'demo.parent@nextgen.local'          => [ 'Demo Parent', 'parent' ],
			'demo.student@nextgen.local'         => [ 'Demo Student', 'student' ],
			'demo.tutorapplicant@nextgen.local'  => [ 'Demo Tutor Applicant', 'tutor_applicant' ],
			'demo.tutor@nextgen.local'           => [ 'Demo Approved Tutor', 'tutor' ],
			'demo.admin@nextgen.local'           => [ 'Demo Admin', 'administrator' ],
			'demo.finance@nextgen.local'         => [ 'Demo Finance', 'ngc_finance' ],
			'demo.support@nextgen.local'         => [ 'Demo Support', 'ngc_support' ],
		];
		$created = 0;
		$ids     = [];
		foreach ( $seed_map as $email => $spec ) {
			$user = get_user_by( 'email', $email );
			if ( ! $user ) {
				$user_id = NGC_Workflow_Orchestrator::ensure_user_role( $email, $spec[0], $spec[1] );
				if ( $user_id ) {
					++$created;
					$ids[] = $user_id;
					update_user_meta( $user_id, 'ngc_is_demo_user', 1 );
					update_user_meta( $user_id, 'ngc_journey_state', 'demo_seeded' );
				}
			} else {
				$user->set_role( $spec[1] );
				update_user_meta( $user->ID, 'ngc_is_demo_user', 1 );
				$ids[] = $user->ID;
			}
		}
		NGC_Platform_Repository::create(
			'demo_seed',
			[
				'seed_key'        => 'demo_users',
				'seed_hash'       => md5( wp_json_encode( $seed_map ) ),
				'created_records' => $created,
				'status'          => 'done',
				'details'         => wp_json_encode( [ 'user_ids' => $ids ] ),
			]
		);
		return [
			'created'  => $created,
			'user_ids' => $ids,
		];
	}

	/**
	 * Clear demo users and demo-only records.
	 *
	 * @return array<string, mixed>
	 */
	public static function clear_demo_data() {
		$users = get_users(
			[
				'meta_key'   => 'ngc_is_demo_user',
				'meta_value' => '1',
				'fields'     => 'ID',
				'number'     => 200,
			]
		);
		$deleted = 0;
		foreach ( $users as $uid ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			if ( wp_delete_user( (int) $uid ) ) {
				++$deleted;
			}
		}
		NGC_Platform_Repository::create(
			'demo_seed',
			[
				'seed_key'        => 'demo_clear',
				'seed_hash'       => md5( (string) time() ),
				'created_records' => $deleted,
				'status'          => 'done',
				'details'         => wp_json_encode( [ 'deleted' => $deleted ] ),
			]
		);
		return [ 'deleted_users' => $deleted ];
	}
}

