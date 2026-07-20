<?php
/**
 * Analytics helper for NextGen Tutors.
 * Handles event logging, metric aggregation and optional email alerts.
 */
if (!defined('ABSPATH')) {
    exit;
}

class NGT_Analytics {
    /** Log an event to the dedicated logs table */
    public static function log_event( $type, $payload = [] ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ngt_logs';
        $payload_json = wp_json_encode( $payload );
        $wpdb->insert(
            $table,
            [
                'event_type' => sanitize_text_field( $type ),
                'payload'    => $payload_json,
            ],
            [ '%s', '%s' ]
        );
        // Fire a hook so extensions can react (e.g., external alerting).
        do_action('ngt/metric_collected', $type, $payload);
        // Simple email alert for critical events (configurable list).
        if ( in_array( $type, [ 'payout_failed', 'lms_sync_error' ], true ) ) {
            self::send_alert_email( $type, $payload );
        }
    }

    /** Export aggregated Prometheus metrics */
    public static function export_prometheus() {
        global $wpdb;
        $table = $wpdb->prefix . 'ngt_logs';
        // Example counters – you can extend as needed.
        $counts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_type, COUNT(*) as cnt FROM $table WHERE created_at >= %s GROUP BY event_type",
                current_time('mysql', 1) // UTC now
            ),
            OBJECT_K
        );
        $out = '';
        foreach ($counts as $type => $row) {
            $metric_name = 'ngt_events_total{type="' . esc_attr($type) . '"}';
            $out .= $metric_name . ' ' . intval($row->cnt) . "\n";
        }
        return $out;
    }

    /** Send an alert email to configured addresses */
    private static function send_alert_email( $type, $payload ) {
        $emails = get_option('ngt_alert_emails', ''); // comma‑delimited list.
        if ( empty($emails) ) {
            return; // No alerts configured.
        }
        $subject = '[NextGen Tutors Alert] ' . ucfirst($type);
        $message = "An event of type '{$type}' occurred.\n\nPayload:\n" . print_r($payload, true);
        $headers = ['Content-Type: text/plain; charset="UTF-8"'];
        $email_array = array_map('trim', explode(',', $emails));
        wp_mail($email_array, $subject, $message, $headers);
    }
}
?>
