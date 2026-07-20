<?php
/**
 * NextGen Tutors Scheduler Class
 *
 * Handles task scheduling and WordPress cron job management.
 */

class NGT_Scheduler {

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
    private function __construct() {
        self::$instance = $this;
        // Register hooks for processing events
        add_action('ngt_process_scheduler', [$this, 'process_events']);
        add_action('ngt_monthly_auto_snapshot', function() { ngt()->send_monthly_audit_report(); });
        
        // Ensure recurring system check is scheduled
        if (!wp_next_scheduled('ngt_process_scheduler')) {
            wp_schedule_event(time(), 'hourly', 'ngt_process_scheduler');
        }

        // Schedule monthly auto-snapshot
        if (!wp_next_scheduled('ngt_monthly_auto_snapshot')) {
            $first_of_month = strtotime('first day of next month 08:00:00');
            wp_schedule_event($first_of_month, 'monthly', 'ngt_monthly_auto_snapshot');
        }
    }

    /**
     * Schedule a one-time event
     *
     * @param string $hook The hook to trigger.
     * @param int $time Timestamp for when to run.
     * @param array $args Optional arguments.
     * @return bool True on success, false on failure.
     */
    public function schedule_once($hook, $time, $args = []) {
        ngt()->logger->info("Scheduling one-time event: $hook", ['time' => date('Y-m-d H:i:s', $time)]);
        
        if (wp_next_scheduled($hook, $args)) {
            wp_clear_scheduled_hook($hook, $args);
        }
        
        $result = wp_schedule_single_event($time, $hook, $args);
        
        if ($result === false) {
            ngt()->logger->error("Failed to schedule event: $hook");
            return false;
        }
        
        return true;
    }

    /**
     * Schedule a recurring event
     *
     * @param string $hook The hook to trigger.
     * @param string $interval How often it should run (hourly, daily, twicedaily).
     * @param int $time Timestamp for the first run.
     * @param array $args Optional arguments.
     * @return bool True on success, false on failure.
     */
    public function schedule_recurring($hook, $interval, $time, $args = []) {
        ngt()->logger->info("Scheduling recurring event: $hook", [
            'interval' => $interval,
            'start_time' => date('Y-m-d H:i:s', $time)
        ]);
        
        if (wp_next_scheduled($hook, $args)) {
            wp_clear_scheduled_hook($hook, $args);
        }
        
        $result = wp_schedule_event($time, $interval, $hook, $args);
        
        if ($result === false) {
            ngt()->logger->error("Failed to schedule recurring event: $hook");
            return false;
        }
        
        return true;
    }

    /**
     * Cancel a scheduled event
     *
     * @param string $hook The hook to cancel.
     * @param array $args Optional arguments.
     * @return bool True if canceled, false otherwise.
     */
    public function cancel_scheduled($hook, $args = []) {
        ngt()->logger->info("Canceling scheduled event: $hook");
        
        $timestamp = wp_next_scheduled($hook, $args);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $hook, $args);
            return true;
        }
        
        return false;
    }

    /**
     * Get all NGT-related scheduled events
     *
     * @return array List of scheduled events.
     */
    public function get_scheduled_events() {
        $crons = _get_cron_array();
        $ngt_events = [];
        
        if (empty($crons)) {
            return [];
        }
        
        foreach ($crons as $time => $cron) {
            foreach ($cron as $hook => $event) {
                if (strpos($hook, 'ngt_') === 0) {
                    foreach ($event as $sig => $data) {
                        $ngt_events[] = [
                            'hook' => $hook,
                            'time' => $time,
                            'formatted_time' => date('Y-m-d H:i:s', $time),
                            'schedule' => $data['schedule'] ?? 'one-time',
                            'interval' => $data['interval'] ?? 0,
                            'args' => $data['args']
                        ];
                    }
                }
            }
        }
        
        return $ngt_events;
    }

    /**
     * Process events (Callback for cron)
     * This is usually handled by WordPress cron system, 
     * but we can call it manually for debugging.
     */
    public function process_events() {
        ngt()->logger->info('Starting scheduler event processing');
        // Custom logic for monitoring/managing events if needed
        do_action('ngt_after_scheduler_process');
    }
}
