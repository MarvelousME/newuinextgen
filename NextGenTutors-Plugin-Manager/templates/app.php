<?php
/**
 * Enterprise app shell + multi-view dashboard.
 *
 * @package NextGenCorePluginManager
 *
 * @var array<string, array<string, mixed>> $scan
 * @var array<string, mixed>               $health
 * @var array<int, array<string, mixed>>   $steps
 * @var array<int, array<string, mixed>>   $logs
 * @var bool                               $readonly
 * @var array<int, array<string, mixed>>   $diagnostics
 * @var array<int, array<string, mixed>>   $repair
 * @var array<int, array<string, mixed>>   $queue_plan
 * @var array{nodes: array<int, array<string, string>>, edges: array<int, array{from: string, to: string}>} $graph
 * @var array<int, array<string, mixed>>   $inactive
 * @var array<int, array<string, mixed>>   $config_hub
 * @var array<int, array<string, mixed>>   $exceptions
 * @var string                             $env_label
 * @var array<int, array<string, mixed>>   $notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$readonly         = ! empty( $readonly );
$notifications    = $notifications ?? [];
$diagnostics      = $diagnostics ?? [];
$repair           = $repair ?? NGCPM_Repair::detect_issues( $scan ?? [] );
$queue_plan       = $queue_plan ?? NGCPM_Queue::build_plan( $scan ?? [] );
$graph            = $graph ?? NGCPM_UI::dependency_graph( $scan ?? [] );
$inactive         = $inactive ?? NGCPM_UI::inactive_plugins( $scan ?? [] );
$config_hub       = $config_hub ?? NGCPM_UI::configuration_hub( $scan ?? [] );
$exceptions       = $exceptions ?? NGCPM_UI::exception_logs( 30 );
$env_label        = $env_label ?? NGCPM_UI::environment_label();
$pct              = (int) ( $health['readiness_percent'] ?? 0 );
$overall          = (string) ( $health['overall_status'] ?? 'NOT_READY' );
$security_score   = NGCPM_UI::security_score( $health );
$pipeline         = NGCPM_UI::pipeline_nodes( $scan );
$verify_rows      = NGCPM_UI::verification_rows( $scan );
$health_cats      = NGCPM_UI::health_categories( $scan, $health );
$settings_url     = admin_url( 'admin.php?page=' . NGCPM_ADMIN_PAGE . '-settings' );
$integrations_ok  = (int) ( $health['required_ready'] ?? 0 );
$failed_count     = (int) ( $health['failed'] ?? 0 );
$last_scan_ts     = (int) get_option( 'ngcpm_last_scan_time', 0 );
$last_scan_label  = $last_scan_ts
	? human_time_diff( $last_scan_ts, time() ) . ' ' . __( 'ago', 'nextgentutors-plugin-manager' )
	: __( 'just now', 'nextgentutors-plugin-manager' );

$kpis = [
	[ 'key' => 'installed', 'label' => __( 'Installed', 'nextgentutors-plugin-manager' ), 'val' => $health['installed'] ?? 0 ],
	[ 'key' => 'active', 'label' => __( 'Active', 'nextgentutors-plugin-manager' ), 'val' => $health['active'] ?? 0 ],
	[ 'key' => 'missing', 'label' => __( 'Missing', 'nextgentutors-plugin-manager' ), 'val' => $health['missing'] ?? 0 ],
	[ 'key' => 'failed', 'label' => __( 'Failed', 'nextgentutors-plugin-manager' ), 'val' => $failed_count ],
	[ 'key' => 'outdated', 'label' => __( 'Outdated', 'nextgentutors-plugin-manager' ), 'val' => $health['outdated'] ?? 0 ],
	[ 'key' => 'manual', 'label' => __( 'Manual', 'nextgentutors-plugin-manager' ), 'val' => $health['manual_required'] ?? 0 ],
	[ 'key' => 'integrations', 'label' => __( 'Integrations', 'nextgentutors-plugin-manager' ), 'val' => $integrations_ok ],
	[ 'key' => 'security', 'label' => __( 'Security', 'nextgentutors-plugin-manager' ), 'val' => $security_score, 'suffix' => '/100' ],
];

$nav_sections = [
	[ 'id' => 'dashboard', 'label' => __( 'Dashboard', 'nextgentutors-plugin-manager' ), 'icon' => 'home', 'group' => 'overview' ],
	[ 'id' => 'readiness', 'label' => __( 'Readiness', 'nextgentutors-plugin-manager' ), 'icon' => 'rocket', 'group' => 'overview' ],
	[ 'id' => 'discovery', 'label' => __( 'Discovery', 'nextgentutors-plugin-manager' ), 'icon' => 'puzzle', 'group' => 'plugins' ],
	[ 'id' => 'add-plugin', 'label' => __( 'Add Plugin', 'nextgentutors-plugin-manager' ), 'icon' => 'download', 'group' => 'plugins' ],
	[ 'id' => 'missing', 'label' => __( 'Missing', 'nextgentutors-plugin-manager' ), 'icon' => 'download', 'group' => 'plugins' ],
	[ 'id' => 'queue', 'label' => __( 'Install Queue', 'nextgentutors-plugin-manager' ), 'icon' => 'layers', 'group' => 'plugins' ],
	[ 'id' => 'graph', 'label' => __( 'Dependency Graph', 'nextgentutors-plugin-manager' ), 'icon' => 'git-branch', 'group' => 'plugins' ],
	[ 'id' => 'activation', 'label' => __( 'Activation', 'nextgentutors-plugin-manager' ), 'icon' => 'power', 'group' => 'plugins' ],
	[ 'id' => 'configuration', 'label' => __( 'Configuration', 'nextgentutors-plugin-manager' ), 'icon' => 'settings', 'group' => 'plugins' ],
	[ 'id' => 'health', 'label' => __( 'System Health', 'nextgentutors-plugin-manager' ), 'icon' => 'heart', 'group' => 'health' ],
	[ 'id' => 'repair', 'label' => __( 'Repair Center', 'nextgentutors-plugin-manager' ), 'icon' => 'wrench', 'group' => 'health' ],
	[ 'id' => 'diagnostics', 'label' => __( 'Diagnostics', 'nextgentutors-plugin-manager' ), 'icon' => 'activity', 'group' => 'health' ],
	[ 'id' => 'verification', 'label' => __( 'Verification', 'nextgentutors-plugin-manager' ), 'icon' => 'shield', 'group' => 'health' ],
	[ 'id' => 'logs', 'label' => __( 'Audit Logs', 'nextgentutors-plugin-manager' ), 'icon' => 'scroll', 'group' => 'logs' ],
	[ 'id' => 'exceptions', 'label' => __( 'Exception Logs', 'nextgentutors-plugin-manager' ), 'icon' => 'scroll', 'group' => 'logs' ],
	[ 'id' => 'security', 'label' => __( 'Security', 'nextgentutors-plugin-manager' ), 'icon' => 'lock', 'group' => 'security' ],
	[ 'id' => 'export', 'label' => __( 'Export', 'nextgentutors-plugin-manager' ), 'icon' => 'export', 'group' => 'data' ],
	[ 'id' => 'about', 'label' => __( 'About', 'nextgentutors-plugin-manager' ), 'icon' => 'info', 'group' => 'data' ],
];
?>
<div id="ngcpm-app" class="ngcpm-shell ngcpm-infinity" data-readonly="<?php echo $readonly ? '1' : '0'; ?>" data-theme="light">

	<a class="ngcpm-skip" href="#ngcpm-main"><?php esc_html_e( 'Skip to content', 'nextgentutors-plugin-manager' ); ?></a>

	<div class="ngcpm-scrim" data-ngcpm-scrim hidden></div>

	<aside class="ngcpm-sidebar" id="ngcpm-sidebar" aria-label="<?php esc_attr_e( 'Main navigation', 'nextgentutors-plugin-manager' ); ?>">
		<div class="ngcpm-sidebar__brand">
			<span class="ngcpm-sidebar__logo" aria-hidden="true"><?php echo NGCPM_UI::icon( 'puzzle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<div>
				<strong>NextGenTutors</strong>
				<span class="ngcpm-sidebar__byline"><?php esc_html_e( 'GET ONLINE NOW', 'nextgentutors-plugin-manager' ); ?></span>
			</div>
		</div>
		<nav class="ngcpm-sidebar__nav">
			<?php
			$group = '';
			foreach ( $nav_sections as $item ) :
				if ( $item['group'] !== $group ) :
					$group = $item['group'];
					?>
					<p class="ngcpm-sidebar__group"><?php echo esc_html( ucfirst( $group ) ); ?></p>
				<?php endif; ?>
				<button type="button" class="ngcpm-sidebar__link <?php echo 'dashboard' === $item['id'] ? 'is-active' : ''; ?>" data-nav="<?php echo esc_attr( $item['id'] ); ?>">
					<span class="ngcpm-sidebar__icon"><?php echo NGCPM_UI::icon( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span><?php echo esc_html( $item['label'] ); ?></span>
				</button>
			<?php endforeach; ?>
			<a class="ngcpm-sidebar__link ngcpm-sidebar__link--href" href="<?php echo esc_url( $settings_url ); ?>">
				<span class="ngcpm-sidebar__icon"><?php echo NGCPM_UI::icon( 'settings' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span><?php esc_html_e( 'Settings', 'nextgentutors-plugin-manager' ); ?></span>
			</a>
		</nav>
	</aside>

	<div class="ngcpm-main-wrap">
		<header class="ngcpm-topbar">
			<button type="button" class="ngcpm-btn ngcpm-btn--icon ngcpm-topbar__menu" data-action="toggle-sidebar" aria-label="<?php esc_attr_e( 'Open menu', 'nextgentutors-plugin-manager' ); ?>">
				<?php echo NGCPM_UI::icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<div class="ngcpm-topbar__title">
				<nav class="ngcpm-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'nextgentutors-plugin-manager' ); ?>">
					<span><?php esc_html_e( 'Overview', 'nextgentutors-plugin-manager' ); ?></span>
					<span class="ngcpm-breadcrumb__sep" aria-hidden="true">/</span>
					<strong data-ngcpm-page-title><?php esc_html_e( 'Dashboard', 'nextgentutors-plugin-manager' ); ?></strong>
				</nav>
			</div>
			<div class="ngcpm-topbar__actions">
				<button type="button" class="ngcpm-btn ngcpm-btn--ghost ngcpm-btn--sm" data-action="command-palette" aria-label="<?php esc_attr_e( 'Command palette', 'nextgentutors-plugin-manager' ); ?>">
					<span class="ngcpm-kbd">⌘K</span>
				</button>
				<span class="ngcpm-env-badge"><?php echo esc_html( $env_label ); ?></span>
			</div>
		</header>

		<main id="ngcpm-main" class="ngcpm-main" aria-live="polite">

			<!-- VIEW: Dashboard -->
			<section class="ngcpm-view is-active" id="ngcpm-view-dashboard" data-view="dashboard">
				<header class="ngcpm-hero">
					<div class="ngcpm-hero__content">
						<p class="ngcpm-eyebrow"><?php esc_html_e( 'NextGenTutors Plugin Manager · GET ONLINE NOW', 'nextgentutors-plugin-manager' ); ?></p>
						<h1 class="ngcpm-display ngcpm-readiness-title"><?php esc_html_e( 'System Readiness', 'nextgentutors-plugin-manager' ); ?></h1>
						<p class="ngcpm-hero__meta">
							<?php
							printf(
								/* translators: %s: last scan time label */
								esc_html__( 'Last scan: %s', 'nextgentutors-plugin-manager' ),
								esc_html( $last_scan_label )
							);
							?>
							· <span class="ngcpm-env-badge ngcpm-env-badge--hero"><?php echo esc_html( $env_label ); ?></span>
						</p>
						<?php if ( ! $readonly ) : ?>
						<div class="ngcpm-hero__actions" role="toolbar">
							<button type="button" class="ngcpm-btn ngcpm-btn--ghost ngcpm-btn--on-dark" data-action="scan"><?php echo NGCPM_UI::icon( 'refresh' ); // phpcs:ignore ?><span><?php esc_html_e( 'Scan', 'nextgentutors-plugin-manager' ); ?></span></button>
							<button type="button" class="ngcpm-btn ngcpm-btn--ghost ngcpm-btn--on-dark" data-action="force-rescan"><?php echo NGCPM_UI::icon( 'refresh' ); // phpcs:ignore ?><span><?php esc_html_e( 'Rescan', 'nextgentutors-plugin-manager' ); ?></span></button>
							<button type="button" class="ngcpm-btn ngcpm-btn--ghost ngcpm-btn--on-dark" data-action="refresh-status"><?php echo NGCPM_UI::icon( 'activity' ); // phpcs:ignore ?><span><?php esc_html_e( 'Refresh', 'nextgentutors-plugin-manager' ); ?></span></button>
							<button type="button" class="ngcpm-btn ngcpm-btn--ghost ngcpm-btn--on-dark" data-nav="queue"><?php echo NGCPM_UI::icon( 'download' ); // phpcs:ignore ?><span><?php esc_html_e( 'Install', 'nextgentutors-plugin-manager' ); ?></span></button>
							<button type="button" class="ngcpm-btn ngcpm-btn--ghost ngcpm-btn--on-dark" data-action="install-activate-all"><?php echo NGCPM_UI::icon( 'power' ); // phpcs:ignore ?><span><?php esc_html_e( 'Fix all', 'nextgentutors-plugin-manager' ); ?></span></button>
							<button type="button" class="ngcpm-btn ngcpm-btn--ghost ngcpm-btn--on-dark" data-nav="verification"><?php echo NGCPM_UI::icon( 'shield' ); // phpcs:ignore ?><span><?php esc_html_e( 'Verify', 'nextgentutors-plugin-manager' ); ?></span></button>
							<button type="button" class="ngcpm-btn ngcpm-btn--ghost ngcpm-btn--on-dark" data-action="export"><?php echo NGCPM_UI::icon( 'export' ); // phpcs:ignore ?><span><?php esc_html_e( 'Export', 'nextgentutors-plugin-manager' ); ?></span></button>
						</div>
						<?php endif; ?>
					</div>
					<div class="ngcpm-hero__score" role="status" aria-label="<?php echo esc_attr( sprintf( __( 'Readiness %d percent, %s', 'nextgentutors-plugin-manager' ), $pct, $overall ) ); ?>">
						<svg class="ngcpm-ring" viewBox="0 0 140 140" aria-hidden="true">
							<circle cx="70" cy="70" r="58" class="ngcpm-ring__track"/>
							<circle cx="70" cy="70" r="58" class="ngcpm-ring__fill" style="stroke-dasharray: <?php echo esc_attr( ( 364 * $pct / 100 ) . ' 364' ); ?>"/>
						</svg>
						<div class="ngcpm-ring__label">
							<strong data-ngcpm-pct><?php echo esc_html( (string) $pct ); ?>%</strong>
							<span class="ngcpm-badge ngcpm-badge--<?php echo esc_attr( strtolower( $overall ) ); ?>" data-ngcpm-overall><?php echo esc_html( $overall ); ?></span>
						</div>
					</div>
				</header>

				<?php if ( ! $readonly && ! empty( $notifications ) ) : ?>
				<section class="ngcpm-notifications" aria-label="<?php esc_attr_e( 'Notifications', 'nextgentutors-plugin-manager' ); ?>" data-notifications-list>
					<?php foreach ( $notifications as $notice ) : ?>
						<article class="ngcpm-notification ngcpm-notification--<?php echo esc_attr( $notice['type'] ?? 'info' ); ?>" data-notification>
							<div class="ngcpm-notification__body">
								<strong><?php echo esc_html( $notice['title'] ?? '' ); ?></strong>
								<p><?php echo esc_html( $notice['message'] ?? '' ); ?></p>
								<?php if ( ! empty( $notice['action'] ) ) : ?>
									<?php
									$act = (string) $notice['action'];
									if ( 0 === strpos( $act, 'nav:' ) ) :
										?>
										<button type="button" class="ngcpm-link-btn" data-nav="<?php echo esc_attr( substr( $act, 4 ) ); ?>"><?php esc_html_e( 'Open details', 'nextgentutors-plugin-manager' ); ?></button>
									<?php else : ?>
										<button type="button" class="ngcpm-link-btn" data-action="<?php echo esc_attr( $act ); ?>"><?php esc_html_e( 'Open details', 'nextgentutors-plugin-manager' ); ?></button>
									<?php endif; ?>
								<?php endif; ?>
							</div>
							<button type="button" class="ngcpm-btn ngcpm-btn--icon ngcpm-notification__close" data-action="dismiss-notification" data-notice-id="<?php echo esc_attr( $notice['id'] ?? '' ); ?>" data-notice-hash="<?php echo esc_attr( $notice['hash'] ?? '' ); ?>" aria-label="<?php esc_attr_e( 'Dismiss notification', 'nextgentutors-plugin-manager' ); ?>"><?php echo NGCPM_UI::icon( 'x' ); // phpcs:ignore ?></button>
						</article>
					<?php endforeach; ?>
				</section>
				<?php endif; ?>

				<div class="ngcpm-kpi-grid" role="list">
					<?php foreach ( $kpis as $kpi ) : ?>
						<button type="button" class="ngcpm-kpi" role="listitem" data-kpi="<?php echo esc_attr( $kpi['key'] ); ?>">
							<span class="ngcpm-kpi__val"><?php echo esc_html( (string) $kpi['val'] ); ?><?php echo isset( $kpi['suffix'] ) ? esc_html( $kpi['suffix'] ) : ''; ?></span>
							<span class="ngcpm-kpi__label"><?php echo esc_html( $kpi['label'] ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>

				<div class="ngcpm-split">
					<section class="ngcpm-panel">
						<h2 class="ngcpm-panel__title"><?php esc_html_e( 'System map', 'nextgentutors-plugin-manager' ); ?></h2>
						<p class="ngcpm-panel__actions">
							<button type="button" class="ngcpm-link-btn" data-nav="graph"><?php esc_html_e( 'View full graph', 'nextgentutors-plugin-manager' ); ?></button>
						</p>
						<ol class="ngcpm-pipeline">
							<?php foreach ( $pipeline as $node ) : ?>
								<li class="ngcpm-pipeline__node <?php echo esc_attr( NGCPM_UI::map_node_class( $node['status'] ) ); ?>">
									<span class="ngcpm-pipeline__dot" aria-hidden="true"></span>
									<span class="ngcpm-pipeline__label"><?php echo esc_html( $node['label'] ); ?></span>
									<span class="<?php echo esc_attr( NGCPM_UI::badge_class( $node['status'] ) ); ?>"><?php echo esc_html( $node['status'] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ol>
					</section>
					<section class="ngcpm-panel">
						<h2 class="ngcpm-panel__title"><?php esc_html_e( 'Recent activity', 'nextgentutors-plugin-manager' ); ?></h2>
						<ul class="ngcpm-timeline">
							<?php if ( empty( $logs ) ) : ?>
								<li class="ngcpm-timeline__empty"><?php esc_html_e( 'No activity yet.', 'nextgentutors-plugin-manager' ); ?></li>
							<?php else : ?>
								<?php foreach ( array_slice( $logs, 0, 5 ) as $log ) : ?>
									<li class="ngcpm-timeline__item">
										<time class="ngcpm-timeline__time"><?php echo esc_html( $log['time'] ?? '' ); ?></time>
										<span class="ngcpm-timeline__type"><?php echo esc_html( $log['type'] ?? '' ); ?></span>
										<span class="ngcpm-timeline__msg"><?php echo esc_html( $log['message'] ?? '' ); ?></span>
									</li>
								<?php endforeach; ?>
							<?php endif; ?>
						</ul>
					</section>
				</div>

				<?php include NGCPM_PLUGIN_DIR . 'templates/partials/plugin-list.php'; ?>

				<section class="ngcpm-panel ngcpm-panel--checklist">
					<h2 class="ngcpm-panel__title"><?php esc_html_e( 'Deployment checklist', 'nextgentutors-plugin-manager' ); ?></h2>
					<p class="ngcpm-panel__actions">
						<button type="button" class="ngcpm-link-btn" data-nav="readiness"><?php esc_html_e( 'View full checklist', 'nextgentutors-plugin-manager' ); ?></button>
					</p>
					<ol class="ngcpm-stepper">
						<?php foreach ( $steps as $step ) : ?>
							<li class="ngcpm-stepper__step <?php echo ! empty( $step['done'] ) ? 'is-done' : ''; ?>">
								<span class="ngcpm-stepper__icon" aria-hidden="true"><?php echo ! empty( $step['done'] ) ? NGCPM_UI::icon( 'check' ) : ''; // phpcs:ignore ?></span>
								<div class="ngcpm-stepper__body">
									<span><?php echo esc_html( $step['label'] ); ?></span>
									<?php if ( ! empty( $step['url'] ) && ! $readonly ) : ?>
										<a class="ngcpm-link" href="<?php echo esc_url( $step['url'] ); ?>"><?php esc_html_e( 'Open setup', 'nextgentutors-plugin-manager' ); ?></a>
									<?php endif; ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
				</section>
			</section>

			<!-- VIEW: Readiness -->
			<section class="ngcpm-view" id="ngcpm-view-readiness" data-view="readiness" hidden>
				<div class="ngcpm-page-head ngcpm-page-head--readiness">
					<h1 class="ngcpm-readiness-title"><?php esc_html_e( 'System Readiness', 'nextgentutors-plugin-manager' ); ?></h1>
					<p><?php esc_html_e( 'Deployment gates must pass before go-live.', 'nextgentutors-plugin-manager' ); ?></p>
				</div>
				<div class="ngcpm-readiness-hero">
					<div class="ngcpm-readiness-hero__score"><?php echo esc_html( (string) $pct ); ?>%</div>
					<p><?php echo esc_html( $overall ); ?></p>
				</div>
				<?php
				$gate_required = (int) ( $health['required_ready'] ?? 0 ) >= (int) ( $health['required_total'] ?? 1 );
				$gate_missing  = 0 === (int) ( $health['missing'] ?? 0 );
				$gate_security = $security_score >= 80;
				$gate_ready    = 'READY' === $overall;
				$gates         = [
					[ 'label' => __( 'All required plugins active', 'nextgentutors-plugin-manager' ), 'pass' => $gate_required ],
					[ 'label' => __( 'No missing required plugins', 'nextgentutors-plugin-manager' ), 'pass' => $gate_missing ],
					[ 'label' => __( 'Security score ≥ 80', 'nextgentutors-plugin-manager' ), 'pass' => $gate_security ],
					[ 'label' => __( 'Overall health READY', 'nextgentutors-plugin-manager' ), 'pass' => $gate_ready ],
				];
				?>
				<ul class="ngcpm-gates">
					<?php foreach ( $gates as $gate ) : ?>
						<li class="ngcpm-gate <?php echo ! empty( $gate['pass'] ) ? 'is-pass' : 'is-pending'; ?>">
							<span class="ngcpm-gate__icon" aria-hidden="true"><?php echo ! empty( $gate['pass'] ) ? NGCPM_UI::icon( 'check' ) : NGCPM_UI::icon( 'activity' ); // phpcs:ignore ?></span>
							<span><?php echo esc_html( $gate['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
				<button type="button" class="ngcpm-btn ngcpm-btn--primary" <?php echo 'READY' !== $overall ? 'disabled' : ''; ?> data-action="export">
					<?php esc_html_e( 'Deploy & export report', 'nextgentutors-plugin-manager' ); ?>
				</button>
			</section>

			<!-- VIEW: Discovery -->
			<section class="ngcpm-view" id="ngcpm-view-discovery" data-view="discovery" hidden>
				<div class="ngcpm-page-head">
					<h1><?php esc_html_e( 'Plugin Discovery', 'nextgentutors-plugin-manager' ); ?></h1>
				</div>
				<div class="ngcpm-toolbar">
					<div class="ngcpm-search">
						<?php echo NGCPM_UI::icon( 'search' ); // phpcs:ignore ?>
						<input type="search" class="ngcpm-search__input" placeholder="<?php esc_attr_e( 'Search plugins…', 'nextgentutors-plugin-manager' ); ?>" data-filter-search aria-label="<?php esc_attr_e( 'Search plugins', 'nextgentutors-plugin-manager' ); ?>" />
					</div>
					<div class="ngcpm-chips" role="group" aria-label="<?php esc_attr_e( 'Filters', 'nextgentutors-plugin-manager' ); ?>">
						<button type="button" class="ngcpm-chip is-active" data-filter="all"><?php esc_html_e( 'All', 'nextgentutors-plugin-manager' ); ?></button>
						<button type="button" class="ngcpm-chip" data-filter="required"><?php esc_html_e( 'Required', 'nextgentutors-plugin-manager' ); ?></button>
						<button type="button" class="ngcpm-chip" data-filter="missing"><?php esc_html_e( 'Missing', 'nextgentutors-plugin-manager' ); ?></button>
						<button type="button" class="ngcpm-chip" data-filter="manual"><?php esc_html_e( 'Manual', 'nextgentutors-plugin-manager' ); ?></button>
					</div>
				</div>
				<?php $view_mode = 'discovery'; include NGCPM_PLUGIN_DIR . 'templates/partials/plugin-list.php'; ?>
			</section>

			<!-- VIEW: Add Plugin -->
			<section class="ngcpm-view" id="ngcpm-view-add-plugin" data-view="add-plugin" hidden>
				<div class="ngcpm-page-head">
					<h1><?php esc_html_e( 'Add Plugin', 'nextgentutors-plugin-manager' ); ?></h1>
					<p class="ngcpm-muted"><?php esc_html_e( 'Search WordPress.org or upload a .zip — same as the native Plugins screen.', 'nextgentutors-plugin-manager' ); ?></p>
				</div>
				<?php include NGCPM_PLUGIN_DIR . 'templates/partials/local-packages.php'; ?>
				<?php if ( ! $readonly ) : ?>
				<div class="ngcpm-add-plugin-grid">
					<div class="ngcpm-panel">
						<h2 class="ngcpm-panel__title"><?php esc_html_e( 'Search WordPress.org', 'nextgentutors-plugin-manager' ); ?></h2>
						<form class="ngcpm-toolbar" data-wporg-search-form onsubmit="return false;">
							<div class="ngcpm-search" style="flex:1">
								<?php echo NGCPM_UI::icon( 'search' ); // phpcs:ignore ?>
								<input type="search" class="ngcpm-search__input" name="term" placeholder="<?php esc_attr_e( 'Plugin name or keyword…', 'nextgentutors-plugin-manager' ); ?>" aria-label="<?php esc_attr_e( 'Search WordPress.org', 'nextgentutors-plugin-manager' ); ?>" />
							</div>
							<button type="submit" class="ngcpm-btn ngcpm-btn--primary ngcpm-btn--sm" data-action="search-wporg"><?php esc_html_e( 'Search', 'nextgentutors-plugin-manager' ); ?></button>
						</form>
						<div class="ngcpm-wporg-results" data-wporg-results aria-live="polite"></div>
					</div>
					<div class="ngcpm-panel">
						<h2 class="ngcpm-panel__title"><?php esc_html_e( 'Upload Plugin', 'nextgentutors-plugin-manager' ); ?></h2>
						<form data-upload-plugin-form enctype="multipart/form-data" onsubmit="return false;">
							<input type="file" name="plugin_zip" accept=".zip,application/zip" />
							<button type="button" class="ngcpm-btn ngcpm-btn--secondary ngcpm-btn--sm" data-action="upload-plugin" style="margin-top:12px"><?php esc_html_e( 'Install uploaded zip', 'nextgentutors-plugin-manager' ); ?></button>
						</form>
						<p class="ngcpm-hint"><?php esc_html_e( 'Premium zips (e.g. Ultimate Addons for Elementor) can be uploaded here. Optional plugins you do not need can be dismissed from Discovery.', 'nextgentutors-plugin-manager' ); ?></p>
					</div>
				</div>
				<?php endif; ?>
			</section>

			<!-- VIEW: Missing -->
			<section class="ngcpm-view" id="ngcpm-view-missing" data-view="missing" hidden>
				<div class="ngcpm-alert ngcpm-alert--warning">
					<strong><?php echo esc_html( sprintf( __( '%d plugins need attention', 'nextgentutors-plugin-manager' ), (int) ( $health['missing'] ?? 0 ) + (int) ( $health['manual_required'] ?? 0 ) ) ); ?></strong>
				</div>
				<?php if ( ! $readonly ) : ?>
					<div class="ngcpm-toolbar">
						<button type="button" class="ngcpm-btn ngcpm-btn--primary ngcpm-btn--sm" data-action="install-missing"><?php esc_html_e( 'Install missing', 'nextgentutors-plugin-manager' ); ?></button>
						<button type="button" class="ngcpm-btn ngcpm-btn--secondary ngcpm-btn--sm" data-action="install-activate-all"><?php esc_html_e( 'Fix all', 'nextgentutors-plugin-manager' ); ?></button>
					</div>
				<?php endif; ?>
				<?php
				$view_mode = 'missing';
				include NGCPM_PLUGIN_DIR . 'templates/partials/plugin-list.php';
				?>
			</section>

			<!-- VIEW: Health -->
			<section class="ngcpm-view" id="ngcpm-view-health" data-view="health" hidden>
				<div class="ngcpm-page-head">
					<h1><?php esc_html_e( 'System Health', 'nextgentutors-plugin-manager' ); ?></h1>
					<?php if ( ! $readonly ) : ?>
						<button type="button" class="ngcpm-btn ngcpm-btn--ghost ngcpm-btn--sm" data-action="refresh-diagnostics"><?php esc_html_e( 'Run health check', 'nextgentutors-plugin-manager' ); ?></button>
					<?php endif; ?>
				</div>
				<div class="ngcpm-health-grid">
					<?php foreach ( $health_cats as $cat ) : ?>
						<article class="ngcpm-health-card">
							<header>
								<h3><?php echo esc_html( $cat['name'] ); ?></h3>
								<span class="ngcpm-badge ngcpm-badge--<?php echo esc_attr( strtolower( str_replace( ' ', '_', $cat['status'] ) ) ); ?>"><?php echo esc_html( $cat['status'] ); ?></span>
							</header>
							<p class="ngcpm-health-card__evidence"><?php echo esc_html( $cat['evidence'] ); ?></p>
							<?php if ( ! $readonly && 'PASS' !== $cat['status'] ) : ?>
								<button type="button" class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--secondary" data-nav="repair"><?php esc_html_e( 'Repair', 'nextgentutors-plugin-manager' ); ?></button>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- VIEW: Verification -->
			<section class="ngcpm-view" id="ngcpm-view-verification" data-view="verification" hidden>
				<div class="ngcpm-page-head">
					<h1><?php esc_html_e( 'Verification Center', 'nextgentutors-plugin-manager' ); ?></h1>
					<?php if ( ! $readonly ) : ?>
						<button type="button" class="ngcpm-btn ngcpm-btn--primary ngcpm-btn--sm" data-action="verify-system"><?php esc_html_e( 'Run all', 'nextgentutors-plugin-manager' ); ?></button>
					<?php endif; ?>
				</div>
				<div class="ngcpm-table-wrap">
					<table class="ngcpm-table ngcpm-table--verify">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Feature', 'nextgentutors-plugin-manager' ); ?></th>
								<th><?php esc_html_e( 'Expected', 'nextgentutors-plugin-manager' ); ?></th>
								<th><?php esc_html_e( 'Actual', 'nextgentutors-plugin-manager' ); ?></th>
								<th><?php esc_html_e( 'Status', 'nextgentutors-plugin-manager' ); ?></th>
								<th><?php esc_html_e( 'Severity', 'nextgentutors-plugin-manager' ); ?></th>
								<th><?php esc_html_e( 'Action', 'nextgentutors-plugin-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $verify_rows as $row ) : ?>
								<tr class="ngcpm-table__row--<?php echo esc_attr( strtolower( $row['status'] ) ); ?>">
									<td><strong><?php echo esc_html( $row['feature'] ); ?></strong></td>
									<td><code><?php echo esc_html( $row['expected'] ); ?></code></td>
									<td><code><?php echo esc_html( $row['actual'] ); ?></code></td>
									<td><span class="ngcpm-badge ngcpm-badge--<?php echo esc_attr( strtolower( $row['status'] ) ); ?>"><?php echo esc_html( $row['status'] ); ?></span></td>
									<td><?php echo esc_html( $row['severity'] ); ?></td>
									<td>
										<?php if ( ! $readonly && '—' !== $row['action'] ) : ?>
											<?php if ( 'Manual' === $row['action'] ) : ?>
												<button type="button" class="ngcpm-link-btn" data-action="show-manual" data-slug="<?php echo esc_attr( $row['slug'] ); ?>"><?php echo esc_html( $row['action'] ); ?></button>
											<?php elseif ( 'Repair' === $row['action'] ) : ?>
												<button type="button" class="ngcpm-link-btn" data-nav="repair"><?php echo esc_html( $row['action'] ); ?></button>
											<?php else : ?>
												<button type="button" class="ngcpm-link-btn" data-action="install" data-slug="<?php echo esc_attr( $row['slug'] ); ?>"><?php echo esc_html( $row['action'] ); ?></button>
											<?php endif; ?>
										<?php else : ?>
											—
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>

			<!-- VIEW: Logs -->
			<section class="ngcpm-view" id="ngcpm-view-logs" data-view="logs" hidden>
				<div class="ngcpm-page-head">
					<h1><?php esc_html_e( 'Audit Logs', 'nextgentutors-plugin-manager' ); ?></h1>
					<?php if ( ! $readonly ) : ?>
						<button type="button" class="ngcpm-btn ngcpm-btn--ghost ngcpm-btn--sm" data-action="export-logs"><?php esc_html_e( 'Export logs', 'nextgentutors-plugin-manager' ); ?></button>
						<button type="button" class="ngcpm-btn ngcpm-btn--secondary ngcpm-btn--sm" data-action="clear-logs"><?php esc_html_e( 'Clear logs', 'nextgentutors-plugin-manager' ); ?></button>
					<?php endif; ?>
				</div>
				<ul class="ngcpm-log-explorer">
					<?php if ( empty( $logs ) ) : ?>
						<li class="ngcpm-empty"><?php esc_html_e( 'No log entries.', 'nextgentutors-plugin-manager' ); ?></li>
					<?php else : ?>
						<?php foreach ( $logs as $log ) : ?>
							<li class="ngcpm-log-row">
								<time><?php echo esc_html( $log['time'] ?? '' ); ?></time>
								<span class="ngcpm-badge ngcpm-badge--info"><?php echo esc_html( $log['type'] ?? '' ); ?></span>
								<span><?php echo esc_html( $log['message'] ?? '' ); ?></span>
							</li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
			</section>

			<!-- VIEW: Security -->
			<section class="ngcpm-view" id="ngcpm-view-security" data-view="security" hidden>
				<div class="ngcpm-page-head">
					<h1><?php esc_html_e( 'Security Center', 'nextgentutors-plugin-manager' ); ?></h1>
				</div>
				<div class="ngcpm-security-score">
					<div class="ngcpm-ring ngcpm-ring--sm">
						<strong><?php echo esc_html( (string) $security_score ); ?></strong>
					</div>
					<p><?php esc_html_e( 'Risk score (lower exposure = higher score)', 'nextgentutors-plugin-manager' ); ?></p>
				</div>
				<ul class="ngcpm-security-list">
					<li class="ngcpm-security-item is-pass"><?php esc_html_e( 'Install actions require install_plugins capability', 'nextgentutors-plugin-manager' ); ?></li>
					<li class="ngcpm-security-item is-pass"><?php esc_html_e( 'AJAX protected with nonces', 'nextgentutors-plugin-manager' ); ?></li>
					<li class="ngcpm-security-item <?php echo NGCPM_Settings::remote_zips_enabled() ? 'is-warning' : 'is-pass'; ?>">
						<?php esc_html_e( 'Remote zip sources', 'nextgentutors-plugin-manager' ); ?>:
						<?php echo NGCPM_Settings::remote_zips_enabled() ? esc_html__( 'Enabled (whitelisted)', 'nextgentutors-plugin-manager' ) : esc_html__( 'Disabled', 'nextgentutors-plugin-manager' ); ?>
					</li>
					<li class="ngcpm-security-item is-pass"><?php esc_html_e( 'Rate limiting on install/batch AJAX', 'nextgentutors-plugin-manager' ); ?></li>
					<li class="ngcpm-security-item is-pass"><?php esc_html_e( 'Filesystem paths hidden from exports', 'nextgentutors-plugin-manager' ); ?></li>
				</ul>
			</section>

			<!-- VIEW: Export -->
			<section class="ngcpm-view" id="ngcpm-view-export" data-view="export" hidden>
				<div class="ngcpm-page-head">
					<h1><?php esc_html_e( 'Export Center', 'nextgentutors-plugin-manager' ); ?></h1>
				</div>
				<div class="ngcpm-export-grid">
					<article class="ngcpm-export-card">
						<h3><?php esc_html_e( 'Dependency report', 'nextgentutors-plugin-manager' ); ?></h3>
						<p><?php esc_html_e( 'JSON snapshot of plugin health and readiness.', 'nextgentutors-plugin-manager' ); ?></p>
						<button type="button" class="ngcpm-btn ngcpm-btn--primary" data-action="export"><?php esc_html_e( 'Download JSON', 'nextgentutors-plugin-manager' ); ?></button>
					</article>
					<article class="ngcpm-export-card">
						<h3><?php esc_html_e( 'Audit logs', 'nextgentutors-plugin-manager' ); ?></h3>
						<p><?php esc_html_e( 'Export action history as JSON.', 'nextgentutors-plugin-manager' ); ?></p>
						<button type="button" class="ngcpm-btn ngcpm-btn--secondary" data-action="export-logs"><?php esc_html_e( 'Export logs', 'nextgentutors-plugin-manager' ); ?></button>
					</article>
				</div>
			</section>

			<?php include NGCPM_PLUGIN_DIR . 'templates/partials/views-extended.php'; ?>

			<div class="ngcpm-progress" hidden aria-hidden="true">
				<div class="ngcpm-progress__track"><div class="ngcpm-progress__bar"></div></div>
				<span class="ngcpm-progress__label"></span>
			</div>
			<div class="ngcpm-toast" role="alert" hidden></div>
			<div class="ngcpm-errors" role="region" aria-label="<?php esc_attr_e( 'Errors', 'nextgentutors-plugin-manager' ); ?>" hidden></div>
		</main>
	</div>

	<?php if ( ! $readonly ) : ?>
	<nav class="ngcpm-bottom-nav" aria-label="<?php esc_attr_e( 'Mobile navigation', 'nextgentutors-plugin-manager' ); ?>">
		<button type="button" class="ngcpm-bottom-nav__item is-active" data-nav="dashboard"><?php echo NGCPM_UI::icon( 'home' ); // phpcs:ignore ?><span><?php esc_html_e( 'Home', 'nextgentutors-plugin-manager' ); ?></span></button>
		<button type="button" class="ngcpm-bottom-nav__item" data-nav="discovery"><?php echo NGCPM_UI::icon( 'puzzle' ); // phpcs:ignore ?><span><?php esc_html_e( 'Plugins', 'nextgentutors-plugin-manager' ); ?></span></button>
		<button type="button" class="ngcpm-bottom-nav__item" data-nav="health"><?php echo NGCPM_UI::icon( 'heart' ); // phpcs:ignore ?><span><?php esc_html_e( 'Health', 'nextgentutors-plugin-manager' ); ?></span></button>
		<button type="button" class="ngcpm-bottom-nav__item" data-action="toggle-drawer"><?php echo NGCPM_UI::icon( 'menu' ); // phpcs:ignore ?><span><?php esc_html_e( 'More', 'nextgentutors-plugin-manager' ); ?></span></button>
	</nav>
	<nav class="ngcpm-sticky-bar" aria-label="<?php esc_attr_e( 'Quick actions', 'nextgentutors-plugin-manager' ); ?>">
		<button type="button" class="ngcpm-btn ngcpm-btn--ghost" data-action="scan"><?php esc_html_e( 'Scan', 'nextgentutors-plugin-manager' ); ?></button>
		<button type="button" class="ngcpm-btn ngcpm-btn--primary" data-action="install-activate-all"><?php esc_html_e( 'Fix all', 'nextgentutors-plugin-manager' ); ?></button>
	</nav>
	<?php endif; ?>

	<!-- Legacy drawer removed — install queue uses full-page view (no overlap) -->
	<div class="ngcpm-queue" id="ngcpm-queue" hidden aria-hidden="true"></div>

	<?php if ( ! $readonly ) : ?>
	<button type="button" class="ngcpm-tour-launch" data-action="start-tour" aria-label="<?php esc_attr_e( 'Start setup tour', 'nextgentutors-plugin-manager' ); ?>">
		<?php echo NGCPM_UI::icon( 'info' ); // phpcs:ignore ?>
		<span><?php esc_html_e( 'Setup tour', 'nextgentutors-plugin-manager' ); ?></span>
	</button>
	<?php endif; ?>

	<!-- Command Palette -->
	<div class="ngcpm-command" id="ngcpm-command" hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Command palette', 'nextgentutors-plugin-manager' ); ?>">
		<div class="ngcpm-command__panel">
			<div class="ngcpm-command__search">
				<?php echo NGCPM_UI::icon( 'search' ); // phpcs:ignore ?>
				<input type="text" class="ngcpm-command__input" placeholder="<?php esc_attr_e( 'Search actions, plugins, pages…', 'nextgentutors-plugin-manager' ); ?>" autocomplete="off" />
			</div>
			<ul class="ngcpm-command__list" role="listbox"></ul>
			<footer class="ngcpm-command__foot">
				<span><kbd>↑↓</kbd> <?php esc_html_e( 'navigate', 'nextgentutors-plugin-manager' ); ?></span>
				<span><kbd>↵</kbd> <?php esc_html_e( 'select', 'nextgentutors-plugin-manager' ); ?></span>
				<span><kbd>esc</kbd> <?php esc_html_e( 'close', 'nextgentutors-plugin-manager' ); ?></span>
			</footer>
		</div>
	</div>
</div>
