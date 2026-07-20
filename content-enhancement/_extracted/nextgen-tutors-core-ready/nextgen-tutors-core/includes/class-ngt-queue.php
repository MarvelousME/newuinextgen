<?php
/**
 * NextGen Tutors Queue Class
 *
 * Handles async job processing and background task execution.
 */

class NGT_Queue {

    private static $instance = null;
    private $max_retries = 3;

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
        // Register hook for processing the queue
        add_action('ngt_process_queue', [$this, 'process']);
        
        // Ensure the queue processor is scheduled
        if (!wp_next_scheduled('ngt_process_queue')) {
            wp_schedule_event(time(), 'every_five_minutes', 'ngt_process_queue');
        }
    }

    /**
     * Add a job to the queue
     * (Wrapper for the database manager's method)
     */
    public function add_job($type, $payload, $scheduled = null) {
        return ngt()->database->queue_job($type, $payload, $scheduled);
    }

    /**
     * Process the queue
     *
     * @param int $limit Number of jobs to process.
     */
    public function process($limit = 10) {
        ngt()->logger->info('Starting queue processing', ['limit' => $limit]);
        
        $jobs = ngt()->database->get_pending_jobs($limit);
        
        if (empty($jobs)) {
            ngt()->logger->info('No pending jobs in queue');
            return;
        }
        
        foreach ($jobs as $job) {
            $this->execute_job($job);
        }
        
        ngt()->logger->info('Queue processing completed');
    }

    /**
     * Execute a single job
     *
     * @param object $job The job object from the database.
     */
    public function execute_job($job) {
        ngt()->logger->info("Executing job: {$job->type}", ['job_id' => $job->id]);
        
        // Mark as processing
        ngt()->database->update_job_status($job->id, 'processing');
        
        try {
            $payload = json_decode($job->payload, true);
            
            // Trigger specific action based on job type
            do_action("ngt_job_{$job->type}", $payload, $job->id);
            
            // Mark as completed
            ngt()->database->update_job_status($job->id, 'completed');
            ngt()->logger->success("Job completed: {$job->type}", ['job_id' => $job->id]);
            
            // Notify success if needed
            do_action('ngt_job_success', $job->id, $job->type);
            
        } catch (Exception $e) {
            $this->handle_job_error($job, $e->getMessage());
        }
    }

    /**
     * Handle job error
     *
     * @param object $job The job object.
     * @param string $error The error message.
     */
    public function handle_job_error($job, $error) {
        ngt()->logger->error("Job failed: {$job->type}", [
            'job_id' => $job->id,
            'error' => $error,
            'retries' => $job->retries
        ]);
        
        if ($job->retries < $this->max_retries) {
            $this->retry_job($job);
        } else {
            ngt()->database->update_job_status($job->id, 'failed');
            ngt()->notifier->send_alert('critical', "Job #{$job->id} ({$job->type}) failed after max retries: $error");
        }
    }

    /**
     * Retry a failed job
     *
     * @param object $job The job object.
     */
    public function retry_job($job) {
        $next_retry = time() + (pow(2, $job->retries) * 60); // Exponential backoff
        
        global $wpdb;
        $table = ngt()->database->get_table_name('queue');
        
        $wpdb->update(
            $table,
            [
                'status' => 'pending',
                'retries' => $job->retries + 1,
                'scheduled_at' => date('Y-m-d H:i:s', $next_retry)
            ],
            ['id' => $job->id]
        );
        
        ngt()->logger->info("Job #{$job->id} scheduled for retry", ['at' => date('Y-m-d H:i:s', $next_retry)]);
    }
}
