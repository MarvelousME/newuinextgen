<?php
/**
 * Intelligence tab — enterprise operational dashboard.
 *
 * @package NextGenTutorsMissionControl
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$intel_ok  = class_exists( 'NGTMC_Intelligence' ) && NGTMC_Intelligence::is_available();
$intel_view = sanitize_key( (string) ( $_GET['intel_view'] ?? 'overview' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$views = [
	'overview'  => __( 'Overview', 'nextgentutors-mission-control' ),
	'events'    => __( 'Events', 'nextgentutors-mission-control' ),
	'plugins'   => __( 'Plugins', 'nextgentutors-mission-control' ),
	'settings'  => __( 'Settings', 'nextgentutors-mission-control' ),
];
?>
<section id="ngtmc-intelligence" class="ngtmc-panel ngtmc-intel-shell" data-testid="ngtmc-intelligence">
	<nav class="ngtmc-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'nextgentutors-mission-control' ); ?>">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . NGTMC_Admin::PAGE ) ); ?>"><?php esc_html_e( 'Mission Control', 'nextgentutors-mission-control' ); ?></a>
		<span aria-hidden="true">›</span>
		<span><?php esc_html_e( 'Intelligence', 'nextgentutors-mission-control' ); ?></span>
	</nav>

	<header class="ngtmc-intel-header">
		<div>
			<h2><?php esc_html_e( 'Operational Intelligence', 'nextgentutors-mission-control' ); ?></h2>
			<p class="ngtmc-meta"><?php esc_html_e( 'Unified real-time reporting across every plugin, workflow, API, and business capability.', 'nextgentutors-mission-control' ); ?></p>
		</div>
		<div class="ngtmc-intel-toolbar">
			<button type="button" class="button button-small" id="ngtmc-intel-theme-toggle" data-testid="ngtmc-intel-theme-toggle" aria-pressed="false">
				<?php esc_html_e( 'Dark mode', 'nextgentutors-mission-control' ); ?>
			</button>
			<span class="ngtmc-intel-live" data-testid="ngtmc-intel-live">
				<?php esc_html_e( 'Live', 'nextgentutors-mission-control' ); ?>
				· <time id="ngtmc-intel-updated">—</time>
			</span>
		</div>
	</header>

	<?php if ( ! $intel_ok ) : ?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'NextGenTutors-Companion intelligence module is not active. Activate Companion and run Repair stack to create intelligence tables.', 'nextgentutors-mission-control' ); ?></p>
		</div>
	<?php else : ?>

	<nav class="ngtmc-intel-subnav" role="tablist" data-testid="ngtmc-intel-subnav">
		<?php foreach ( $views as $key => $label ) : ?>
			<a
				href="<?php echo esc_url( admin_url( 'admin.php?page=' . NGTMC_Admin::PAGE . '&tab=intelligence&intel_view=' . $key ) ); ?>"
				class="ngtmc-intel-subtab <?php echo $intel_view === $key ? 'is-active' : ''; ?>"
				data-intel-view="<?php echo esc_attr( $key ); ?>"
				data-testid="ngtmc-intel-view-<?php echo esc_attr( $key ); ?>"
				role="tab"
				aria-selected="<?php echo $intel_view === $key ? 'true' : 'false'; ?>"
			><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<div id="ngtmc-intel-drill" class="ngtmc-intel-drill" hidden data-testid="ngtmc-intel-drill">
		<button type="button" class="button-link" id="ngtmc-intel-drill-back">← <?php esc_html_e( 'Back to overview', 'nextgentutors-mission-control' ); ?></button>
		<span id="ngtmc-intel-drill-label"></span>
	</div>

	<?php if ( 'overview' === $intel_view ) : ?>
		<div class="ngtmc-intel-view" data-view="overview">
			<p class="ngtmc-meta"><?php esc_html_e( 'Drag widgets to reorder. Layout saves automatically.', 'nextgentutors-mission-control' ); ?></p>
			<div id="ngtmc-intel-widgets" class="ngtmc-intel-widgets" data-testid="ngtmc-intel-widgets">
			<div class="ngtmc-intel-widget" data-widget="brief" draggable="true">
			<div id="ngtmc-intel-brief" class="ngtmc-intel-brief" data-testid="ngtmc-intel-brief">
				<p><?php esc_html_e( 'Loading executive brief…', 'nextgentutors-mission-control' ); ?></p>
			</div>
			</div>
			<div class="ngtmc-intel-widget" data-widget="kpis" draggable="true">
			<div id="ngtmc-intel-kpis" class="ngtmc-intel-grid" data-testid="ngtmc-intel-kpis"></div>
			</div>
			<div class="ngtmc-intel-widget" data-widget="health" draggable="true">
			<div id="ngtmc-intel-health" class="ngtmc-intel-health" data-testid="ngtmc-intel-health"></div>
			</div>
			<div class="ngtmc-intel-charts ngtmc-intel-widget" data-widget="charts" draggable="true">
				<div class="ngtmc-intel-chart-wrap" data-widget="chart-bookings">
					<h4><?php esc_html_e( 'Bookings (7d)', 'nextgentutors-mission-control' ); ?></h4>
					<canvas id="ngtmc-chart-bookings"></canvas>
				</div>
				<div class="ngtmc-intel-chart-wrap" data-widget="chart-errors">
					<h4><?php esc_html_e( 'Errors (7d)', 'nextgentutors-mission-control' ); ?></h4>
					<canvas id="ngtmc-chart-errors"></canvas>
				</div>
				<div class="ngtmc-intel-chart-wrap" data-widget="chart-api">
					<h4><?php esc_html_e( 'API requests (7d)', 'nextgentutors-mission-control' ); ?></h4>
					<canvas id="ngtmc-chart-api"></canvas>
				</div>
				<div class="ngtmc-intel-chart-wrap ngtmc-intel-chart-wrap--donut" data-widget="chart-workflows">
					<h4><?php esc_html_e( 'Workflows today', 'nextgentutors-mission-control' ); ?></h4>
					<canvas id="ngtmc-chart-workflows"></canvas>
				</div>
				<div class="ngtmc-intel-chart-wrap" data-widget="chart-sankey">
					<h4><?php esc_html_e( 'Event flow (Sankey)', 'nextgentutors-mission-control' ); ?></h4>
					<canvas id="ngtmc-chart-sankey" data-testid="ngtmc-chart-sankey"></canvas>
				</div>
				<div class="ngtmc-intel-chart-wrap" data-widget="chart-network">
					<h4><?php esc_html_e( 'Plugin network', 'nextgentutors-mission-control' ); ?></h4>
					<div id="ngtmc-network-graph" class="ngtmc-network-graph" data-testid="ngtmc-network-graph"></div>
				</div>
				<div class="ngtmc-intel-chart-wrap" data-widget="chart-geo">
					<h4><?php esc_html_e( 'Regional activity (geo)', 'nextgentutors-mission-control' ); ?></h4>
					<canvas id="ngtmc-chart-geo" data-testid="ngtmc-chart-geo"></canvas>
				</div>
				<div class="ngtmc-intel-chart-wrap" data-widget="chart-radar">
					<h4><?php esc_html_e( 'Health radar', 'nextgentutors-mission-control' ); ?></h4>
					<canvas id="ngtmc-chart-radar" data-testid="ngtmc-chart-radar"></canvas>
				</div>
				<div class="ngtmc-intel-chart-wrap" data-widget="chart-funnel">
					<h4><?php esc_html_e( 'Booking funnel', 'nextgentutors-mission-control' ); ?></h4>
					<canvas id="ngtmc-chart-funnel" data-testid="ngtmc-chart-funnel"></canvas>
				</div>
			</div>
			<div class="ngtmc-intel-widget" data-widget="notifications" draggable="true">
			<h3><?php esc_html_e( 'Notification center', 'nextgentutors-mission-control' ); ?></h3>
			<div id="ngtmc-intel-notifications" class="ngtmc-intel-notifications" data-testid="ngtmc-intel-notifications"></div>
			</div>
			<div class="ngtmc-intel-widget" data-widget="ask" draggable="true">
			<div class="ngtmc-intel-ask">
				<label class="screen-reader-text" for="ngtmc-intel-ask"><?php esc_html_e( 'Ask intelligence', 'nextgentutors-mission-control' ); ?></label>
				<input type="text" id="ngtmc-intel-ask" class="regular-text" placeholder="<?php esc_attr_e( 'Why did bookings decrease? Which plugin has the most errors?', 'nextgentutors-mission-control' ); ?>" data-testid="ngtmc-intel-ask-input" />
				<button type="button" class="button button-secondary" id="ngtmc-intel-ask-btn" data-testid="ngtmc-intel-ask-btn"><?php esc_html_e( 'Ask', 'nextgentutors-mission-control' ); ?></button>
			</div>
			<p id="ngtmc-intel-ask-answer" class="ngtmc-meta" data-testid="ngtmc-intel-ask-answer"></p>
			</div>
			</div>
		</div>

	<?php elseif ( 'events' === $intel_view ) : ?>
		<div class="ngtmc-intel-view" data-view="events">
			<div class="ngtmc-intel-filters" data-testid="ngtmc-intel-filters">
				<input type="search" id="ngtmc-intel-search" placeholder="<?php esc_attr_e( 'Search messages…', 'nextgentutors-mission-control' ); ?>" aria-label="<?php esc_attr_e( 'Search events', 'nextgentutors-mission-control' ); ?>" />
				<select id="ngtmc-intel-filter-severity" aria-label="<?php esc_attr_e( 'Filter severity', 'nextgentutors-mission-control' ); ?>">
					<option value=""><?php esc_html_e( 'All severities', 'nextgentutors-mission-control' ); ?></option>
					<option value="info">info</option>
					<option value="warning">warning</option>
					<option value="error">error</option>
					<option value="critical">critical</option>
				</select>
				<select id="ngtmc-intel-filter-plugin" aria-label="<?php esc_attr_e( 'Filter plugin', 'nextgentutors-mission-control' ); ?>">
					<option value=""><?php esc_html_e( 'All plugins', 'nextgentutors-mission-control' ); ?></option>
				</select>
				<select class="ngtmc-intel-pivot" aria-label="<?php esc_attr_e( 'Pivot by', 'nextgentutors-mission-control' ); ?>">
					<option value=""><?php esc_html_e( 'No pivot', 'nextgentutors-mission-control' ); ?></option>
					<option value="plugin_slug"><?php esc_html_e( 'Plugin', 'nextgentutors-mission-control' ); ?></option>
					<option value="severity"><?php esc_html_e( 'Severity', 'nextgentutors-mission-control' ); ?></option>
					<option value="module"><?php esc_html_e( 'Module', 'nextgentutors-mission-control' ); ?></option>
				</select>
				<button type="button" class="button" id="ngtmc-intel-export-csv" data-testid="ngtmc-intel-export"><?php esc_html_e( 'Export CSV', 'nextgentutors-mission-control' ); ?></button>
			</div>
			<div id="ngtmc-virtual-grid" class="ngtmc-virtual-grid" data-testid="ngtmc-virtual-grid">
				<div class="ngtmc-virtual-head">
					<span><?php esc_html_e( 'Time', 'nextgentutors-mission-control' ); ?></span>
					<span><?php esc_html_e( 'Event', 'nextgentutors-mission-control' ); ?></span>
					<span><?php esc_html_e( 'Plugin', 'nextgentutors-mission-control' ); ?></span>
					<span><?php esc_html_e( 'Module', 'nextgentutors-mission-control' ); ?></span>
					<span><?php esc_html_e( 'Severity', 'nextgentutors-mission-control' ); ?></span>
					<span><?php esc_html_e( 'Message', 'nextgentutors-mission-control' ); ?></span>
				</div>
				<div class="ngtmc-virtual-viewport">
					<div class="ngtmc-virtual-spacer"></div>
					<div class="ngtmc-virtual-canvas"></div>
				</div>
				<div class="ngtmc-virtual-meta ngtmc-meta"></div>
			</div>
		</div>

	<?php elseif ( 'plugins' === $intel_view ) : ?>
		<div class="ngtmc-intel-view" data-view="plugins">
			<h3><?php esc_html_e( 'Plugin health matrix', 'nextgentutors-mission-control' ); ?></h3>
			<table class="widefat striped" id="ngtmc-intel-plugin-matrix" data-testid="ngtmc-intel-plugin-matrix">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Plugin', 'nextgentutors-mission-control' ); ?></th>
						<th><?php esc_html_e( 'Version', 'nextgentutors-mission-control' ); ?></th>
						<th><?php esc_html_e( 'Status', 'nextgentutors-mission-control' ); ?></th>
						<th><?php esc_html_e( 'Features', 'nextgentutors-mission-control' ); ?></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
			<h3><?php esc_html_e( 'Cron & queues', 'nextgentutors-mission-control' ); ?></h3>
			<table class="widefat striped" id="ngtmc-intel-cron-matrix" data-testid="ngtmc-intel-cron-matrix">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Hook', 'nextgentutors-mission-control' ); ?></th>
						<th><?php esc_html_e( 'Label', 'nextgentutors-mission-control' ); ?></th>
						<th><?php esc_html_e( 'Scheduled', 'nextgentutors-mission-control' ); ?></th>
						<th><?php esc_html_e( 'Next run', 'nextgentutors-mission-control' ); ?></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>

	<?php else : ?>
		<div class="ngtmc-intel-view" data-view="settings">
			<form id="ngtmc-intel-config-form" class="ngtmc-intel-config" data-testid="ngtmc-intel-config">
				<table class="form-table" role="presentation">
					<tr>
						<th><?php esc_html_e( 'Enabled', 'nextgentutors-mission-control' ); ?></th>
						<td><label><input type="checkbox" name="enabled" value="1" /> <?php esc_html_e( 'Collect and report events', 'nextgentutors-mission-control' ); ?></label></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Retention (days)', 'nextgentutors-mission-control' ); ?></th>
						<td><input type="number" name="retention_days" min="7" max="365" class="small-text" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Refresh interval (ms)', 'nextgentutors-mission-control' ); ?></th>
						<td><input type="number" name="refresh_interval_ms" min="2000" max="60000" class="small-text" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Sampling rate', 'nextgentutors-mission-control' ); ?></th>
						<td><input type="number" name="sampling_rate" min="0.01" max="1" step="0.01" class="small-text" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Alert email', 'nextgentutors-mission-control' ); ?></th>
						<td><input type="email" name="notify_email" class="regular-text" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Teams webhook', 'nextgentutors-mission-control' ); ?></th>
						<td><input type="url" name="teams_webhook_url" class="large-text" placeholder="https://outlook.office.com/webhook/..." /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Slack webhook', 'nextgentutors-mission-control' ); ?></th>
						<td><input type="url" name="slack_webhook_url" class="large-text" placeholder="https://hooks.slack.com/services/..." /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'WhatsApp gateway URL', 'nextgentutors-mission-control' ); ?></th>
						<td><input type="url" name="whatsapp_webhook_url" class="large-text" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'SMS gateway URL', 'nextgentutors-mission-control' ); ?></th>
						<td><input type="url" name="sms_webhook_url" class="large-text" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Generic webhook', 'nextgentutors-mission-control' ); ?></th>
						<td><input type="url" name="webhook_url" class="large-text" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Mask PII', 'nextgentutors-mission-control' ); ?></th>
						<td><label><input type="checkbox" name="mask_pii" value="1" /> <?php esc_html_e( 'Mask email/phone in payloads', 'nextgentutors-mission-control' ); ?></label></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'SSE live stream', 'nextgentutors-mission-control' ); ?></th>
						<td><label><input type="checkbox" name="sse_enabled" value="1" /> <?php esc_html_e( 'Enable server-sent events', 'nextgentutors-mission-control' ); ?></label></td>
					</tr>
				</table>
				<p><button type="submit" class="button button-primary" data-testid="ngtmc-intel-config-save"><?php esc_html_e( 'Save settings', 'nextgentutors-mission-control' ); ?></button></p>
				<p id="ngtmc-intel-config-status" class="ngtmc-meta" role="status"></p>
			</form>
			<h3><?php esc_html_e( 'Intelligence audit log', 'nextgentutors-mission-control' ); ?></h3>
			<ul id="ngtmc-intel-audit" class="ngtmc-intel-audit" data-testid="ngtmc-intel-audit"></ul>
		</div>
	<?php endif; ?>

	<?php endif; ?>
</section>
