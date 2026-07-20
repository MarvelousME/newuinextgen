<?php
/**
 * Theme CSS class adoption helper.
 *
 * @package RevampHtmlImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps static HTML classes to BeyondInfinity / NextGen theme classes.
 */
class RHI_Css_Adoption {

	/**
	 * Class map: source => replacement (space-separated).
	 *
	 * @return array<string, string>
	 */
	public static function class_map() {
		return [
			'wrap'           => 'ngt-container ng-container ng-container--boxed',
			'btn btn--lime'  => 'ngt-btn ngt-btn--primary btn btn--lime',
			'btn btn--ghost' => 'ngt-btn ngt-btn--outline btn btn--ghost',
			'btn btn--shine' => 'ngt-btn ngt-btn--primary btn btn--lime btn--shine',
			'btn btn--block' => 'ngt-btn ngt-btn--block btn btn--block',
			'section'        => 'ngt-section ng-section section',
			'section--tight' => 'ngt-section ng-section section section--tight',
			'lead'           => 'ngt-lead lead',
			'h-serif'        => 'ngt-heading h-serif',
			'eyebrow'        => 'bi-eyebrow eyebrow',
			'stat-card'      => 'ngt-card stat-card',
			'value-card'     => 'ngt-card value-card',
			'form-card'      => 'ngt-card form-card',
			'contact-card'   => 'ngt-card contact-card',
			'cta-band'       => 'ngt-cta cta-band',
			'pagehead'       => 'ngt-hero pagehead',
			'pagehead__inner'=> 'ngt-hero__content pagehead__inner',
			'hero'           => 'ngt-hero hero',
			'hero__inner'    => 'ngt-container hero__inner',
			'steps'          => 'ngt-grid-3 steps',
			'step'           => 'ngt-card step',
			'dir-grid'       => 'ngt-grid-3 dir-grid',
			'filters'        => 'ngt-card filters',
			'kpi-card'       => 'ngt-card kpi-card',
			'panel'          => 'ngt-card panel',
		];
	}

	/**
	 * Apply class adoption to HTML string.
	 *
	 * @param string $html HTML content.
	 * @return array{html:string,issues:array<int,string>}
	 */
	public static function adopt( $html ) {
		$issues = [];
		$map    = self::class_map();

		// Longest keys first to avoid partial replacements.
		uksort(
			$map,
			static function ( $a, $b ) {
				return strlen( $b ) - strlen( $a );
			}
		);

		foreach ( $map as $from => $to ) {
			$pattern = '/\bclass=(["\'])((?:[^"\']*\s)?' . preg_quote( $from, '/' ) . '(?:\s[^"\']*)?)\1/i';
			$html    = preg_replace_callback(
				$pattern,
				static function ( $m ) use ( $from, $to ) {
					$classes = $m[2];
					if ( false === strpos( $classes, $from ) ) {
						return $m[0];
					}
					$new = trim( str_replace( $from, $to, $classes ) );
					$new = preg_replace( '/\s+/', ' ', $new );
					return 'class="' . esc_attr( $new ) . '"';
				},
				$html
			) ?: $html;
		}

		// Wrap bare sections without container.
		if ( preg_match( '/class="[^"]*section[^"]*"/', $html ) && false === strpos( $html, 'ng-container' ) ) {
			$issues[] = 'mobile_layout:sections_without_ng_container';
		}

		// Flag heavy inline styles.
		if ( preg_match_all( '/style="[^"]{80,}"/', $html, $m ) && count( $m[0] ) > 5 ) {
			$issues[] = 'styling:heavy_inline_styles';
		}

		return [
			'html'   => $html,
			'issues' => $issues,
		];
	}

	/**
	 * Convert internal .html links to WordPress permalinks.
	 *
	 * @param string $html     HTML.
	 * @param string $base_dir Base directory of source file.
	 * @return string
	 */
	public static function rewrite_links( $html ) {
		$slug_map = RHI_Page_Matcher::filename_slug_map();

		return preg_replace_callback(
			'/href=(["\'])([^"\']+\.html?)(\1)/i',
			static function ( $m ) use ( $slug_map ) {
				$href = $m[2];
				$file = basename( $href );
				if ( isset( $slug_map[ $file ] ) ) {
					$slug = $slug_map[ $file ];
					if ( 'home' === $slug ) {
						return 'href="' . esc_url( home_url( '/' ) ) . '"';
					}
					$page = get_page_by_path( $slug );
					if ( $page ) {
						return 'href="' . esc_url( get_permalink( $page ) ) . '"';
					}
					return 'href="' . esc_url( home_url( '/' . $slug . '/' ) ) . '"';
				}
				return $m[0];
			},
			$html
		) ?: $html;
	}
}
