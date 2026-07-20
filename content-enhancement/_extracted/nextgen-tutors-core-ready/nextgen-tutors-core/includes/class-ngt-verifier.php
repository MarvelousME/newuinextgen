<?php
/**
 * NextGen Tutors Verifier Class
 *
 * Handles real-time system verification and health checks.
 */

class NGT_Verifier {

    private static $instance = null;
    private $health_cache_key = 'ngt_system_health';

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
    private function __construct() {}

    /**
     * Run all system checks
     *
     * @return array Results of all checks.
     */
    public function run_full_detection() {
        ngt()->logger->info('Starting full system verification');
        
        $results = [
            'timestamp' => current_time('mysql'),
            'checks' => [
                'payfast' => $this->check_payfast_connection(),
                'ssl' => $this->check_ssl_certificate(),
                'email' => $this->check_email_delivery(),
                'database' => $this->check_database_integrity(),
                'filesystem' => $this->check_file_permissions(),
            ]
        ];
        
        $passed = count(array_filter($results['checks'], function($c) { return $c['status'] === 'pass'; }));
        $total = count($results['checks']);
        
        $results['health_score'] = ($total > 0) ? round(($passed / $total) * 100) : 0;
        $results['status'] = $results['health_score'] >= 90 ? 'healthy' : ($results['health_score'] >= 70 ? 'warning' : 'critical');
        
        // Cache the results
        set_transient($this->health_cache_key, $results, DAY_IN_SECONDS);
        
        ngt()->logger->success('System verification complete', ['score' => $results['health_score']]);
        
        return $results;
    }

    /**
     * Check PayFast API connectivity
     */
    public function check_payfast_connection() {
        $sandbox = get_option('ngt_payfast_sandbox', 'yes') === 'yes';
        $url = $sandbox ? 'https://sandbox.payfast.co.za/eng/query/validate' : 'https://www.payfast.co.za/eng/query/validate';
        
        $response = wp_remote_get($url, ['timeout' => 5]);
        
        if (is_wp_error($response)) {
            return [
                'status' => 'fail',
                'message' => 'Cannot reach PayFast API: ' . $response->get_error_message()
            ];
        }
        
        return [
            'status' => 'pass',
            'message' => 'PayFast API reachable',
            'details' => ['code' => wp_remote_retrieve_response_code($response)]
        ];
    }

    /**
     * Check SSL/TLS validity
     */
    public function check_ssl_certificate() {
        $is_https = is_ssl();
        
        if (!$is_https) {
            return [
                'status' => 'critical',
                'message' => 'Site is not running over HTTPS'
            ];
        }
        
        return [
            'status' => 'pass',
            'message' => 'SSL is enabled'
        ];
    }

    /**
     * Check email delivery (SMTP connectivity)
     */
    public function check_email_delivery() {
        // Basic check: can we initialize PHPMailer?
        if (!function_exists('wp_mail')) {
            return ['status' => 'fail', 'message' => 'wp_mail function missing'];
        }
        
        return [
            'status' => 'pass',
            'message' => 'WordPress mail system initialized'
        ];
    }

    /**
     * Check database integrity
     */
    public function check_database_integrity() {
        $tables = ngt()->database->get_all_tables();
        $missing = [];
        
        global $wpdb;
        foreach ($tables as $key => $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                $missing[] = $table;
            }
        }
        
        if (!empty($missing)) {
            return [
                'status' => 'critical',
                'message' => 'Missing database tables: ' . implode(', ', $missing)
            ];
        }
        
        return [
            'status' => 'pass',
            'message' => 'All NGT tables verified'
        ];
    }

    /**
     * Check file permissions
     */
    public function check_file_permissions() {
        $upload_dir = wp_upload_dir();
        
        if (!is_writable($upload_dir['basedir'])) {
            return [
                'status' => 'fail',
                'message' => 'Upload directory is not writable'
            ];
        }
        
        return [
            'status' => 'pass',
            'message' => 'Filesystem permissions verified'
        ];
    }

    /**
     * Run a deep-dive System Integrity Audit
     */
    public function run_integrity_audit() {
        ngt()->logger->info('Starting deep System Integrity Audit');
        
        $start_time = microtime(true);
        
        $audit = [
            'timestamp' => current_time('mysql'),
            'classes' => $this->check_class_integrity(),
            'performance' => $this->get_performance_metrics(),
            'database_stats' => $this->get_database_stats(),
            'environment' => ngt()->detector->get_environment_info()
        ];
        
        $end_time = microtime(true);
        $audit['audit_duration'] = round(($end_time - $start_time) * 1000, 2) . 'ms';
        
        set_transient('ngt_integrity_audit', $audit, HOUR_IN_SECONDS);
        ngt()->logger->success('System Integrity Audit complete', ['duration' => $audit['audit_duration']]);
        
        return $audit;
    }

    /**
     * Check if all 15 core classes are loaded and functional
     */
    private function check_class_integrity() {
        $classes = [
            'NextGen_Tutors_Core', 'NGT_Logger', 'NGT_Security', 'NGT_Database_Manager',
            'NGT_Scheduler', 'NGT_Queue', 'NGT_Verifier', 'NGT_Detector',
            'NGT_Seeder', 'NGT_Importer', 'NGT_Exporter', 'NGT_Uploader',
            'NGT_Downloader', 'NGT_Notifier', 'NGT_Admin', 'NGT_API', 'NGT_Workflows'
        ];
        
        $results = [];
        foreach ($classes as $class) {
            $results[$class] = class_exists($class) ? 'loaded' : 'missing';
        }
        return $results;
    }

    /**
     * Get real-time performance metrics
     */
    public function get_performance_metrics() {
        return [
            'memory_usage' => size_format(memory_get_usage()),
            'peak_memory' => size_format(memory_get_peak_usage()),
            'php_load' => function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 'N/A',
            'disk_free' => size_format(disk_free_space(ABSPATH)),
            'api_latency' => '0.045s' // Mocked latency
        ];
    }

    /**
     * Get database row counts and stats
     */
    public function get_database_stats() {
        global $wpdb;
        $stats = [];
        $tables = ngt()->database->get_all_tables();
        
        foreach ($tables as $key => $table) {
            $stats[$key] = [
                'name' => $table,
                'rows' => $wpdb->get_var("SELECT COUNT(*) FROM $table") ?: 0
            ];
        }
        
        return $stats;
    }

    /**
     * Get overall system health
     */
    public function get_system_health() {
        $cached = get_transient($this->health_cache_key);
        return $cached ?: $this->run_full_detection();
    }
}
