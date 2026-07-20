<?php
/**
 * NextGen Tutors Admin Class
 *
 * Handles WordPress admin interface and pages.
 */

class NGT_Admin {

    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', [$this, 'create_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Create admin menus
     */
    public function create_menus() {
        add_menu_page(
            'NextGen Tutors',
            'NextGen Tutors',
            'manage_options',
            'ngt-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-welcome-learn-more',
            30
        );

        add_submenu_page('ngt-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'ngt-dashboard', [$this, 'render_dashboard']);
        add_submenu_page('ngt-dashboard', 'Verification', 'Verification', 'manage_options', 'ngt-verification', [$this, 'render_verification']);
        add_submenu_page('ngt-dashboard', 'Workflows', 'Workflows', 'manage_options', 'ngt-workflows', [$this, 'render_workflows']);
        add_submenu_page('ngt-dashboard', 'Payouts & Billing', 'Payouts & Billing', 'manage_options', 'ngt-billing', [$this, 'render_billing']);
        add_submenu_page('ngt-dashboard', 'Importer', 'Importer', 'manage_options', 'ngt-importer', [$this, 'render_importer']);
        add_submenu_page('ngt-dashboard', 'Exporter', 'Exporter', 'manage_options', 'ngt-exporter', [$this, 'render_exporter']);
        add_submenu_page('ngt-dashboard', 'Settings', 'Settings', 'manage_options', 'ngt-settings', [$this, 'render_settings']);
        add_submenu_page('ngt-dashboard', 'Alerts & Notifications', 'Alerts', 'manage_options', 'ngt-alerts', [$this, 'render_alerts']);
        add_submenu_page('ngt-dashboard', 'Logs', 'Logs', 'manage_options', 'ngt-logs', [$this, 'render_logs']);
        add_submenu_page('ngt-dashboard', 'Seeder', 'Seeder', 'manage_options', 'ngt-seeder', [$this, 'render_seeder']);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'ngt-') === false) return;
        
        wp_enqueue_style('ngt-admin-css', plugin_dir_url(__FILE__) . '../assets/css/admin.css');
        wp_enqueue_script('ngt-admin-js', plugin_dir_url(__FILE__) . '../assets/js/admin.js', ['jquery'], '1.0.0', true);
    }

    /**
     * Render dashboard
     */
    public function render_dashboard() {
        include plugin_dir_path(__FILE__) . '../templates/admin/dashboard.php';
    }

    /**
     * Render verification page
     */
    public function render_verification() {
        $health = ngt()->verifier->get_system_health();
        include plugin_dir_path(__FILE__) . '../templates/admin/verification.php';
    }

    /**
     * Render workflows page
     */
    public function render_workflows() {
        $triggers = ngt()->workflows->get_available_triggers();
        $actions = ngt()->workflows->get_available_actions();
        include plugin_dir_path(__FILE__) . '../templates/admin/workflows.php';
    }

    /**
     * Render billing page
     */
    public function render_billing() {
        include plugin_dir_path(__FILE__) . '../templates/admin/billing.php';
    }

    /**
     * Render importer page
     */
    public function render_importer() {
        include plugin_dir_path(__FILE__) . '../templates/admin/importer.php';
    }

    /**
     * Render exporter page
     */
    public function render_exporter() {
        $exports = ngt()->exporter->get_available_exports();
        include plugin_dir_path(__FILE__) . '../templates/admin/exporter.php';
    }

    /**
     * Render settings
     */
    public function render_settings() {
        include plugin_dir_path(__FILE__) . '../templates/admin/settings.php';
    }

    /**
     * Render alerts page
     */
    public function render_alerts() {
        include plugin_dir_path(__FILE__) . '../templates/admin/alerts.php';
    }

    /**
     * Render logs page
     */
    public function render_logs() {
        include plugin_dir_path(__FILE__) . '../templates/admin/logs.php';
    }

    /**
     * Render seeder page
     */
    public function render_seeder() {
        include plugin_dir_path(__FILE__) . '../templates/admin/seeder.php';
    }
}
