<?php
/**
 * Shared parsing helpers for catalog kind renderers.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Kind_Parser' ) ) {
	/**
	 * Parses catalog item strings.
	 */
	class NGT_UI_Kind_Parser {

		/**
		 * @param string $raw  CSV/pipe items.
		 * @param string $text Fallback text.
		 * @return array<int, string>
		 */
		public static function parse_items( string $raw, string $text ): array {
			if ( '' === $raw && '' !== $text ) {
				$raw = $text;
			}
			if ( '' === $raw ) {
				return array( 'Design', 'Build', 'Ship', 'Grow' );
			}
			$parts = preg_split( '/\s*[|,]\s*/', $raw ) ?: array();
			$out   = array();
			foreach ( $parts as $p ) {
				$p = trim( (string) $p );
				if ( '' !== $p ) {
					$out[] = $p;
				}
			}
			return $out ?: array( 'NextGen' );
		}
	}
}
