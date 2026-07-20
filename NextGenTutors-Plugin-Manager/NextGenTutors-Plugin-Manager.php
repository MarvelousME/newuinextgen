<?php
/**
 * Plugin Name:       NextGenTutors Plugin Manager
 * Plugin URI:        https://www.nextgentutors.co.za/
 * Description:       Detects required core plugins, reports dependency health, and provides secure one-click install/activate workflows.
 * Version:           1.3.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            NextGen Tutors
 * Text Domain:       nextgentutors-plugin-manager
 * License:           GPL-2.0-or-later
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NGCPM_VERSION', '1.3.3' );
define( 'NGCPM_PLUGIN_FILE', __FILE__ );
define( 'NGCPM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NGCPM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NGCPM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'NGCPM_ADMIN_PAGE', 'ui-ux-pro-max' );
define( 'NGCPM_LOG_LIMIT', 500 );

require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-ui.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-assets.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-logger.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-registry.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-scanner.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-installer.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-activator.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-errors.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-lifecycle.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-discovery.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-health.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-rate-limiter.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-queue.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-diagnostics.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-cookies.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-notifications.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-buttons.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-repair.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-view-model.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-settings.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-local-packages.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-ajax.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-shortcode.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-admin.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-plugin.php';
require_once NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-cli.php';

register_activation_hook( NGCPM_PLUGIN_FILE, [ 'NGCPM_Plugin', 'activate' ] );
register_deactivation_hook( NGCPM_PLUGIN_FILE, [ 'NGCPM_Plugin', 'deactivate' ] );

NGCPM_Plugin::instance();
