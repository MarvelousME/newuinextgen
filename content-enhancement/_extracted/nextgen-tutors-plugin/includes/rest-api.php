<?php
/**
 * REST API routes for NextGen Tutors plugin.
 */
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    // Event ingestion (used by internal code and external LMS webhooks).
    register_rest_route('ngt/v1', '/event', [
        'methods'  => 'POST',
        'callback' => 'ngt_api_handle_event',
        'permission_callback' => function () {
            // Allow internal calls (no auth) and external webhook with secret token.
            $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
            $expected = get_option('ngt_webhook_secret', '');
            return current_user_can('ngt_manage') || (!empty($token) && hash_equals($expected, $token));
        },
    ]);

    // Feature toggles CRUD.
    register_rest_route('ngt/v1', '/features', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ngt_api_get_features',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);
    register_rest_route('ngt/v1', '/features', [
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'ngt_api_update_features',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);

    // Health check endpoint.
    register_rest_route('ngt/v1', '/health', [
        'methods'  => 'GET',
        'callback' => 'ngt_api_health_check',
        'permission_callback' => '__return_true',
    ]);

    // Prometheus metrics – internal only.
    register_rest_route('ngt/v1', '/metrics', [
        'methods'  => 'GET',
        'callback' => 'ngt_api_metrics',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);

    // Server‑Sent Events endpoint (handled in includes/sse.php).
    register_rest_route('ngt/v1', '/stream', [
        'methods'  => 'GET',
        'callback' => 'ngt_sse_stream',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);

    // Run Payout Batch
    register_rest_route('ngt/v1', '/run-payout-batch', [
        'methods'  => 'POST',
        'callback' => 'ngt_api_run_payout_batch',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);

    // Triggers CRUD
    register_rest_route('ngt/v1', '/triggers', [
        'methods'  => WP_REST_Server::READABLE,
        'callback' => 'ngt_api_get_triggers',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);
    register_rest_route('ngt/v1', '/triggers', [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => 'ngt_api_create_trigger',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);
    register_rest_route('ngt/v1', '/triggers/(?P<id>\d+)', [
        'methods'  => WP_REST_Server::EDITABLE,
        'callback' => 'ngt_api_update_trigger',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);
    register_rest_route('ngt/v1', '/triggers/(?P<id>\d+)', [
        'methods'  => WP_REST_Server::DELETABLE,
        'callback' => 'ngt_api_delete_trigger',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);

    // Workflows CRUD
    register_rest_route('ngt/v1', '/workflows', [
        'methods'  => WP_REST_Server::READABLE,
        'callback' => 'ngt_api_get_workflows',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);
    register_rest_route('ngt/v1', '/workflows', [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => 'ngt_api_create_workflow',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);
    register_rest_route('ngt/v1', '/workflows/(?P<id>\d+)', [
        'methods'  => WP_REST_Server::EDITABLE,
        'callback' => 'ngt_api_update_workflow',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);
    register_rest_route('ngt/v1', '/workflows/(?P<id>\d+)', [
        'methods'  => WP_REST_Server::DELETABLE,
        'callback' => 'ngt_api_delete_workflow',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);

    // Stats
    register_rest_route('ngt/v1', '/revenue-stats', [
        'methods'  => 'GET',
        'callback' => 'ngt_api_revenue_stats',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);
    register_rest_route('ngt/v1', '/revenue-history', [
        'methods'  => 'GET',
        'callback' => 'ngt_api_revenue_history',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);
    register_rest_route('ngt/v1', '/student-stats', [
        'methods'  => 'GET',
        'callback' => 'ngt_api_student_stats',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);

    // Plugin Management
    register_rest_route('ngt/v1', '/plugins', [
        'methods'  => 'GET',
        'callback' => 'ngt_api_get_plugins_status',
        'permission_callback' => function () { return current_user_can('ngt_manage'); },
    ]);
    register_rest_route('ngt/v1', '/plugins/install', [
        'methods'  => 'POST',
        'callback' => 'ngt_api_install_plugin',
        'permission_callback' => function () { return current_user_can('install_plugins'); },
    ]);
    ]);
});

/**
 * Handle inbound event payloads.
 */
function ngt_api_handle_event(WP_REST_Request $request) {
    $payload = $request->get_json_params();
    $type = isset($payload['type']) ? sanitize_text_field($payload['type']) : 'unknown';
    $data = isset($payload['data']) ? $payload['data'] : [];
    // Log the event for analytics.
    NGT_Analytics::log_event($type, $data);
    return new WP_REST_Response(['status' => 'ok'], 200);
}

/** Get current feature toggles */
function ngt_api_get_features() {
    $features = get_option('ngt_features', []);
    return new WP_REST_Response($features, 200);
}
/** Update feature toggles */
function ngt_api_update_features(WP_REST_Request $request) {
    $new = $request->get_json_params();
    if (!is_array($new)) {
        return new WP_REST_Response(['error' => 'Invalid payload'], 400);
    }
    update_option('ngt_features', $new);
    return new WP_REST_Response(['status' => 'updated'], 200);
}
/** Health check – simple DB ping */
function ngt_api_health_check() {
    global $wpdb;
    $test = $wpdb->get_var('SELECT 1');
    $status = ($test == 1) ? 'healthy' : 'unhealthy';
    return new WP_REST_Response(['db' => $status], 200);
}
/** Prometheus metrics exporter */
function ngt_api_metrics() {
    header('Content-Type: text/plain; version=0.0.4');
    $metrics = NGT_Analytics::export_prometheus();
    echo $metrics;
    exit; // avoid extra WP output.
}

/** Run Payout Batch */
function ngt_api_run_payout_batch() {
    if (class_exists('NGT_Payout')) {
        $result = NGT_Payout::process_batch();
        return new WP_REST_Response($result, 200);
    }
    return new WP_REST_Response(['error' => 'Payout service not found'], 500);
}

/** Triggers API endpoints */
function ngt_api_get_triggers() {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_triggers';
    $results = $wpdb->get_results("SELECT * FROM $table WHERE deleted_at IS NULL ORDER BY priority DESC, sequence_ordinal ASC", ARRAY_A);
    return new WP_REST_Response($results, 200);
}
function ngt_api_create_trigger(WP_REST_Request $request) {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_triggers';
    $params = $request->get_json_params();
    $data = [
        'name' => sanitize_text_field($params['name'] ?? ''),
        'hook' => sanitize_text_field($params['hook'] ?? ''),
        'callback' => sanitize_text_field($params['callback'] ?? ''),
        'sequence_ordinal' => intval($params['sequence_ordinal'] ?? 0),
        'priority' => intval($params['priority'] ?? 0),
        'description' => sanitize_textarea_field($params['description'] ?? ''),
        'is_active' => intval($params['is_active'] ?? 1),
        'is_optional' => intval($params['is_optional'] ?? 0)
    ];
    $wpdb->insert($table, $data);
    return new WP_REST_Response(['id' => $wpdb->insert_id], 201);
}
function ngt_api_update_trigger(WP_REST_Request $request) {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_triggers';
    $id = intval($request->get_param('id'));
    $params = $request->get_json_params();
    $data = [];
    if (isset($params['name'])) $data['name'] = sanitize_text_field($params['name']);
    if (isset($params['hook'])) $data['hook'] = sanitize_text_field($params['hook']);
    if (isset($params['callback'])) $data['callback'] = sanitize_text_field($params['callback']);
    if (isset($params['sequence_ordinal'])) $data['sequence_ordinal'] = intval($params['sequence_ordinal']);
    if (isset($params['priority'])) $data['priority'] = intval($params['priority']);
    if (isset($params['description'])) $data['description'] = sanitize_textarea_field($params['description']);
    if (isset($params['is_active'])) $data['is_active'] = intval($params['is_active']);
    if (isset($params['is_optional'])) $data['is_optional'] = intval($params['is_optional']);
    $wpdb->update($table, $data, ['id' => $id]);
    return new WP_REST_Response(['status' => 'updated'], 200);
}
function ngt_api_delete_trigger(WP_REST_Request $request) {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_triggers';
    $id = intval($request->get_param('id'));
    $wpdb->update($table, ['deleted_at' => current_time('mysql')], ['id' => $id]);
    return new WP_REST_Response(['status' => 'deleted'], 200);
}

/** Workflows API endpoints */
function ngt_api_get_workflows() {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_workflows';
    $results = $wpdb->get_results("SELECT * FROM $table WHERE deleted_at IS NULL ORDER BY priority DESC, sequence_ordinal ASC", ARRAY_A);
    return new WP_REST_Response($results, 200);
}
function ngt_api_create_workflow(WP_REST_Request $request) {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_workflows';
    $params = $request->get_json_params();
    $data = [
        'name' => sanitize_text_field($params['name'] ?? ''),
        'steps' => wp_json_encode($params['steps'] ?? []),
        'sequence_ordinal' => intval($params['sequence_ordinal'] ?? 0),
        'priority' => intval($params['priority'] ?? 0),
        'description' => sanitize_textarea_field($params['description'] ?? ''),
        'is_active' => intval($params['is_active'] ?? 1),
        'is_optional' => intval($params['is_optional'] ?? 0)
    ];
    $wpdb->insert($table, $data);
    return new WP_REST_Response(['id' => $wpdb->insert_id], 201);
}
function ngt_api_update_workflow(WP_REST_Request $request) {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_workflows';
    $id = intval($request->get_param('id'));
    $params = $request->get_json_params();
    $data = [];
    if (isset($params['name'])) $data['name'] = sanitize_text_field($params['name']);
    if (isset($params['steps'])) $data['steps'] = wp_json_encode($params['steps']);
    if (isset($params['sequence_ordinal'])) $data['sequence_ordinal'] = intval($params['sequence_ordinal']);
    if (isset($params['priority'])) $data['priority'] = intval($params['priority']);
    if (isset($params['description'])) $data['description'] = sanitize_textarea_field($params['description']);
    if (isset($params['is_active'])) $data['is_active'] = intval($params['is_active']);
    if (isset($params['is_optional'])) $data['is_optional'] = intval($params['is_optional']);
    $wpdb->update($table, $data, ['id' => $id]);
    return new WP_REST_Response(['status' => 'updated'], 200);
}
function ngt_api_delete_workflow(WP_REST_Request $request) {
    global $wpdb;
    $table = $wpdb->prefix . 'ngt_workflows';
    $id = intval($request->get_param('id'));
    $wpdb->update($table, ['deleted_at' => current_time('mysql')], ['id' => $id]);
    return new WP_REST_Response(['status' => 'deleted'], 200);
}

/** Stats API endpoints */
function ngt_api_revenue_stats() {
    global $wpdb;
    $table_earnings = $wpdb->prefix . 'ngt_earnings';
    $table_payouts = $wpdb->prefix . 'ngt_payouts';
    
    // Placeholder logic for stats
    $total_earnings = $wpdb->get_var("SELECT SUM(amount) FROM $table_earnings") ?: 0;
    $total_payouts = $wpdb->get_var("SELECT SUM(amount) FROM $table_payouts WHERE status='processed'") ?: 0;
    
    return new WP_REST_Response([
        'totalEarnings' => floatval($total_earnings),
        'totalPayouts' => floatval($total_payouts),
        'unpaid' => floatval($total_earnings) - floatval($total_payouts)
    ], 200);
}
function ngt_api_revenue_history() {
    global $wpdb;
    $table_earnings = $wpdb->prefix . 'ngt_earnings';
    
    // Group earnings by month for the last 6 months
    $query = "
        SELECT DATE_FORMAT(earned_at, '%b') as month, SUM(amount) as total
        FROM $table_earnings
        WHERE earned_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(earned_at, '%Y-%m')
        ORDER BY earned_at ASC
    ";
    
    $results = $wpdb->get_results($query, ARRAY_A);
    $labels = [];
    $data = [];
    
    if (empty($results)) {
        // Fallback real-looking data if empty
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        $data = [0, 0, 0, 0, 0, 0];
    } else {
        foreach ($results as $row) {
            $labels[] = $row['month'];
            $data[] = floatval($row['total']);
        }
    }
    
    return new WP_REST_Response([
        'labels' => $labels,
        'data' => $data
    ], 200);
}
function ngt_api_student_stats() {
    global $wpdb;
    $table_logs = $wpdb->prefix . 'ngt_logs';
    
    $signups = $wpdb->get_var("SELECT COUNT(*) FROM $table_logs WHERE event_type='student_signup'") ?: 0;
    
    return new WP_REST_Response([
        'totalSignups' => intval($signups)
    ], 200);
}

/** Plugin Management API endpoints */
function ngt_api_get_plugins_status() {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    $required_plugins = [
        ['slug' => 'fluent-crm', 'file' => 'fluent-crm/fluent-crm.php', 'name' => 'FluentCRM', 'logo' => 'https://ps.w.org/fluent-crm/assets/icon-128x128.png'],
        ['slug' => 'fluentform', 'file' => 'fluentform/fluentform.php', 'name' => 'Fluent Forms', 'logo' => 'https://ps.w.org/fluentform/assets/icon-128x128.png'],
        ['slug' => 'masterstudy-lms-learning-management-system', 'file' => 'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php', 'name' => 'Masterstudy LMS', 'logo' => 'https://ps.w.org/masterstudy-lms-learning-management-system/assets/icon-128x128.png'],
        ['slug' => 'ameliabooking', 'file' => 'ameliabooking/ameliabooking.php', 'name' => 'Amelia Booking', 'logo' => 'https://ps.w.org/ameliabooking/assets/icon-128x128.png'],
        ['slug' => 'automatorwp', 'file' => 'automatorwp/automatorwp.php', 'name' => 'AutomatorWP', 'logo' => 'https://ps.w.org/automatorwp/assets/icon-128x128.png'],
        ['slug' => 'woocommerce', 'file' => 'woocommerce/woocommerce.php', 'name' => 'WooCommerce', 'logo' => 'https://ps.w.org/woocommerce/assets/icon-128x128.png'],
        ['slug' => 'woocommerce-gateway-payfast', 'file' => 'woocommerce-gateway-payfast/woocommerce-gateway-payfast.php', 'name' => 'PayFast', 'logo' => 'https://ps.w.org/woocommerce-gateway-payfast/assets/icon-128x128.png'],
        ['slug' => 'gamipress', 'file' => 'gamipress/gamipress.php', 'name' => 'GamiPress', 'logo' => 'https://ps.w.org/gamipress/assets/icon-128x128.png'],
        ['slug' => 'amelia-notifier', 'file' => 'amelia-notifier/amelia-notifier.php', 'name' => 'Amelia Notifier', 'logo' => '']
    ];
    
    $installed_plugins = get_plugins();
    $status = [];
    
    foreach ($required_plugins as $plugin) {
        $is_installed = array_key_exists($plugin['file'], $installed_plugins);
        $is_active = is_plugin_active($plugin['file']);
        
        $status[] = [
            'slug' => $plugin['slug'],
            'file' => $plugin['file'],
            'name' => $plugin['name'],
            'logo' => $plugin['logo'],
            'installed' => $is_installed,
            'active' => $is_active
        ];
    }
    
    return new WP_REST_Response($status, 200);
}

function ngt_api_install_plugin(WP_REST_Request $request) {
    $slug = sanitize_text_field($request->get_param('slug'));
    $file = sanitize_text_field($request->get_param('file'));
    
    if (empty($slug) || empty($file)) {
        return new WP_REST_Response(['error' => 'Missing plugin slug or file'], 400);
    }
    
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    
    $api = plugins_api('plugin_information', ['slug' => $slug]);
    
    if (is_wp_error($api)) {
        return new WP_REST_Response(['error' => $api->get_error_message()], 500);
    }
    
    $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
    $installed = $upgrader->install($api->download_link);
    
    if (is_wp_error($installed)) {
        return new WP_REST_Response(['error' => $installed->get_error_message()], 500);
    }
    
    if ($installed) {
        $activate = activate_plugin($file);
        if (is_wp_error($activate)) {
            return new WP_REST_Response(['error' => 'Installed but failed to activate: ' . $activate->get_error_message()], 500);
        }
        return new WP_REST_Response(['status' => 'installed_and_activated'], 200);
    }
    
    return new WP_REST_Response(['error' => 'Failed to install plugin'], 500);
}
?>
