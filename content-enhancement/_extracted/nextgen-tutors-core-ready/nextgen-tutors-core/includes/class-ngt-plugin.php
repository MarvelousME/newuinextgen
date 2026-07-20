<?php
/**
 * NextGen Tutors Core Plugin Main Class
 *
 * Singleton pattern for main plugin orchestration
 */

class NextGen_Tutors_Core {

    private static $instance = null;
    public $version = '2.0.0';
    public $db_version = '1.0';
    public $admin;
    public $database;
    public $scheduler;
    public $importer;
    public $exporter;
    public $uploader;
    public $downloader;
    public $notifier;
    public $verifier;
    public $detector;
    public $seeder;
    public $logger;
    public $api;
    public $security;
    public $workflows;
    public $queue;

    /**
     * Singleton instance getter
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Initialize plugin
     */
    private function __construct() {
        self::$instance = $this;
        $this->load_dependencies();
        $this->initialize_modules();
        $this->setup_hooks();
    }

    /**
     * Load all required files
     */
    private function load_dependencies() {
        // Core utilities
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-logger.php';
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-security.php';
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-database.php';

        // Async & Scheduling
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-scheduler.php';
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-queue.php';

        // Core Features
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-verifier.php';
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-detector.php';
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-seeder.php';

        // File Operations
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-importer.php';
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-exporter.php';
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-uploader.php';
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-downloader.php';

        // Notifications
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-notifier.php';

        // Workflows
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-workflows.php';

        // Admin & API
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-admin.php';
        require_once NGT_CORE_PLUGIN_DIR . 'includes/class-ngt-api.php';
    }

    /**
     * Initialize all modules as singletons
     */
    private function initialize_modules() {
        $this->logger = NGT_Logger::get_instance();
        $this->security = NGT_Security::get_instance();
        $this->database = NGT_Database_Manager::get_instance();
        $this->scheduler = NGT_Scheduler::get_instance();
        $this->verifier = NGT_Verifier::get_instance();
        $this->detector = NGT_Detector::get_instance();
        $this->seeder = NGT_Seeder::get_instance();
        $this->importer = NGT_Importer::get_instance();
        $this->exporter = NGT_Exporter::get_instance();
        $this->uploader = NGT_Uploader::get_instance();
        $this->downloader = NGT_Downloader::get_instance();
        $this->notifier = NGT_Notifier::get_instance();
        $this->admin = NGT_Admin::get_instance();
        $this->api = NGT_API::get_instance();
        
        // Custom public property for workflows
        $this->workflows = NGT_Workflows::get_instance();

        $this->logger->info('NextGen Tutors Core initialized successfully');
    }

    /**
     * Setup plugin hooks
     */
    private function setup_hooks() {
        // Admin hooks
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Async task processing
        add_action('ngt_cleanup_logs', [$this->logger, 'cleanup']);

        // Verification on plugin load
        add_action('admin_init', [$this, 'run_system_check']);

        // REST API (Handled in NGT_API constructor)

        // POPIA compliance hooks
        add_action('woocommerce_after_checkout_billing_form', [$this, 'render_popia_consent']);
        add_action('woocommerce_checkout_process', [$this, 'validate_popia_consent']);
        add_action('woocommerce_checkout_create_order', [$this, 'save_popia_audit_log']);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'ngt_') === false && strpos($hook, 'nextgen') === false) {
            return;
        }

        wp_enqueue_style(
            'ngt-admin-css',
            NGT_CORE_PLUGIN_URL . 'assets/css/admin.css',
            [],
            NGT_CORE_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'ngt-admin-js',
            NGT_CORE_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-api'],
            NGT_CORE_PLUGIN_VERSION,
            true
        );

        wp_enqueue_script(
            'ngt-realtime-js',
            NGT_CORE_PLUGIN_URL . 'assets/js/real-time.js',
            ['jquery'],
            NGT_CORE_PLUGIN_VERSION,
            true
        );

        wp_localize_script('ngt-admin-js', 'ngtSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url(),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'plugin_version' => NGT_CORE_PLUGIN_VERSION,
            'debug_mode' => WP_DEBUG,
            'locale' => get_locale(),
        ]);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'ngt-frontend-css',
            NGT_CORE_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            NGT_CORE_PLUGIN_VERSION
        );
    }

    /**
     * Run system checks on admin init
     */
    public function run_system_check() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check once per day
        $last_check = get_transient('ngt_last_system_check');
        if ($last_check) {
            return;
        }

        // Run verifier
        $results = $this->verifier->run_full_detection();

        // Store results
        set_transient('ngt_system_check_results', $results, 24 * HOUR_IN_SECONDS);
        set_transient('ngt_last_system_check', time(), 24 * HOUR_IN_SECONDS);

        // Log any issues
        foreach ($results['checks'] as $check_name => $check_data) {
            if ($check_data['status'] !== 'pass') {
                $this->logger->error("System Check Failed: {$check_name}", $check_data);
            }
        }
    }

    /**
     * Render POPIA consent checkbox at checkout
     */
    public function render_popia_consent() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        $this->logger->debug('Rendering POPIA consent form');

        ob_start();
        include NGT_CORE_PLUGIN_DIR . 'templates/popia-consent-form.php';
        echo ob_get_clean();
    }

    /**
     * Validate POPIA consent on checkout
     */
    public function validate_popia_consent() {
        if (empty($_POST['ngt_popia_consent']) || $_POST['ngt_popia_consent'] !== '1') {
            wc_add_notice(
                __('POPIA consent is mandatory. Please accept to proceed.', NGT_TEXTDOMAIN),
                'error'
            );
            $this->logger->warning('POPIA consent not provided at checkout');
        }
    }

    /**
     * Save POPIA audit trail
     */
    public function save_popia_audit_log($order, $data) {
        if (!isset($_POST['ngt_popia_consent']) || $_POST['ngt_popia_consent'] !== '1') {
            return;
        }

        $audit = [
            'accepted' => true,
            'timestamp' => current_time('mysql'),
            'ip_address' => $this->security->get_client_ip(),
            'user_agent' => wc_get_user_agent(),
            'consent_ver' => '2.0',
        ];

        // Encrypt sensitive data
        $audit['ip_address_encrypted'] = $this->security->encrypt_data($audit['ip_address']);
        unset($audit['ip_address']);

        // Save to order
        $order->update_meta_data('_ngt_popia_consent', $audit);

        // Save to user if registered
        if ($order->get_customer_id()) {
            update_user_meta($order->get_customer_id(), '_ngt_popia_consent', $audit);
        }

        // Log to database
        $this->database->insert_consent_log($order->get_customer_id(), $audit);

        $this->logger->info('POPIA consent recorded for order ' . $order->get_id());
    }

    /**
     * Send monthly audit report (triggered by cron)
     */
    public function send_monthly_audit_report() {
        $file_url = $this->exporter->generate_audit_snapshot();
        $admin_email = get_option('admin_email');
        
        $this->notifier->send_email(
            $admin_email,
            "Monthly System Audit Snapshot - " . date('F Y'),
            "Hi Admin, your monthly system audit snapshot is ready. You can download it here: <a href='$file_url'>Download Report</a>"
        );
        
        $this->logger->info("Monthly auto-snapshot sent to $admin_email");
    }

    /**
     * Get plugin version
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Get database version
     */
    public function get_db_version() {
        return $this->db_version;
    }
}

// Return instance for easy access
function ngt() {
    return NextGen_Tutors_Core::get_instance();
}
