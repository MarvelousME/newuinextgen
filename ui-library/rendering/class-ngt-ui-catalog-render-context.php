<?php
/**
 * Immutable render context passed to kind strategies.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Catalog_Render_Context' ) ) {
	/**
	 * Value object for catalog kind rendering.
	 */
	class NGT_UI_Catalog_Render_Context {

		/**
		 * @param string               $name     Component slug.
		 * @param string               $label    Human label.
		 * @param array<string, mixed> $settings Resolved settings.
		 * @param string               $text     Plain text.
		 * @param string               $content  HTML content.
		 * @param array<int, string>   $items    Parsed list items.
		 */
		public function __construct(
			public string $name,
			public string $label,
			public array $settings,
			public string $text,
			public string $content,
			public array $items
		) {
		}
	}
}
