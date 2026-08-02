<?php
/**
 * Agent Operations Centre — kill switches, registry, approvals, fraud/safeguarding stats.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI for the autonomous agent control plane.
 */
final class NGC_Agent_Ops_Admin {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ], 22 );
		add_action( 'admin_post_ngc_agent_ops_pause', [ __CLASS__, 'handle_pause' ] );
		add_action( 'admin_post_ngc_agent_ops_approve', [ __CLASS__, 'handle_approve' ] );
	}

	public static function register_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		add_submenu_page( function_exists('ngt_admin_parent') ? ngt_admin_parent() : 'ngt-admin',
			__( 'Agent Operations', 'nextgencompanion' ),
			__( 'Agent Operations', 'nextgencompanion' ),
			'manage_options',
			'ngc-agent-ops',
			[ __CLASS__, 'render_page' ]
		);
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status  = NGC_Agent_Control_Plane::status_summary();
		$agents  = NGC_Agent_Control_Plane::registry();
		$fraud   = class_exists( 'NGC_Fraud_Engine' ) ? NGC_Fraud_Engine::stats() : [ 'open' => 0, 'high' => 0 ];
		$safe    = class_exists( 'NGC_Safeguarding' ) ? NGC_Safeguarding::stats() : [ 'open' => 0, 'high' => 0 ];
		$pending = self::pending_approvals();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Agent Operations Centre', 'nextgencompanion' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Governed multi-agent control plane. High-impact actions require human approval. Kill switches halt all or per-agent execution.', 'nextgencompanion' ); ?></p>

			<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:16px 0">
				<div class="card" style="padding:12px"><strong><?php echo esc_html( (string) $status['registry_count'] ); ?></strong><br><?php esc_html_e( 'Registered agents', 'nextgencompanion' ); ?></div>
				<div class="card" style="padding:12px"><strong><?php echo esc_html( (string) $status['awaiting_approval'] ); ?></strong><br><?php esc_html_e( 'Awaiting approval', 'nextgencompanion' ); ?></div>
				<div class="card" style="padding:12px"><strong><?php echo esc_html( (string) $fraud['open'] ); ?></strong><br><?php esc_html_e( 'Open fraud cases', 'nextgencompanion' ); ?></div>
				<div class="card" style="padding:12px"><strong><?php echo esc_html( (string) $safe['open'] ); ?></strong><br><?php esc_html_e( 'Open safeguarding cases', 'nextgencompanion' ); ?></div>
				<div class="card" style="padding:12px;background:<?php echo $status['global_paused'] ? '#fef2f2' : '#f0fdf4'; ?>">
					<strong><?php echo $status['global_paused'] ? esc_html__( 'PAUSED', 'nextgencompanion' ) : esc_html__( 'ACTIVE', 'nextgencompanion' ); ?></strong><br>
					<?php esc_html_e( 'Global kill switch', 'nextgencompanion' ); ?>
				</div>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:24px">
				<?php wp_nonce_field( 'ngc_agent_ops_pause' ); ?>
				<input type="hidden" name="action" value="ngc_agent_ops_pause" />
				<input type="hidden" name="scope" value="global" />
				<input type="hidden" name="paused" value="<?php echo $status['global_paused'] ? '0' : '1'; ?>" />
				<?php
				submit_button(
					$status['global_paused'] ? __( 'Resume all agents', 'nextgencompanion' ) : __( 'Global kill switch — pause all', 'nextgencompanion' ),
					$status['global_paused'] ? 'primary' : 'delete',
					'submit',
					false
				);
				?>
			</form>

			<h2><?php esc_html_e( 'Agent registry', 'nextgencompanion' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Agent', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Autonomy', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Tools', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Status', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'nextgencompanion' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $agents as $id => $agent ) : ?>
						<?php $paused = NGC_Agent_Control_Plane::is_agent_paused( $id ); ?>
						<tr>
							<td><strong><?php echo esc_html( $agent['name'] ?? $id ); ?></strong><br><code><?php echo esc_html( $id ); ?></code></td>
							<td>L<?php echo esc_html( (string) ( $agent['autonomy'] ?? 0 ) ); ?></td>
							<td><small><?php echo esc_html( implode( ', ', (array) ( $agent['tools'] ?? [] ) ) ); ?></small></td>
							<td><?php echo $paused ? esc_html__( 'Paused', 'nextgencompanion' ) : esc_html__( 'Active', 'nextgencompanion' ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
									<?php wp_nonce_field( 'ngc_agent_ops_pause' ); ?>
									<input type="hidden" name="action" value="ngc_agent_ops_pause" />
									<input type="hidden" name="scope" value="agent" />
									<input type="hidden" name="agent_id" value="<?php echo esc_attr( $id ); ?>" />
									<input type="hidden" name="paused" value="<?php echo $paused ? '0' : '1'; ?>" />
									<button type="submit" class="button button-small"><?php echo $paused ? esc_html__( 'Resume', 'nextgencompanion' ) : esc_html__( 'Pause', 'nextgencompanion' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2 style="margin-top:28px"><?php esc_html_e( 'Pending approvals', 'nextgencompanion' ); ?></h2>
			<?php if ( empty( $pending ) ) : ?>
				<p><?php esc_html_e( 'No pending approval requests.', 'nextgencompanion' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead><tr><th>ID</th><th><?php esc_html_e( 'Action', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Task', 'nextgencompanion' ); ?></th><th></th></tr></thead>
					<tbody>
						<?php foreach ( $pending as $row ) : ?>
							<tr>
								<td><?php echo esc_html( (string) $row['id'] ); ?></td>
								<td><code><?php echo esc_html( $row['action_id'] ); ?></code></td>
								<td><?php echo esc_html( (string) $row['task_id'] ); ?></td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
										<?php wp_nonce_field( 'ngc_agent_ops_approve' ); ?>
										<input type="hidden" name="action" value="ngc_agent_ops_approve" />
										<input type="hidden" name="task_id" value="<?php echo esc_attr( (string) $row['task_id'] ); ?>" />
										<input type="hidden" name="approve" value="1" />
										<button class="button button-primary button-small"><?php esc_html_e( 'Approve', 'nextgencompanion' ); ?></button>
									</form>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
										<?php wp_nonce_field( 'ngc_agent_ops_approve' ); ?>
										<input type="hidden" name="action" value="ngc_agent_ops_approve" />
										<input type="hidden" name="task_id" value="<?php echo esc_attr( (string) $row['task_id'] ); ?>" />
										<input type="hidden" name="approve" value="0" />
										<button class="button button-small"><?php esc_html_e( 'Reject', 'nextgencompanion' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function pending_approvals() {
		global $wpdb;
		$table = $wpdb->prefix . 'ngc_agent_approvals';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY id DESC LIMIT 50", ARRAY_A );
		return is_array( $rows ) ? $rows : [];
	}

	public static function handle_pause() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_agent_ops_pause' );
		$paused = ! empty( $_POST['paused'] );
		if ( 'global' === ( $_POST['scope'] ?? '' ) ) {
			NGC_Agent_Control_Plane::set_global_pause( $paused );
		} else {
			NGC_Agent_Control_Plane::set_agent_pause( sanitize_key( (string) ( $_POST['agent_id'] ?? '' ) ), $paused );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-agent-ops' ) );
		exit;
	}

	public static function handle_approve() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_agent_ops_approve' );
		$task_id = (int) ( $_POST['task_id'] ?? 0 );
		$approve = ! empty( $_POST['approve'] );
		NGC_Agent_Control_Plane::decide_approval( $task_id, $approve );
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-agent-ops' ) );
		exit;
	}
}
