<?php
/**
 * Plugin Name: NextGen Tutors Core
 * Plugin URI: https://nextgentutors.co.za
 * Description: Complete AI-orchestrated tutoring platform with POPIA compliance, async processing, real-time verification, import/export, and automated workflows.
 * Version: 2.0.0
 * Author: Marvin Saunders (Get Online NOW @ 2026)
 * Author URI: https://getonlinenow.co.za
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: nextgen-tutors-core
 * Domain Path: /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NGT_CORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NGT_CORE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NGT_CORE_PLUGIN_VERSION', '2.0.0');
define('NGT_CORE_PLUGIN_SLUG', 'nextgen-tutors-core');
define('NGT_TEXTDOMAIN', 'nextgen-tutors-core');
define('NGT_DB_VERSION', '1.0');

// Load plugin updater
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-plugin.php';

/**
 * Initialize plugin
 */
function ngt_initialize_plugin() {
    // Load text domain
    load_plugin_textdomain(NGT_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Load composer autoloader if exists
    if (file_exists(NGT_CORE_PLUGIN_DIR . 'vendor/autoload.php')) {
        require_once NGT_CORE_PLUGIN_DIR . 'vendor/autoload.php';
    }

    // Initialize main plugin class
    NextGen_Tutors_Core::get_instance();
}
add_action('plugins_loaded', 'ngt_initialize_plugin');

/**
 * Activation hook
 */
function ngt_activate_plugin() {
    // Manually load dependencies for activation context
    require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-plugin.php';
    $ngt = NextGen_Tutors_Core::get_instance();

    // Run migrations
    $ngt->database->create_tables();

    // Setup default options
    if (!get_option('ngt_plugin_settings')) {
        update_option('ngt_plugin_settings', [
            'version' => NGT_CORE_PLUGIN_VERSION,
            'db_version' => NGT_DB_VERSION,
            'enabled' => true,
            'debug_mode' => WP_DEBUG,
        ]);
    }

    // Schedule cron jobs
    if (!wp_next_scheduled('ngt_cleanup_logs')) {
        wp_schedule_event(time(), 'daily', 'ngt_cleanup_logs');
    }
    if (!wp_next_scheduled('ngt_process_queue')) {
        wp_schedule_event(time(), 'five_minutes', 'ngt_process_queue');
    }
}
register_activation_hook(__FILE__, 'ngt_activate_plugin');

/**
 * Deactivation hook
 */
function ngt_deactivate_plugin() {
    wp_clear_scheduled_hook('ngt_cleanup_logs');
    wp_clear_scheduled_hook('ngt_process_queue');
}
register_deactivation_hook(__FILE__, 'ngt_deactivate_plugin');

/**
 * Plugin update check
 */
function ngt_check_plugin_version() {
    $stored_version = get_option('NGT_CORE_PLUGIN_VERSION');

    if ($stored_version !== NGT_CORE_PLUGIN_VERSION) {
        // Run migrations for new version
        ngt()->database->create_tables();
        update_option('NGT_CORE_PLUGIN_VERSION', NGT_CORE_PLUGIN_VERSION);
    }
}
add_action('admin_init', 'ngt_check_plugin_version');
