<?php
/**
 * Subject taxonomy / tracks provider.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subject cards and explorer grids.
 */
class NGC_UI_Subject_Data_Provider extends NGC_UI_Data_Provider {

	/**
	 * @return string
	 */
	public function get_key() {
		return 'subject';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return taxonomy_exists( 'subject' ) || function_exists( 'bi_get_subject_tracks' );
	}

	/**
	 * @param array<string, mixed> $args Args.
	 * @return array<int, array<string, mixed>>
	 */
	public function list( $args = [] ) {
		if ( taxonomy_exists( 'subject' ) ) {
			$terms = get_terms(
				[
					'taxonomy'   => 'subject',
					'hide_empty' => false,
					'number'     => (int) ( $args['limit'] ?? 20 ),
				]
			);
			if ( ! is_wp_error( $terms ) && $terms ) {
				return array_map(
					static function ( $term ) {
						return [
							'id'    => $term->term_id,
							'slug'  => $term->slug,
							'name'  => $term->name,
							'desc'  => $term->description,
							'count' => (int) $term->count,
						];
					},
					$terms
				);
			}
		}

		if ( function_exists( 'bi_get_subject_tracks' ) && $this->demo_allowed() ) {
			return array_map(
				static function ( $row ) {
					return [
						'slug' => sanitize_title( $row['name'] ),
						'name' => $row['name'],
						'desc' => $row['desc'],
					];
				},
				bi_get_subject_tracks()
			);
		}

		return [];
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @param string               $component Component.
	 * @return array<string, mixed>
	 */
	public function map_to_component( $row, $component ) {
		return [
			'title' => $row['name'] ?? '',
			'desc'  => $row['desc'] ?? '',
			'url'   => home_url( '/subjects/' . ( $row['slug'] ?? '' ) . '/' ),
			'count' => $row['count'] ?? null,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify_source() {
		return [
			'provider' => $this->get_key(),
			'taxonomy' => 'subject',
			'fallback' => 'bi_get_subject_tracks (demo only)',
		];
	}
}
