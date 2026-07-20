<?php
/**
 * Workflows engine for NextGen Tutors
 */
if (!defined('ABSPATH')) {
    exit;
}

class NGT_Workflows {
    
    /**
     * Execute a workflow by ID
     */
    public static function execute($workflow_id, $context_args = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'ngt_workflows';
        
        $workflow = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d AND is_active = 1 AND deleted_at IS NULL", $workflow_id));
        
        if (!$workflow) {
            return false;
        }

        $steps = json_decode($workflow->steps, true);
        
        if (!is_array($steps)) {
            NGT_Analytics::log_event('workflow_error', ['workflow_id' => $workflow_id, 'error' => 'Invalid JSON steps']);
            return false;
        }
        
        NGT_Analytics::log_event('workflow_started', ['workflow_id' => $workflow_id, 'name' => $workflow->name]);
        
        $execution_state = ['args' => $context_args, 'results' => []];
        
        foreach ($steps as $index => $step) {
            try {
                $result = self::process_step($step, $execution_state);
                $execution_state['results'][$index] = $result;
            } catch (Exception $e) {
                NGT_Analytics::log_event('workflow_failed', [
                    'workflow_id' => $workflow_id,
                    'step_index' => $index,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        }
        
        NGT_Analytics::log_event('workflow_completed', ['workflow_id' => $workflow_id]);
        return true;
    }
    
    /**
     * Process an individual workflow step
     */
    private static function process_step($step, &$state) {
        $type = $step['type'] ?? 'unknown';
        
        switch ($type) {
            case 'email':
                $to = self::parse_variables($step['to'] ?? '', $state);
                $subject = self::parse_variables($step['subject'] ?? '', $state);
                $message = self::parse_variables($step['message'] ?? '', $state);
                wp_mail($to, $subject, $message);
                return true;
                
            case 'log':
                $message = self::parse_variables($step['message'] ?? '', $state);
                NGT_Analytics::log_event('workflow_log', ['message' => $message]);
                return true;
                
            case 'http_post':
                $url = self::parse_variables($step['url'] ?? '', $state);
                $body = self::parse_variables($step['body'] ?? '{}', $state);
                $response = wp_remote_post($url, [
                    'body' => json_decode($body, true) ?: $body,
                    'headers' => ['Content-Type' => 'application/json']
                ]);
                if (is_wp_error($response)) {
                    throw new Exception($response->get_error_message());
                }
                return wp_remote_retrieve_body($response);
                
            default:
                throw new Exception("Unknown step type: $type");
        }
    }
    
    /**
     * Parse variables in a string based on execution state
     */
    private static function parse_variables($string, $state) {
        // Very basic variable replacement (e.g. {{args.0}} or {{results.1}})
        return preg_replace_callback('/\{\{([a-zA-Z0-9_\.]+)\}\}/', function($matches) use ($state) {
            $path = explode('.', $matches[1]);
            $current = $state;
            foreach ($path as $key) {
                if (is_array($current) && isset($current[$key])) {
                    $current = $current[$key];
                } else if (is_object($current) && isset($current->$key)) {
                    $current = $current->$key;
                } else {
                    return $matches[0]; // Not found, keep original
                }
            }
            return is_string($current) || is_numeric($current) ? $current : json_encode($current);
        }, $string);
    }
}
?>
