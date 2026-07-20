<?php
/**
 * NextGen Tutors Detector Class
 *
 * Auto-detects and adapts to the environment.
 */

class NGT_Detector {

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
     * Detect conflicting plugins
     */
    public function detect_plugin_conflicts() {
        $active_plugins = get_option('active_plugins', []);
        $conflicts = [];
        
        $known_conflicts = [
            'other-tutor-plugin/plugin.php' => 'Conflicting Tutor LMS found',
            'wp-caching-plugin/cache.php' => 'Aggressive caching may affect real-time sync'
        ];
        
        foreach ($active_plugins as $plugin) {
            if (isset($known_conflicts[$plugin])) {
                $conflicts[] = $known_conflicts[$plugin];
            }
        }
        
        return $conflicts;
    }

    /**
     * Detect theme compatibility
     */
    public function detect_theme_compatibility() {
        $theme = wp_get_theme();
        $name = $theme->get('Name');
        
        $compatible_themes = ['Smarthead', 'NextGen Tutors', 'Hello Elementor'];
        
        foreach ($compatible_themes as $compat) {
            if (stripos($name, $compat) !== false) {
                return ['compatible' => true, 'theme' => $name];
            }
        }
        
        return ['compatible' => false, 'theme' => $name, 'message' => 'Theme might require custom integration'];
    }

    /**
     * Detect PHP version and extensions
     */
    public function detect_php_version() {
        return [
            'version' => PHP_VERSION,
            'supported' => version_compare(PHP_VERSION, '7.4', '>='),
            'extensions' => [
                'curl' => extension_loaded('curl'),
                'openssl' => extension_loaded('openssl'),
                'mbstring' => extension_loaded('mbstring'),
                'mysqli' => extension_loaded('mysqli'),
                'json' => extension_loaded('json')
            ]
        ];
    }

    /**
     * Detect server environment
     */
    public function detect_server_environment() {
        return [
            'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'os' => PHP_OS,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        ];
    }

    /**
     * Get full environment info
     */
    public function get_environment_info() {
        return [
            'php' => $this->detect_php_version(),
            'server' => $this->detect_server_environment(),
            'wordpress' => [
                'version' => get_bloginfo('version'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
                'language' => get_bloginfo('language')
            ],
            'conflicts' => $this->detect_plugin_conflicts(),
            'theme' => $this->detect_theme_compatibility()
        ];
    }
}
