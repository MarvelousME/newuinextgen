<?php
/**
 * CPT registration, roles, pages, and setup.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Data_Model {

	/** @var array<string, array<string, mixed>> */
	private static $post_types = [
		'ngt_parent_profile'   => [ 'Parent Profile', 'Parent Profiles' ],
		'ngt_student_profile'  => [ 'Student Profile', 'Student Profiles' ],
		'ngt_tutor_profile'    => [ 'Tutor Profile', 'Tutor Profiles' ],
		'ngt_booking'          => [ 'Booking', 'Bookings' ],
		'ngt_lesson'           => [ 'Lesson', 'Lessons' ],
		'ngt_progress_report'  => [ 'Progress Report', 'Progress Reports' ],
		'ngt_support_case'     => [ 'Support Case', 'Support Cases' ],
		'ngt_payout'           => [ 'Payout', 'Payouts' ],
		'ngt_review'           => [ 'Review', 'Reviews' ],
	];

	public static function register_hooks(): void {
		add_action( 'init', [ __CLASS__, 'register_post_types' ] );
		add_action( 'init', [ __CLASS__, 'register_roles' ] );
		add_filter( 'acf/settings/load_json', [ __CLASS__, 'acf_load_json' ] );
	}

	public static function install(): void {
		self::register_post_types();
		self::register_roles();
		self::ensure_pages();
		flush_rewrite_rules();
	}

	public static function register_post_types(): void {
		foreach ( self::$post_types as $slug => $labels ) {
			register_post_type(
				$slug,
				[
					'labels'       => [
						'name'          => $labels[1],
						'singular_name' => $labels[0],
					],
					'public'       => false,
					'show_ui'      => true,
					'show_in_menu' => 'ngt-hub',
					'supports'     => [ 'title', 'editor', 'custom-fields' ],
					'capability_type' => 'post',
					'map_meta_cap' => true,
				]
			);
		}
	}

	public static function register_roles(): void {
		$roles = [
			'ngt_parent'  => __( 'NextGen Parent', 'nextgen-automation-hub' ),
			'ngt_student' => __( 'NextGen Student', 'nextgen-automation-hub' ),
			'ngt_tutor'   => __( 'NextGen Tutor', 'nextgen-automation-hub' ),
			'ngt_support' => __( 'NextGen Support', 'nextgen-automation-hub' ),
		];

		foreach ( $roles as $role => $label ) {
			if ( ! get_role( $role ) ) {
				add_role( $role, $label, [ 'read' => true ] );
			}
		}

		$admin = get_role( 'administrator' );
		if ( $admin && ! $admin->has_cap( 'ngt_manage_hub' ) ) {
			$admin->add_cap( 'ngt_manage_hub' );
		}
	}

	/**
	 * @param array<int, string> $paths Paths.
	 * @return array<int, string>
	 */
	public static function acf_load_json( array $paths ): array {
		$paths[] = NGT_HUB_DIR . 'acf-json';
		return $paths;
	}

	public static function ensure_pages(): void {
		$pages = [
			'student-dashboard'  => __( 'Student Dashboard', 'nextgen-automation-hub' ),
			'parent-dashboard'   => __( 'Parent Dashboard', 'nextgen-automation-hub' ),
			'tutor-dashboard'    => __( 'Tutor Dashboard', 'nextgen-automation-hub' ),
			'admin-control-plane'=> __( 'Admin Control Plane', 'nextgen-automation-hub' ),
			'support-center'     => __( 'Support Center', 'nextgen-automation-hub' ),
			'login'              => __( 'Login', 'nextgen-automation-hub' ),
			'register'           => __( 'Register', 'nextgen-automation-hub' ),
		];

		foreach ( $pages as $slug => $title ) {
			$existing = get_page_by_path( $slug );
			if ( $existing ) {
				continue;
			}
			wp_insert_post(
				[
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => self::default_page_content( $slug ),
				]
			);
		}
	}

	private static function default_page_content( string $slug ): string {
		$map = [
			'student-dashboard'   => '[ngt_dashboard role="student"]',
			'parent-dashboard'    => '[ngt_dashboard role="parent"]',
			'tutor-dashboard'     => '[ngt_dashboard role="tutor"]',
			'admin-control-plane' => '[ngt_dashboard role="admin"]',
			'support-center'      => '[ngt_dashboard role="support"][ngt_rtm]',
			'login'               => '[ngt_login]',
			'register'            => '[ngt_register]',
		];
		return $map[ $slug ] ?? '';
	}

	/**
	 * Count posts by type and optional meta.
	 */
	public static function count_posts( string $type, array $meta_query = [] ): int {
		$args = [
			'post_type'      => $type,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		];
		if ( $meta_query ) {
			$args['meta_query'] = $meta_query;
		}
		$q = new WP_Query( $args );
		return (int) $q->found_posts;
	}

	/**
	 * @return array<int, WP_Post>
	 */
	public static function get_user_lessons( int $user_id, string $role, int $limit = 10 ): array {
		$meta_key = 'tutor' === $role ? 'ngt_tutor_user_id' : 'ngt_student_user_id';
		$q = new WP_Query(
			[
				'post_type'      => 'ngt_lesson',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'meta_query'     => [
					[
						'key'   => $meta_key,
						'value' => $user_id,
					],
				],
				'meta_key'       => 'ngt_lesson_date',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
			]
		);
		return $q->posts;
	}
}
