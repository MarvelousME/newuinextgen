<?php
/**
 * Gutenberg dynamic block — ngt-ui/component.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Gutenberg' ) ) {
	/**
	 * Registers one SSR block that selects any registry component.
	 */
	class NGT_UI_Gutenberg {

		/**
		 * Hook registration.
		 */
		public static function init(): void {
			add_action( 'init', array( __CLASS__, 'register_block' ), 20 );
		}

		/**
		 * Register block type from metadata + render callback.
		 */
		public static function register_block(): void {
			if ( ! function_exists( 'register_block_type' ) || ! class_exists( 'NGT_UI_Registry' ) ) {
				return;
			}
			if ( ! defined( 'NGT_UI_LIBRARY_URL' ) || ! defined( 'NGT_UI_LIBRARY_DIR' ) ) {
				return;
			}

			$dir = trailingslashit( NGT_UI_LIBRARY_DIR ) . 'integrations/gutenberg/';

			$asset = array(
				'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ),
				'version'      => defined( 'NGT_UI_LIBRARY_VERSION' ) ? NGT_UI_LIBRARY_VERSION : '1.0.0',
			);

			wp_register_script(
				'ngt-ui-block-editor',
				trailingslashit( NGT_UI_LIBRARY_URL ) . 'integrations/gutenberg/block.js',
				$asset['dependencies'],
				$asset['version'],
				true
			);

			$options = array();
			foreach ( NGT_UI_Registry::all() as $slug => $component ) {
				$options[] = array(
					'value' => $slug,
					'label' => $component->get_label() . ' (' . $slug . ')',
				);
			}
			wp_localize_script(
				'ngt-ui-block-editor',
				'ngtUiBlockData',
				array(
					'components' => $options,
				)
			);

			register_block_type(
				$dir . 'block.json',
				array(
					'editor_script'   => 'ngt-ui-block-editor',
					'render_callback' => array( __CLASS__, 'render' ),
				)
			);
		}

		/**
		 * Server-side render — canonical renderer only.
		 *
		 * @param array<string, mixed> $attributes Block attributes.
		 * @param string               $content    Inner content.
		 */
		public static function render( array $attributes, string $content = '' ): string {
			$name = sanitize_key( (string) ( $attributes['component'] ?? '' ) );
			if ( '' === $name || ! class_exists( 'NGT_UI_Renderer' ) ) {
				return '';
			}

			$settings = array();
			if ( ! empty( $attributes['settingsJson'] ) ) {
				$decoded = json_decode( (string) $attributes['settingsJson'], true );
				if ( is_array( $decoded ) ) {
					$settings = $decoded;
				}
			}

			foreach ( array( 'text', 'label', 'items', 'title', 'content', 'href', 'from', 'to', 'value' ) as $key ) {
				if ( isset( $attributes[ $key ] ) && '' !== $attributes[ $key ] && null !== $attributes[ $key ] ) {
					$settings[ $key ] = $attributes[ $key ];
				}
			}

			return NGT_UI_Renderer::render(
				$name,
				$settings,
				array( 'content' => $content, 'editor' => 'gutenberg' )
			);
		}
	}
}
