<?php
/**
 * WordPress page matcher.
 *
 * @package RevampHtmlImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps HTML files to WordPress pages.
 */
class RHI_Page_Matcher {

	/**
	 * Known filename → slug map from page-map.json + HTML sources.
	 *
	 * @return array<string, string>
	 */
	public static function filename_slug_map() {
		return [
			'index.html'           => 'home',
			'about.html'           => 'about',
			'become-a-tutor.html'  => 'become-a-tutor',
			'find-a-tutor.html'    => 'find-a-tutor',
			'contact.html'         => 'contact',
			'pricing.html'         => 'pricing',
			'blog.html'            => 'blog',
			'guarantee.html'       => 'guarantee',
			'safety-guide.html'    => 'safety-guide',
			'tutor-vetting.html'   => 'tutor-vetting',
			'privacy.html'         => 'privacy-policy',
			'terms.html'           => 'terms',
			'onboarding.html'      => 'onboarding',
			'wordpress-setup.html' => 'wordpress-setup',
			'tutor-dashboard.html' => 'tutor-dashboard',
			'dashboard.html'       => 'student-dashboard',
			'tutor-profile.html'   => '',
		];
	}

	/**
	 * Pages that should be skipped (app shells / dynamic).
	 *
	 * @return string[]
	 */
	public static function skip_filenames() {
		return [];
	}

	/**
	 * @param string               $relative_path Relative HTML path.
	 * @param array<string, mixed> $parsed        Parser output.
	 * @return array<string, mixed>
	 */
	public static function match_file( $relative_path, $parsed ) {
		$filename = strtolower( basename( $relative_path ) );
		$map      = self::filename_slug_map();
		$notes    = [];

		if ( in_array( $filename, self::skip_filenames(), true ) ) {
			return self::result( 'SKIP', 0, '', null, 'File marked as skip list.' );
		}

		$suggested_slug = $map[ $filename ] ?? '';
		if ( ! $suggested_slug ) {
			$suggested_slug = sanitize_title( pathinfo( $filename, PATHINFO_FILENAME ) );
		}

		$confidence = 50;
		$action     = 'REVIEW_REQUIRED';

		if ( isset( $map[ $filename ] ) && '' !== $map[ $filename ] ) {
			$suggested_slug = $map[ $filename ];
			$confidence     = 90;
			$action         = 'UPDATE';
		} elseif ( 'tutor-profile.html' === $filename ) {
			$notes[]    = 'No page-map entry for tutor profile template.';
			$confidence = 30;
			$action     = 'REVIEW_REQUIRED';
		} elseif ( isset( $map[ $filename ] ) && '' === $map[ $filename ] ) {
			$notes[] = 'Explicitly unmapped file.';
		}

		// Boost confidence via title/slug match.
		$wp_page = self::find_wp_page( $suggested_slug, $parsed['title'] ?? '' );
		if ( $wp_page ) {
			$confidence = min( 100, $confidence + 10 );
			$action     = 'UPDATE';
		} else {
			if ( $confidence >= 80 ) {
				$action = 'CREATE';
			}
		}

		// Dashboard ambiguity.
		if ( 'dashboard.html' === $filename ) {
			$notes[]    = 'dashboard.html mapped to student-dashboard; verify if parent-dashboard intended.';
			$confidence = 75;
			$action     = 'REVIEW_REQUIRED';
		}

		// Dynamic JS-heavy pages.
		if ( ! empty( $parsed['has_dynamic_js'] ) ) {
			$notes[] = 'Contains dynamic JS placeholders; static content only will import.';
		}
		if ( ! empty( $parsed['has_form'] ) && false !== strpos( $filename, 'find-a-tutor' ) ) {
			$notes[] = 'Page may use ngc_find_tutor_form shortcode — preserve shortcode region.';
		}

		// Hash skip check.
		if ( $wp_page ) {
			$stored_hash = get_post_meta( $wp_page->ID, '_revamp_source_hash', true );
			if ( $stored_hash && $stored_hash === ( $parsed['source_hash'] ?? '' ) ) {
				$notes[] = 'Source hash unchanged — will skip unless force mode.';
			}
		}

		return self::result( $action, $confidence, $suggested_slug, $wp_page, implode( ' ', $notes ), $parsed );
	}

	/**
	 * @param string $action          Action.
	 * @param int    $confidence      Score.
	 * @param string $suggested_slug  Slug.
	 * @param WP_Post|null $wp_page   Matched page.
	 * @param string $notes           Notes.
	 * @param array<string,mixed> $parsed Parsed data.
	 * @return array<string, mixed>
	 */
	private static function result( $action, $confidence, $suggested_slug, $wp_page, $notes = '', $parsed = [] ) {
		return [
			'action'              => $action,
			'confidence'          => (int) $confidence,
			'suggested_slug'      => $suggested_slug,
			'wp_page_id'          => $wp_page ? (int) $wp_page->ID : 0,
			'wp_page_title'       => $wp_page ? $wp_page->post_title : '',
			'wp_page_status'      => $wp_page ? $wp_page->post_status : '',
			'has_page_builder'    => $wp_page ? self::has_page_builder( $wp_page->ID ) : false,
			'notes'               => $notes,
			'detected_title'      => $parsed['title'] ?? '',
			'detected_h1'         => $parsed['h1'] ?? '',
			'image_count'         => $parsed['image_count'] ?? 0,
		];
	}

	/**
	 * @param string $slug  Page slug.
	 * @param string $title Document title.
	 * @return WP_Post|null
	 */
	public static function find_wp_page( $slug, $title = '' ) {
		if ( 'home' === $slug ) {
			$front = (int) get_option( 'page_on_front' );
			if ( $front ) {
				return get_post( $front );
			}
			$slug = 'home';
		}

		$page = get_page_by_path( $slug );
		if ( $page ) {
			return $page;
		}

		if ( $title ) {
			$clean = preg_replace( '/\s*[—–-]\s*NextGen.*$/i', '', $title );
			$found = get_page_by_title( $clean, OBJECT, 'page' );
			if ( $found ) {
				return $found;
			}
		}

		return null;
	}

	/**
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function has_page_builder( $post_id ) {
		$meta_keys = [ '_elementor_edit_mode', '_wpb_vc_js_status', '_et_pb_use_builder' ];
		foreach ( $meta_keys as $key ) {
			if ( get_post_meta( $post_id, $key, true ) ) {
				return true;
			}
		}
		return false;
	}
}
