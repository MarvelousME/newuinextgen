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
		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_action( 'admin_init', [ __CLASS__, 'install_bundled_packages_after_activation' ], 20 );
		NGCPM_Settings::init();
		NGCPM_Admin::init();
		NGCPM_Ajax::init();
		NGCPM_Shortcode::init();
		NGCPM_CLI::init();
	}

	/**
	 * Load translations at init or later.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'nextgentutors-plugin-manager', false, dirname( NGCPM_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Activation hook.
	 */
	public static function activate() {
		NGCPM_Settings::install_defaults();
		NGCPM_Local_Packages::ensure_directories();
		update_option( 'ngcpm_bundled_install_pending', '1', false );
		self::maybe_create_frontend_page();
		NGCPM_Logger::log( 'system', 'Plugin activated', [] );
		flush_rewrite_rules();
	}

	/**
	 * Install and activate bundled zips on the first authenticated admin request.
	 *
	 * Running after activation avoids holding WordPress's plugin-activation request
	 * open while large packages are extracted.
	 */
	public static function install_bundled_packages_after_activation() {
		if ( '1' !== (string) get_option( 'ngcpm_bundled_install_pending', '0' ) ) {
			return;
		}
		if ( ! current_user_can( 'install_plugins' ) || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ) {
			return;
		}

		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		try {
			NGCPM_Local_Packages::ensure_directories();
			$results = NGCPM_Local_Packages::install_all_bundled( true );
			NGCPM_Logger::log( 'bundled_auto_install', 'Bundled package installation completed', [ 'count' => count( $results ) ] );
			delete_option( 'ngcpm_bundled_install_pending' );
		} catch ( Throwable $e ) {
			// Keep WordPress usable and retain the pending marker for a later retry.
			NGCPM_Logger::log(
				'bundled_auto_install_exception',
				'Bundled installation stopped safely: ' . wp_strip_all_tags( $e->getMessage() ),
				[]
			);
		}
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
