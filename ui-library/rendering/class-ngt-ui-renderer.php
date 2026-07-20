<?php
/**
 * Canonical UI renderer.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Renderer' ) ) {
	/**
	 * Single render entry for shortcodes, PHP, and future editors.
	 */
	class NGT_UI_Renderer {

		/**
		 * @param string               $name     Component slug.
		 * @param array<string, mixed> $settings Settings.
		 * @param array<string, mixed> $context  Context.
		 */
		public static function render( string $name, array $settings = array(), array $context = array() ): string {
			$component = NGT_UI_Registry::get( $name );
			if ( ! $component ) {
				if ( current_user_can( 'edit_theme_options' ) ) {
					return '<!-- ngt-ui: unknown component ' . esc_html( $name ) . ' -->';
				}
				return '';
			}

			$clean = $component->sanitize_settings( $settings );
			NGT_UI_Assets::enqueue_for( $name );
			$html = $component->render( $clean, $context );
			return is_string( $html ) ? $html : '';
		}
	}
}

if ( ! function_exists( 'ngt_render_ui_component' ) ) {
	/**
	 * Public PHP API.
	 *
	 * @param string               $name     Component slug.
	 * @param array<string, mixed> $settings Settings.
	 * @param array<string, mixed> $context  Context.
	 */
	function ngt_render_ui_component( string $name, array $settings = array(), array $context = array() ): string {
		if ( ! class_exists( 'NGT_UI_Renderer' ) ) {
			return '';
		}
		return NGT_UI_Renderer::render( $name, $settings, $context );
	}
}
