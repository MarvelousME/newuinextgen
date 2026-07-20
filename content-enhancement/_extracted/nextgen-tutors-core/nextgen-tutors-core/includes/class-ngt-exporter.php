<?php
/**
 * NextGen Tutors Exporter Class
 *
 * Handles data exports to various formats.
 */

class NGT_Exporter {

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
     * List exportable types
     */
    public function get_available_exports() {
        return ['contacts', 'earnings', 'logs', 'audit_trail'];
    }

    /**
     * Export contacts
     */
    public function export_contacts($format, $filters = []) {
        ngt()->logger->info("Starting contact export", ['format' => $format, 'filters' => $filters]);
        
        global $wpdb;
        $table = ngt()->database->get_table_name('contacts');
        $data = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
        
        return $this->generate_export_file($data, $format, 'ngt_contacts_' . date('Ymd'));
    }

    /**
     * Export earnings
     */
    public function export_earnings($format, $filters = []) {
        ngt()->logger->info("Starting earnings export", ['format' => $format, 'filters' => $filters]);
        
        global $wpdb;
        $table = ngt()->database->get_table_name('earnings');
        $query = "SELECT * FROM $table WHERE 1=1";
        
        if (!empty($filters['date_from'])) {
            $query .= $wpdb->prepare(" AND created_at >= %s", $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $query .= $wpdb->prepare(" AND created_at <= %s", $filters['date_to'] . ' 23:59:59');
        }

        $data = $wpdb->get_results($query, ARRAY_A);
        
        return $this->generate_export_file($data, $format, 'ngt_earnings_' . date('Ymd'));
    }

    /**
     * Generate a One-Click PDF Invoice for a parent
     */
    public function generate_invoice($order_id) {
        ngt()->logger->info("Generating PDF invoice for order #$order_id");
        
        $order_data = [
            'id' => $order_id,
            'date' => date('Y-m-d'),
            'amount' => 450.00, // In production, fetch from WooCommerce order
            'items' => [['name' => '1hr Mathematics Tutoring', 'price' => 450.00]]
        ];
        
        return $this->generate_export_file([$order_data], 'pdf', 'ngt_invoice_' . $order_id);
    }

    /**
     * Generate a Tutor Performance Certificate (PDF)
     */
    public function generate_tutor_certificate($tutor_name, $rank) {
        ngt()->logger->info("Generating Performance Certificate for $tutor_name (Rank #$rank)");
        
        $certificate_data = [
            'name' => $tutor_name,
            'rank' => $rank,
            'award' => "Excellence in Tutoring Award",
            'date' => date('F Y'),
            'signature' => 'NextGen Tutors Board'
        ];
        
        return $this->generate_export_file([$certificate_data], 'pdf', 'ngt_certificate_' . sanitize_title($tutor_name));
    }

    /**
     * Generate a System Audit Snapshot (Board Report)
     */
    public function generate_audit_snapshot() {
        ngt()->logger->info("Generating System Audit Snapshot");
        
        $health = ngt()->verifier->get_system_health();
        
        $report_data = [
            'report_name' => 'NextGen Tutors System Audit',
            'timestamp' => current_time('mysql'),
            'health_score' => $health['health_score'],
            'checks' => $health['checks']
        ];
        
        return $this->generate_export_file([$report_data], 'pdf', 'ngt_audit_snapshot_' . date('Ymd_His'));
    }

    /**
     * Generate file
     */
    public function generate_export_file($data, $format, $filename) {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename . '.' . $format;
        
        if ($format === 'csv' || $format === 'excel') {
            // Excel-compatible CSV for simplicity, or native XLSX logic
            $fp = fopen($file_path, 'w');
            if (!empty($data)) {
                fputcsv($fp, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($fp, $row);
                }
            }
            fclose($fp);
        } elseif ($format === 'json') {
            file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
        } elseif ($format === 'pdf') {
            // Simplified PDF generation (HTML stream)
            $html = "<h1>NGT Report: $filename</h1><table border='1'>";
            if (!empty($data)) {
                $html .= "<tr><th>" . implode("</th><th>", array_keys($data[0])) . "</th></tr>";
                foreach ($data as $row) {
                    $html .= "<tr><td>" . implode("</td><td>", $row) . "</td></tr>";
                }
            }
            $html .= "</table>";
            file_put_contents($file_path, $html); // In production, use mPDF/dompdf here
        }
        
        ngt()->logger->success("Export file generated: $file_path");
        
        return $upload_dir['url'] . '/' . $filename . '.' . $format;
    }
}
