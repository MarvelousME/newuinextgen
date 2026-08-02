<?php
/**
 * Admin settings, workflow editor, and setup tools.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Admin {

	public static function register_hooks(): void {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
		add_action( 'admin_post_ngt_hub_save_workflows', [ __CLASS__, 'save_workflows' ] );
		add_action( 'admin_post_ngt_hub_repair_setup', [ __CLASS__, 'repair_setup' ] );
	}

	public static function menu(): void {
		if ( class_exists( 'NGC_Admin_Shell' ) ) {
			return; // NEXT GEN TUTORS shell owns navigation.
		}
		add_menu_page(
			__( 'NextGen Hub', 'nextgen-automation-hub' ),
			__( 'NextGen Hub', 'nextgen-automation-hub' ),
			'ngt_manage_hub',
			'ngt-hub',
			[ __CLASS__, 'render_page' ],
			'dashicons-networking',
			58
		);
	}

	public static function enqueue( string $hook ): void {
		if ( false === strpos( $hook, 'ngt-hub' ) ) {
			return;
		}
		wp_enqueue_style( 'ngt-hub-admin', NGT_HUB_URL . 'assets/css/ngt-hub.css', [], NGT_Hub::VERSION );
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'ngt_manage_hub' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'nextgen-automation-hub' ) );
		}

		$workflows = NGT_Hub_Workflows::get_workflows();
		$tab       = sanitize_key( $_GET['tab'] ?? 'workflows' );
		?>
		<div class="wrap ngt-admin">
			<h1><?php esc_html_e( 'NextGen Automation Hub', 'nextgen-automation-hub' ); ?> <small>v<?php echo esc_html( NGT_Hub::VERSION ); ?></small></h1>
			<?php
			$company = class_exists( 'NGC_Business_Profile' ) ? NGC_Business_Profile::get() : [];
			if ( ! empty( $company['company_name'] ) ) :
				?>
				<div class="notice notice-info" data-testid="ngt-hub-business-identity">
					<p>
						<strong><?php echo esc_html( (string) $company['company_name'] ); ?></strong>
						· <?php echo esc_html( (string) ( $company['email'] ?? '' ) ); ?>
						· <?php echo esc_html( (string) ( $company['phone'] ?? '' ) ); ?>
					</p>
				</div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ngt-hub&tab=workflows' ) ); ?>" class="nav-tab <?php echo 'workflows' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Workflows', 'nextgen-automation-hub' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ngt-hub&tab=system' ) ); ?>" class="nav-tab <?php echo 'system' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'System', 'nextgen-automation-hub' ); ?></a>
			</nav>

			<?php if ( 'system' === $tab ) : ?>
				<div class="ngt-card" style="margin-top:20px;padding:20px;max-width:720px">
					<h2><?php esc_html_e( 'Setup & Repair', 'nextgen-automation-hub' ); ?></h2>
					<p><?php esc_html_e( 'Re-run database migrations, seed RTM rooms, create dashboard pages, and import bundled workflows.', 'nextgen-automation-hub' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'ngt_hub_repair_setup' ); ?>
						<input type="hidden" name="action" value="ngt_hub_repair_setup" />
						<?php submit_button( __( 'Run / Repair Setup', 'nextgen-automation-hub' ) ); ?>
					</form>
					<ul style="margin-top:16px">
						<li><?php esc_html_e( 'Daily health check cron:', 'nextgen-automation-hub' ); ?> <?php echo wp_next_scheduled( NGT_Hub_Workflows::HEALTH_CRON ) ? esc_html__( 'Scheduled', 'nextgen-automation-hub' ) : esc_html__( 'Not scheduled', 'nextgen-automation-hub' ); ?></li>
						<li><?php esc_html_e( 'Monthly payout cron:', 'nextgen-automation-hub' ); ?> <?php echo wp_next_scheduled( NGT_Hub_Payouts::CRON_HOOK ) ? esc_html__( 'Scheduled', 'nextgen-automation-hub' ) : esc_html__( 'Not scheduled', 'nextgen-automation-hub' ); ?></li>
						<li><?php esc_html_e( 'Companion bridge:', 'nextgen-automation-hub' ); ?> <?php echo class_exists( 'NGC_Automation_Hub_Bridge' ) ? esc_html__( 'Active', 'nextgen-automation-hub' ) : esc_html__( 'Not detected', 'nextgen-automation-hub' ); ?></li>
					</ul>
				</div>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:20px">
					<?php wp_nonce_field( 'ngt_hub_save_workflows' ); ?>
					<input type="hidden" name="action" value="ngt_hub_save_workflows" />
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Enabled', 'nextgen-automation-hub' ); ?></th>
								<th><?php esc_html_e( 'Workflow', 'nextgen-automation-hub' ); ?></th>
								<th><?php esc_html_e( 'Trigger', 'nextgen-automation-hub' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'nextgen-automation-hub' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $workflows as $i => $wf ) : ?>
								<tr>
									<td>
										<input type="hidden" name="workflows[<?php echo esc_attr( (string) $i ); ?>][key]" value="<?php echo esc_attr( $wf['key'] ?? '' ); ?>" />
										<input type="checkbox" name="workflows[<?php echo esc_attr( (string) $i ); ?>][enabled]" value="1" <?php checked( ! empty( $wf['enabled'] ) ); ?> />
									</td>
									<td><strong><?php echo esc_html( $wf['name'] ?? $wf['key'] ?? '' ); ?></strong><br><code><?php echo esc_html( $wf['key'] ?? '' ); ?></code></td>
									<td><code><?php echo esc_html( $wf['trigger']['event'] ?? '' ); ?></code></td>
									<td><?php echo esc_html( (string) count( $wf['actions'] ?? [] ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?php submit_button( __( 'Save Workflow Toggles', 'nextgen-automation-hub' ) ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function save_workflows(): void {
		if ( ! current_user_can( 'ngt_manage_hub' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'nextgen-automation-hub' ) );
		}
		check_admin_referer( 'ngt_hub_save_workflows' );

		$existing  = NGT_Hub_Workflows::get_workflows();
		$submitted = $_POST['workflows'] ?? [];
		$map       = [];

		if ( is_array( $submitted ) ) {
			foreach ( $submitted as $row ) {
				if ( ! is_array( $row ) || empty( $row['key'] ) ) {
					continue;
				}
				$map[ sanitize_key( $row['key'] ) ] = ! empty( $row['enabled'] );
			}
		}

		foreach ( $existing as &$wf ) {
			$key = $wf['key'] ?? '';
			if ( $key && array_key_exists( $key, $map ) ) {
				$wf['enabled'] = $map[ $key ];
			}
		}
		unset( $wf );

		NGT_Hub_Workflows::save_workflows( $existing );
		wp_safe_redirect( admin_url( 'admin.php?page=ngt-hub&tab=workflows&updated=1' ) );
		exit;
	}

	public static function repair_setup(): void {
		if ( ! current_user_can( 'ngt_manage_hub' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'nextgen-automation-hub' ) );
		}
		check_admin_referer( 'ngt_hub_repair_setup' );

		NGT_Hub_Database::install();
		NGT_Hub_Data_Model::install();
		NGT_Hub_Workflows::import_bundled();
		NGT_Hub_Workflows::schedule_health_cron();
		NGT_Hub_Payouts::schedule_cron();

		wp_safe_redirect( admin_url( 'admin.php?page=ngt-hub&tab=system&repaired=1' ) );
		exit;
	}
}
