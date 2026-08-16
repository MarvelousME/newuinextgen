<?php
/**
 * Seeds published tutors CPT posts from the design-system roster.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Idempotent marketplace seed — powers carousel, matcher, and live CPT helper.
 */
class NGC_Tutor_Seeder {

	const FLAG = 'ngc_tutors_cpt_seeded';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'maybe_seed_on_boot' ], 25 );
	}

	/**
	 * Whether demo CPT seeding is allowed (off by default for production).
	 *
	 * @return bool
	 */
	public static function demo_seed_allowed() {
		if ( class_exists( 'NGC_Demo_Env' ) && NGC_Demo_Env::is_production_environment() ) {
			return false;
		}
		$env_flag = getenv( 'NGC_ALLOW_DEMO_SEED' );
		if ( is_string( $env_flag ) && in_array( strtolower( trim( $env_flag ) ), [ '0', 'false', 'no', 'off' ], true ) ) {
			return false;
		}
		if ( apply_filters( 'ngc_allow_demo_tutor_seed', null ) === false ) {
			return false;
		}
		if ( apply_filters( 'ngc_demo_seed_allowed', null ) === false ) {
			return false;
		}
		if ( class_exists( 'NGC_Demo_Env' ) && NGC_Demo_Env::seed_allowed() ) {
			return true;
		}
		if ( apply_filters( 'ngc_allow_demo_tutor_seed', null ) === true ) {
			return true;
		}
		if ( defined( 'NGC_ALLOW_DEMO_SEED' ) && NGC_ALLOW_DEMO_SEED ) {
			return true;
		}
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$host = wp_parse_url( home_url(), PHP_URL_HOST );
			if ( in_array( $host, [ 'localhost', '127.0.0.1' ], true ) || ( is_string( $host ) && str_ends_with( $host, '.local' ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Seed when CPT exists but has no published tutors (first boot / repair).
	 */
	public static function maybe_seed_on_boot() {
		if ( ! self::demo_seed_allowed() ) {
			return;
		}
		if ( ! post_type_exists( 'tutors' ) ) {
			return;
		}
		$expected = count( self::seed_data() );
		if ( self::published_count() < $expected ) {
			self::ensure_seeded();
		}
	}

	/**
	 * @return int Published tutor count.
	 */
	public static function published_count() {
		if ( ! post_type_exists( 'tutors' ) ) {
			return 0;
		}
		$counts = wp_count_posts( 'tutors' );
		return isset( $counts->publish ) ? (int) $counts->publish : 0;
	}

	/**
	 * Ensure demo tutors exist as published CPT posts.
	 *
	 * @param bool $force Re-seed missing entries even when flag is set.
	 * @return array{created:int,skipped:int,total:int}
	 */
	public static function ensure_seeded( $force = false ) {
		if ( ! self::demo_seed_allowed() && ! $force ) {
			return [ 'created' => 0, 'skipped' => 0, 'total' => self::published_count() ];
		}
		if ( ! post_type_exists( 'tutors' ) ) {
			return [ 'created' => 0, 'skipped' => 0, 'total' => 0 ];
		}

		$created = 0;
		$skipped = 0;

		foreach ( self::seed_data() as $tutor ) {
			$slug = sanitize_title( $tutor['name'] );
			$existing = get_page_by_path( $slug, OBJECT, 'tutors' );
			if ( ! $existing ) {
				$found = get_posts(
					[
						'post_type'      => 'tutors',
						'post_status'    => 'any',
						'name'           => $slug,
						'posts_per_page' => 1,
						'fields'         => 'ids',
					]
				);
				$existing = ! empty( $found ) ? get_post( $found[0] ) : null;
			}

			if ( $existing && ! $force ) {
				++$skipped;
				continue;
			}

			if ( $existing ) {
				$post_id = (int) $existing->ID;
				wp_update_post(
					[
						'ID'           => $post_id,
						'post_status'  => 'publish',
						'post_excerpt' => $tutor['bio'],
						'post_content' => $tutor['bio'],
					]
				);
			} else {
				$post_id = wp_insert_post(
					[
						'post_type'    => 'tutors',
						'post_status'  => 'publish',
						'post_title'   => $tutor['name'],
						'post_name'    => $slug,
						'post_excerpt' => $tutor['bio'],
						'post_content' => $tutor['bio'],
					],
					true
				);
				if ( is_wp_error( $post_id ) || ! $post_id ) {
					continue;
				}
				++$created;
			}

			self::apply_tutor_meta( (int) $post_id, $tutor );
		}

		update_option( self::FLAG, NGC_VERSION, false );

		if ( class_exists( 'NGC_Tutor_Cpt_Source' ) ) {
			NGC_Tutor_Cpt_Source::ensure_showcase_tutor();
		}

		if ( class_exists( 'NGC_Audit' ) && $created > 0 ) {
			NGC_Audit::log( 'tutors_cpt_seeded', 'tutors', 0, [ 'created' => $created, 'total' => self::published_count() ] );
		}

		return [
			'created' => $created,
			'skipped' => $skipped,
			'total'   => self::published_count(),
		];
	}

	/**
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $tutor   Seed row.
	 */
	private static function apply_tutor_meta( $post_id, $tutor ) {
		$rate    = (int) ( $tutor['rate'] ?? $tutor['hourlyRate'] ?? 320 );
		$rating  = (float) ( $tutor['rating'] ?? 4.8 );
		$reviews = (int) ( $tutor['reviews'] ?? 0 );
		$mode    = sanitize_text_field( (string) ( $tutor['mode'] ?? $tutor['groupType'] ?? 'both' ) );

		update_post_meta( $post_id, 'tutor_rate', $rate );
		update_post_meta( $post_id, 'hourly_rate', $rate );
		update_post_meta( $post_id, '_ngc_hourly_rate', $rate );
		update_post_meta( $post_id, 'tutor_average_rating', $rating );
		update_post_meta( $post_id, '_ngc_rating', $rating );
		update_post_meta( $post_id, 'tutor_review_count', $reviews );
		update_post_meta( $post_id, '_ngc_reviews', $reviews );
		update_post_meta( $post_id, 'tutor_vetted', 1 );
		update_post_meta( $post_id, '_ngc_vetted', 1 );
		update_post_meta( $post_id, 'vetted', 1 );
		update_post_meta( $post_id, 'tutor_available', 1 );
		update_post_meta( $post_id, 'available', 1 );
		update_post_meta( $post_id, 'tutor_degree', (string) ( $tutor['degree'] ?? '' ) );
		update_post_meta( $post_id, 'tutor_mode', $mode );
		update_post_meta( $post_id, '_ngc_mode', $mode );
		update_post_meta( $post_id, 'ngc_demo_seed', 1 );

		if ( ! empty( $tutor['imageUrl'] ) ) {
			update_post_meta( $post_id, 'tutor_image_url', esc_url_raw( (string) $tutor['imageUrl'] ) );
			if ( apply_filters( 'ngc_tutor_seed_sideload_images', false ) ) {
				self::maybe_attach_external_image( $post_id, (string) $tutor['imageUrl'] );
			}
		}

		if ( ! empty( $tutor['subjects'] ) ) {
			wp_set_object_terms( $post_id, array_map( 'strval', (array) $tutor['subjects'] ), 'subject' );
		}
		if ( ! empty( $tutor['province'] ) ) {
			wp_set_object_terms( $post_id, array_map( 'strval', (array) $tutor['province'] ), 'province' );
		}
		if ( ! empty( $tutor['grades'] ) ) {
			wp_set_object_terms( $post_id, array_map( 'strval', (array) $tutor['grades'] ), 'grade' );
		}
		if ( ! empty( $tutor['format'] ) && taxonomy_exists( 'learning_format' ) ) {
			wp_set_object_terms( $post_id, array_map( 'strval', (array) $tutor['format'] ), 'learning_format' );
		}
	}

	/**
	 * @param int    $post_id  Post ID.
	 * @param string $image_url Remote image URL.
	 */
	private static function maybe_attach_external_image( $post_id, $image_url ) {
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		$attachment_id = media_sideload_image( $image_url, $post_id, null, 'id' );
		if ( ! is_wp_error( $attachment_id ) ) {
			set_post_thumbnail( $post_id, (int) $attachment_id );
		}
	}

	/**
	 * Design-system tutor roster for CPT seeding.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function seed_data() {
		if ( function_exists( 'bi_get_demo_tutors' ) ) {
			add_filter( 'bi_demo_content_enabled', '__return_true' );
			$demo = bi_get_demo_tutors( 12 );
			remove_filter( 'bi_demo_content_enabled', '__return_true' );
			$rows = [];
			$provinces = [ 'Gauteng', 'Western Cape', 'KwaZulu-Natal', 'Gauteng', 'KwaZulu-Natal', 'Gauteng', 'Western Cape' ];
			$formats   = [ 'Hybrid', 'Online', 'In-Person', 'Online', 'Hybrid', 'Online', 'Online' ];
			$grades    = [
				[ 'Grade 10', 'Grade 11', 'Grade 12' ],
				[ 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12' ],
				[ 'Grade 10', 'Grade 11', 'Grade 12' ],
				[ 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12' ],
				[ 'Grade 10', 'Grade 11', 'Grade 12' ],
				[ 'Grade 11', 'Grade 12' ],
				[ 'Grade 10', 'Grade 11', 'Grade 12' ],
			];
			$reviews   = [ 127, 156, 89, 64, 127, 89, 41 ];

			foreach ( $demo as $i => $t ) {
				$rows[] = [
					'name'       => $t['name'],
					'rate'       => (int) ( $t['hourlyRate'] ?? 320 ),
					'rating'     => (float) ( $t['rating'] ?? 4.8 ),
					'reviews'    => $reviews[ $i ] ?? 50,
					'degree'     => $t['degree'] ?? '',
					'bio'        => $t['bio'] ?? '',
					'subjects'   => (array) ( $t['subjects'] ?? [] ),
					'province'   => [ $provinces[ $i ] ?? 'Gauteng' ],
					'grades'     => $grades[ $i ] ?? [ 'Grade 10', 'Grade 11', 'Grade 12' ],
					'format'     => [ $formats[ $i ] ?? 'Online' ],
					'groupType'  => $t['groupType'] ?? 'both',
					'imageUrl'   => $t['imageUrl'] ?? '',
				];
			}
			return apply_filters( 'ngc_tutor_seed_data', $rows );
		}

		return apply_filters(
			'ngc_tutor_seed_data',
			[]
		);
	}

	/**
	 * Remove demo-seeded tutor CPT posts (ngc_demo_seed = 1).
	 *
	 * @return int Number of posts deleted.
	 */
	public static function purge_demo_tutors() {
		if ( ! post_type_exists( 'tutors' ) ) {
			return 0;
		}
		$ids = get_posts(
			[
				'post_type'      => 'tutors',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => 'ngc_demo_seed', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => '1',
			]
		);
		$deleted = 0;
		foreach ( $ids as $post_id ) {
			if ( wp_delete_post( (int) $post_id, true ) ) {
				++$deleted;
			}
		}
		if ( class_exists( 'NGC_Audit' ) && $deleted > 0 ) {
			NGC_Audit::log( 'tutors_demo_purged', 'tutors', 0, [ 'deleted' => $deleted ] );
		}
		return $deleted;
	}
}
