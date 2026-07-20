<?php
/**
 * Automation Studio admin shell.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mounts the visual orchestration studio SPA.
 */
class NGC_Studio_Admin {

	const PAGE_SLUG = 'ngc-automation-studio';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ], 56 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/**
	 * Register top-level studio menu.
	 */
	public static function register_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		add_menu_page(
			__( 'Automation Studio', 'nextgencompanion' ),
			__( 'Automation Studio', 'nextgencompanion' ),
			'manage_options',
			self::PAGE_SLUG,
			[ __CLASS__, 'render_app' ],
			'dashicons-networking',
			56
		);
	}

	/**
	 * Enqueue studio bundle on studio page only.
	 *
	 * @param string $hook Hook suffix.
	 */
	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, self::PAGE_SLUG ) ) {
			return;
		}

		$css = NGC_PLUGIN_DIR . 'assets/studio/studio.bundle.css';
		$js  = NGC_PLUGIN_DIR . 'assets/studio/studio.bundle.js';
		$ver = file_exists( $js ) ? (string) filemtime( $js ) : NGC_VERSION;

		if ( file_exists( $css ) ) {
			wp_enqueue_style( 'ngc-studio', NGC_PLUGIN_URL . 'assets/studio/studio.bundle.css', [], $ver );
		}
		wp_enqueue_style( 'ngc-studio-shell', NGC_PLUGIN_URL . 'assets/studio/studio-shell.css', [], NGC_VERSION );

		if ( file_exists( $js ) ) {
			wp_enqueue_script( 'ngc-studio', NGC_PLUGIN_URL . 'assets/studio/studio.bundle.js', [], $ver, true );
		} else {
			wp_enqueue_script( 'ngc-studio-fallback', NGC_PLUGIN_URL . 'assets/studio/studio-fallback.js', [], NGC_VERSION, true );
		}

		wp_localize_script(
			file_exists( $js ) ? 'ngc-studio' : 'ngc-studio-fallback',
			'NGC_STUDIO',
			[
				'restRoot'   => esc_url_raw( rest_url( NGC_Rest::NAMESPACE . '/studio/' ) ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'pluginUrl'  => NGC_PLUGIN_URL,
				'version'    => NGC_VERSION,
				'triggers'   => NGC_Studio_Triggers::catalog(),
				'nodeTypes'  => NGC_Studio_Triggers::node_types(),
				'runtime'    => NGC_Studio_Runtime::status(),
				'livePollMs' => 1500,
				'liveUseSse' => true,
				'i18n'       => [
					'save'      => __( 'Save & Apply', 'nextgencompanion' ),
					'publish'   => __( 'Publish', 'nextgencompanion' ),
					'simulate'  => __( 'Simulate', 'nextgencompanion' ),
					'execute'   => __( 'Execute', 'nextgencompanion' ),
					'saved'     => __( 'Workflow saved and applied.', 'nextgencompanion' ),
					'published' => __( 'Workflow published — triggers active.', 'nextgencompanion' ),
				],
			]
		);
	}

	/**
	 * Render SPA mount point.
	 */
	public static function render_app() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'nextgencompanion' ) );
		}
		echo '<div class="wrap ngc-studio-wrap">';
		echo '<div id="ngc-studio-root" class="ngc-studio-root" data-studio-version="' . esc_attr( NGC_VERSION ) . '"></div>';
		echo '</div>';
	}
}
