<?php
/**
 * P2 extended views: graph, repair, diagnostics, activation, config, exceptions, about, queue.
 *
 * @package NextGenCorePluginManager
 *
 * @var array{nodes: array<int, array<string, string>>, edges: array<int, array{from: string, to: string}>} $graph
 * @var array<int, array<string, mixed>> $repair
 * @var array<int, array<string, mixed>> $diagnostics
 * @var array<int, array<string, mixed>> $queue_plan
 * @var array<int, array<string, mixed>> $inactive
 * @var array<int, array<string, mixed>> $config_hub
 * @var array<int, array<string, mixed>> $exceptions
 * @var bool $readonly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$graph       = $graph ?? [ 'nodes' => [], 'edges' => [] ];
$repair      = $repair ?? [];
$diagnostics = $diagnostics ?? [];
$queue_plan  = $queue_plan ?? [];
$inactive    = $inactive ?? [];
$config_hub  = $config_hub ?? [];
$exceptions  = $exceptions ?? [];
?>
<!-- VIEW: Install Queue (full page) -->
<section class="ngcpm-view" id="ngcpm-view-queue" data-view="queue" hidden>
	<div class="ngcpm-page-head">
		<h1><?php esc_html_e( 'Install Queue', 'nextgentutors-plugin-manager' ); ?></h1>
		<p><?php esc_html_e( 'Sequential install and activate — one plugin per request.', 'nextgentutors-plugin-manager' ); ?></p>
		<?php if ( ! $readonly ) : ?>
			<button type="button" class="ngcpm-btn ngcpm-btn--primary ngcpm-btn--sm" data-action="run-sequential-queue"><?php esc_html_e( 'Start queue', 'nextgentutors-plugin-manager' ); ?></button>
		<?php endif; ?>
	</div>
	<?php include NGCPM_PLUGIN_DIR . 'templates/partials/local-packages.php'; ?>
	<p class="ngcpm-queue-page__summary" data-queue-summary></p>
	<ul class="ngcpm-queue-page" data-queue-page-list>
		<?php if ( empty( $queue_plan ) ) : ?>
			<li class="ngcpm-empty"><?php esc_html_e( 'All plugins are active. Nothing to queue.', 'nextgentutors-plugin-manager' ); ?></li>
		<?php else : ?>
			<?php foreach ( $queue_plan as $item ) : ?>
				<li class="ngcpm-queue-page__item" data-queue-slug="<?php echo esc_attr( $item['slug'] ); ?>" data-queue-action="<?php echo esc_attr( $item['action'] ); ?>">
					<strong><?php echo esc_html( $item['name'] ); ?></strong>
					<span class="<?php echo esc_attr( NGCPM_UI::badge_class( $item['status'] ?? 'queued' ) ); ?>"><?php echo esc_html( strtoupper( (string) ( $item['action'] ?? 'queued' ) ) ); ?></span>
					<?php if ( ! empty( $item['message'] ) ) : ?>
						<p class="ngcpm-queue-page__msg"><?php echo esc_html( $item['message'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>
</section>

<!-- VIEW: Dependency Graph -->
<section class="ngcpm-view" id="ngcpm-view-graph" data-view="graph" hidden>
	<div class="ngcpm-page-head">
		<h1><?php esc_html_e( 'Dependency Graph', 'nextgentutors-plugin-manager' ); ?></h1>
		<p><?php esc_html_e( 'Logical dependency chain for core stack plugins.', 'nextgentutors-plugin-manager' ); ?></p>
	</div>
	<div class="ngcpm-graph" role="img" aria-label="<?php esc_attr_e( 'Plugin dependency graph', 'nextgentutors-plugin-manager' ); ?>">
		<?php foreach ( $graph['nodes'] as $node ) : ?>
			<div class="ngcpm-graph__node <?php echo esc_attr( NGCPM_UI::map_node_class( $node['status'] ) ); ?>" data-graph-id="<?php echo esc_attr( $node['id'] ); ?>">
				<span class="ngcpm-graph__label"><?php echo esc_html( $node['label'] ); ?></span>
				<span class="<?php echo esc_attr( NGCPM_UI::badge_class( $node['status'] ) ); ?>"><?php echo esc_html( $node['status'] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
	<?php if ( ! empty( $graph['edges'] ) ) : ?>
		<ul class="ngcpm-graph-edges" aria-label="<?php esc_attr_e( 'Dependencies', 'nextgentutors-plugin-manager' ); ?>">
			<?php foreach ( $graph['edges'] as $edge ) : ?>
				<li><code><?php echo esc_html( $edge['from'] ); ?></code> → <code><?php echo esc_html( $edge['to'] ); ?></code></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>

<!-- VIEW: Repair Center -->
<section class="ngcpm-view" id="ngcpm-view-repair" data-view="repair" hidden>
	<div class="ngcpm-page-head">
		<h1><?php esc_html_e( 'Repair Center', 'nextgentutors-plugin-manager' ); ?></h1>
		<p><?php esc_html_e( 'Guided fixes with preview before execution.', 'nextgentutors-plugin-manager' ); ?></p>
		<?php if ( ! $readonly && ! empty( $repair ) ) : ?>
			<button type="button" class="ngcpm-btn ngcpm-btn--primary ngcpm-btn--sm" data-action="repair-all"><?php esc_html_e( 'Repair all auto-fixable', 'nextgentutors-plugin-manager' ); ?></button>
			<button type="button" class="ngcpm-btn ngcpm-btn--ghost ngcpm-btn--sm" data-nav="repair"><?php esc_html_e( 'View all recommendations', 'nextgentutors-plugin-manager' ); ?></button>
		<?php endif; ?>
	</div>
	<div class="ngcpm-repair-grid">
		<?php if ( empty( $repair ) ) : ?>
			<p class="ngcpm-empty"><?php esc_html_e( 'No repairable issues detected.', 'nextgentutors-plugin-manager' ); ?></p>
		<?php else : ?>
			<?php foreach ( $repair as $issue ) : ?>
				<article class="ngcpm-repair-card">
					<header>
						<h3><?php echo esc_html( $issue['title'] ); ?></h3>
						<span class="ngcpm-badge ngcpm-badge--<?php echo esc_attr( strtolower( $issue['severity'] ) ); ?>"><?php echo esc_html( $issue['severity'] ); ?></span>
					</header>
					<p class="ngcpm-repair-card__name"><?php echo esc_html( $issue['name'] ); ?></p>
					<p class="ngcpm-repair-card__preview"><?php echo esc_html( $issue['preview'] ); ?></p>
					<p class="ngcpm-repair-card__confidence"><?php echo esc_html( sprintf( __( 'Confidence: %d%%', 'nextgentutors-plugin-manager' ), (int) $issue['confidence'] ) ); ?></p>
					<?php if ( ! $readonly && ! empty( $issue['can_execute'] ) ) : ?>
						<button type="button" class="ngcpm-btn ngcpm-btn--secondary ngcpm-btn--sm" data-action="repair-one" data-slug="<?php echo esc_attr( $issue['slug'] ); ?>" data-strategy="<?php echo esc_attr( $issue['strategy'] ); ?>"><?php esc_html_e( 'Run repair', 'nextgentutors-plugin-manager' ); ?></button>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</section>

<!-- VIEW: Diagnostics -->
<section class="ngcpm-view" id="ngcpm-view-diagnostics" data-view="diagnostics" hidden>
	<div class="ngcpm-page-head">
		<h1><?php esc_html_e( 'Diagnostics', 'nextgentutors-plugin-manager' ); ?></h1>
		<?php if ( ! $readonly ) : ?>
			<button type="button" class="ngcpm-btn ngcpm-btn--ghost ngcpm-btn--sm" data-action="refresh-diagnostics"><?php esc_html_e( 'Re-run probes', 'nextgentutors-plugin-manager' ); ?></button>
			<button type="button" class="ngcpm-btn ngcpm-btn--secondary ngcpm-btn--sm" data-action="cookie-probe"><?php esc_html_e( 'Run cookie probe', 'nextgentutors-plugin-manager' ); ?></button>
		<?php endif; ?>
	</div>
	<ul class="ngcpm-diagnostics" data-diagnostics-list>
		<?php if ( empty( $diagnostics ) ) : ?>
			<li class="ngcpm-empty" data-diagnostics-placeholder><?php esc_html_e( 'Diagnostics load on demand. Open this view or click Re-run probes.', 'nextgentutors-plugin-manager' ); ?></li>
		<?php else : ?>
			<?php foreach ( $diagnostics as $check ) : ?>
				<li class="ngcpm-diagnostics__row ngcpm-diagnostics__row--<?php echo esc_attr( strtolower( $check['status'] ) ); ?>">
					<div class="ngcpm-diagnostics__head">
						<strong><?php echo esc_html( $check['name'] ); ?></strong>
						<span class="ngcpm-badge ngcpm-badge--<?php echo esc_attr( strtolower( $check['status'] ) ); ?>"><?php echo esc_html( $check['status'] ); ?></span>
					</div>
					<p class="ngcpm-diagnostics__evidence"><?php echo esc_html( $check['evidence'] ); ?></p>
					<?php if ( ! empty( $check['recommendation'] ) ) : ?>
						<p class="ngcpm-diagnostics__rec"><?php echo esc_html( $check['recommendation'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>
</section>

<!-- VIEW: Activation Manager -->
<section class="ngcpm-view" id="ngcpm-view-activation" data-view="activation" hidden>
	<div class="ngcpm-page-head">
		<h1><?php esc_html_e( 'Activation Manager', 'nextgentutors-plugin-manager' ); ?></h1>
		<p><?php esc_html_e( 'Installed plugins that are not yet active.', 'nextgentutors-plugin-manager' ); ?></p>
		<?php if ( ! $readonly && ! empty( $inactive ) ) : ?>
			<button type="button" class="ngcpm-btn ngcpm-btn--primary ngcpm-btn--sm" data-action="activate-all"><?php esc_html_e( 'Activate all', 'nextgentutors-plugin-manager' ); ?></button>
		<?php endif; ?>
	</div>
	<ul class="ngcpm-activation-list">
		<?php if ( empty( $inactive ) ) : ?>
			<li class="ngcpm-empty"><?php esc_html_e( 'No inactive plugins.', 'nextgentutors-plugin-manager' ); ?></li>
		<?php else : ?>
			<?php foreach ( $inactive as $row ) : ?>
				<li class="ngcpm-activation-list__item">
					<div>
						<strong><?php echo esc_html( $row['name'] ); ?></strong>
						<?php if ( ! empty( $row['version'] ) ) : ?>
							<span class="ngcpm-muted">v<?php echo esc_html( $row['version'] ); ?></span>
						<?php endif; ?>
					</div>
					<span class="<?php echo esc_attr( NGCPM_UI::badge_class( $row['status'] ) ); ?>"><?php echo esc_html( $row['status'] ); ?></span>
					<?php if ( ! $readonly ) : ?>
						<button type="button" class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--secondary" data-action="activate" data-slug="<?php echo esc_attr( $row['slug'] ); ?>"><?php esc_html_e( 'Activate', 'nextgentutors-plugin-manager' ); ?></button>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>
</section>

<!-- VIEW: Plugin Configuration -->
<section class="ngcpm-view" id="ngcpm-view-configuration" data-view="configuration" hidden>
	<div class="ngcpm-page-head">
		<h1><?php esc_html_e( 'Plugin Configuration', 'nextgentutors-plugin-manager' ); ?></h1>
		<p><?php esc_html_e( 'Quick links to active plugin setup screens.', 'nextgentutors-plugin-manager' ); ?></p>
	</div>
	<ul class="ngcpm-config-hub">
		<?php if ( empty( $config_hub ) ) : ?>
			<li class="ngcpm-empty"><?php esc_html_e( 'Activate plugins to see configuration links.', 'nextgentutors-plugin-manager' ); ?></li>
		<?php else : ?>
			<?php foreach ( $config_hub as $row ) : ?>
				<li class="ngcpm-config-hub__item">
					<div>
						<strong><?php echo esc_html( $row['name'] ); ?></strong>
						<?php if ( ! empty( $row['notes'] ) ) : ?>
							<p class="ngcpm-muted"><?php echo esc_html( $row['notes'] ); ?></p>
						<?php endif; ?>
					</div>
					<a class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--secondary" href="<?php echo esc_url( $row['url'] ); ?>"><?php esc_html_e( 'Open setup', 'nextgentutors-plugin-manager' ); ?></a>
				</li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>
</section>

<!-- VIEW: Exception Logs -->
<section class="ngcpm-view" id="ngcpm-view-exceptions" data-view="exceptions" hidden>
	<div class="ngcpm-page-head">
		<h1><?php esc_html_e( 'Exception Logs', 'nextgentutors-plugin-manager' ); ?></h1>
		<p><?php esc_html_e( 'Errors captured by NextGen Companion when available.', 'nextgentutors-plugin-manager' ); ?></p>
	</div>
	<ul class="ngcpm-exception-list">
		<?php if ( empty( $exceptions ) ) : ?>
			<li class="ngcpm-empty"><?php esc_html_e( 'No exception log entries.', 'nextgentutors-plugin-manager' ); ?></li>
		<?php else : ?>
			<?php foreach ( $exceptions as $entry ) : ?>
				<li class="ngcpm-exception-list__item">
					<time><?php echo esc_html( $entry['time'] ?? $entry['timestamp'] ?? '' ); ?></time>
					<span class="ngcpm-badge ngcpm-badge--danger"><?php echo esc_html( $entry['type'] ?? $entry['level'] ?? 'error' ); ?></span>
					<span><?php echo esc_html( $entry['message'] ?? '' ); ?></span>
					<?php if ( ! empty( $entry['file'] ) ) : ?>
						<code class="ngcpm-exception-list__file"><?php echo esc_html( basename( (string) $entry['file'] ) ); ?>:<?php echo esc_html( (string) ( $entry['line'] ?? '' ) ); ?></code>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		<?php endif; ?>
	</ul>
</section>

<!-- VIEW: About -->
<section class="ngcpm-view" id="ngcpm-view-about" data-view="about" hidden>
	<div class="ngcpm-page-head">
		<h1><?php esc_html_e( 'About', 'nextgentutors-plugin-manager' ); ?></h1>
	</div>
	<article class="ngcpm-about">
		<p><strong><?php esc_html_e( 'NextGenTutors Plugin Manager', 'nextgentutors-plugin-manager' ); ?></strong> v<?php echo esc_html( NGCPM_VERSION ); ?></p>
		<p><?php esc_html_e( 'Enterprise dependency manager for the NextGen Tutors stack. GET ONLINE NOW.', 'nextgentutors-plugin-manager' ); ?></p>
		<ul class="ngcpm-about__meta">
			<li><?php esc_html_e( 'Rate limiting on install/batch endpoints', 'nextgentutors-plugin-manager' ); ?></li>
			<li><?php esc_html_e( 'Sequential queue processing (no atomic batch timeouts)', 'nextgentutors-plugin-manager' ); ?></li>
			<li><?php esc_html_e( 'System fonts only — no external CDN', 'nextgentutors-plugin-manager' ); ?></li>
			<li><?php esc_html_e( 'Diagnostics, repair center, dependency graph', 'nextgentutors-plugin-manager' ); ?></li>
		</ul>
		<p><a class="ngcpm-link" href="<?php echo esc_url( admin_url( 'admin.php?page=' . NGCPM_ADMIN_PAGE . '-settings' ) ); ?>"><?php esc_html_e( 'Plugin settings', 'nextgentutors-plugin-manager' ); ?></a></p>
	</article>
</section>
