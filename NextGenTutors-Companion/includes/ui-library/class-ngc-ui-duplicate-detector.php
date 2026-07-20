<?php
/**
 * Duplicate content detector for import tooling.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compares extracted strings against CMS + inventories.
 */
class NGC_UI_Duplicate_Detector {

	/**
	 * Normalize text for comparison.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function normalize( $text ) {
		$text = wp_strip_all_tags( (string) $text );
		$text = strtolower( trim( preg_replace( '/\s+/', ' ', $text ) ) );
		return $text;
	}

	/**
	 * Similarity ratio 0-1.
	 *
	 * @param string $a A.
	 * @param string $b B.
	 * @return float
	 */
	public static function similarity( $a, $b ) {
		$a = self::normalize( $a );
		$b = self::normalize( $b );
		if ( $a === $b ) {
			return 1.0;
		}
		if ( '' === $a || '' === $b ) {
			return 0.0;
		}
		similar_text( $a, $b, $pct );
		return round( $pct / 100, 3 );
	}

	/**
	 * Scan heading list for duplicates across sources.
	 *
	 * @param array<int, array{source:string, headings:array<int, array{text:string}>}> $sources Sources.
	 * @param float $threshold Similarity threshold.
	 * @return array<int, array<string, mixed>>
	 */
	public static function scan_headings( $sources, $threshold = 0.92 ) {
		$flat = [];
		foreach ( $sources as $src ) {
			foreach ( $src['headings'] ?? [] as $h ) {
				$text = $h['text'] ?? '';
				if ( strlen( $text ) < 8 ) {
					continue;
				}
				$flat[] = [
					'source' => $src['source'] ?? '',
					'text'   => $text,
					'norm'   => self::normalize( $text ),
				];
			}
		}

		$dupes = [];
		$count = count( $flat );
		for ( $i = 0; $i < $count; $i++ ) {
			for ( $j = $i + 1; $j < $count; $j++ ) {
				if ( $flat[ $i ]['norm'] === $flat[ $j ]['norm'] ) {
					$dupes[] = [
						'item'       => $flat[ $i ]['text'],
						'source_a'   => $flat[ $i ]['source'],
						'source_b'   => $flat[ $j ]['source'],
						'similarity' => 'exact',
						'action'     => 'skip_duplicate',
					];
					continue;
				}
				$sim = self::similarity( $flat[ $i ]['text'], $flat[ $j ]['text'] );
				if ( $sim >= $threshold ) {
					$dupes[] = [
						'item'       => $flat[ $i ]['text'],
						'source_a'   => $flat[ $i ]['source'],
						'source_b'   => $flat[ $j ]['source'],
						'similarity' => $sim,
						'action'     => 'human_review',
					];
				}
			}
		}

		return $dupes;
	}

	/**
	 * Check if text already exists in a CMS section field.
	 *
	 * @param string $text      Text.
	 * @param string $page_key    Page key.
	 * @param string $section_key Section key.
	 * @return bool
	 */
	public static function exists_in_cms( $text, $page_key, $section_key ) {
		if ( ! class_exists( 'NGC_Section_CMS' ) ) {
			return false;
		}
		$section = NGC_Section_CMS::get_section( $page_key, $section_key );
		if ( ! $section ) {
			return false;
		}
		$hay = self::normalize( wp_json_encode( $section ) );
		return false !== strpos( $hay, self::normalize( $text ) );
	}
}
