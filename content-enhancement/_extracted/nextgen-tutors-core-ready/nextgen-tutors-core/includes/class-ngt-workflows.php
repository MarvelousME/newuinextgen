<?php
/**
 * NextGen Tutors Workflows Class
 *
 * Handles dynamic automation and workflow execution.
 */

class NGT_Workflows {

    private static $instance = null;
    private $workflows_option = 'ngt_active_workflows';

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
        $this->initialize_active_workflows();
    }

    /**
     * Initialize and wire up active workflows to system hooks
     */
    public function initialize_active_workflows() {
        $workflows = get_option($this->workflows_option, []);
        
        foreach ($workflows as $workflow) {
            if ($workflow['status'] !== 'active') continue;
            
            $trigger = $workflow['trigger'];
            add_action($trigger, function() use ($workflow) {
                $this->execute_workflow($workflow['id']);
            });
        }
    }

    /**
     * Save/Update a workflow
     */
    public function save_workflow($workflow_data) {
        $workflows = get_option($this->workflows_option, []);
        $workflows[$workflow_data['id']] = $workflow_data;
        
        update_option($this->workflows_option, $workflows);
        ngt()->logger->success("Workflow saved and rewired: {$workflow_data['name']}");
        
        return true;
    }

    /**
     * Execute a workflow
     */
    public function execute_workflow($workflow_id, $context = []) {
        $workflows = get_option($this->workflows_option, []);
        $workflow = $workflows[$workflow_id] ?? null;

        if (!$workflow) return;

        ngt()->logger->info("Executing workflow: {$workflow['name']}", ['id' => $workflow_id]);

        foreach ($workflow['steps'] as $step) {
            // Check for conditions
            if (!empty($step['conditions'])) {
                if (!$this->check_conditions($step['conditions'], $context)) {
                    ngt()->logger->info("Step skipped due to condition mismatch", ['step' => $step['type']]);
                    continue;
                }
            }
            $this->execute_step($step, $context);
        }
    }

    /**
     * Check if step conditions are met
     */
    private function check_conditions($conditions, $context) {
        foreach ($conditions as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $value = $condition['value'];
            
            $context_value = $context[$field] ?? null;
            
            switch ($operator) {
                case 'equals': if ($context_value != $value) return false; break;
                case 'contains': if (strpos($context_value, $value) === false) return false; break;
                case 'greater_than': if ($context_value <= $value) return false; break;
            }
        }
        return true;
    }

    /**
     * Execute a single step
     */
    private function execute_step($step, $context) {
        switch ($step['type']) {
            case 'send_email':
                // Support comma-delimited list
                $recipients = explode(',', $step['to']);
                foreach ($recipients as $to) {
                    ngt()->notifier->send_email(trim($to), $step['subject'], $step['body']);
                }
                break;
            case 'add_to_queue':
                ngt()->queue->add_job($step['job_type'], $step['payload']);
                break;
            case 'log_event':
                ngt()->logger->info($step['message']);
                break;
            case 'trigger_webhook':
                wp_remote_post($step['url'], ['body' => $step['payload']]);
                break;
        }
    }

    /**
     * Check leaderboard and trigger rewards
     */
    public function check_leaderboard_rewards($top_tutors) {
        $last_top_3 = get_option('ngt_last_top_3_tutors', []);
        $current_top_3 = array_slice($top_tutors, 0, 3);
        
        foreach ($current_top_3 as $tutor) {
            if (!in_array($tutor['name'], $last_top_3)) {
                // New tutor in top 3!
                $this->execute_reward($tutor);
            }
        }
        
        update_option('ngt_last_top_3_tutors', array_column($current_top_3, 'name'));
    }

    /**
     * Execute Reward Workflow
     */
    private function execute_reward($tutor) {
        ngt()->logger->success("Triggering reward for Top 3 Tutor: {$tutor['name']}");
        
        // 1. Generate Certificate
        $cert_url = ngt()->exporter->generate_tutor_certificate($tutor['name'], $tutor['rank']);
        
        // 2. Send Notification Email
        $tutor_email = "tutor_" . sanitize_title($tutor['name']) . "@example.com";
        ngt()->notifier->send_email(
            $tutor_email,
            "Congratulations! You've Reached the Top 3!",
            "Hi {$tutor['name']}, amazing work! You are now ranked #{$tutor['rank']} on the NextGen Tutors leaderboard. Download your Excellence Certificate here: $cert_url"
        );
        
        // 3. Trigger system hook for other workflows
        do_action('ngt_tutor_rank_reached_top_3', ['tutor' => $tutor, 'certificate' => $cert_url]);
    }

    /**
     * Get available triggers
     */
    public function get_available_triggers() {
        return [
            'ngt_import_completed' => 'Data Import Completed',
            'ngt_payment_received' => 'Payment Received (WooCommerce)',
            'ngt_user_registered' => 'New User Registered',
            'ngt_health_check_failed' => 'System Health Check Failed',
            'ngt_payout_requested' => 'Tutor Payout Requested',
            'ngt_tutor_rank_reached_top_3' => 'Tutor Reached Top 3 on Leaderboard'
        ];
    }

    /**
     * Get available actions
     */
    public function get_available_actions() {
        return [
            'send_email' => 'Send Custom Email',
            'add_to_queue' => 'Add Background Job',
            'log_event' => 'Log to Audit Trail',
            'trigger_webhook' => 'Trigger n8n Webhook'
        ];
    }
}
