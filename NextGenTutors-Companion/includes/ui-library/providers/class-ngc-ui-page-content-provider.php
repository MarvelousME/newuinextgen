<?php
/**
 * CMS page section content provider.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads NGC_Section_CMS + WP page meta.
 */
class NGC_UI_Page_Content_Provider extends NGC_UI_Data_Provider {

	/**
	 * @return string
	 */
	public function get_key() {
		return 'page_content';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return class_exists( 'NGC_Section_CMS' );
	}

	/**
	 * @param array<string, mixed> $args { page_key, section_key?, limit? }.
	 * @return array<int, array<string, mixed>>
	 */
	public function list( $args = [] ) {
		if ( ! $this->is_available() ) {
			return [];
		}

		$page_key    = sanitize_key( $args['page_key'] ?? 'home' );
		$section_key = isset( $args['section_key'] ) ? sanitize_key( $args['section_key'] ) : '';

		if ( $section_key ) {
			$section = NGC_Section_CMS::get_section( $page_key, $section_key );
			return $section ? [ array_merge( $section, [ 'page_key' => $page_key, 'section_key' => $section_key ] ) ] : [];
		}

		$keys = $this->section_keys_for_page( $page_key );
		$out  = [];
		foreach ( $keys as $key ) {
			$section = NGC_Section_CMS::get_section( $page_key, $key );
			if ( $section ) {
				$out[] = array_merge( $section, [ 'page_key' => $page_key, 'section_key' => $key ] );
			}
		}
		$limit = isset( $args['limit'] ) ? (int) $args['limit'] : 0;
		return $limit > 0 ? array_slice( $out, 0, $limit ) : $out;
	}

	/**
	 * @param string $page_key Page key.
	 * @return string[]
	 */
	private function section_keys_for_page( $page_key ) {
		$map = [
			'home' => [ 'hero', 'marquee', 'how_it_works', 'subjects', 'featured_tutors', 'reviews', 'pricing_teaser', 'faq_teaser', 'cta_final' ],
		];
		return $map[ $page_key ] ?? [];
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @param string               $component Component.
	 * @return array<string, mixed>
	 */
	public function map_to_component( $row, $component ) {
		switch ( $component ) {
			case 'hero':
				return [
					'title'    => trim( ( $row['title'] ?? '' ) . ' ' . ( $row['title_accent'] ?? '' ) ),
					'subtitle' => $row['lead'] ?? $row['subtitle'] ?? '',
					'cta'      => [
						'label' => $row['cta_primary'] ?? $row['cta_label'] ?? '',
						'url'   => $row['cta_url'] ?? home_url( '/find-a-tutor' ),
					],
					'image_id' => (int) ( $row['image_id'] ?? $row['hero_image_id'] ?? 0 ),
					'stats'    => $row['stats'] ?? [],
				];
			default:
				return $row;
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify_source() {
		return [
			'provider' => $this->get_key(),
			'class'    => 'NGC_Section_CMS',
			'tables'   => [ 'ngc_page_sections' ],
		];
	}
}
