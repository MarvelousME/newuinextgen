<?php
/**
 * NextGen Tutors Security Class
 *
 * Handles encryption, sanitization, validation, and security utilities
 */

class NGT_Security {

    private static $instance = null;
    private $encryption_key;
    private $allowed_file_types = [
        'csv' => 'text/csv',
        'json' => 'application/json',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
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
        // Get encryption key from options or generate one
        $this->encryption_key = get_option('ngt_encryption_key');

        if (!$this->encryption_key) {
            $this->encryption_key = bin2hex(random_bytes(32));
            update_option('ngt_encryption_key', $this->encryption_key);
        }
    }

    /**
     * Encrypt data using AES-256-CBC
     */
    public function encrypt_data($data) {
        if (empty($data)) {
            return '';
        }

        // Generate IV
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        // Encrypt
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-cbc',
            hex2bin($this->encryption_key),
            0,
            $iv
        );

        // Return base64 encoded IV + encrypted data
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data
     */
    public function decrypt_data($encrypted_data) {
        if (empty($encrypted_data)) {
            return '';
        }

        try {
            // Decode base64
            $data = base64_decode($encrypted_data, true);

            if ($data === false) {
                return '';
            }

            // Extract IV
            $cipher = 'aes-256-cbc';
            $iv_length = openssl_cipher_iv_length($cipher);
            $iv = substr($data, 0, $iv_length);
            $encrypted = substr($data, $iv_length);

            // Decrypt
            $decrypted = openssl_decrypt(
                $encrypted,
                $cipher,
                hex2bin($this->encryption_key),
                0,
                $iv
            );

            return $decrypted ?: '';
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Get client IP address (accounting for proxies)
     */
    public function get_client_ip() {
        // Check for IP from shared internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for IP passed from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Handle multiple IPs
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        }
        // Check for remote IP
        else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        }

        // Validate IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return 'UNKNOWN';
        }

        return $ip;
    }

    /**
     * Generate secure nonce
     */
    public function generate_nonce($action = 'ngt_action') {
        return wp_create_nonce($action);
    }

    /**
     * Validate external service token (e.g. for Grafana/Prometheus)
     */
    public function validate_external_token($request) {
        $token = $request->get_header('Authorization');
        if (!$token) return false;
        
        $token = str_replace('Bearer ', '', $token);
        $saved_token = get_option('ngt_external_metrics_token');
        
        return hash_equals($saved_token, $token);
    }

    /**
     * Verify nonce
     */
    public function verify_nonce($nonce, $action = 'ngt_action') {
        return wp_verify_nonce($nonce, $action) === 1;
    }

    /**
     * Sanitize text input
     */
    public function sanitize_text($text) {
        return sanitize_text_field($text);
    }

    /**
     * Sanitize email
     */
    public function sanitize_email($email) {
        return sanitize_email($email);
    }

    /**
     * Sanitize URL
     */
    public function sanitize_url($url) {
        return esc_url_raw($url);
    }

    /**
     * Sanitize JSON
     */
    public function sanitize_json($json) {
        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $this->sanitize_array($decoded);
    }

    /**
     * Sanitize array recursively
     */
    public function sanitize_array($array) {
        if (!is_array($array)) {
            return sanitize_text_field($array);
        }

        $sanitized = [];

        foreach ($array as $key => $value) {
            $sanitized[sanitize_key($key)] = is_array($value)
                ? $this->sanitize_array($value)
                : sanitize_text_field($value);
        }

        return $sanitized;
    }

    /**
     * Validate email format
     */
    public function validate_email($email) {
        return is_email($email);
    }

    /**
     * Validate IP address
     */
    public function validate_ip($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate CSV format
     */
    public function validate_csv_file($file_path) {
        if (!file_exists($file_path)) {
            return ['valid' => false, 'error' => 'File not found'];
        }

        $file = fopen($file_path, 'r');

        if (!$file) {
            return ['valid' => false, 'error' => 'Cannot open file'];
        }

        $row = 0;
        $headers = null;

        while (($data = fgetcsv($file)) !== false) {
            $row++;

            if ($row === 1) {
                $headers = $data;
                continue;
            }

            if (count($data) !== count($headers)) {
                fclose($file);
                return [
                    'valid' => false,
                    'error' => "Row $row has inconsistent columns",
                ];
            }
        }

        fclose($file);

        return [
            'valid' => true,
            'rows' => $row - 1,
            'columns' => count($headers),
            'headers' => $headers,
        ];
    }

    /**
     * Validate JSON file
     */
    public function validate_json_file($file_path) {
        if (!file_exists($file_path)) {
            return ['valid' => false, 'error' => 'File not found'];
        }

        $content = file_get_contents($file_path);
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'valid' => false,
                'error' => 'Invalid JSON: ' . json_last_error_msg(),
            ];
        }

        return [
            'valid' => true,
            'structure' => gettype($decoded),
            'size' => strlen($content),
        ];
    }

    /**
     * Check if user can access admin function
     */
    public function user_can_access($capability = 'manage_options') {
        return current_user_can($capability);
    }

    /**
     * Rate limit check
     */
    public function check_rate_limit($key, $limit = 10, $period = 3600) {
        $transient = 'ngt_ratelimit_' . $key;
        $count = get_transient($transient);

        if ($count === false) {
            set_transient($transient, 1, $period);
            return true;
        }

        if ($count >= $limit) {
            return false;
        }

        set_transient($transient, $count + 1, $period);
        return true;
    }

    /**
     * Get allowed file types
     */
    public function get_allowed_file_types() {
        return $this->allowed_file_types;
    }

    /**
     * Validate file type
     */
    public function validate_file_type($file_path) {
        $file_type = wp_check_filetype($file_path);
        $extension = strtolower($file_type['ext']);

        if (!isset($this->allowed_file_types[$extension])) {
            return false;
        }

        return true;
    }

    /**
     * Generate HMAC signature for webhooks
     */
    public function generate_hmac($payload, $secret) {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify HMAC signature
     */
    public function verify_hmac($payload, $signature, $secret) {
        $expected = $this->generate_hmac($payload, $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Sanitize filename
     */
    public function sanitize_filename($filename) {
        return sanitize_file_name($filename);
    }

    /**
     * Generate secure random token
     */
    public function generate_token($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
}
