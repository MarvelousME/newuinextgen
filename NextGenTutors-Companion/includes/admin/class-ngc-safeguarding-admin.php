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
		$cap = current_user_can( 'ngc_manage_safeguarding' ) ? 'ngc_manage_safeguarding' : 'manage_options';
		if ( ! current_user_can( $cap ) && ! current_user_can( 'ngc_manage_fraud' ) ) {
			return;
		}
		add_submenu_page( function_exists('ngt_admin_parent') ? ngt_admin_parent() : 'ngt-admin',
			__( 'Safeguarding', 'nextgencompanion' ),
			__( 'Safeguarding', 'nextgencompanion' ),
			current_user_can( 'ngc_manage_safeguarding' ) ? 'ngc_manage_safeguarding' : 'manage_options',
			'ngc-safeguarding',
			[ __CLASS__, 'render_page' ]
		);
		add_submenu_page( function_exists('ngt_admin_parent') ? ngt_admin_parent() : 'ngt-admin',
			__( 'Fraud cases', 'nextgencompanion' ),
			__( 'Fraud cases', 'nextgencompanion' ),
			current_user_can( 'ngc_manage_fraud' ) ? 'ngc_manage_fraud' : 'manage_options',
			'ngc-fraud-cases',
			[ __CLASS__, 'render_fraud_page' ]
		);
	}

	public static function render_page() {
		if ( ( ! current_user_can( 'ngc_manage_safeguarding' ) && ! current_user_can( 'manage_options' ) ) || ! class_exists( 'NGC_Safeguarding' ) ) {
			return;
		}
		$stats = NGC_Safeguarding::stats();
		NGC_Admin_Layout::render_page(
			[
				'title'   => __( 'Safeguarding', 'nextgencompanion' ),
				'summary' => sprintf(
					/* translators: 1 open 2 high 3 breached */
					__( 'Open: %1$d · High/critical: %2$d · SLA breached: %3$d. AI signals are review-only.', 'nextgencompanion' ),
					(int) $stats['open'],
					(int) $stats['high'],
					(int) $stats['breached']
				),
				'content' => static function () {
					NGC_Admin_Grid::render( 'safeguarding_cases' );
				},
				'help'    => __( 'Never store unmasked ID/bank data in notes.', 'nextgencompanion' ),
			]
		);
	}

	public static function render_fraud_page() {
		if ( ( ! current_user_can( 'ngc_manage_fraud' ) && ! current_user_can( 'manage_options' ) ) || ! class_exists( 'NGC_Fraud_Engine' ) ) {
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
		if ( ! current_user_can( 'ngc_manage_safeguarding' ) && ! current_user_can( 'manage_options' ) ) {
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
		if ( ! current_user_can( 'ngc_manage_fraud' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_fraud_resolve' );
		NGC_Fraud_Engine::resolve_case( (int) ( $_POST['case_id'] ?? 0 ), sanitize_key( (string) ( $_POST['resolution'] ?? 'false_positive' ) ) );
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-fraud-cases' ) );
		exit;
	}
}
