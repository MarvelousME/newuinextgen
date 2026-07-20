<?php
/**
 * Companion bridge — locate and boot the shared ui-library.
 *
 * @package NextGenCompanion
 */

declare(strict_types=1);

if ( ! class_exists( 'NGC_UI_Library_Bridge' ) ) {
	/**
	 * Resolves ui-library path and boots it once.
	 */
	class NGC_UI_Library_Bridge {

		/**
		 * Hook into Companion bootstrap.
		 */
		public static function init(): void {
			$path = self::resolve_path();
			if ( ! $path ) {
				add_action(
					'admin_notices',
					static function () {
						if ( ! current_user_can( 'manage_options' ) ) {
							return;
						}
						echo '<div class="notice notice-warning"><p>';
						echo esc_html__( 'NextGen UI Library not found. Mount/copy ui-library to wp-content/ngt-ui-library.', 'nextgencompanion' );
						echo '</p></div>';
					}
				);
				return;
			}

			if ( ! defined( 'NGT_UI_LIBRARY_DIR' ) ) {
				define( 'NGT_UI_LIBRARY_DIR', trailingslashit( $path ) );
			}
			if ( ! defined( 'NGT_UI_LIBRARY_URL' ) ) {
				define( 'NGT_UI_LIBRARY_URL', self::path_to_url( $path ) );
			}
			if ( ! defined( 'NGT_UI_LIBRARY_VERSION' ) ) {
				define( 'NGT_UI_LIBRARY_VERSION', defined( 'NGC_VERSION' ) ? NGC_VERSION : '1.0.0' );
			}

			$boot = NGT_UI_LIBRARY_DIR . 'bootstrap/class-ngt-ui-bootstrap.php';
			if ( is_readable( $boot ) ) {
				require_once $boot;
				if ( class_exists( 'NGT_UI_Bootstrap' ) ) {
					NGT_UI_Bootstrap::init();
				}
			}
		}

		/**
		 * Candidate filesystem locations.
		 */
		private static function resolve_path(): string {
			$candidates = array(
				WP_CONTENT_DIR . '/ngt-ui-library',
				dirname( NGC_PLUGIN_DIR ) . '/ngt-ui-library',
			);

			// Monorepo sibling when plugins live beside ui-library on the host.
			if ( defined( 'NGC_PLUGIN_DIR' ) ) {
				$candidates[] = dirname( NGC_PLUGIN_DIR, 2 ) . '/ui-library';
				$candidates[] = dirname( NGC_PLUGIN_DIR ) . '/../ui-library';
			}

			foreach ( $candidates as $dir ) {
				$dir = wp_normalize_path( $dir );
				if ( is_readable( $dir . '/bootstrap/class-ngt-ui-bootstrap.php' ) ) {
					return untrailingslashit( $dir );
				}
			}
			return '';
		}

		/**
		 * Convert path under wp-content to URL.
		 *
		 * @param string $path Absolute path.
		 */
		private static function path_to_url( string $path ): string {
			$path    = wp_normalize_path( untrailingslashit( $path ) );
			$content = wp_normalize_path( WP_CONTENT_DIR );
			if ( 0 === strpos( $path, $content ) ) {
				$rel = ltrim( substr( $path, strlen( $content ) ), '/' );
				return trailingslashit( content_url( $rel ) );
			}
			return trailingslashit( content_url( 'ngt-ui-library' ) );
		}
	}
}
