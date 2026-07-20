<?php
/**
 * Frontend shortcode [ngc_plugin_manager].
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public read-only dependency status (write for admins only).
 */
class NGCPM_Shortcode {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_shortcode( 'ngc_plugin_manager', [ __CLASS__, 'render' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'maybe_assets' ] );
	}

	/**
	 * Enqueue assets on pages with shortcode.
	 */
	public static function maybe_assets() {
		global $post;
		if ( ! $post || ! has_shortcode( $post->post_content, 'ngc_plugin_manager' ) ) {
			return;
		}
		if ( ! NGCPM_Settings::frontend_enabled() ) {
			return;
		}
		self::enqueue();
	}

	/**
	 * Enqueue CSS/JS.
	 */
	private static function enqueue() {
		NGCPM_Assets::enqueue();
	}

	/**
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts ) {
		if ( ! NGCPM_Settings::frontend_enabled() ) {
			return '';
		}

		$readonly = ! current_user_can( 'install_plugins' );

		self::enqueue();

		ob_start();
		echo '<div class="ngcpm-frontend-wrap ngcpm-root">';
		NGCPM_View_Model::render( NGCPM_View_Model::for_app( $readonly, 0 ) );
		echo '</div>';
		return ob_get_clean();
	}
}
