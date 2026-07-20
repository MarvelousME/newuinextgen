<?php
/**
 * Tutor marketplace data provider.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tutors CPT + marketplace helpers — never hardcodes roster in templates.
 */
class NGC_UI_Tutor_Data_Provider extends NGC_UI_Data_Provider {

	/**
	 * @return string
	 */
	public function get_key() {
		return 'tutor';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return post_type_exists( 'tutors' );
	}

	/**
	 * @param array<string, mixed> $args Query args.
	 * @return array<int, array<string, mixed>>
	 */
	public function list( $args = [] ) {
		if ( function_exists( 'ngt_get_tutors' ) ) {
			$tutors = ngt_get_tutors();
		} elseif ( function_exists( 'bi_get_live_tutors' ) ) {
			$tutors = bi_get_live_tutors();
		} else {
			$tutors = $this->query_cpt( $args );
		}

		if ( empty( $tutors ) && $this->demo_allowed() && function_exists( 'ngt_get_demo_tutor_roster' ) ) {
			$tutors = apply_filters( 'ngc_ui_demo_tutors', ngt_get_demo_tutor_roster() );
		}

		if ( ! empty( $args['subject'] ) ) {
			$slug = sanitize_title( $args['subject'] );
			$tutors = array_values(
				array_filter(
					$tutors,
					static function ( $t ) use ( $slug ) {
						foreach ( (array) ( $t['subjects'] ?? [] ) as $s ) {
							if ( sanitize_title( $s ) === $slug ) {
								return true;
							}
						}
						return false;
					}
				)
			);
		}

		$limit = max( 1, min( 24, (int) ( $args['limit'] ?? 6 ) ) );
		return array_slice( $tutors, 0, $limit );
	}

	/**
	 * @param array<string, mixed> $args Args.
	 * @return array<int, array<string, mixed>>
	 */
	private function query_cpt( $args ) {
		if ( ! $this->is_available() ) {
			return [];
		}

		$q = new WP_Query(
			[
				'post_type'      => 'tutors',
				'post_status'    => 'publish',
				'posts_per_page' => (int) ( $args['limit'] ?? 6 ),
				'no_found_rows'  => true,
				'orderby'        => 'meta_value_num',
				'meta_key'       => '_ngc_rating',
				'order'          => 'DESC',
			]
		);

		$rows = [];
		while ( $q->have_posts() ) {
			$q->the_post();
			$id       = get_the_ID();
			$subjects = wp_get_post_terms( $id, 'subject', [ 'fields' => 'names' ] );
			$province = wp_get_post_terms( $id, 'province', [ 'fields' => 'names' ] );
			$grades   = wp_get_post_terms( $id, 'grade', [ 'fields' => 'names' ] );

			$rows[] = [
				'id'       => $id,
				'name'     => get_the_title(),
				'avatar'   => get_the_post_thumbnail_url( $id, 'thumbnail' ) ?: '',
				'subjects' => is_wp_error( $subjects ) ? [] : $subjects,
				'grades'   => is_wp_error( $grades ) ? '' : implode( ', ', array_slice( (array) $grades, 0, 3 ) ),
				'location' => is_wp_error( $province ) || empty( $province ) ? '' : $province[0],
				'rate'     => (int) get_post_meta( $id, '_ngc_hourly_rate', true ),
				'bio'      => wp_trim_words( get_the_excerpt(), 25 ),
				'rating'   => (float) get_post_meta( $id, '_ngc_rating', true ),
				'reviews'  => (int) get_post_meta( $id, '_ngc_reviews', true ),
				'url'      => get_permalink(),
				'vetted'   => (bool) get_post_meta( $id, '_ngc_vetted', true ),
			];
		}
		wp_reset_postdata();
		return $rows;
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @param string               $component Component.
	 * @return array<string, mixed>
	 */
	public function map_to_component( $row, $component ) {
		if ( 'tutor-card' !== $component ) {
			return $row;
		}
		return [
			'name'     => $row['name'] ?? '',
			'photo'    => $row['avatar'] ?? '',
			'subjects' => $row['subjects'] ?? [],
			'grades'   => $row['grades'] ?? '',
			'rating'   => $row['rating'] ?? 0,
			'reviews'  => $row['reviews'] ?? 0,
			'price'    => $row['rate'] ?? 0,
			'location' => $row['location'] ?? '',
			'vetted'   => ! empty( $row['vetted'] ),
			'book_url' => $row['url'] ?? '',
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify_source() {
		return [
			'provider'  => $this->get_key(),
			'post_type' => 'tutors',
			'meta'      => [ '_ngc_rating', '_ngc_hourly_rate', '_ngc_reviews', '_ngc_vetted' ],
			'demo_gate' => 'NGC_Platform_Demo::is_enabled',
		];
	}
}
