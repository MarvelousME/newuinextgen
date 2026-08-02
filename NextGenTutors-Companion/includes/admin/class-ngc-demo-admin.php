<?php
/**
 * Demo Control Centre admin UI (Phase 14 §14.21).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restricted demo operations console.
 */
final class NGC_Demo_Admin {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ], 65 );
		add_action( 'admin_post_ngc_demo_action', [ __CLASS__, 'handle_action' ] );
		add_action( 'admin_post_ngc_demo_login_as', [ __CLASS__, 'handle_login_as' ] );
	}

	/**
	 * Register under Platform.
	 */
	public static function register_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		add_submenu_page( function_exists('ngt_admin_parent') ? ngt_admin_parent() : 'ngt-admin',
			__( 'Demo Control Centre', 'nextgencompanion' ),
			__( 'Demo Control Centre', 'nextgencompanion' ),
			'manage_options',
			'ngc-demo-control',
			[ __CLASS__, 'render' ]
		);
	}

	/**
	 * Render control centre.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$status  = class_exists( 'NGC_Demo_Seeder' ) ? NGC_Demo_Seeder::status() : [];
		$verify  = class_exists( 'NGC_Demo_Verifier' ) ? NGC_Demo_Verifier::verify() : [ 'ok' => false ];
		$dir     = class_exists( 'NGC_Demo_Registry' ) ? NGC_Demo_Registry::directory_for_admin() : [];
		$flash   = '';
		$flash_key = 'ngc_demo_flash_' . get_current_user_id();
		$stored  = get_transient( $flash_key );
		if ( is_string( $stored ) && $stored !== '' ) {
			$flash = $stored;
			delete_transient( $flash_key );
		} elseif ( isset( $_GET['ngc_demo_msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// Legacy query-arg flash (may be double-encoded from older builds).
			$flash = sanitize_text_field( rawurldecode( wp_unslash( $_GET['ngc_demo_msg'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		?>
		<div class="wrap" data-testid="ngc-demo-control">
			<h1><?php esc_html_e( 'Demo Control Centre', 'nextgencompanion' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Phase 14 — relational seed, clock, journeys, evidence. Demo-only; blocked in production without demo mode.', 'nextgencompanion' ); ?></p>

			<?php if ( $flash ) : ?>
				<div class="notice notice-success" data-testid="ngc-demo-flash"><p><?php echo esc_html( $flash ); ?></p></div>
			<?php endif; ?>

			<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:16px 0">
				<div class="card" style="padding:12px" data-testid="ngc-demo-mode-card"><strong data-testid="ngc-demo-mode"><?php echo NGC_Demo_Env::is_demo_mode() ? 'ON' : 'OFF'; ?></strong><br><?php esc_html_e( 'Demo mode', 'nextgencompanion' ); ?></div>
				<div class="card" style="padding:12px"><strong data-testid="ngc-demo-seed-version"><?php echo esc_html( NGC_Demo_Env::SEED_VERSION ); ?></strong><br><?php esc_html_e( 'Seed version', 'nextgencompanion' ); ?></div>
				<div class="card" style="padding:12px" data-testid="ngc-demo-verify-card"><strong data-testid="ngc-demo-verify"><?php echo ! empty( $verify['ok'] ) ? 'PASS' : 'FAIL'; ?></strong><br><?php esc_html_e( 'Verify', 'nextgencompanion' ); ?></div>
				<div class="card" style="padding:12px"><strong data-testid="ngc-demo-clock"><?php echo esc_html( (string) ( $status['clock']['iso'] ?? '' ) ); ?></strong><br><?php esc_html_e( 'Demo clock', 'nextgencompanion' ); ?></div>
			</div>

			<div data-testid="ngc-demo-actions" style="margin-bottom:20px">
				<p style="display:flex;flex-wrap:wrap;gap:8px">
					<?php
					$ops = [
						'enable'          => [ __( 'Enable demo mode', 'nextgencompanion' ), 'button-primary', 'ngc-demo-enable' ],
						'seed'            => [ __( 'Seed all', 'nextgencompanion' ), 'button-primary', 'ngc-demo-seed' ],
						'verify'          => [ __( 'Verify', 'nextgencompanion' ), '', 'ngc-demo-verify-btn' ],
						'run_journeys'    => [ __( 'Run all journeys', 'nextgencompanion' ), '', 'ngc-demo-run-journeys' ],
						'export_evidence' => [ __( 'Export evidence', 'nextgencompanion' ), '', 'ngc-demo-export' ],
						'advance_day'     => [ __( 'Advance clock +1 day', 'nextgencompanion' ), '', 'ngc-demo-advance' ],
						'process_queues'  => [ __( 'Process schedulers', 'nextgencompanion' ), '', 'ngc-demo-queues' ],
						'reset'           => [ __( 'Reset all demo', 'nextgencompanion' ), '', 'ngc-demo-reset' ],
					];
					foreach ( $ops as $op_key => $meta ) :
						[ $label, $extra_class, $testid ] = $meta;
						?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;margin:0">
							<?php wp_nonce_field( 'ngc_demo_action' ); ?>
							<input type="hidden" name="action" value="ngc_demo_action" />
							<input type="hidden" name="op" value="<?php echo esc_attr( $op_key ); ?>" />
							<button
								type="submit"
								class="button <?php echo esc_attr( $extra_class ); ?>"
								data-testid="<?php echo esc_attr( $testid ); ?>"
								<?php echo 'reset' === $op_key ? 'onclick="return confirm(\'Reset ALL demo data?\');"' : ''; ?>
							><?php echo esc_html( $label ); ?></button>
						</form>
					<?php endforeach; ?>
				</p>
			</div>

			<h2><?php esc_html_e( 'Demo account directory', 'nextgencompanion' ); ?></h2>
			<p><?php esc_html_e( 'Real authentication — use these credentials only while demo mode is on.', 'nextgencompanion' ); ?></p>
			<table class="widefat striped" data-testid="ngc-demo-directory">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Stable ID', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Role', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Email', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Password', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'State', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Login', 'nextgencompanion' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $dir as $row ) : ?>
						<tr data-testid="ngc-demo-row-<?php echo esc_attr( $row['stable_id'] ); ?>" data-stable-id="<?php echo esc_attr( $row['stable_id'] ); ?>">
							<td><code><?php echo esc_html( $row['stable_id'] ); ?></code></td>
							<td><?php echo esc_html( $row['role'] ); ?></td>
							<td data-testid="ngc-demo-email"><?php echo esc_html( $row['email'] ); ?></td>
							<td><code data-testid="ngc-demo-password"><?php echo esc_html( $row['password'] ); ?></code></td>
							<td><?php echo esc_html( $row['state'] ); ?></td>
							<td>
								<?php if ( ! empty( $row['user_id'] ) && NGC_Demo_Env::is_demo_mode() ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
										<?php wp_nonce_field( 'ngc_demo_login_as' ); ?>
										<input type="hidden" name="action" value="ngc_demo_login_as" />
										<input type="hidden" name="user_id" value="<?php echo esc_attr( (string) $row['user_id'] ); ?>" />
										<button class="button button-small" data-testid="ngc-demo-switch"><?php esc_html_e( 'Switch to user', 'nextgencompanion' ); ?></button>
									</form>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Last verify failures', 'nextgencompanion' ); ?></h2>
			<pre data-testid="ngc-demo-failures"><?php echo esc_html( wp_json_encode( $verify['failures'] ?? [], JSON_PRETTY_PRINT ) ); ?></pre>

			<h2><?php esc_html_e( 'Seed graph (summary)', 'nextgencompanion' ); ?></h2>
			<pre data-testid="ngc-demo-seed-graph"><?php echo esc_html( wp_json_encode( $status['graph'] ?? [], JSON_PRETTY_PRINT ) ); ?></pre>
		</div>
		<?php
	}

	/**
	 * Handle control actions.
	 */
	public static function handle_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_demo_action' );
		$op  = sanitize_key( wp_unslash( $_POST['op'] ?? '' ) );
		$msg = $op;
		switch ( $op ) {
			case 'enable':
				NGC_Demo_Env::set_demo_mode( true );
				$msg = 'Demo mode enabled';
				break;
			case 'seed':
				$result = NGC_Demo_Seeder::seed( 'all' );
				$msg    = is_wp_error( $result ) ? $result->get_error_message() : 'Seed complete';
				break;
			case 'verify':
				$v   = NGC_Demo_Verifier::verify();
				$msg = ! empty( $v['ok'] ) ? 'Verify PASS' : 'Verify FAIL: ' . implode( '; ', $v['failures'] );
				break;
			case 'run_journeys':
				$batch = NGC_Demo_Journeys::run_all();
				$count = is_array( $batch ) ? count( $batch ) : 0;
				$ok    = true;
				foreach ( (array) $batch as $row ) {
					if ( empty( $row['verify']['ok'] ) && is_wp_error( $row['seed'] ?? null ) ) {
						$ok = false;
						break;
					}
					if ( isset( $row['verify']['ok'] ) && empty( $row['verify']['ok'] ) ) {
						$ok = false;
						break;
					}
				}
				$msg = $ok
					? sprintf( 'Journeys executed (%d)', $count )
					: sprintf( 'Journeys executed with failures (%d)', $count );
				break;
			case 'export_evidence':
				$path = NGC_Demo_Evidence::export_all();
				$msg  = is_wp_error( $path ) ? $path->get_error_message() : 'Evidence: ' . $path;
				break;
			case 'advance_day':
				NGC_Demo_Clock::advance( DAY_IN_SECONDS );
				$msg = 'Clock advanced +1 day';
				break;
			case 'process_queues':
				NGC_Demo_Clock::run_scheduled_hooks();
				$msg = 'Schedulers processed';
				break;
			case 'reset':
				$result = NGC_Demo_Reset::reset( 'all' );
				$msg    = is_wp_error( $result ) ? $result->get_error_message() : 'Reset complete';
				break;
		}
		set_transient( 'ngc_demo_flash_' . get_current_user_id(), $msg, 120 );
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-demo-control' ) );
		exit;
	}

	/**
	 * Secure switch-user for demo personas (demo mode only).
	 */
	public static function handle_login_as() {
		if ( ! current_user_can( 'manage_options' ) || ! NGC_Demo_Env::is_demo_mode() ) {
			wp_die( esc_html__( 'Demo login switch blocked.', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_demo_login_as' );
		$user_id = (int) ( $_POST['user_id'] ?? 0 );
		$user    = get_userdata( $user_id );
		if ( ! $user || '1' !== (string) get_user_meta( $user_id, 'ngc_is_demo_user', true ) ) {
			wp_die( esc_html__( 'Not a demo user.', 'nextgencompanion' ) );
		}
		$actor = get_current_user_id();
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'demo_login_switch', 'user', $user_id, [ 'by' => $actor ] );
		}
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );
		$landing = (string) get_user_meta( $user_id, 'ngc_demo_landing', true );
		$url     = home_url( '/' );
		if ( 'wp-admin' === $landing ) {
			$url = admin_url();
		} elseif ( $landing ) {
			$url = home_url( '/' . trim( $landing, '/' ) . '/' );
		}
		wp_safe_redirect( $url );
		exit;
	}
}
