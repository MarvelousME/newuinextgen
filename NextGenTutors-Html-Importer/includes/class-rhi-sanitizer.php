<?php
/**
 * Content sanitizer.
 *
 * @package RevampHtmlImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize HTML for WordPress post content.
 */
class RHI_Sanitizer {

	/** @var string[] */
	private static $removed_log = [];

	/**
	 * @return string[]
	 */
	public static function reset_removed_log() {
		self::$removed_log = [];
		return self::$removed_log;
	}

	/**
	 * @return string[]
	 */
	public static function get_removed_log() {
		return self::$removed_log;
	}

	/**
	 * Sanitize final HTML content.
	 *
	 * @param string $html Raw HTML.
	 * @return string
	 */
	public static function sanitize_content( $html ) {
		self::reset_removed_log();

		$html = self::strip_windows_paths( $html );
		$html = self::remove_dangerous_tags( $html );
		$html = self::strip_event_handlers( $html );
		$html = self::normalize_whitespace( $html );

		$allowed = wp_kses_allowed_html( 'post' );
		$allowed = self::extend_allowed_tags( $allowed );

		return wp_kses( $html, $allowed );
	}

	/**
	 * @param string $html HTML.
	 * @return string
	 */
	public static function strip_windows_paths( $html ) {
		return preg_replace(
			'#[A-Za-z]:\\\\[^"\'<>\s]+#',
			'',
			$html
		) ?: $html;
	}

	/**
	 * @param string $html HTML.
	 * @return string
	 */
	private static function remove_dangerous_tags( $html ) {
		$patterns = [
			'/<script\b[^>]*>[\s\S]*?<\/script>/i',
			'/<style\b[^>]*>[\s\S]*?<\/style>/i',
			'/<iframe\b[^>]*>[\s\S]*?<\/iframe>/i',
			'/<object\b[^>]*>[\s\S]*?<\/object>/i',
			'/<embed\b[^>]*\/?>/i',
		];
		foreach ( $patterns as $pattern ) {
			if ( preg_match_all( $pattern, $html, $m ) ) {
				foreach ( $m[0] as $match ) {
					self::$removed_log[] = 'removed_tag:' . substr( $match, 0, 40 );
				}
			}
			$html = preg_replace( $pattern, '', $html ) ?: $html;
		}
		return $html;
	}

	/**
	 * @param string $html HTML.
	 * @return string
	 */
	private static function strip_event_handlers( $html ) {
		return preg_replace( '/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html ) ?: $html;
	}

	/**
	 * @param string $html HTML.
	 * @return string
	 */
	private static function normalize_whitespace( $html ) {
		$html = preg_replace( '/\r\n|\r/', "\n", $html ) ?: $html;
		return trim( $html );
	}

	/**
	 * @param array<string, array<string, bool>> $allowed Allowed tags.
	 * @return array<string, array<string, bool>>
	 */
	private static function extend_allowed_tags( $allowed ) {
		$extra = [
			'section' => [ 'class' => true, 'id' => true, 'aria-hidden' => true, 'role' => true, 'style' => true ],
			'article' => [ 'class' => true, 'id' => true, 'style' => true ],
			'aside'   => [ 'class' => true, 'id' => true, 'style' => true ],
			'nav'     => [ 'class' => true, 'aria-label' => true ],
			'span'    => [ 'class' => true, 'id' => true, 'style' => true, 'aria-hidden' => true ],
			'div'     => [ 'class' => true, 'id' => true, 'style' => true, 'role' => true, 'aria-hidden' => true ],
			'img'     => [
				'src' => true, 'alt' => true, 'class' => true, 'width' => true, 'height' => true,
				'loading' => true, 'decoding' => true, 'referrerpolicy' => true,
			],
			'svg'     => [ 'viewbox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true, 'aria-hidden' => true ],
			'path'    => [ 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ],
			'circle'  => [ 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true ],
			'canvas'  => [ 'id' => true, 'class' => true, 'aria-hidden' => true ],
			'form'    => [ 'class' => true, 'id' => true, 'method' => true, 'action' => true ],
			'input'   => [ 'type' => true, 'name' => true, 'id' => true, 'class' => true, 'placeholder' => true, 'value' => true, 'aria-label' => true, 'min' => true, 'max' => true, 'step' => true ],
			'select'  => [ 'name' => true, 'id' => true, 'class' => true, 'aria-label' => true ],
			'option'  => [ 'value' => true, 'selected' => true ],
			'textarea'=> [ 'name' => true, 'id' => true, 'class' => true, 'placeholder' => true, 'rows' => true, 'aria-label' => true ],
			'button'  => [ 'type' => true, 'class' => true, 'id' => true, 'data-format' => true, 'data-sort' => true ],
			'label'   => [ 'for' => true, 'class' => true ],
		];
		foreach ( $extra as $tag => $attrs ) {
			if ( isset( $allowed[ $tag ] ) ) {
				$allowed[ $tag ] = array_merge( $allowed[ $tag ], $attrs );
			} else {
				$allowed[ $tag ] = $attrs;
			}
		}
		return $allowed;
	}
}
