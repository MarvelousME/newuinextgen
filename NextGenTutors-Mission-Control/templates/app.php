<?php
/**
 * Mission Control shell template.
 *
 * @package NextGenTutorsMissionControl
 *
 * @var array<string, mixed>               $snapshot
 * @var array<string, mixed>|false         $flash
 * @var array<string, mixed>               $overrides
 * @var array<int, array<string, string>>  $links
 * @var string                             $tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$state_status = (string) ( $snapshot['state']['status'] ?? 'UNKNOWN' );
$biz          = is_array( $snapshot['business'] ?? null ) ? $snapshot['business'] : [];
$company_ok   = ! empty( $biz['applied'] );
$theme_ok     = ! empty( $snapshot['theme']['ok'] );
$companion_ok = ! empty( $snapshot['companion']['active'] );
$demo_on      = ! empty( $snapshot['demo']['mode'] );
$ai_paused    = ! empty( $snapshot['ai']['paused'] );
$obs          = is_array( $snapshot['observability'] ?? null ) ? $snapshot['observability'] : [];
$cron_rows    = is_array( $obs['cron'] ?? null ) ? $obs['cron'] : [];
$hub_delegate = is_array( $obs['hub_delegation'] ?? null ) ? $obs['hub_delegation'] : [];

$tabs = [
	'status'        => __( 'Status', 'nextgentutors-mission-control' ),
	'intelligence'  => __( 'Intelligence', 'nextgentutors-mission-control' ),
	'configure'     => __( 'Configure', 'nextgentutors-mission-control' ),
	'overrides'     => __( 'Overrides', 'nextgentutors-mission-control' ),
	'map'           => __( 'Control Map', 'nextgentutors-mission-control' ),
];
?>
<div class="wrap ngtmc" data-testid="ngtmc-mission-control">
	<header class="ngtmc-hero">
		<div>
			<p class="ngtmc-eyebrow"><?php esc_html_e( 'NextGenTutors · GET ONLINE NOW', 'nextgentutors-mission-control' ); ?></p>
			<h1><?php esc_html_e( 'Mission Control', 'nextgentutors-mission-control' ); ?></h1>
			<p class="ngtmc-lede"><?php esc_html_e( 'Master panel to configure, override, repair, and orchestrate the entire system.', 'nextgentutors-mission-control' ); ?></p>
		</div>
		<div class="ngtmc-state" data-testid="ngtmc-orchestrator-state">
			<span class="ngtmc-pill ngtmc-pill--<?php echo esc_attr( strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $state_status ) ) ); ?>">
				<?php echo esc_html( $state_status ); ?>
			</span>
			<small><?php echo esc_html( (string) ( $snapshot['state']['updated_at'] ?? '' ) ); ?></small>
		</div>
	</header>

	<?php if ( is_array( $flash ) ) : ?>
		<div class="notice notice-<?php echo ! empty( $flash['ok'] ) ? 'success' : 'warning'; ?>" data-testid="ngtmc-flash">
			<p>
				<?php
				printf(
					/* translators: 1: operation, 2: ok/fail */
					esc_html__( 'Operation “%1$s” finished: %2$s', 'nextgentutors-mission-control' ),
					esc_html( (string) ( $flash['op'] ?? '' ) ),
					! empty( $flash['ok'] ) ? 'OK' : 'CHECK'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper ngtmc-tabs" data-testid="ngtmc-tabs">
		<?php foreach ( $tabs as $key => $label ) : ?>
			<a
				href="<?php echo esc_url( admin_url( 'admin.php?page=' . NGTMC_Admin::PAGE . '&tab=' . $key ) ); ?>"
				class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"
				data-testid="ngtmc-tab-<?php echo esc_attr( $key ); ?>"
			><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<?php if ( 'status' === $tab ) : ?>
		<section class="ngtmc-grid" data-testid="ngtmc-status">
			<article class="ngtmc-card <?php echo $theme_ok ? 'is-ok' : 'is-bad'; ?>">
				<h2><?php esc_html_e( 'Theme', 'nextgentutors-mission-control' ); ?></h2>
				<p data-testid="ngtmc-theme-name"><?php echo esc_html( (string) ( $snapshot['theme']['name'] ?? '' ) ); ?></p>
				<code><?php echo esc_html( (string) ( $snapshot['theme']['stylesheet'] ?? '' ) ); ?></code>
			</article>
			<article class="ngtmc-card <?php echo $companion_ok ? 'is-ok' : 'is-bad'; ?>">
				<h2><?php esc_html_e( 'Companion', 'nextgentutors-mission-control' ); ?></h2>
				<p data-testid="ngtmc-companion-version"><?php echo $companion_ok ? 'v' . esc_html( (string) $snapshot['companion']['version'] ) : esc_html__( 'Inactive', 'nextgentutors-mission-control' ); ?></p>
			</article>
			<article class="ngtmc-card <?php echo $company_ok ? 'is-ok' : 'is-bad'; ?>">
				<h2><?php esc_html_e( 'Business Profile', 'nextgentutors-mission-control' ); ?></h2>
				<p data-testid="ngtmc-business-applied"><?php echo $company_ok ? 'APPLIED' : 'MISSING'; ?></p>
				<small><?php echo esc_html( (string) ( $biz['email'] ?? '' ) ); ?> · <?php echo esc_html( (string) ( $biz['phone'] ?? '' ) ); ?></small>
			</article>
			<article class="ngtmc-card">
				<h2><?php esc_html_e( 'Demo mode', 'nextgentutors-mission-control' ); ?></h2>
				<p data-testid="ngtmc-demo-mode"><?php echo $demo_on ? 'ON' : 'OFF'; ?></p>
			</article>
			<article class="ngtmc-card <?php echo $ai_paused ? 'is-warn' : 'is-ok'; ?>">
				<h2><?php esc_html_e( 'AI bridge', 'nextgentutors-mission-control' ); ?></h2>
				<p data-testid="ngtmc-ai-state">
					<?php
					if ( empty( $snapshot['ai']['plugin'] ) ) {
						esc_html_e( 'Not installed', 'nextgentutors-mission-control' );
					} else {
						echo ! empty( $snapshot['ai']['enabled'] ) ? 'ENABLED' : 'DISABLED';
						echo $ai_paused ? ' · PAUSED' : '';
					}
					?>
				</p>
			</article>
			<article class="ngtmc-card">
				<h2><?php esc_html_e( 'Maintenance', 'nextgentutors-mission-control' ); ?></h2>
				<p data-testid="ngtmc-maintenance"><?php echo ! empty( $overrides['maintenance_mode'] ) ? 'ON' : 'OFF'; ?></p>
			</article>
		</section>

		<section class="ngtmc-panel">
			<h2><?php esc_html_e( 'Plugin matrix', 'nextgentutors-mission-control' ); ?></h2>
			<table class="widefat striped" data-testid="ngtmc-plugin-matrix">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Package', 'nextgentutors-mission-control' ); ?></th>
						<th><?php esc_html_e( 'Installed', 'nextgentutors-mission-control' ); ?></th>
						<th><?php esc_html_e( 'Active', 'nextgentutors-mission-control' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( (array) ( $snapshot['plugins'] ?? [] ) as $row ) : ?>
						<tr>
							<td><?php echo esc_html( (string) ( $row['label'] ?? '' ) ); ?></td>
							<td><?php echo ! empty( $row['installed'] ) ? 'YES' : 'NO'; ?></td>
							<td><?php echo ! empty( $row['active'] ) ? 'YES' : 'NO'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<?php if ( ! empty( $obs ) ) : ?>
		<section class="ngtmc-panel" data-testid="ngtmc-observability">
			<h2><?php esc_html_e( 'Observability & cron queues', 'nextgentutors-mission-control' ); ?></h2>
			<?php if ( ! empty( $hub_delegate ) ) : ?>
				<p class="ngtmc-meta" data-testid="ngtmc-hub-delegation">
					<?php
					printf(
						/* translators: 1: REST namespace, 2: hub payout cron state */
						esc_html__( 'Hub REST: %1$s · Hub payout cron: %2$s', 'nextgentutors-mission-control' ),
						esc_html( (string) ( $hub_delegate['rest_namespace'] ?? 'n/a' ) ),
						! empty( $hub_delegate['hub_payout_cron'] ) ? esc_html__( 'ACTIVE (should be off)', 'nextgentutors-mission-control' ) : esc_html__( 'off', 'nextgentutors-mission-control' )
					);
					?>
				</p>
			<?php endif; ?>
			<table class="widefat striped" data-testid="ngtmc-cron-matrix">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Cron hook', 'nextgentutors-mission-control' ); ?></th>
						<th><?php esc_html_e( 'Label', 'nextgentutors-mission-control' ); ?></th>
						<th><?php esc_html_e( 'Scheduled', 'nextgentutors-mission-control' ); ?></th>
						<th><?php esc_html_e( 'Next run (UTC)', 'nextgentutors-mission-control' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $cron_rows as $row ) : ?>
						<?php
						$warn = ( 'warn' === ( $row['delegation'] ?? '' ) && ! empty( $row['scheduled'] ) );
						?>
						<tr class="<?php echo $warn ? 'ngtmc-row-warn' : ''; ?>">
							<td><code><?php echo esc_html( (string) ( $row['hook'] ?? '' ) ); ?></code></td>
							<td><?php echo esc_html( (string) ( $row['label'] ?? '' ) ); ?></td>
							<td><?php echo ! empty( $row['scheduled'] ) ? 'YES' : 'NO'; ?></td>
							<td><?php echo esc_html( (string) ( $row['next_run'] ?? '—' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
		<?php endif; ?>

	<?php elseif ( 'intelligence' === $tab ) : ?>
		<?php include NGTMC_PLUGIN_DIR . 'templates/intelligence.php'; ?>

	<?php elseif ( 'configure' === $tab ) : ?>
		<section class="ngtmc-panel" data-testid="ngtmc-configure">
			<h2><?php esc_html_e( 'Orchestrate', 'nextgentutors-mission-control' ); ?></h2>
			<p><?php esc_html_e( 'Runs the same safe operations as `wp ngt system` — configure identity, repair roles/tables, optional demo seed, verify, export evidence.', 'nextgentutors-mission-control' ); ?></p>

			<div class="ngtmc-actions">
				<?php
				$actions = [
					'configure' => __( 'Configure identity', 'nextgentutors-mission-control' ),
					'repair'    => __( 'Repair stack', 'nextgentutors-mission-control' ),
					'seed'      => __( 'Seed demo data', 'nextgentutors-mission-control' ),
					'verify'    => __( 'Verify system', 'nextgentutors-mission-control' ),
					'export'    => __( 'Export report', 'nextgentutors-mission-control' ),
				];
				foreach ( $actions as $op => $label ) :
					?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ngtmc-action-form">
						<input type="hidden" name="action" value="ngtmc_action" />
						<input type="hidden" name="ngtmc_op" value="<?php echo esc_attr( $op ); ?>" />
						<input type="hidden" name="ngtmc_tab" value="configure" />
						<?php wp_nonce_field( 'ngtmc_action' ); ?>
						<button type="submit" class="button button-secondary" data-testid="ngtmc-op-<?php echo esc_attr( $op ); ?>">
							<?php echo esc_html( $label ); ?>
						</button>
					</form>
				<?php endforeach; ?>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ngtmc-pipeline" data-testid="ngtmc-pipeline">
				<input type="hidden" name="action" value="ngtmc_action" />
				<input type="hidden" name="ngtmc_op" value="pipeline" />
				<input type="hidden" name="ngtmc_tab" value="configure" />
				<?php wp_nonce_field( 'ngtmc_action' ); ?>
				<label>
					<input type="checkbox" name="ngtmc_seed" value="1" />
					<?php esc_html_e( 'Include demo seed in full pipeline', 'nextgentutors-mission-control' ); ?>
				</label>
				<button type="submit" class="button button-primary button-hero" data-testid="ngtmc-op-pipeline">
					<?php esc_html_e( 'Run full pipeline', 'nextgentutors-mission-control' ); ?>
				</button>
			</form>
		</section>

	<?php elseif ( 'overrides' === $tab ) : ?>
		<section class="ngtmc-panel" data-testid="ngtmc-overrides">
			<h2><?php esc_html_e( 'System overrides', 'nextgentutors-mission-control' ); ?></h2>
			<p><?php esc_html_e( 'Force modes across the stack. “Inherit” leaves the underlying plugin setting unchanged.', 'nextgentutors-mission-control' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ngtmc-overrides-form">
				<input type="hidden" name="action" value="ngtmc_action" />
				<input type="hidden" name="ngtmc_op" value="overrides" />
				<input type="hidden" name="ngtmc_tab" value="overrides" />
				<?php wp_nonce_field( 'ngtmc_action' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th><?php esc_html_e( 'Maintenance mode', 'nextgentutors-mission-control' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="maintenance_mode" value="1" <?php checked( ! empty( $overrides['maintenance_mode'] ) ); ?> data-testid="ngtmc-override-maintenance" />
								<?php esc_html_e( 'Block public visitors (admins still allowed)', 'nextgentutors-mission-control' ); ?>
							</label>
							<p>
								<input class="large-text" type="text" name="maintenance_message" value="<?php echo esc_attr( (string) $overrides['maintenance_message'] ); ?>" />
							</p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Demo mode', 'nextgentutors-mission-control' ); ?></th>
						<td>
							<select name="demo_mode" data-testid="ngtmc-override-demo">
								<option value="inherit" <?php selected( null === $overrides['demo_mode'] ); ?>><?php esc_html_e( 'Inherit', 'nextgentutors-mission-control' ); ?></option>
								<option value="1" <?php selected( true === $overrides['demo_mode'] ); ?>><?php esc_html_e( 'Force ON', 'nextgentutors-mission-control' ); ?></option>
								<option value="0" <?php selected( false === $overrides['demo_mode'] ); ?>><?php esc_html_e( 'Force OFF', 'nextgentutors-mission-control' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'AI enabled', 'nextgentutors-mission-control' ); ?></th>
						<td>
							<select name="ai_enabled" data-testid="ngtmc-override-ai-enabled">
								<option value="inherit" <?php selected( null === $overrides['ai_enabled'] ); ?>><?php esc_html_e( 'Inherit', 'nextgentutors-mission-control' ); ?></option>
								<option value="1" <?php selected( true === $overrides['ai_enabled'] ); ?>><?php esc_html_e( 'Force ON', 'nextgentutors-mission-control' ); ?></option>
								<option value="0" <?php selected( false === $overrides['ai_enabled'] ); ?>><?php esc_html_e( 'Force OFF', 'nextgentutors-mission-control' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'AI global pause', 'nextgentutors-mission-control' ); ?></th>
						<td>
							<select name="ai_global_pause" data-testid="ngtmc-override-ai-pause">
								<option value="inherit" <?php selected( null === $overrides['ai_global_pause'] ); ?>><?php esc_html_e( 'Inherit', 'nextgentutors-mission-control' ); ?></option>
								<option value="1" <?php selected( true === $overrides['ai_global_pause'] ); ?>><?php esc_html_e( 'Force PAUSED', 'nextgentutors-mission-control' ); ?></option>
								<option value="0" <?php selected( false === $overrides['ai_global_pause'] ); ?>><?php esc_html_e( 'Force RESUME', 'nextgentutors-mission-control' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Suppress public booking CTAs', 'nextgentutors-mission-control' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="suppress_public_booking" value="1" <?php checked( ! empty( $overrides['suppress_public_booking'] ) ); ?> data-testid="ngtmc-override-booking" />
								<?php esc_html_e( 'Adds body class for CSS/JS suppression', 'nextgentutors-mission-control' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Force support email', 'nextgentutors-mission-control' ); ?></th>
						<td>
							<input type="email" class="regular-text" name="force_support_email" value="<?php echo esc_attr( (string) $overrides['force_support_email'] ); ?>" data-testid="ngtmc-override-email" placeholder="support@nextgentutors.co.za" />
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Force support phone', 'nextgentutors-mission-control' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="force_support_phone" value="<?php echo esc_attr( (string) $overrides['force_support_phone'] ); ?>" data-testid="ngtmc-override-phone" placeholder="0813340625" />
						</td>
					</tr>
				</table>

				<p>
					<button type="submit" class="button button-primary" data-testid="ngtmc-overrides-save">
						<?php esc_html_e( 'Save overrides', 'nextgentutors-mission-control' ); ?>
					</button>
				</p>
			</form>
		</section>

	<?php else : ?>
		<section class="ngtmc-map" data-testid="ngtmc-control-map">
			<h2><?php esc_html_e( 'Control map', 'nextgentutors-mission-control' ); ?></h2>
			<p><?php esc_html_e( 'Deep-links into existing specialist consoles — Mission Control does not duplicate their UIs.', 'nextgentutors-mission-control' ); ?></p>
			<div class="ngtmc-map-grid">
				<?php foreach ( $links as $link ) : ?>
					<a class="ngtmc-map-card" href="<?php echo esc_url( $link['url'] ); ?>" data-testid="<?php echo esc_attr( $link['testid'] ); ?>">
						<strong><?php echo esc_html( $link['label'] ); ?></strong>
						<span><?php echo esc_html( $link['desc'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</div>
