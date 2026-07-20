<?php
/**
 * Design token helpers for NGT UI library.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Tokens' ) ) {
	/**
	 * Central token values — prefer CSS custom properties at render time.
	 */
	class NGT_UI_Tokens {

		public const ACCENT     = 'var(--ngt-color-accent)';
		public const ACCENT_2   = 'var(--ngt-color-magic-accent-2)';
		public const ACCENT_HEX = '#c4a35a';
		public const ACCENT_2_HEX = '#3b2f6e';

		/**
		 * Resolve accent color for inline styles (falls back to token hex).
		 *
		 * @param string $setting User override from component settings.
		 */
		public static function accent( string $setting = '' ): string {
			if ( '' !== $setting && 0 !== strpos( $setting, 'var(' ) ) {
				return $setting;
			}
			return self::ACCENT;
		}

		/**
		 * @param string $setting User override.
		 */
		public static function accent_secondary( string $setting = '' ): string {
			if ( '' !== $setting && 0 !== strpos( $setting, 'var(' ) ) {
				return $setting;
			}
			return self::ACCENT_2;
		}

		/**
		 * Default schema color values (for admin pickers / JSON schema).
		 */
		public static function schema_accent_default(): string {
			return self::ACCENT_HEX;
		}

		public static function schema_accent_2_default(): string {
			return self::ACCENT_2_HEX;
		}
	}
}
