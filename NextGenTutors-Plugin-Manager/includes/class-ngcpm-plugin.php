<?php
/**
 * Plugin bootstrap.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin singleton.
 */
class NGCPM_Plugin {

	/** @var NGCPM_Plugin|null */
	private static $instance = null;

	/**
	 * @return NGCPM_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	/**
	 * Initialize modules.
	 */
	public function init() {
		load_plugin_textdomain( 'nextgentutors-plugin-manager', false, dirname( NGCPM_PLUGIN_BASENAME ) . '/languages' );
		NGCPM_Settings::init();
		NGCPM_Admin::init();
		NGCPM_Ajax::init();
		NGCPM_Shortcode::init();
		NGCPM_CLI::init();
	}

	/**
	 * Activation hook.
	 */
	public static function activate() {
		NGCPM_Settings::install_defaults();
		NGCPM_Local_Packages::ensure_directories();
		self::maybe_create_frontend_page();
		NGCPM_Logger::log( 'system', 'Plugin activated', [] );
		flush_rewrite_rules();
	}

	/**
	 * Create /ui-page with shortcode when frontend is enabled.
	 */
	private static function maybe_create_frontend_page() {
		if ( ! NGCPM_Settings::frontend_enabled() ) {
			return;
		}
		$existing = get_page_by_path( 'ui-page' );
		if ( $existing instanceof WP_Post ) {
			return;
		}
		wp_insert_post(
			[
				'post_title'   => __( 'System Status', 'nextgentutors-plugin-manager' ),
				'post_name'    => 'ui-page',
				'post_content' => '[ngc_plugin_manager]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			]
		);
	}

	/**
	 * Deactivation hook.
	 */
	public static function deactivate() {
		NGCPM_Logger::log( 'system', 'Plugin deactivated', [] );
		flush_rewrite_rules();
	}
}
