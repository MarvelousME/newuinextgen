<?php
/**
 * NextGen Tutors Importer Class
 *
 * Handles data imports from various formats.
 */

class NGT_Importer {

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
     * Detect file format
     */
    public function detect_format($file_path) {
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        return in_array($ext, ['csv', 'json']) ? $ext : 'unknown';
    }

    /**
     * Validate file structure
     */
    public function validate_file($file_path) {
        $format = $this->detect_format($file_path);
        
        if ($format === 'csv') {
            return ngt()->security->validate_csv_file($file_path);
        } elseif ($format === 'json') {
            return ngt()->security->validate_json_file($file_path);
        }
        
        return ['valid' => false, 'error' => 'Unsupported format'];
    }

    /**
     * Parse file
     */
    public function parse_file($file_path) {
        $format = $this->detect_format($file_path);
        $data = [];
        
        if ($format === 'csv') {
            if (($handle = fopen($file_path, 'r')) !== false) {
                $headers = fgetcsv($handle);
                while (($row = fgetcsv($handle)) !== false) {
                    $data[] = array_combine($headers, $row);
                }
                fclose($handle);
            }
        } elseif ($format === 'json') {
            $content = file_get_contents($file_path);
            $data = json_decode($content, true);
        }
        
        return $data;
    }

    /**
     * Preview import (first 5 rows)
     */
    public function preview_import($file_path) {
        $data = $this->parse_file($file_path);
        return array_slice($data, 0, 5);
    }

    /**
     * Execute import (Async via Queue)
     */
    public function execute_import($file_path, $options = []) {
        ngt()->logger->info("Initiating import for: $file_path", ['options' => $options]);
        
        $validation = $this->validate_file($file_path);
        if (!$validation['valid']) {
            throw new Exception("Invalid file: " . $validation['error']);
        }
        
        // Add to queue
        $job_id = ngt()->queue->add_job('import_data', [
            'file_path' => $file_path,
            'options' => $options
        ]);
        
        ngt()->logger->success("Import job queued", ['job_id' => $job_id]);
        
        return $job_id;
    }
}
