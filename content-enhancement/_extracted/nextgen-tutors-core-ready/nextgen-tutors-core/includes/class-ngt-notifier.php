<?php
/**
 * NextGen Tutors Notifier Class
 *
 * Handles alerts and notifications.
 */

class NGT_Notifier {

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
    private function __construct() {}

    /**
     * Send email
     */
    public function send_email($to, $subject, $body) {
        ngt()->logger->info("Sending email to $to", ['subject' => $subject]);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $result = wp_mail($to, $subject, $body, $headers);
        
        if ($result) {
            ngt()->logger->success("Email sent successfully");
        } else {
            ngt()->logger->error("Failed to send email");
        }
        
        return $result;
    }

    /**
     * Send system alert (to admin dashboard)
     */
    public function send_alert($type, $message) {
        global $wpdb;
        $table = ngt()->database->get_table_name('alerts');
        
        $wpdb->insert($table, [
            'type' => $type,
            'message' => $message,
            'status' => 'unread',
            'created_at' => current_time('mysql')
        ]);
        
        ngt()->logger->info("System alert created: $type", ['message' => $message]);
        
        // Trigger action for external integrations (e.g. Slack/SMS)
        do_action('ngt_alert_created', $type, $message);
    }

    /**
     * Notify user (in-app)
     */
    public function notify_user($user_id, $message) {
        global $wpdb;
        $table = ngt()->database->get_table_name('notifications');
        
        $wpdb->insert($table, [
            'user_id' => $user_id,
            'message' => $message,
            'read_status' => 0,
            'created_at' => current_time('mysql')
        ]);
    }

    /**
     * Log notification
     */
    public function log_notification($user_id, $data) {
        ngt()->logger->info("Notification logged for user $user_id", ['data' => $data]);
    }
}
