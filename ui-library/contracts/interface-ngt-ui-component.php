<?php
/**
 * Canonical UI component contract (foundation).
 *
 * Stage 5 will register real components against this interface.
 * No placeholder controls: implementations must render real output.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! interface_exists( 'NGT_UI_Component_Interface' ) ) {
	/**
	 * Shared contract for every ui-library component.
	 */
	interface NGT_UI_Component_Interface {

		/**
		 * Unique component slug (e.g. magic-card).
		 */
		public function get_name(): string;

		/**
		 * Human label.
		 */
		public function get_label(): string;

		/**
		 * Category slug.
		 */
		public function get_category(): string;

		/**
		 * Settings schema for editors / shortcodes.
		 *
		 * @return array<string, mixed>
		 */
		public function get_settings_schema(): array;

		/**
		 * Default settings.
		 *
		 * @return array<string, mixed>
		 */
		public function get_default_settings(): array;

		/**
		 * Sanitize settings.
		 *
		 * @param array<string, mixed> $settings Raw settings.
		 * @return array<string, mixed>
		 */
		public function sanitize_settings( array $settings ): array;

		/**
		 * Render HTML.
		 *
		 * @param array<string, mixed> $settings Sanitized settings.
		 * @param array<string, mixed> $context  Request/editor context.
		 */
		public function render( array $settings, array $context = array() ): string;

		/**
		 * Style handles / relative assets.
		 *
		 * @return array<int, string>
		 */
		public function get_style_dependencies(): array;

		/**
		 * Script handles / relative assets.
		 *
		 * @return array<int, string>
		 */
		public function get_script_dependencies(): array;

		/**
		 * Whether an editor mode is supported.
		 *
		 * @param string $editor gutenberg|elementor|wpbakery|shortcode|php.
		 */
		public function supports_editor( string $editor ): bool;
	}
}
