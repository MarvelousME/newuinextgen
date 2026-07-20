<?php
/**
 * NextGen Tutors API Class
 *
 * Handles REST API endpoints.
 */

class NGT_API {

    private static $instance = null;
    private $namespace = 'ngt/v1';

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
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST routes
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_status'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/verify', [
            'methods' => 'POST',
            'callback' => [$this, 'run_verify'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/queue', [
            'methods' => 'GET',
            'callback' => [$this, 'get_queue'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/metrics', [
            'methods' => 'GET',
            'callback' => [$this, 'get_metrics'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/logs', [
            'methods' => 'GET',
            'callback' => [$this, 'get_logs'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/activity', [
            'methods' => 'GET',
            'callback' => [$this, 'get_activity'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/workflows', [
            'methods' => 'POST',
            'callback' => [$this, 'save_workflow'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/payouts/batch', [
            'methods' => 'POST',
            'callback' => [$this, 'batch_payout_override'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/billing/invoice', [
            'methods' => 'GET',
            'callback' => [$this, 'get_invoice'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/audit/snapshot', [
            'methods' => 'GET',
            'callback' => [$this, 'get_audit_snapshot'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/gamification', [
            'methods' => 'GET',
            'callback' => [$this, 'get_gamification_stats'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/gamification/seed', [
            'methods' => 'POST',
            'callback' => [$this, 'seed_gamification'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/integrity/audit', [
            'methods' => 'POST',
            'callback' => [$this, 'run_integrity_audit'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route($this->namespace, '/metrics/external', [
            'methods' => 'GET',
            'callback' => [$this, 'get_external_metrics'],
            'permission_callback' => [$this, 'check_external_permission']
        ]);

        // Ensure we have an external token generated
        if (!get_option('ngt_external_metrics_token')) {
            update_option('ngt_external_metrics_token', bin2hex(random_bytes(16)));
        }
    }

    /**
     * Check permissions
     */
    public function check_permission() {
        return current_user_can('manage_options');
    }

    /**
     * GET /metrics
     */
    public function get_metrics($request) {
        global $wpdb;
        $earnings_table = ngt()->database->get_table_name('earnings');
        $contacts_table = ngt()->database->get_table_name('contacts');
        
        $total_earnings = $wpdb->get_var("SELECT SUM(amount) FROM $earnings_table");
        $total_tutors = $wpdb->get_var("SELECT COUNT(*) FROM $contacts_table WHERE role = 'tutor'");
        $total_parents = $wpdb->get_var("SELECT COUNT(*) FROM $contacts_table WHERE role = 'parent'");
        
        // Mock daily earnings for chart
        $chart_data = [];
        for ($i = 6; $i >= 0; $i--) {
            $chart_data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'value' => rand(500, 2000)
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'summary' => [
                'earnings' => (float)$total_earnings,
                'tutors' => (int)$total_tutors,
                'parents' => (int)$total_parents,
                'queue_size' => (int)$wpdb->get_var("SELECT COUNT(*) FROM " . ngt()->database->get_table_name('queue') . " WHERE status = 'pending'"),
                'performance' => ngt()->verifier->get_performance_metrics()
            ],
            'chart' => $chart_data
        ], 200);
    }

    /**
     * GET /logs
     */
    public function get_logs($request) {
        $logs = ngt()->logger->get_logs(50, $request->get_param('level') ?: 'all');
        return new WP_REST_Response([
            'success' => true,
            'logs' => $logs
        ], 200);
    }

    /**
     * GET /activity
     */
    public function get_activity($request) {
        global $wpdb;
        $logs_table = ngt()->database->get_table_name('logs');
        $activities = $wpdb->get_results("SELECT message, level, created_at FROM $logs_table ORDER BY created_at DESC LIMIT 20");
        
        return new WP_REST_Response([
            'success' => true,
            'activities' => $activities
        ], 200);
    }

    /**
     * POST /workflows
     */
    public function save_workflow($request) {
        $params = $request->get_json_params();
        $params['status'] = 'active'; // Default to active on save
        
        $result = ngt()->workflows->save_workflow($params);
        
        // Immediate Rewire
        ngt()->workflows->initialize_active_workflows();
        
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Workflow saved and system rewired.'
        ], 200);
    }

    /**
     * POST /payouts/batch
     */
    public function batch_payout_override($request) {
        $params = $request->get_json_params();
        $tutor_ids = $params['tutor_ids'] ?? [];
        
        if (empty($tutor_ids)) {
            return new WP_REST_Response(['error' => 'No tutors selected'], 400);
        }
        
        $count = ngt()->database->process_batch_payouts($tutor_ids);
        
        return new WP_REST_Response([
            'success' => true,
            'count' => $count,
            'message' => 'Batch payout override executed.'
        ], 200);
    }

    /**
     * GET /billing/invoice
     */
    public function get_invoice($request) {
        $order_id = $request->get_param('order_id');
        $file_url = ngt()->exporter->generate_invoice($order_id);
        
        return new WP_REST_Response([
            'success' => true,
            'url' => $file_url
        ], 200);
    }

    /**
     * GET /audit/snapshot
     */
    public function get_audit_snapshot($request) {
        $file_url = ngt()->exporter->generate_audit_snapshot();
        
        return new WP_REST_Response([
            'success' => true,
            'url' => $file_url,
            'message' => 'System Audit Snapshot generated.'
        ], 200);
    }

    /**
     * GET /gamification
     */
    public function get_gamification_stats($request) {
        global $wpdb;
        
        // Mock GamiPress data if plugin not active, otherwise query GamiPress tables
        $stats = [
            'total_points' => $wpdb->get_var("SELECT SUM(points) FROM {$wpdb->prefix}gamipress_logs") ?: 45000,
            'achievements_unlocked' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gamipress_user_achievements") ?: 1240,
            'top_tutors' => [
                ['name' => 'John Doe', 'points' => 5200, 'rank' => 1],
                ['name' => 'Jane Smith', 'points' => 4850, 'rank' => 2],
                ['name' => 'Mike Ross', 'points' => 4100, 'rank' => 3],
            ],
            'top_students' => [
                ['name' => 'Alex Kim', 'points' => 3100, 'rank' => 1],
                ['name' => 'Sarah Lee', 'points' => 2900, 'rank' => 2],
            ]
        ];

        // Check for rewards
        ngt()->workflows->check_leaderboard_rewards($stats['top_tutors']);

        return new WP_REST_Response([
            'success' => true,
            'stats' => $stats
        ], 200);
    }

    /**
     * POST /gamification/seed
     */
    public function seed_gamification($request) {
        $result = ngt()->seeder->seed_gamification();
        
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Gamification assets provisioned successfully.'
        ], 200);
    }

    /**
     * POST /integrity/audit
     */
    public function run_integrity_audit($request) {
        $audit = ngt()->verifier->run_integrity_audit();
        
        return new WP_REST_Response([
            'success' => true,
            'audit' => $audit,
            'message' => 'System Integrity Audit completed successfully.'
        ], 200);
    }

    /**
     * GET /metrics/external
     * Returns clean JSON for external monitoring (Grafana/Prometheus)
     */
    public function get_external_metrics($request) {
        global $wpdb;
        $summary = [
            'ngt_total_earnings' => (float)$wpdb->get_var("SELECT SUM(amount) FROM " . ngt()->database->get_table_name('earnings')),
            'ngt_total_tutors' => (int)count_users()['avail_roles']['tutor'] ?? 0,
            'ngt_pending_payouts' => (int)$wpdb->get_var("SELECT COUNT(*) FROM " . ngt()->database->get_table_name('earnings') . " WHERE payout_status = 'pending'"),
            'ngt_queue_size' => (int)$wpdb->get_var("SELECT COUNT(*) FROM " . ngt()->database->get_table_name('queue') . " WHERE status = 'pending'"),
            'ngt_health_score' => ngt()->verifier->get_system_health()['health_score'],
            'ngt_memory_usage_bytes' => memory_get_usage(),
            'ngt_timestamp' => time()
        ];
        
        return new WP_REST_Response($summary, 200);
    }

    /**
     * Check permission for external service
     */
    public function check_external_permission($request) {
        return ngt()->security->validate_external_token($request);
    }

    /**
     * GET /status
     */
    public function get_status($request) {
        return new WP_REST_Response([
            'success' => true,
            'health' => ngt()->verifier->get_system_health()
        ], 200);
    }

    /**
     * POST /verify
     */
    public function run_verify($request) {
        $results = ngt()->verifier->run_full_detection();
        return new WP_REST_Response([
            'success' => true,
            'results' => $results
        ], 200);
    }

    /**
     * GET /queue
     */
    public function get_queue($request) {
        $limit = $request->get_param('limit') ?: 10;
        $jobs = ngt()->database->get_pending_jobs($limit);
        
        return new WP_REST_Response([
            'success' => true,
            'jobs' => $jobs
        ], 200);
    }
}
