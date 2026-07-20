<?php
/**
 * Self-healing — repair pages, roles, tables.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repair utilities.
 */
class NGC_Self_Healing {

	/**
	 * Repair all known issues.
	 *
	 * @return array<string, bool>
	 */
	public static function repair_all() {
		if ( class_exists( 'NGC_Repair_Engine' ) ) {
			$report = NGC_Repair_Engine::execute( [ 'dry_run' => false, 'approved' => true ] );
		return [
			'tables' => self::repair_tables(),
			'roles'  => self::repair_roles(),
			'pages'  => self::repair_pages(),
			'tutors' => self::repair_tutors(),
			'report' => $report,
		];
		}
		return [
			'tables' => self::repair_tables(),
			'roles'  => self::repair_roles(),
			'pages'  => self::repair_pages(),
			'tutors' => self::repair_tutors(),
		];
	}

	/**
	 * @return bool
	 */
	public static function repair_tables() {
		if ( ! NGC_Database::tables_exist() ) {
			NGC_Database::create_tables();
		}
		return NGC_Database::tables_exist();
	}

	/**
	 * @return bool
	 */
	public static function repair_roles() {
		NGC_Roles::install();
		return NGC_Verification::check_pass( NGC_Verification::run_checks(), 'roles' );
	}

	/**
	 * Ensure core pages exist (minimal — does not override theme page sync).
	 *
	 * @return bool
	 */
	public static function repair_pages() {
		$pages = [
			'find-a-tutor'      => __( 'Find a Tutor', 'nextgencompanion' ),
			'become-a-tutor'    => __( 'Become a Tutor', 'nextgencompanion' ),
			'student-dashboard' => __( 'Student Dashboard', 'nextgencompanion' ),
			'tutor-dashboard'   => __( 'Tutor Dashboard', 'nextgencompanion' ),
			'parent-dashboard'  => __( 'Parent Dashboard', 'nextgencompanion' ),
			'admin-dashboard'   => __( 'Admin Dashboard', 'nextgencompanion' ),
			'login'             => __( 'Login', 'nextgencompanion' ),
		];

		$ok = true;
		foreach ( $pages as $slug => $title ) {
			$existing = get_page_by_path( $slug );
			if ( ! $existing ) {
				$id = wp_insert_post(
					[
						'post_title'  => $title,
						'post_name'   => $slug,
						'post_status' => 'publish',
						'post_type'   => 'page',
					],
					true
				);
				if ( is_wp_error( $id ) ) {
					$ok = false;
				}
			}
		}
		return $ok;
	}

	/**
	 * Seed published tutors CPT when empty.
	 *
	 * @return bool
	 */
	public static function repair_tutors() {
		if ( ! class_exists( 'NGC_Tutor_Seeder' ) ) {
			return false;
		}
		$result = NGC_Tutor_Seeder::ensure_seeded( true );
		return (int) ( $result['total'] ?? 0 ) > 0;
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'maybe_auto_repair' ] );
	}

	/**
	 * Auto-repair on admin if tables missing.
	 */
	public static function maybe_auto_repair() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! NGC_Database::tables_exist() ) {
			self::repair_tables();
		}
	}
}
