<?php
/**
 * Component registry.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Registry' ) ) {
	/**
	 * Stores component instances by slug.
	 */
	class NGT_UI_Registry {

		/**
		 * @var array<string, NGT_UI_Component_Interface>
		 */
		private static $components = array();

		/**
		 * @param NGT_UI_Component_Interface $component Component.
		 */
		public static function register( NGT_UI_Component_Interface $component ): void {
			$name = $component->get_name();
			if ( '' === $name ) {
				return;
			}
			self::$components[ $name ] = $component;
		}

		/**
		 * @param string $name Slug.
		 */
		public static function get( string $name ): ?NGT_UI_Component_Interface {
			return self::$components[ $name ] ?? null;
		}

		/**
		 * @return array<string, NGT_UI_Component_Interface>
		 */
		public static function all(): array {
			return self::$components;
		}

		/**
		 * @param string $name Slug.
		 */
		public static function has( string $name ): bool {
			return isset( self::$components[ $name ] );
		}
	}
}
