<?php
/**
 * Plugin Name: NextGen Universal API
 * Plugin URI: https://nextgentutors.co.za
 * Description: Scans every active plugin, maps its native REST API where one exists and is actually live, and auto-generates secure, schema-validated CRUD REST endpoints for any plugin database tables that have no exposed API. Includes an API key manager and a built-in test console.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: NextGen Tutors
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nuapi
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'NUAPI_VERSION', '1.0.0' );
define( 'NUAPI_FILE', __FILE__ );
define( 'NUAPI_DIR', plugin_dir_path( __FILE__ ) );
define( 'NUAPI_URL', plugin_dir_url( __FILE__ ) );
define( 'NUAPI_MIN_PHP', '7.4' );

if ( version_compare( PHP_VERSION, NUAPI_MIN_PHP, '<' ) ) {
	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-error"><p><strong>NextGen Universal API</strong> requires PHP 7.4 or higher. Please ask your host to upgrade PHP, then reactivate this plugin.</p></div>';
	} );
	return;
}

require_once NUAPI_DIR . 'includes/class-nuapi-logger.php';
require_once NUAPI_DIR . 'includes/class-nuapi-scanner.php';
require_once NUAPI_DIR . 'includes/class-nuapi-security.php';
require_once NUAPI_DIR . 'includes/class-nuapi-crud.php';
require_once NUAPI_DIR . 'includes/class-nuapi-admin.php';

final class NUAPI_Plugin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		register_activation_hook( NUAPI_FILE, array( $this, 'on_activate' ) );
		register_deactivation_hook( NUAPI_FILE, array( $this, 'on_deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'activated_plugin', array( 'NUAPI_Scanner', 'invalidate_registry' ) );
		add_action( 'deactivated_plugin', array( 'NUAPI_Scanner', 'invalidate_registry' ) );
	}

	public function on_activate() {
		if ( false === get_option( 'nuapi_settings' ) ) {
			add_option( 'nuapi_settings', array(
				'enabled_tables' => array(),
				'write_tables'   => array(),
				'rate_limit'     => 120,
			) );
		}
		if ( false === get_option( 'nuapi_api_keys' ) ) {
			add_option( 'nuapi_api_keys', array() );
		}
		NUAPI_Logger::maybe_create_table();
		NUAPI_Scanner::invalidate_registry();
	}

	public function on_deactivate() {
		delete_transient( 'nuapi_registry_cache' );
	}

	public function init() {
		load_plugin_textdomain( 'nuapi', false, dirname( plugin_basename( NUAPI_FILE ) ) . '/languages' );

		NUAPI_Security::init();
		NUAPI_CRUD::init();

		if ( is_admin() ) {
			NUAPI_Admin::init();
		}
	}
}

NUAPI_Plugin::instance();
