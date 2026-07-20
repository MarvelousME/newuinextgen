<?php
/**
 * Custom roles and capabilities.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Role registration.
 */
class NGC_Roles {

	/**
	 * @return array<string, array<string, bool>>
	 */
	public static function role_definitions() {
		return [
			'parent' => [
				'read'                   => true,
				'ngc_view_dashboard'     => true,
				'ngc_request_match'      => true,
				'ngc_book_sessions'      => true,
				'ngc_submit_reviews'     => true,
				'ngc_manage_wallet'      => true,
			],
			'parent_guardian' => [
				'read'                   => true,
				'ngc_view_dashboard'     => true,
				'ngc_request_match'      => true,
				'ngc_book_sessions'      => true,
				'ngc_submit_reviews'     => true,
				'ngc_manage_wallet'      => true,
				'ngc_manage_learners'    => true,
			],
			'student' => [
				'read'                   => true,
				'ngc_view_dashboard'     => true,
				'ngc_book_sessions'      => true,
			],
			'child_learner' => [
				'read'                   => true,
				'ngc_view_dashboard'     => true,
			],
			'tutor' => [
				'read'                   => true,
				'ngc_view_dashboard'     => true,
				'ngc_view_earnings'      => true,
				'ngc_manage_availability'=> true,
				'ngc_accept_matches'     => true,
			],
			'tutor_applicant' => [
				'read'                   => true,
				'ngc_view_dashboard'     => true,
			],
			'ngc_finance' => [
				'read'                   => true,
				'ngc_view_dashboard'     => true,
				'ngc_manage_invoices'    => true,
				'ngc_manage_payouts'     => true,
				'ngc_view_finance'       => true,
			],
			'ngc_support' => [
				'read'                   => true,
				'ngc_view_dashboard'     => true,
				'ngc_manage_matches'     => true,
				'ngc_manage_bookings'    => true,
				'ngc_review_tutors'      => true,
				'ngc_view_audit'         => true,
			],
			'ngc_compliance' => [
				'read'               => true,
				'ngc_view_dashboard' => true,
				'ngc_view_audit'     => true,
				'ngc_manage_privacy' => true,
			],
			'ngc_safeguarding' => [
				'read'                    => true,
				'ngc_view_dashboard'      => true,
				'ngc_manage_safeguarding' => true,
				'ngc_view_audit'          => true,
			],
			'ngc_operations' => [
				'read'                => true,
				'ngc_view_dashboard'  => true,
				'ngc_manage_matches'  => true,
				'ngc_manage_bookings' => true,
				'ngc_admin_operations'=> true,
			],
			'ngc_content' => [
				'read'               => true,
				'ngc_view_dashboard' => true,
				'edit_posts'         => true,
				'edit_pages'         => true,
				'upload_files'       => true,
			],
			'ngc_auditor' => [
				'read'               => true,
				'ngc_view_dashboard' => true,
				'ngc_view_audit'     => true,
				'ngc_view_finance'   => true,
			],
			'ngc_ai_ops' => [
				'read'               => true,
				'ngc_view_dashboard' => true,
				'ngc_admin_operations' => true,
				'ngc_view_audit'     => true,
			],
		];
	}

	/**
	 * Install roles and grant admin caps.
	 */
	public static function install() {
		foreach ( self::role_definitions() as $role_slug => $caps ) {
			$role = get_role( $role_slug );
			if ( $role ) {
				foreach ( $caps as $cap => $grant ) {
					$role->add_cap( $cap, $grant );
				}
			} else {
				add_role( $role_slug, self::role_label( $role_slug ), $caps );
			}
		}

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$all_caps = [
				'ngc_view_dashboard', 'ngc_request_match', 'ngc_book_sessions',
				'ngc_submit_reviews', 'ngc_manage_wallet', 'ngc_manage_learners',
				'ngc_view_earnings', 'ngc_manage_availability', 'ngc_accept_matches',
				'ngc_manage_invoices', 'ngc_manage_payouts', 'ngc_view_finance',
				'ngc_manage_matches', 'ngc_manage_bookings', 'ngc_review_tutors',
				'ngc_view_audit', 'ngc_admin_operations',
				'ngc_manage_exports', 'ngc_run_diagnostics',
			];
			foreach ( $all_caps as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}

	/**
	 * @param string $slug Role slug.
	 * @return string
	 */
	public static function role_label( $slug ) {
		$labels = [
			'parent'            => __( 'Parent', 'nextgencompanion' ),
			'parent_guardian'   => __( 'Parent / Guardian', 'nextgencompanion' ),
			'student'           => __( 'Student', 'nextgencompanion' ),
			'child_learner'     => __( 'Child Learner', 'nextgencompanion' ),
			'tutor'             => __( 'Tutor', 'nextgencompanion' ),
			'tutor_applicant'   => __( 'Tutor Applicant', 'nextgencompanion' ),
			'ngc_finance'       => __( 'NGC Finance', 'nextgencompanion' ),
			'ngc_support'       => __( 'NGC Support', 'nextgencompanion' ),
			'ngc_compliance'    => __( 'NGC Compliance', 'nextgencompanion' ),
			'ngc_safeguarding'  => __( 'NGC Safeguarding', 'nextgencompanion' ),
			'ngc_operations'    => __( 'NGC Operations', 'nextgencompanion' ),
			'ngc_content'       => __( 'NGC Content', 'nextgencompanion' ),
			'ngc_auditor'       => __( 'NGC Auditor', 'nextgencompanion' ),
			'ngc_ai_ops'        => __( 'NGC AI Operations', 'nextgencompanion' ),
		];
		return $labels[ $slug ] ?? ucfirst( str_replace( '_', ' ', $slug ) );
	}

	/**
	 * @param int $user_id User ID.
	 * @return string student|parent|tutor|admin
	 */
	public static function dashboard_type_for_user( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return 'student';
		}
		if ( user_can( $user, 'manage_options' ) || user_can( $user, 'ngc_admin_operations' ) ) {
			return 'admin';
		}
		if ( in_array( 'tutor', (array) $user->roles, true ) ) {
			return 'tutor';
		}
		if ( in_array( 'parent', (array) $user->roles, true ) || in_array( 'parent_guardian', (array) $user->roles, true ) ) {
			return 'parent';
		}
		return 'student';
	}

	/**
	 * Hook init.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'install' ], 1 );
	}
}
