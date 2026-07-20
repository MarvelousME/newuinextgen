<?php
/**
 * NextGen Tutors Downloader Class
 *
 * Handles secure file downloads.
 */

class NGT_Downloader {

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
     * Create temporary download link
     */
    public function create_download_link($file_path, $expire_hours = 24) {
        $token = ngt()->security->generate_token(32);
        $expiry = time() + ($expire_hours * 3600);
        
        set_transient('ngt_dl_' . $token, [
            'path' => $file_path,
            'user_id' => get_current_user_id(),
            'expires' => $expiry
        ], $expire_hours * 3600);
        
        return add_query_arg('ngt_dl', $token, home_url());
    }

    /**
     * Verify access and stream file
     */
    public function download_file($token) {
        $data = get_transient('ngt_dl_' . $token);
        
        if (!$data || !file_exists($data['path'])) {
            wp_die('Download link expired or file missing');
        }

        // Verify user if needed
        if ($data['user_id'] !== get_current_user_id() && !current_user_can('manage_options')) {
            wp_die('Access denied');
        }

        $this->log_download($data['path'], $data['user_id']);
        
        // Stream file
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($data['path']) . '"');
        header('Content-Length: ' . filesize($data['path']));
        readfile($data['path']);
        exit;
    }

    /**
     * Log download for audit trail
     */
    public function log_download($path, $user_id) {
        ngt()->logger->info('File downloaded', [
            'path' => $path,
            'user_id' => $user_id,
            'ip' => ngt()->security->get_client_ip()
        ]);
    }
}
