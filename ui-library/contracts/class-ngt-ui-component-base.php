<?php
/**
 * Abstract base for NGT UI components.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Component_Base' ) ) {
	/**
	 * Shared helpers for components.
	 */
	abstract class NGT_UI_Component_Base implements NGT_UI_Component_Interface {

		/**
		 * @return array<string, mixed>
		 */
		public function get_default_settings(): array {
			$schema   = $this->get_settings_schema();
			$defaults = array();
			foreach ( $schema as $key => $def ) {
				if ( is_array( $def ) && array_key_exists( 'default', $def ) ) {
					$defaults[ $key ] = $def['default'];
				}
			}
			return $defaults;
		}

		/**
		 * @param array<string, mixed> $settings Raw.
		 * @return array<string, mixed>
		 */
		public function sanitize_settings( array $settings ): array {
			$out    = $this->get_default_settings();
			$schema = $this->get_settings_schema();
			foreach ( $schema as $key => $def ) {
				if ( ! array_key_exists( $key, $settings ) ) {
					continue;
				}
				$type  = is_array( $def ) && isset( $def['type'] ) ? (string) $def['type'] : 'string';
				$value = $settings[ $key ];
				switch ( $type ) {
					case 'boolean':
						$out[ $key ] = (bool) $value;
						break;
					case 'integer':
						$out[ $key ] = (int) $value;
						break;
					case 'number':
						$out[ $key ] = (float) $value;
						break;
					case 'color':
						$out[ $key ] = sanitize_hex_color( (string) $value ) ?: $out[ $key ];
						break;
					case 'html':
						$out[ $key ] = wp_kses_post( (string) $value );
						break;
					case 'enum':
						$allowed     = ( is_array( $def ) && isset( $def['enum'] ) && is_array( $def['enum'] ) ) ? $def['enum'] : array();
						$out[ $key ] = in_array( $value, $allowed, true ) ? $value : $out[ $key ];
						break;
					default:
						$out[ $key ] = sanitize_text_field( (string) $value );
				}
			}
			return $out;
		}

		/**
		 * @param string $editor Editor id.
		 */
		public function supports_editor( string $editor ): bool {
			return in_array( $editor, array( 'shortcode', 'php', 'gutenberg', 'elementor', 'wpbakery' ), true );
		}

		/**
		 * Unique instance id.
		 */
		protected function instance_id( string $prefix = 'ngt-ui' ): string {
			return $prefix . '-' . wp_unique_id();
		}
	}
}
