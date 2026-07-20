<?php
/**
 * Asset registration for UI library.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Assets' ) ) {
	/**
	 * Registers base + per-component assets; enqueues on demand.
	 */
	class NGT_UI_Assets {

		/**
		 * @var array<string, bool>
		 */
		private static $queued_components = array();

		/**
		 * @var bool
		 */
		private static $registered = false;

		/**
		 * Register handles once (frontend + block editor).
		 */
		public static function register(): void {
			self::register_handles();
		}

		/**
		 * Register only on relevant admin screens.
		 *
		 * @param string $hook Admin page hook suffix.
		 */
		public static function register_admin( string $hook ): void {
			if ( ! self::admin_needs_assets( $hook ) ) {
				return;
			}
			self::register_handles();
		}

		/**
		 * @param string $hook Admin hook.
		 */
		private static function admin_needs_assets( string $hook ): bool {
			if ( false !== strpos( $hook, 'ngt-ui' ) || false !== strpos( $hook, 'ngc-operations' ) ) {
				return true;
			}
			if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
				return true;
			}
			if ( false !== strpos( $hook, 'elementor' ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Idempotent handle registration.
		 */
		private static function register_handles(): void {
			if ( self::$registered ) {
				return;
			}
			if ( ! defined( 'NGT_UI_LIBRARY_URL' ) || ! defined( 'NGT_UI_LIBRARY_VERSION' ) ) {
				return;
			}
			self::$registered = true;

			$ver = NGT_UI_LIBRARY_VERSION;
			$url = trailingslashit( NGT_UI_LIBRARY_URL );

			wp_register_style( 'ngt-ui-tokens', $url . 'tokens/tokens.css', array(), $ver );
			wp_register_style( 'ngt-ui-base', $url . 'assets/css/ngt-ui-base.css', array( 'ngt-ui-tokens' ), $ver );
			wp_register_script( 'ngt-ui-runtime', $url . 'assets/js/ngt-ui-runtime.js', array(), $ver, true );

			wp_register_style( 'ngt-ui-catalog', $url . 'assets/css/components/catalog.css', array( 'ngt-ui-base' ), $ver );

			wp_register_script( 'ngt-ui-vendor-loader', $url . 'assets/js/ngt-ui-vendor-loader.js', array(), $ver, true );
			wp_register_script( 'ngt-ui-catalog-core', $url . 'assets/js/components/catalog-core.js', array( 'ngt-ui-runtime', 'ngt-ui-vendor-loader' ), $ver, true );
			wp_register_script( 'ngt-ui-catalog-interactive', $url . 'assets/js/components/catalog-interactive.js', array( 'ngt-ui-runtime', 'ngt-ui-vendor-loader', 'ngt-ui-catalog-core' ), $ver, true );
			wp_register_script( 'ngt-ui-catalog', $url . 'assets/js/components/catalog.js', array( 'ngt-ui-catalog-core', 'ngt-ui-catalog-interactive' ), $ver, true );

			$kinds = array( 'button', 'text', 'pattern', 'card', 'device', 'progress', 'list', 'media', 'map', 'interactive', 'misc' );
			foreach ( $kinds as $kind ) {
				wp_register_style(
					'ngt-ui-kind-' . $kind,
					$url . 'assets/css/kinds/kind-' . $kind . '.css',
					array( 'ngt-ui-catalog' ),
					$ver
				);
			}

			$map = array(
				'magic-card'         => array( 'css' => 'assets/css/components/magic-card.css', 'js' => 'assets/js/components/magic-card.js' ),
				'border-beam'        => array( 'css' => 'assets/css/components/border-beam.css', 'js' => '' ),
				'marquee'            => array( 'css' => 'assets/css/components/marquee.css', 'js' => '' ),
				'income-calculator'  => array( 'css' => 'assets/css/components/income-calculator.css', 'js' => 'assets/js/components/income-calculator.js' ),
			);

			foreach ( $map as $slug => $files ) {
				$style = 'ngt-ui-' . $slug;
				wp_register_style( $style, $url . $files['css'], array( 'ngt-ui-base' ), $ver );
				if ( $files['js'] ) {
					wp_register_script( 'ngt-ui-' . $slug, $url . $files['js'], array( 'ngt-ui-runtime' ), $ver, true );
				}
			}
		}

		/**
		 * Enqueue assets for a component slug.
		 *
		 * @param string $slug Component name.
		 */
		public static function enqueue_for( string $slug ): void {
			self::register_handles();

			if ( isset( self::$queued_components[ $slug ] ) ) {
				return;
			}
			wp_enqueue_style( 'ngt-ui-base' );
			wp_enqueue_script( 'ngt-ui-runtime' );

			$component = class_exists( 'NGT_UI_Registry' ) ? NGT_UI_Registry::get( $slug ) : null;
			if ( $component ) {
				foreach ( $component->get_style_dependencies() as $handle ) {
					if ( wp_style_is( $handle, 'registered' ) ) {
						wp_enqueue_style( $handle );
					}
				}
				foreach ( $component->get_script_dependencies() as $handle ) {
					if ( wp_script_is( $handle, 'registered' ) ) {
						wp_enqueue_script( $handle );
					}
				}
			} else {
				$style = 'ngt-ui-' . $slug;
				if ( wp_style_is( $style, 'registered' ) ) {
					wp_enqueue_style( $style );
				}
				$script = 'ngt-ui-' . $slug;
				if ( wp_script_is( $script, 'registered' ) ) {
					wp_enqueue_script( $script );
				}
			}

			self::$queued_components[ $slug ] = true;
		}
	}
}
