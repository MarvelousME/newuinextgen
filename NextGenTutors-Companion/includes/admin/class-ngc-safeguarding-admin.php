<?php
/**
 * Safeguarding moderator queue — assign, escalate, resolve, SLA.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI for safeguarding case operations.
 */
final class NGC_Safeguarding_Admin {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ], 23 );
		add_action( 'admin_post_ngc_safeguarding_action', [ __CLASS__, 'handle_action' ] );
		add_action( 'admin_post_ngc_fraud_resolve', [ __CLASS__, 'handle_fraud_resolve' ] );
	}

	public static function register_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		add_submenu_page(
			'ngc-operations',
			__( 'Safeguarding', 'nextgencompanion' ),
			__( 'Safeguarding', 'nextgencompanion' ),
			'manage_options',
			'ngc-safeguarding',
			[ __CLASS__, 'render_page' ]
		);
		add_submenu_page(
			'ngc-operations',
			__( 'Fraud cases', 'nextgencompanion' ),
			__( 'Fraud cases', 'nextgencompanion' ),
			'manage_options',
			'ngc-fraud-cases',
			[ __CLASS__, 'render_fraud_page' ]
		);
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) || ! class_exists( 'NGC_Safeguarding' ) ) {
			return;
		}
		$stats = NGC_Safeguarding::stats();
		$cases = NGC_Safeguarding::query( [ 'limit' => 50 ] );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Safeguarding moderator queue', 'nextgencompanion' ); ?></h1>
			<p class="description"><?php esc_html_e( 'AI signals are review-only. Escalate on SLA breach or risk. Never store unmasked ID/bank data in notes.', 'nextgencompanion' ); ?></p>

			<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin:16px 0">
				<div class="card" style="padding:12px"><strong><?php echo esc_html( (string) $stats['open'] ); ?></strong><br><?php esc_html_e( 'Open', 'nextgencompanion' ); ?></div>
				<div class="card" style="padding:12px"><strong><?php echo esc_html( (string) $stats['high'] ); ?></strong><br><?php esc_html_e( 'High / critical', 'nextgencompanion' ); ?></div>
				<div class="card" style="padding:12px;background:#fef2f2"><strong><?php echo esc_html( (string) $stats['breached'] ); ?></strong><br><?php esc_html_e( 'SLA breached', 'nextgencompanion' ); ?></div>
				<div class="card" style="padding:12px"><strong><?php echo esc_html( (string) $stats['escalated'] ); ?></strong><br><?php esc_html_e( 'Escalated', 'nextgencompanion' ); ?></div>
			</div>

			<table class="widefat striped">
				<thead>
					<tr>
						<th>ID</th>
						<th><?php esc_html_e( 'Priority', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Status', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'SLA', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Summary', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Assigned', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'nextgencompanion' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $cases ) ) : ?>
						<tr><td colspan="7"><?php esc_html_e( 'No open safeguarding cases.', 'nextgencompanion' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $cases as $case ) : ?>
						<?php
						$sla = NGC_Safeguarding::sla_status( $case );
						?>
						<tr<?php echo $sla['breached'] ? ' style="background:#fef2f2"' : ''; ?>>
							<td><?php echo esc_html( (string) $case->id ); ?></td>
							<td><code><?php echo esc_html( $case->priority ); ?></code></td>
							<td><?php echo esc_html( $case->status ); ?><?php echo ! empty( $case->ai_signal ) ? ' · AI' : ''; ?></td>
							<td><strong><?php echo esc_html( $sla['label'] ); ?></strong><br><small><?php echo esc_html( (string) ( $case->due_at ?? '' ) ); ?></small></td>
							<td><?php echo esc_html( $case->summary ); ?></td>
							<td><?php echo $case->assigned_to ? esc_html( '#' . $case->assigned_to ) : '—'; ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
									<?php wp_nonce_field( 'ngc_safeguarding_action' ); ?>
									<input type="hidden" name="action" value="ngc_safeguarding_action" />
									<input type="hidden" name="case_id" value="<?php echo esc_attr( (string) $case->id ); ?>" />
									<input type="hidden" name="op" value="assign" />
									<button class="button button-small"><?php esc_html_e( 'Assign me', 'nextgencompanion' ); ?></button>
								</form>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
									<?php wp_nonce_field( 'ngc_safeguarding_action' ); ?>
									<input type="hidden" name="action" value="ngc_safeguarding_action" />
									<input type="hidden" name="case_id" value="<?php echo esc_attr( (string) $case->id ); ?>" />
									<input type="hidden" name="op" value="escalate" />
									<button class="button button-small"><?php esc_html_e( 'Escalate', 'nextgencompanion' ); ?></button>
								</form>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
									<?php wp_nonce_field( 'ngc_safeguarding_action' ); ?>
									<input type="hidden" name="action" value="ngc_safeguarding_action" />
									<input type="hidden" name="case_id" value="<?php echo esc_attr( (string) $case->id ); ?>" />
									<input type="hidden" name="op" value="resolve" />
									<input type="hidden" name="resolution" value="closed" />
									<button class="button button-primary button-small"><?php esc_html_e( 'Resolve', 'nextgencompanion' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function render_fraud_page() {
		if ( ! current_user_can( 'manage_options' ) || ! class_exists( 'NGC_Fraud_Engine' ) ) {
			return;
		}
		$stats = NGC_Fraud_Engine::stats();
		$cases = NGC_Fraud_Engine::query_cases( [ 'status' => 'open', 'limit' => 50 ] );
		$rules = NGC_Fraud_Engine::rules();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Fraud case review', 'nextgencompanion' ); ?></h1>
			<p><?php echo esc_html( sprintf( /* translators: 1 open 2 high */ __( 'Open: %1$d · High: %2$d · Rules loaded: %3$d', 'nextgencompanion' ), $stats['open'], $stats['high'], count( $rules ) ) ); ?></p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>ID</th>
						<th><?php esc_html_e( 'Severity', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Title', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Entity', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Score', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Resolve', 'nextgencompanion' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $cases ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No open fraud cases.', 'nextgencompanion' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $cases as $case ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $case->id ); ?></td>
							<td><?php echo esc_html( $case->severity ); ?></td>
							<td><?php echo esc_html( $case->title ); ?></td>
							<td><code><?php echo esc_html( $case->entity_type . ':' . $case->entity_id ); ?></code></td>
							<td><?php echo esc_html( (string) $case->score ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
									<?php wp_nonce_field( 'ngc_fraud_resolve' ); ?>
									<input type="hidden" name="action" value="ngc_fraud_resolve" />
									<input type="hidden" name="case_id" value="<?php echo esc_attr( (string) $case->id ); ?>" />
									<select name="resolution">
										<option value="confirmed"><?php esc_html_e( 'Confirmed', 'nextgencompanion' ); ?></option>
										<option value="false_positive"><?php esc_html_e( 'False positive', 'nextgencompanion' ); ?></option>
										<option value="escalated"><?php esc_html_e( 'Escalated', 'nextgencompanion' ); ?></option>
									</select>
									<button class="button button-small"><?php esc_html_e( 'Resolve', 'nextgencompanion' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<h2><?php esc_html_e( 'Active rules', 'nextgencompanion' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Rule', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Threshold', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Action', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Weight', 'nextgencompanion' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $rules as $key => $rule ) : ?>
						<tr>
							<td><code><?php echo esc_html( $key ); ?></code></td>
							<td><?php echo esc_html( (string) ( $rule['threshold'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $rule['action'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $rule['weight'] ?? '' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function handle_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_safeguarding_action' );
		$case_id = (int) ( $_POST['case_id'] ?? 0 );
		$op      = sanitize_key( (string) ( $_POST['op'] ?? '' ) );
		if ( 'assign' === $op ) {
			NGC_Safeguarding::assign( $case_id, get_current_user_id() );
		} elseif ( 'escalate' === $op ) {
			NGC_Safeguarding::escalate( $case_id, 'moderator' );
		} elseif ( 'resolve' === $op ) {
			NGC_Safeguarding::resolve( $case_id, sanitize_key( (string) ( $_POST['resolution'] ?? 'closed' ) ), 'moderator resolve' );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-safeguarding' ) );
		exit;
	}

	public static function handle_fraud_resolve() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_fraud_resolve' );
		NGC_Fraud_Engine::resolve_case( (int) ( $_POST['case_id'] ?? 0 ), sanitize_key( (string) ( $_POST['resolution'] ?? 'false_positive' ) ) );
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-fraud-cases' ) );
		exit;
	}
}
