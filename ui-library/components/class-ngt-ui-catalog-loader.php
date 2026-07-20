<?php
/**
 * Loads catalog components into the registry.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Catalog_Loader' ) ) {
	/**
	 * Registers all Magic UI catalog entries.
	 */
	class NGT_UI_Catalog_Loader {

		/**
		 * Register every catalog component not already registered.
		 */
		public static function register_all(): void {
			$dir  = defined( 'NGT_UI_LIBRARY_DIR' ) ? NGT_UI_LIBRARY_DIR : dirname( __DIR__ );
			$file = trailingslashit( $dir ) . 'catalog/components.php';
			if ( ! is_readable( $file ) ) {
				return;
			}
			$catalog = include $file;
			if ( ! is_array( $catalog ) ) {
				return;
			}
			foreach ( $catalog as $slug => $meta ) {
				$slug = sanitize_key( (string) $slug );
				if ( '' === $slug || NGT_UI_Registry::get( $slug ) ) {
					continue;
				}
				if ( ! is_array( $meta ) ) {
					$meta = array();
				}
				NGT_UI_Registry::register( new NGT_UI_Catalog_Component( $slug, $meta ) );
			}
		}

		/**
		 * @return array<string, array<string, mixed>>
		 */
		public static function get_catalog(): array {
			$dir  = defined( 'NGT_UI_LIBRARY_DIR' ) ? NGT_UI_LIBRARY_DIR : dirname( __DIR__ );
			$file = trailingslashit( $dir ) . 'catalog/components.php';
			if ( ! is_readable( $file ) ) {
				return array();
			}
			$catalog = include $file;
			return is_array( $catalog ) ? $catalog : array();
		}
	}
}
