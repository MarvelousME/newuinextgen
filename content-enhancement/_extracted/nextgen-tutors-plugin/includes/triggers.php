<?php
/**
 * Triggers engine for NextGen Tutors
 */
if (!defined('ABSPATH')) {
    exit;
}

class NGT_Triggers {
    
    /**
     * Boot up the triggers by registering them to WP hooks
     */
    public static function init() {
        global $wpdb;
        $table = $wpdb->prefix . 'ngt_triggers';
        
        // Check if table exists to prevent crash on early init before activation
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return;
        }

        $active_triggers = $wpdb->get_results("SELECT * FROM $table WHERE is_active = 1 AND deleted_at IS NULL ORDER BY priority DESC");
        
        if (!empty($active_triggers)) {
            foreach ($active_triggers as $trigger) {
                if (empty($trigger->hook) || empty($trigger->callback)) {
                    continue;
                }
                
                // We use an anonymous function to wrap the dynamic callback
                add_action($trigger->hook, function() use ($trigger) {
                    $args = func_get_args();
                    
                    // Log the trigger firing
                    NGT_Analytics::log_event('trigger_fired', [
                        'trigger_id' => $trigger->id,
                        'name' => $trigger->name,
                        'hook' => $trigger->hook
                    ]);
                    
                    // Route to workflows engine if callback specifies a workflow
                    if (strpos($trigger->callback, 'workflow:') === 0) {
                        $workflow_id = (int) str_replace('workflow:', '', $trigger->callback);
                        NGT_Workflows::execute($workflow_id, $args);
                    } 
                    // Support standard WP functions or static class methods
                    else if (is_callable($trigger->callback)) {
                        call_user_func_array($trigger->callback, $args);
                    }
                    
                }, $trigger->priority, 10); // Assume max 10 arguments for dynamic hooks
            }
        }
    }
}

// Initialize triggers after plugins loaded
add_action('plugins_loaded', ['NGT_Triggers', 'init']);
?>
