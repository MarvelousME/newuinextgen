<?php
/**
 * NextGen Tutors Uploader Class
 *
 * Handles secure file uploads.
 */

class NGT_Uploader {

    private static $instance = null;
    private $max_size = 52428800; // 50MB

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
     * Handle upload
     */
    public function upload_file($file_input_name, $options = []) {
        if (!isset($_FILES[$file_input_name])) {
            throw new Exception('No file uploaded');
        }

        $file = $_FILES[$file_input_name];
        
        $this->validate_file($file);
        $this->scan_file($file['tmp_name']);
        
        return $this->store_file($file);
    }

    /**
     * Validate file
     */
    public function validate_file($file) {
        if ($file['size'] > $this->max_size) {
            throw new Exception('File exceeds maximum size');
        }

        $allowed = ngt()->security->get_allowed_file_types();
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!isset($allowed[$ext])) {
            throw new Exception('File type not allowed');
        }

        return true;
    }

    /**
     * Scan file (Placeholder for virus scan)
     */
    public function scan_file($file_path) {
        ngt()->logger->info("Scanning file: $file_path");
        // Integration with ClamAV or similar would go here
        return true;
    }

    /**
     * Store file securely
     */
    public function store_file($file) {
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['path'] . '/ngt_uploads';
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $filename = ngt()->security->sanitize_filename($file['name']);
        $target_path = $target_dir . '/' . time() . '_' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            ngt()->logger->success("File uploaded successfully", ['path' => $target_path]);
            return $target_path;
        }

        throw new Exception('Failed to move uploaded file');
    }
}
