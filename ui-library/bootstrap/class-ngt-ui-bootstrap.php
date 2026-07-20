<?php
/**
 * NGT UI Library bootstrap.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Bootstrap' ) ) {
	/**
	 * Loads contracts, components, assets, editors, and admin.
	 */
	class NGT_UI_Bootstrap {

		/**
		 * Boot once.
		 */
		public static function init(): void {
			if ( did_action( 'ngt_ui_library_booted' ) ) {
				return;
			}

			$dir = defined( 'NGT_UI_LIBRARY_DIR' ) ? NGT_UI_LIBRARY_DIR : dirname( __DIR__ );
			$dir = trailingslashit( $dir );

			require_once $dir . 'contracts/interface-ngt-ui-component.php';
			require_once $dir . 'contracts/class-ngt-ui-component-base.php';
			require_once $dir . 'registry/class-ngt-ui-registry.php';
			require_once $dir . 'registry/class-ngt-ui-assets.php';
			require_once $dir . 'rendering/class-ngt-ui-renderer.php';
			require_once $dir . 'rendering/class-ngt-ui-kind-registry.php';
			require_once $dir . 'components/class-ngt-ui-magic-card.php';
			require_once $dir . 'components/class-ngt-ui-border-beam.php';
			require_once $dir . 'components/class-ngt-ui-marquee.php';
			require_once $dir . 'components/class-ngt-ui-income-calculator.php';
			require_once $dir . 'tokens/class-ngt-ui-tokens.php';
			require_once $dir . 'components/class-ngt-ui-catalog-component.php';
			require_once $dir . 'components/class-ngt-ui-catalog-loader.php';
			require_once $dir . 'integrations/shortcodes/class-ngt-ui-shortcodes.php';
			require_once $dir . 'integrations/gutenberg/class-ngt-ui-gutenberg.php';
			require_once $dir . 'integrations/elementor/class-ngt-ui-elementor.php';
			require_once $dir . 'integrations/wpbakery/class-ngt-ui-wpbakery.php';
			require_once $dir . 'admin/class-ngt-ui-admin.php';
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				require_once $dir . 'integrations/cli/class-ngt-ui-cli.php';
			}

			NGT_UI_Registry::register( new NGT_UI_Magic_Card() );
			NGT_UI_Registry::register( new NGT_UI_Border_Beam() );
			NGT_UI_Registry::register( new NGT_UI_Marquee() );
			NGT_UI_Registry::register( new NGT_UI_Income_Calculator() );
			NGT_UI_Catalog_Loader::register_all();

			add_action( 'wp_enqueue_scripts', array( 'NGT_UI_Assets', 'register' ), 5 );
			add_action( 'enqueue_block_editor_assets', array( 'NGT_UI_Assets', 'register' ), 5 );
			add_action( 'admin_enqueue_scripts', array( 'NGT_UI_Assets', 'register_admin' ), 5 );

			NGT_UI_Shortcodes::init();
			NGT_UI_Gutenberg::init();
			NGT_UI_Elementor::init();
			NGT_UI_WPBakery::init();
			NGT_UI_Admin::init();

			do_action( 'ngt_ui_library_booted' );
		}
	}
}
