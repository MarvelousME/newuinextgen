<?php
/**
 * Custom post types and taxonomies.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CPT and taxonomy registration.
 */
class NGC_Post_Types {

	/**
	 * Register CPTs and taxonomies.
	 */
	public static function register() {
		register_post_type(
			'tutors',
			[
				'labels'       => [
					'name'          => __( 'Tutors', 'nextgencompanion' ),
					'singular_name' => __( 'Tutor', 'nextgencompanion' ),
				],
				'public'       => true,
				'has_archive'  => true,
				'rewrite'      => [ 'slug' => 'tutors' ],
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-welcome-learn-more',
				'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
			]
		);

		register_post_type(
			'testimonials',
			[
				'labels'       => [
					'name'          => __( 'Testimonials', 'nextgencompanion' ),
					'singular_name' => __( 'Testimonial', 'nextgencompanion' ),
				],
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-format-quote',
				'supports'     => [ 'title', 'editor', 'thumbnail' ],
			]
		);

		register_post_type(
			'resources',
			[
				'labels'       => [
					'name'          => __( 'Resources', 'nextgencompanion' ),
					'singular_name' => __( 'Resource', 'nextgencompanion' ),
				],
				'public'       => true,
				'has_archive'  => true,
				'rewrite'      => [ 'slug' => 'resources' ],
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-media-document',
				'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
			]
		);

		$taxonomies = [
			'subject'          => [ 'tutors', 'resources' ],
			'province'         => [ 'tutors' ],
			'grade'            => [ 'tutors', 'resources' ],
			'learning_format'  => [ 'tutors' ],
		];

		foreach ( $taxonomies as $tax => $objects ) {
			register_taxonomy(
				$tax,
				$objects,
				[
					'labels'       => [
						'name' => ucfirst( $tax ),
					],
					'public'       => true,
					'hierarchical' => true,
					'show_in_rest' => true,
					'rewrite'      => [ 'slug' => $tax ],
				]
			);
		}
	}

	/**
	 * @param int $user_id Tutor user ID.
	 * @return WP_Post|null
	 */
	public static function get_tutor_post_by_user_id( $user_id ) {
		$posts = get_posts(
			[
				'post_type'      => 'tutors',
				'posts_per_page' => 1,
				'meta_key'       => 'ngc_tutor_user_id',
				'meta_value'     => (int) $user_id,
				'post_status'    => 'publish',
			]
		);
		return $posts[0] ?? null;
	}

	/**
	 * @param int $user_id Tutor user ID.
	 * @return int Post ID.
	 */
	public static function ensure_tutor_post( $user_id ) {
		$existing = self::get_tutor_post_by_user_id( $user_id );
		if ( $existing ) {
			return (int) $existing->ID;
		}
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return 0;
		}
		$post_id = wp_insert_post(
			[
				'post_type'   => 'tutors',
				'post_title'  => $user->display_name,
				'post_status' => 'publish',
				'post_author' => $user_id,
			],
			true
		);
		if ( ! is_wp_error( $post_id ) && $post_id ) {
			update_post_meta( $post_id, 'ngc_tutor_user_id', $user_id );
		}
		return is_wp_error( $post_id ) ? 0 : (int) $post_id;
	}

	/**
	 * Hook init.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register' ], 5 );
	}
}

/**
 * Global helper for theme interop.
 *
 * @param int $user_id User ID.
 * @return WP_Post|null
 */
function ngc_get_tutor_post_by_user_id( $user_id ) {
	return NGC_Post_Types::get_tutor_post_by_user_id( $user_id );
}
