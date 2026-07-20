<?php
/**
 * NextGen Tutors Logger Class
 *
 * Handles all plugin logging with database storage and file fallback
 */

class NGT_Logger {

    private static $instance = null;
    private $table_name;
    private $log_file;
    private $max_log_size = 10485760; // 10MB
    private $retention_days = 30;

    const LOG_INFO = 'info';
    const LOG_WARNING = 'warning';
    const LOG_ERROR = 'error';
    const LOG_DEBUG = 'debug';
    const LOG_SUCCESS = 'success';

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
        self::$instance = $this;
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ngt_logs';
        $this->log_file = NGT_CORE_PLUGIN_DIR . 'logs/nextgen-tutors.log';

        // Ensure logs directory exists
        if (!is_dir(NGT_CORE_PLUGIN_DIR . 'logs')) {
            wp_mkdir_p(NGT_CORE_PLUGIN_DIR . 'logs');
        }
    }

    /**
     * Log info message
     */
    public function info($message, $context = []) {
        $this->log(self::LOG_INFO, $message, $context);
    }

    /**
     * Log warning message
     */
    public function warning($message, $context = []) {
        $this->log(self::LOG_WARNING, $message, $context);
    }

    /**
     * Log error message
     */
    public function error($message, $context = []) {
        $this->log(self::LOG_ERROR, $message, $context);
    }

    /**
     * Log debug message
     */
    public function debug($message, $context = []) {
        if (WP_DEBUG) {
            $this->log(self::LOG_DEBUG, $message, $context);
        }
    }

    /**
     * Log success message
     */
    public function success($message, $context = []) {
        $this->log(self::LOG_SUCCESS, $message, $context);
    }

    /**
     * Main logging function
     */
    private function log($level, $message, $context = []) {
        global $wpdb;

        // Build log entry
        $entry = [
            'level' => $level,
            'message' => $message,
            'context' => json_encode($context),
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'created_at' => current_time('mysql'),
        ];

        // Try database first
        $inserted = $wpdb->insert(
            $this->table_name,
            $entry,
            ['%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        // Fallback to file if database fails
        if (!$inserted) {
            $this->log_to_file($entry);
        }
    }

    /**
     * Log to file as fallback
     */
    private function log_to_file($entry) {
        $log_message = sprintf(
            "[%s] [%s] %s | User: %d | IP: %s\n",
            $entry['created_at'],
            strtoupper($entry['level']),
            $entry['message'],
            $entry['user_id'],
            $entry['ip_address']
        );

        if (!empty($entry['context']) && $entry['context'] !== '[]') {
            $log_message .= "Context: " . $entry['context'] . "\n";
        }

        $log_message .= str_repeat('-', 80) . "\n";

        // Check file size
        if (file_exists($this->log_file) && filesize($this->log_file) > $this->max_log_size) {
            $this->rotate_log_file();
        }

        // Append to file
        error_log($log_message, 3, $this->log_file);
    }

    /**
     * Rotate log file when too large
     */
    private function rotate_log_file() {
        $timestamp = date('Y-m-d-H-i-s');
        $rotated = NGT_CORE_PLUGIN_DIR . "logs/nextgen-tutors-{$timestamp}.log";
        rename($this->log_file, $rotated);
    }

    /**
     * Get recent logs
     */
    public function get_logs($limit = 100, $level = null) {
        global $wpdb;

        $query = "SELECT * FROM {$this->table_name}";
        $params = [];

        if ($level) {
            $query .= " WHERE level = %s";
            $params[] = $level;
        }

        $query .= " ORDER BY created_at DESC LIMIT %d";
        $params[] = $limit;

        return $wpdb->get_results(
            $wpdb->prepare($query, ...$params),
            ARRAY_A
        );
    }

    /**
     * Get log statistics
     */
    public function get_statistics($days = 7) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT level, COUNT(*) as count FROM {$this->table_name}
             WHERE created_at > DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY level",
            $days
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Cleanup old logs (called by cron)
     */
    public function cleanup() {
        global $wpdb;

        // Delete logs older than retention period
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name}
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $this->retention_days
            )
        );

        // Also cleanup old log files
        $log_dir = NGT_CORE_PLUGIN_DIR . 'logs';
        if (is_dir($log_dir)) {
            $files = glob($log_dir . '/nextgen-tutors-*.log');
            foreach ($files as $file) {
                if (filemtime($file) < strtotime('-30 days')) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Clear all logs (admin action only)
     */
    public function clear_all($days = 0) {
        global $wpdb;

        if ($days > 0) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$this->table_name}
                     WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                    $days
                )
            );
        } else {
            $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        }
    }

    /**
     * Export logs as CSV
     */
    public function export_csv($days = 30) {
        global $wpdb;

        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name}
                 WHERE created_at > DATE_SUB(NOW(), INTERVAL %d DAY)
                 ORDER BY created_at DESC",
                $days
            ),
            ARRAY_A
        );

        $filename = 'ngt-logs-' . date('Y-m-d-H-i-s') . '.csv';

        // Output CSV headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // CSV column headers
        fputcsv($output, ['ID', 'Level', 'Message', 'User ID', 'IP Address', 'URL', 'Created At']);

        // CSV rows
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['level'],
                $log['message'],
                $log['user_id'],
                $log['ip_address'],
                $log['url'],
                $log['created_at'],
            ]);
        }

        fclose($output);
    }

    /**
     * Get database table name
     */
    public function get_table_name() {
        return $this->table_name;
    }
}
