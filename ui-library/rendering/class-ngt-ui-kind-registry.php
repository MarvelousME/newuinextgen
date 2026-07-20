<?php
/**
 * Maps catalog kinds to renderer strategies.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Kind_Registry' ) ) {
	/**
	 * Singleton registry of kind renderers.
	 */
	class NGT_UI_Kind_Registry {

		/**
		 * @var array<string, NGT_UI_Kind_Renderer_Interface>|null
		 */
		private static $renderers = null;

		/**
		 * Load kind renderer classes once.
		 */
		public static function boot(): void {
			if ( null !== self::$renderers ) {
				return;
			}

			$dir = defined( 'NGT_UI_LIBRARY_DIR' ) ? NGT_UI_LIBRARY_DIR : dirname( __DIR__ );
			$dir = trailingslashit( $dir );

			require_once $dir . 'contracts/interface-ngt-ui-kind-renderer.php';
			require_once $dir . 'rendering/class-ngt-ui-catalog-render-context.php';
			require_once $dir . 'rendering/class-ngt-ui-kind-parser.php';

			$kinds_dir = $dir . 'rendering/kinds/';
			foreach ( glob( $kinds_dir . 'class-ngt-ui-kind-*.php' ) ?: array() as $file ) {
				require_once $file;
			}

			self::$renderers = array();
			$classes           = array(
				'NGT_UI_Kind_Button',
				'NGT_UI_Kind_Text',
				'NGT_UI_Kind_Pattern',
				'NGT_UI_Kind_Card',
				'NGT_UI_Kind_Device',
				'NGT_UI_Kind_Progress',
				'NGT_UI_Kind_List',
				'NGT_UI_Kind_Media',
				'NGT_UI_Kind_Map',
				'NGT_UI_Kind_Interactive',
				'NGT_UI_Kind_Misc',
			);

			foreach ( $classes as $class ) {
				if ( ! class_exists( $class ) ) {
					continue;
				}
				$instance = new $class();
				if ( $instance instanceof NGT_UI_Kind_Renderer_Interface ) {
					self::$renderers[ $instance->kind() ] = $instance;
				}
			}
		}

		/**
		 * @param string $kind Catalog kind slug.
		 */
		public static function get( string $kind ): ?NGT_UI_Kind_Renderer_Interface {
			self::boot();
			$kind = sanitize_key( $kind );
			return self::$renderers[ $kind ] ?? self::$renderers['misc'] ?? null;
		}
	}
}
