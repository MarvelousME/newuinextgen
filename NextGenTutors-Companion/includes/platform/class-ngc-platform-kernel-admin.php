<?php
/**
 * Platform admin screens — Queue / DLQ / Ledger recon / Audit.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI under NGC shell.
 */
final class NGC_Platform_Kernel_Admin {

	/**
	 * Init menus.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ], 65 );
		add_action( 'admin_post_ngc_platform_dlq_replay', [ __CLASS__, 'handle_dlq_replay' ] );
		add_action( 'admin_post_ngc_platform_recon_run', [ __CLASS__, 'handle_recon_run' ] );
		add_action( 'admin_post_ngc_platform_worm_export', [ __CLASS__, 'handle_worm_export' ] );
	}

	/**
	 * Register submenu.
	 */
	public static function menu() {
		$cap = 'ngc_manage_platform';
		if ( ! current_user_can( $cap ) && current_user_can( 'manage_options' ) ) {
			$cap = 'manage_options';
		}
		$parent = function_exists( 'ngt_admin_parent' ) ? ngt_admin_parent() : 'ngt-admin';
		add_submenu_page(
			$parent,
			'Platform Kernel',
			'Platform Kernel',
			$cap,
			'ngc-platform-kernel',
			[ __CLASS__, 'render' ]
		);
	}

	/**
	 * Render admin page.
	 */
	public static function render() {
		if ( ! NGC_Authz_Matrix::can( 'ngc_manage_platform', 'platform_admin' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		$stats = NGC_Durable_Queue::stats();
		$dlq   = NGC_Queue_DLQ::list_open( 20 );
		$recon = get_option( 'ngc_last_recon_report', [] );
		$verify = NGC_Immutable_Audit::verify();
		$auth   = NGC_Platform::authority_enabled();
		?>
		<div class="wrap">
			<h1>Enterprise Platform Kernel</h1>
			<p>Workflow authority: <strong><?php echo $auth ? 'ON' : 'OFF (legacy)'; ?></strong>
				| Tenant: <?php echo (int) NGC_Tenant_Context::id(); ?></p>

			<h2>Queue</h2>
			<pre><?php echo esc_html( wp_json_encode( $stats, JSON_PRETTY_PRINT ) ); ?></pre>

			<h2>DLQ (open)</h2>
			<table class="widefat striped">
				<thead><tr><th>ID</th><th>Queue</th><th>Reason</th><th>Attempts</th><th></th></tr></thead>
				<tbody>
				<?php if ( empty( $dlq ) ) : ?>
					<tr><td colspan="5">Empty</td></tr>
				<?php else : ?>
					<?php foreach ( $dlq as $row ) : ?>
						<tr>
							<td><?php echo (int) $row->id; ?></td>
							<td><?php echo esc_html( $row->queue_name ); ?></td>
							<td><?php echo esc_html( substr( (string) $row->reason, 0, 120 ) ); ?></td>
							<td><?php echo (int) $row->attempts; ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="ngc_platform_dlq_replay" />
									<input type="hidden" name="dlq_id" value="<?php echo (int) $row->id; ?>" />
									<?php wp_nonce_field( 'ngc_dlq_replay_' . (int) $row->id ); ?>
									<button class="button">Replay</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<h2>Reconciliation</h2>
			<pre><?php echo esc_html( wp_json_encode( $recon, JSON_PRETTY_PRINT ) ); ?></pre>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ngc_platform_recon_run" />
				<?php wp_nonce_field( 'ngc_recon_run' ); ?>
				<button class="button button-primary">Run reconciliation</button>
			</form>

			<h2>Audit chain</h2>
			<pre><?php echo esc_html( wp_json_encode( $verify, JSON_PRETTY_PRINT ) ); ?></pre>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ngc_platform_worm_export" />
				<?php wp_nonce_field( 'ngc_worm_export' ); ?>
				<button class="button">WORM export</button>
			</form>
		</div>
		<?php
	}

	/**
	 * Replay DLQ.
	 */
	public static function handle_dlq_replay() {
		if ( ! current_user_can( 'ngc_manage_platform' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Forbidden' );
		}
		$id = isset( $_POST['dlq_id'] ) ? (int) $_POST['dlq_id'] : 0;
		check_admin_referer( 'ngc_dlq_replay_' . $id );
		NGC_Queue_DLQ::replay( $id );
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-platform-kernel&replayed=1' ) );
		exit;
	}

	/**
	 * Run recon.
	 */
	public static function handle_recon_run() {
		if ( ! current_user_can( 'ngc_view_finance' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'ngc_recon_run' );
		NGC_Reconciliation::run( [] );
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-platform-kernel&recon=1' ) );
		exit;
	}

	/**
	 * WORM export.
	 */
	public static function handle_worm_export() {
		if ( ! current_user_can( 'ngc_manage_platform' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'ngc_worm_export' );
		NGC_Worm_Export::export( [ 'legal_hold' => true ] );
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-platform-kernel&worm=1' ) );
		exit;
	}
}
