<?php
/**
 * Visual Builder admin shell.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mounts the Visual Builder SPA (bundle or fallback).
 */
class NGC_Builder_Admin {

	const PAGE_SLUG = 'ngc-visual-builder';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ], 57 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/**
	 * Fallback menu when unified shell is absent.
	 */
	public static function register_menu() {
		if ( ! NGC_Visual_Builder::can_edit() ) {
			return;
		}
		if ( class_exists( 'NGC_Admin_Shell' ) ) {
			return;
		}
		add_menu_page(
			__( 'Visual Builder', 'nextgencompanion' ),
			__( 'Visual Builder', 'nextgencompanion' ),
			'manage_options',
			self::PAGE_SLUG,
			[ __CLASS__, 'render_app' ],
			'dashicons-art',
			57
		);
	}

	/**
	 * @param string $hook Hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( false === strpos( (string) $hook, self::PAGE_SLUG ) ) {
			return;
		}

		$css = NGC_PLUGIN_DIR . 'assets/builder/builder-shell.css';
		$js  = NGC_PLUGIN_DIR . 'assets/builder/builder.bundle.js';
		$fb  = NGC_PLUGIN_DIR . 'assets/builder/builder-fallback.js';
		$ver = NGC_VERSION;

		if ( file_exists( $css ) ) {
			wp_enqueue_style( 'ngc-builder-shell', NGC_PLUGIN_URL . 'assets/builder/builder-shell.css', [], (string) filemtime( $css ) );
		}

		$handle = 'ngc-builder-fallback';
		if ( file_exists( $js ) ) {
			$handle = 'ngc-builder';
			wp_enqueue_script( $handle, NGC_PLUGIN_URL . 'assets/builder/builder.bundle.js', [], (string) filemtime( $js ), true );
		} elseif ( file_exists( $fb ) ) {
			wp_enqueue_script( $handle, NGC_PLUGIN_URL . 'assets/builder/builder-fallback.js', [], (string) filemtime( $fb ), true );
		}

		wp_localize_script(
			$handle,
			'NGC_BUILDER',
			[
				'restRoot' => esc_url_raw( rest_url( NGC_Rest::NAMESPACE . '/builder/' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'version'  => $ver,
				'host'     => NGC_Visual_Builder::host_status(),
				'i18n'     => [
					'save'    => __( 'Save draft', 'nextgencompanion' ),
					'publish' => __( 'Publish', 'nextgencompanion' ),
					'migrate' => __( 'Migrate sections', 'nextgencompanion' ),
				],
			]
		);
	}

	/**
	 * Render mount point.
	 */
	public static function render_app() {
		if ( ! NGC_Visual_Builder::can_edit() ) {
			wp_die( esc_html__( 'You do not have permission to edit with Visual Builder.', 'nextgencompanion' ) );
		}
		echo '<div class="wrap ngc-builder-wrap">';
		echo '<div id="ngc-builder-root" data-testid="ngc-builder-root"></div>';
		echo '</div>';
	}
}
