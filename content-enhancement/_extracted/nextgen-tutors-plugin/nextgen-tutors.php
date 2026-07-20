<?php
/**
 * Plugin Name: NextGen Tutors
 * Description: Core functionality for NextGen Tutors platform – bookings, earnings, payouts, LMS integration, real‑time analytics, and Mission Control.
 * Version: 1.0.0
 * Author: MarvelousME
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access.
}

// Define plugin constants.
define('NGT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NGT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include core components.
require_once NGT_PLUGIN_DIR . 'includes/activation.php';
require_once NGT_PLUGIN_DIR . 'includes/analytics.php';
require_once NGT_PLUGIN_DIR . 'includes/rest-api.php';
require_once NGT_PLUGIN_DIR . 'includes/sse.php';
require_once NGT_PLUGIN_DIR . 'includes/payout.php';
require_once NGT_PLUGIN_DIR . 'includes/triggers.php';
require_once NGT_PLUGIN_DIR . 'includes/workflows.php';
require_once NGT_PLUGIN_DIR . 'admin/mission-control.php';

// Register custom capability on plugin activation.
register_activation_hook(__FILE__, ['NGT_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['NGT_Activator', 'deactivate']);

// Ensure custom capability exists for delegated admins.
function ngt_add_custom_capability() {
    $role = get_role('administrator');
    if ($role && !$role->has_cap('ngt_manage')) {
        $role->add_cap('ngt_manage');
    }
}
add_action('init', 'ngt_add_custom_capability');
?>
