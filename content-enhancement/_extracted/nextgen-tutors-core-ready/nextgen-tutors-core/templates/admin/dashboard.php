<?php
/**
 * NextGen Tutors High-Fidelity Dashboard
 */
$health = ngt()->verifier->get_system_health();
?>
<div class="wrap ngt-admin-wrap">
    <div class="ngt-dashboard-header">
        <h1>NextGen Tutors Control Center <span class="badge">v2.0.0</span></h1>
        <div class="ngt-quick-actions">
            <button id="refresh-dashboard" class="button"><span class="dashicons dashicons-update"></span> Refresh</button>
            <button id="generate-audit-snapshot" class="button"><span class="dashicons dashicons-media-spreadsheet"></span> Audit Snapshot</button>
            <button id="run-integrity-audit" class="button"><span class="dashicons dashicons-analytics"></span> Integrity Audit</button>
            <button id="provision-gamification" class="button"><span class="dashicons dashicons-awards"></span> Provision Gamification</button>
            <button id="run-health-check" class="button button-primary"><span class="dashicons dashicons-shield"></span> Run Health Check</button>
        </div>
    </div>

    <!-- KPI Row -->
    <div class="ngt-kpi-grid">
        <div class="ngt-card kpi-card" id="kpi-earnings">
            <div class="kpi-icon"><span class="dashicons dashicons-chart-area"></span></div>
            <div class="kpi-content">
                <span class="kpi-label">Total Earnings</span>
                <span class="kpi-value">R0.00</span>
                <span class="kpi-trend up">↑ 12%</span>
            </div>
        </div>
        <div class="ngt-card kpi-card" id="kpi-tutors">
            <div class="kpi-icon"><span class="dashicons dashicons-businessman"></span></div>
            <div class="kpi-content">
                <span class="kpi-label">Active Tutors</span>
                <span class="kpi-value">0</span>
                <span class="kpi-trend">Stable</span>
            </div>
        </div>
        <div class="ngt-card kpi-card" id="kpi-parents">
            <div class="kpi-icon"><span class="dashicons dashicons-groups"></span></div>
            <div class="kpi-content">
                <span class="kpi-label">Registered Parents</span>
                <span class="kpi-value">0</span>
                <span class="kpi-trend up">↑ 5%</span>
            </div>
        </div>
        <div class="ngt-card kpi-card" id="kpi-queue">
            <div class="kpi-icon"><span class="dashicons dashicons-clock"></span></div>
            <div class="kpi-content">
                <span class="kpi-label">Pending Jobs</span>
                <span class="kpi-value">0</span>
                <span class="kpi-trend" id="queue-status">Idle</span>
            </div>
        </div>
    </div>

    <div class="ngt-main-grid">
        <!-- Main Chart & Metrics -->
        <div class="ngt-column-main">
            <div class="ngt-card chart-container">
                <h3>Revenue Performance (Last 7 Days)</h3>
                <div id="ngt-revenue-chart" style="height: 300px; background: #fdfdfd; border-radius: 8px; display: flex; align-items: flex-end; justify-content: space-around; padding: 20px;">
                    <!-- Bars will be injected here by JS -->
                </div>
            </div>

            <div class="ngt-card">
                <h3>System Health & Performance Monitoring</h3>
                <div class="health-overview">
                    <div class="health-score">
                        <span class="score-number"><?php echo $health['health_score']; ?>%</span>
                        <span class="score-label">Health Score</span>
                    </div>
                    <div class="perf-metrics">
                        <div class="metric-item"><strong>Memory:</strong> <span id="perf-mem">--</span></div>
                        <div class="metric-item"><strong>Load:</strong> <span id="perf-load">--</span></div>
                        <div class="metric-item"><strong>Latency:</strong> <span id="perf-lat">--</span></div>
                    </div>
                    <div class="health-checks">
                        <?php foreach($health['checks'] as $key => $check): ?>
                            <div class="check-item status-<?php echo $check['status']; ?>">
                                <span class="dashicons dashicons-<?php echo $check['status'] === 'pass' ? 'yes' : 'warning'; ?>"></span>
                                <strong><?php echo ucfirst($key); ?>:</strong> <?php echo $check['message']; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="ngt-card gamification-section">
                <h3>Gamification & Leaderboards</h3>
                <div class="gamipress-grid">
                    <div class="leaderboard-card">
                        <h4>Top Tutors</h4>
                        <ul id="tutor-leaderboard" class="ngt-leaderboard">
                            <!-- Injected by JS -->
                        </ul>
                    </div>
                    <div class="leaderboard-card">
                        <h4>Top Students</h4>
                        <ul id="student-leaderboard" class="ngt-leaderboard">
                            <!-- Injected by JS -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: Activity & Logs -->
        <div class="ngt-column-sidebar">
            <div class="ngt-card plugin-shortcuts">
                <h3>Integrated Plugin Shortcuts</h3>
                <div class="shortcut-grid">
                    <a href="<?php echo admin_url('admin.php?page=wpamelia-dashboard'); ?>" class="shortcut-btn amelia">
                        <span class="dashicons dashicons-calendar-alt"></span> Amelia
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=fluentcrm-admin'); ?>" class="shortcut-btn fluentcrm">
                        <span class="dashicons dashicons-email-alt"></span> FluentCRM
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=wc-settings'); ?>" class="shortcut-btn woo">
                        <span class="dashicons dashicons-cart"></span> WooCommerce
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=automatorwp_automation'); ?>" class="shortcut-btn automator">
                        <span class="dashicons dashicons-performance"></span> AutomatorWP
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=ngt-settings'); ?>" class="shortcut-btn settings">
                        <span class="dashicons dashicons-admin-settings"></span> Core Settings
                    </a>
                </div>
            </div>

            <div class="ngt-card monitoring-integration">
                <h3>Monitoring Integration</h3>
                <p class="ngt-help-text">Use these credentials to connect <strong>Grafana</strong> or <strong>Prometheus</strong>.</p>
                <div class="integration-item">
                    <label>Metrics URL:</label>
                    <input type="text" readonly value="<?php echo esc_url(rest_url('ngt/v1/metrics/external')); ?>" class="ngt-input-copy">
                </div>
                <div class="integration-item">
                    <label>Bearer Token:</label>
                    <input type="text" readonly value="<?php echo esc_attr(get_option('ngt_external_metrics_token')); ?>" class="ngt-input-copy">
                </div>
            </div>

            <div class="ngt-card activity-feed">
                <h3>Real-time Activity Feed</h3>
                <ul id="ngt-activity-list">
                    <li class="loading">Loading activities...</li>
                </ul>
            </div>

            <div class="ngt-card log-viewer-mini">
                <h3>Critical Error Logs</h3>
                <div class="mini-log-container" id="ngt-error-logs">
                    <!-- Logs injected here -->
                </div>
                <a href="<?php echo admin_url('admin.php?page=ngt-logs'); ?>" class="view-all">View All Logs</a>
            </div>
        </div>
    </div>
</div>

