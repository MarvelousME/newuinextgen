<?php
/**
 * Companion-backed data accessors for Elementor widgets.
 *
 * Widgets never query CPTs or invent marketplace logic.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data layer.
 */
class BI_EL_Data {

	/**
	 * Companion available.
	 */
	public static function companion_active() {
		return function_exists( 'bi_companion_active' ) ? bi_companion_active() : class_exists( 'NGC_Plugin', false );
	}

	/**
	 * UI provider payload.
	 *
	 * @param string               $provider Provider key.
	 * @param string               $slug     Component slug.
	 * @param array<string, mixed> $args     Args.
	 * @return array<int, array<string, mixed>>
	 */
	public static function component_data( $provider, $slug, $args = [] ) {
		if ( ! self::companion_active() || ! class_exists( 'NGC_UI_Provider_Registry' ) ) {
			return [];
		}
		$data = NGC_UI_Provider_Registry::component_data( (string) $provider, (string) $slug, $args );
		return is_array( $data ) ? $data : [];
	}

	/**
	 * Live tutors.
	 *
	 * @param array<string, mixed> $args Args.
	 * @return array<int, mixed>
	 */
	public static function tutors( $args = [] ) {
		$limit   = max( 1, min( 24, (int) ( $args['limit'] ?? 6 ) ) );
		$subject = sanitize_text_field( (string) ( $args['subject'] ?? '' ) );
		$rows    = self::component_data( 'tutor', 'tutor-card', [ 'limit' => $limit, 'subject' => $subject ] );
		if ( $rows ) {
			return $rows;
		}
		if ( function_exists( 'bi_get_live_tutors' ) ) {
			$tutors = bi_get_live_tutors( $limit );
			if ( $subject && function_exists( 'bi_filter_tutors_by_subject' ) ) {
				$tutors = bi_filter_tutors_by_subject( $tutors, $subject );
			}
			return is_array( $tutors ) ? $tutors : [];
		}
		return [];
	}

	/**
	 * Subjects catalog.
	 *
	 * @param int $limit Limit.
	 * @return array<int, mixed>
	 */
	public static function subjects( $limit = 8 ) {
		$rows = self::component_data( 'subject', 'subject-card', [ 'limit' => $limit ] );
		if ( $rows ) {
			return $rows;
		}
		if ( class_exists( 'NGC_Subjects_CMS' ) ) {
			$catalog = NGC_Subjects_CMS::catalog();
			$out     = [];
			$i       = 0;
			foreach ( (array) $catalog as $slug => $label ) {
				$out[] = [ 'slug' => $slug, 'name' => $label, 'title' => $label ];
				if ( ++$i >= $limit ) {
					break;
				}
			}
			return $out;
		}
		return function_exists( 'bi_get_subject_tracks' ) ? array_slice( (array) bi_get_subject_tracks(), 0, $limit ) : [];
	}

	/**
	 * Pricing tiers.
	 *
	 * @param int $limit Limit.
	 * @return array<int, mixed>
	 */
	public static function pricing( $limit = 3 ) {
		$rows = self::component_data( 'pricing', 'pricing-card', [ 'limit' => $limit ] );
		if ( $rows ) {
			return array_slice( $rows, 0, $limit );
		}
		if ( function_exists( 'ngc_get_pricing_tiers' ) ) {
			return array_slice( (array) ngc_get_pricing_tiers(), 0, $limit );
		}
		return [];
	}

	/**
	 * Reviews / testimonials.
	 *
	 * @param int $limit Limit.
	 * @return array<int, mixed>
	 */
	public static function reviews( $limit = 4 ) {
		return self::component_data( 'review', 'review-card', [ 'limit' => $limit ] );
	}

	/**
	 * Marketing stats.
	 *
	 * @return array<int, mixed>
	 */
	public static function stats() {
		$rows = self::component_data( 'analytics', 'stats-band', [ 'limit' => 6 ] );
		if ( $rows ) {
			return $rows;
		}
		return function_exists( 'bi_real_stat_cards' ) ? (array) bi_real_stat_cards() : [];
	}

	/**
	 * FAQ items from Section CMS (no invented copy on production).
	 *
	 * @return array<int, array{q?:string,a?:string}>
	 */
	public static function faq() {
		if ( class_exists( 'NGC_Section_CMS' ) ) {
			$section = NGC_Section_CMS::get_section( 'home', 'faq' );
			if ( is_array( $section ) && ! empty( $section['items'] ) && is_array( $section['items'] ) ) {
				return $section['items'];
			}
		}
		return [];
	}

	/**
	 * CMS hero copy.
	 *
	 * @param string $page_key Page key.
	 * @return array<string, mixed>
	 */
	public static function hero_cms( $page_key = 'home' ) {
		$rows = self::component_data( 'page_content', 'hero', [ 'page_key' => $page_key ] );
		return is_array( $rows ) && isset( $rows[0] ) && is_array( $rows[0] ) ? $rows[0] : ( is_array( $rows ) ? $rows : [] );
	}

	/**
	 * Empty-state markup (editor hint vs production silence).
	 *
	 * @param string $message Editor message.
	 * @return string
	 */
	public static function empty_state( $message ) {
		if ( function_exists( 'bi_el_ds_is_editor_mode' ) && bi_el_ds_is_editor_mode() ) {
			return '<p class="bi-el__empty" role="status">' . esc_html( $message ) . '</p>';
		}
		return '';
	}
}
