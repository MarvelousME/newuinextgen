<?php
/**
 * NextGen Tutors Database Manager Class
 *
 * Handles database table creation, migrations, and database operations
 */

class NGT_Database_Manager {

    private static $instance = null;
    private $tables = [
        'logs' => 'wp_ngt_logs',
        'queue' => 'wp_ngt_queue',
        'contacts' => 'wp_ngt_contacts',
        'earnings' => 'wp_ngt_earnings',
        'ratings' => 'wp_ngt_ratings',
        'verification' => 'wp_ngt_verification',
        'consent' => 'wp_ngt_consent',
        'alerts' => 'wp_ngt_alerts',
        'notifications' => 'wp_ngt_notifications',
    ];

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
    }

    /**
     * Create all necessary tables
     */
    public function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Logs table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ngt_logs (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            level VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            context LONGTEXT,
            user_id BIGINT DEFAULT 0,
            ip_address VARCHAR(45),
            url TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX level (level),
            INDEX created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        dbDelta($sql);

        // Queue table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ngt_queue (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            job_type VARCHAR(100) NOT NULL,
            payload LONGTEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            attempts INT DEFAULT 0,
            max_attempts INT DEFAULT 3,
            error_message TEXT,
            scheduled_at DATETIME,
            started_at DATETIME,
            completed_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX job_type (job_type),
            INDEX status (status),
            INDEX scheduled_at (scheduled_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        dbDelta($sql);

        // Contacts table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ngt_contacts (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            email VARCHAR(255),
            phone VARCHAR(20),
            role VARCHAR(50),
            status VARCHAR(20) DEFAULT 'active',
            metadata LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY email (email),
            INDEX user_id (user_id),
            INDEX status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        dbDelta($sql);

        // Earnings table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ngt_earnings (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            tutor_id BIGINT NOT NULL,
            session_id BIGINT,
            order_id BIGINT,
            amount DECIMAL(10, 2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'ZAR',
            payfast_transaction_id VARCHAR(100),
            status VARCHAR(20) DEFAULT 'pending',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            paid_at DATETIME,
            INDEX tutor_id (tutor_id),
            INDEX status (status),
            INDEX created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        dbDelta($sql);

        // Ratings table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ngt_ratings (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            session_id BIGINT,
            parent_id BIGINT,
            tutor_id BIGINT,
            rating INT CHECK (rating >= 1 AND rating <= 5),
            feedback TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX session_id (session_id),
            INDEX tutor_id (tutor_id),
            INDEX rating (rating)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        dbDelta($sql);

        // Tutor verification table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ngt_verification (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            verified_by BIGINT,
            documents LONGTEXT,
            notes TEXT,
            verified_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY user_id (user_id),
            INDEX status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        dbDelta($sql);

        // POPIA Consent table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ngt_consent (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            contact_id BIGINT,
            consent_version VARCHAR(10),
            accepted BOOLEAN DEFAULT 0,
            ip_address_encrypted VARCHAR(255),
            user_agent VARCHAR(255),
            consent_type VARCHAR(50) DEFAULT 'checkout',
            expires_at DATETIME,
            withdrawn_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX user_id (user_id),
            INDEX created_at (created_at),
            INDEX expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        dbDelta($sql);

        // Alerts table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ngt_alerts (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            alert_type VARCHAR(100) NOT NULL,
            severity VARCHAR(20) DEFAULT 'info',
            title VARCHAR(255),
            message TEXT,
            action_url TEXT,
            dismissed INT DEFAULT 0,
            dismissed_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX alert_type (alert_type),
            INDEX severity (severity),
            INDEX dismissed (dismissed)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        dbDelta($sql);

        // Notifications table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ngt_notifications (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            contact_id BIGINT,
            notification_type VARCHAR(100),
            subject VARCHAR(255),
            message TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            sent_at DATETIME,
            opened_at DATETIME,
            clicked_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX user_id (user_id),
            INDEX notification_type (notification_type),
            INDEX status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        dbDelta($sql);
    }

    /**
     * Insert contact
     */
    public function insert_contact($data) {
        global $wpdb;

        $defaults = [
            'user_id' => 0,
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'role' => 'parent',
            'status' => 'active',
            'metadata' => '',
        ];

        $data = array_merge($defaults, $data);

        return $wpdb->insert(
            $wpdb->prefix . 'ngt_contacts',
            $data,
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Get contact by email
     */
    public function get_contact_by_email($email) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ngt_contacts WHERE email = %s",
                $email
            ),
            ARRAY_A
        );
    }

    /**
     * Insert earnings record
     */
    public function insert_earnings($data) {
        global $wpdb;

        $defaults = [
            'tutor_id' => 0,
            'session_id' => null,
            'order_id' => null,
            'amount' => 0,
            'currency' => 'ZAR',
            'status' => 'pending',
        ];

        $data = array_merge($defaults, $data);

        return $wpdb->insert(
            $wpdb->prefix . 'ngt_earnings',
            $data,
            ['%d', '%d', '%d', '%f', '%s', '%s']
        );
    }

    /**
     * Get earnings by tutor
     */
    public function get_earnings_by_tutor($tutor_id, $limit = 100) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ngt_earnings WHERE tutor_id = %d ORDER BY created_at DESC LIMIT %d",
                $tutor_id,
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * Insert consent log
     */
    public function insert_consent_log($user_id, $data) {
        global $wpdb;

        $defaults = [
            'user_id' => $user_id,
            'contact_id' => 0,
            'consent_version' => '2.0',
            'accepted' => true,
            'user_agent' => '',
            'consent_type' => 'checkout',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ];

        $data = array_merge($defaults, $data);

        return $wpdb->insert(
            $wpdb->prefix . 'ngt_consent',
            $data,
            ['%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Queue a job
     */
    public function queue_job($job_type, $payload, $scheduled_at = null) {
        global $wpdb;

        return $wpdb->insert(
            $wpdb->prefix . 'ngt_queue',
            [
                'job_type' => $job_type,
                'payload' => json_encode($payload),
                'status' => 'pending',
                'scheduled_at' => $scheduled_at,
            ],
            ['%s', '%s', '%s', '%s']
        );
    }

    /**
     * Get pending jobs
     */
    public function get_pending_jobs($limit = 10) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ngt_queue
                 WHERE status = 'pending'
                 AND (scheduled_at IS NULL OR scheduled_at <= NOW())
                 AND attempts < max_attempts
                 ORDER BY created_at ASC
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * Update job status
     */
    public function update_job_status($job_id, $status, $error_message = '') {
        global $wpdb;

        $update_data = [
            'status' => $status,
        ];

        if ($status === 'completed') {
            $update_data['completed_at'] = current_time('mysql');
        } elseif ($status === 'processing') {
            $update_data['started_at'] = current_time('mysql');
        } elseif ($status === 'failed') {
            $update_data['error_message'] = $error_message;
        }

        return $wpdb->update(
            $wpdb->prefix . 'ngt_queue',
            $update_data,
            ['id' => $job_id]
        );
    }

    /**
     * Batch Payout Override
     * Mark all pending earnings as paid for a list of tutors
     */
    public function process_batch_payouts($tutor_ids) {
        global $wpdb;
        $table = $this->get_table_name('earnings');
        
        $ids = implode(',', array_map('intval', $tutor_ids));
        
        $result = $wpdb->query("UPDATE $table SET payout_status = 'paid', paid_at = NOW() WHERE tutor_id IN ($ids) AND payout_status = 'pending'");
        
        if ($result > 0) {
            foreach ($tutor_ids as $tutor_id) {
                // Fetch tutor email (mocked for now, in production fetch from WP user)
                $tutor_email = "tutor_$tutor_id@example.com";
                ngt()->notifier->send_email(
                    $tutor_email, 
                    "Payout Receipt: Payment Processed", 
                    "Hi, your recent earnings have been processed and paid out via Batch Override. Please check your bank account within 24-48 hours."
                );
            }
        }

        ngt()->logger->success("Batch payout override complete and notifications sent", ['count' => $result, 'tutors' => $ids]);
        
        return $result;
    }

    /**
     * Get table name
     */
    public function get_table_name($table_key) {
        return $this->tables[$table_key] ?? null;
    }

    /**
     * Get all table names
     */
    public function get_all_tables() {
        return $this->tables;
    }
}
