<?php
/**
 * Activation / Deactivation handlers for NextGen Tutors plugin.
 */
class NGT_Activator {
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        // Table: earnings
        $table_earnings = $wpdb->prefix . 'ngt_earnings';
        $sql_earnings = "CREATE TABLE $table_earnings (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tutor_id BIGINT(20) UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            earned_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY tutor_id (tutor_id)
        ) $charset_collate;";
        // Table: payouts
        $table_payouts = $wpdb->prefix . 'ngt_payouts';
        $sql_payouts = "CREATE TABLE $table_payouts (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tutor_id BIGINT(20) UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status VARCHAR(20) NOT NULL,
            processed_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY tutor_id (tutor_id)
        ) $charset_collate;";
        // Table: logs (for analytics & SSE)
        $table_logs = $wpdb->prefix . 'ngt_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type VARCHAR(50) NOT NULL,
            payload LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_earnings);
        dbDelta($sql_payouts);
        dbDelta($sql_logs);

        // Table: triggers (soft delete)
        $table_triggers = $wpdb->prefix . 'ngt_triggers';
        $sql_triggers = "CREATE TABLE $table_triggers (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            hook VARCHAR(100) NOT NULL,
            callback VARCHAR(150) NOT NULL,
            sequence_ordinal INT NOT NULL DEFAULT 0,
            priority INT NOT NULL DEFAULT 0,
            description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            is_optional TINYINT(1) NOT NULL DEFAULT 0,
            deleted_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active)
        ) $charset_collate;";
        dbDelta($sql_triggers);

        // Table: workflows (soft delete)
        $table_workflows = $wpdb->prefix . 'ngt_workflows';
        $sql_workflows = "CREATE TABLE $table_workflows (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            steps LONGTEXT NOT NULL,
            sequence_ordinal INT NOT NULL DEFAULT 0,
            priority INT NOT NULL DEFAULT 0,
            description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            is_optional TINYINT(1) NOT NULL DEFAULT 0,
            deleted_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active)
        ) $charset_collate;";
        dbDelta($sql_workflows);
        // Set default feature toggles.
        add_option('ngt_features', [
            'booking' => true,
            'payouts' => true,
            'lms_sync' => true,
        ]);
        add_option('ngt_alert_emails', 'marvin.saunders@gmail.com,marvin@getonlinenow.co.za');
    }
    public static function deactivate() {
        // Optionally clean up options – keep tables for data integrity.
        // delete_option('ngt_features'); // Uncomment if you want to purge.
    }
}
?>
