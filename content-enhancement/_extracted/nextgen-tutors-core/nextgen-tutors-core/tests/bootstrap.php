<?php
define('NGT_CORE_PLUGIN_DIR', dirname(__DIR__) . '/');
define('NGT_CORE_PLUGIN_VERSION', '2.0.0');
define('DAY_IN_SECONDS', 86400);
define('HOUR_IN_SECONDS', 3600);

if (!function_exists('add_action')) {
    function add_action() {}
    function add_filter() {}
    function do_action() {}
    function apply_filters($tag, $value) { return $value; }
    function plugin_dir_path() { return NGT_CORE_PLUGIN_DIR; }
    function get_option($key, $default = false) { return $default; }
    function update_option() { return true; }
    function set_transient() { return true; }
    function get_transient() { return false; }
    function current_time() { return date('Y-m-d H:i:s'); }
    function wp_create_nonce() { return 'nonce'; }
    function wp_verify_nonce() { return true; }
    function wp_mkdir_p() { return true; }
    function wp_next_scheduled() { return false; }
    function wp_schedule_event() { return true; }
    function get_current_user_id() { return 1; }
    function wp_remote_get() { return []; }
    function is_wp_error() { return false; }
    function wp_remote_retrieve_response_code() { return 200; }
    function is_ssl() { return true; }
    function wp_upload_dir() { return ['basedir' => sys_get_temp_dir()]; }
    function size_format($b) { return $b . ' B'; }
    if (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }
    function get_bloginfo() { return 'Test'; }
    
    class MockWPDB {
        public $prefix = 'wp_';
        public $insert_id = 42;
        public function insert() { return 1; }
        public function get_var() { return null; }
        public function get_results() { return []; }
        public function prepare($query, ...$args) { return $query; }
        public function query() { return true; }
    }
    global $wpdb;
    $wpdb = new MockWPDB();
}

require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-plugin.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-logger.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-security.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-database.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-scheduler.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-queue.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-verifier.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-detector.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-seeder.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-importer.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-exporter.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-uploader.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-downloader.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-notifier.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-admin.php';
require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-api.php';
