<?php
/**
 * WordPress admin menus for NGC operations.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI.
 */
class NGC_Admin {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menus' ] );
		add_action( 'admin_notices', [ __CLASS__, 'verification_notice' ] );
	}

	/**
	 * Register admin menus.
	 */
	public static function register_menus() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'ngc_manage_matches' ) ) {
			return;
		}

		add_menu_page(
			__( 'NextGenTutors-Companion', 'nextgencompanion' ),
			__( 'NextGen', 'nextgencompanion' ),
			'manage_options',
			'ngc-operations',
			[ __CLASS__, 'render_dashboard' ],
			'dashicons-welcome-learn-more',
			58
		);

		add_submenu_page(
			'ngc-operations',
			__( 'Tutor Applications', 'nextgencompanion' ),
			__( 'Applications', 'nextgencompanion' ),
			'ngc_review_tutors',
			'ngc-applications',
			[ __CLASS__, 'render_applications' ]
		);

		add_submenu_page(
			'ngc-operations',
			__( 'Matches', 'nextgencompanion' ),
			__( 'Matches', 'nextgencompanion' ),
			'ngc_manage_matches',
			'ngc-matches',
			[ __CLASS__, 'render_matches' ]
		);

		add_submenu_page(
			'ngc-operations',
			__( 'Payouts', 'nextgencompanion' ),
			__( 'Payouts', 'nextgencompanion' ),
			'ngc_manage_payouts',
			'ngc-payouts',
			[ __CLASS__, 'render_payouts' ]
		);

		add_submenu_page(
			'ngc-operations',
			__( 'System Health', 'nextgencompanion' ),
			__( 'Health', 'nextgencompanion' ),
			'manage_options',
			'ngc-health',
			[ __CLASS__, 'render_health' ]
		);

		add_submenu_page(
			'ngc-operations',
			__( 'Errors & Exceptions', 'nextgencompanion' ),
			__( 'Errors', 'nextgencompanion' ),
			'manage_options',
			'ngc-errors',
			[ 'NGC_Exception_Log', 'render_dashboard' ]
		);
	}

	/**
	 * Operations dashboard.
	 */
	public static function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		$checks = NGC_Verification::run_checks();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'NextGen Companion', 'nextgencompanion' ); ?> <small>v<?php echo esc_html( NGC_VERSION ); ?></small></h1>
			<p><?php esc_html_e( 'Business operations overview.', 'nextgencompanion' ); ?></p>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Check', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Status', 'nextgencompanion' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $checks as $key => $val ) :
					if ( 'ok' === $key || 'version' === $key ) {
						continue;
					}
					$display = self::format_check_value( $val, $key );
					?>
					<tr>
						<td><?php echo esc_html( ucfirst( $key ) ); ?></td>
						<td><?php echo wp_kses_post( $display ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ngc-health&ngc_repair=1&_wpnonce=' . wp_create_nonce( 'ngc_repair' ) ) ); ?>"><?php esc_html_e( 'Run self-healing repair', 'nextgencompanion' ); ?></a></p>
		</div>
		<?php
	}

	/**
	 * Tutor applications list.
	 */
	public static function render_applications() {
		if ( ! current_user_can( 'ngc_review_tutors' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}

		if ( isset( $_GET['approve'], $_GET['id'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ngc_app_action' ) ) {
			NGC_Tutor_Lifecycle::approve( (int) $_GET['id'] );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Application approved.', 'nextgencompanion' ) . '</p></div>';
		}
		if ( isset( $_GET['reject'], $_GET['id'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ngc_app_action' ) ) {
			NGC_Tutor_Lifecycle::reject( (int) $_GET['id'] );
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Application rejected.', 'nextgencompanion' ) . '</p></div>';
		}

		$apps = NGC_Tutor_Lifecycle::list_applications( 'pending', 100 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Pending Tutor Applications', 'nextgencompanion' ); ?></h1>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>ID</th>
						<th><?php esc_html_e( 'Name', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Email', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Subjects', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'nextgencompanion' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $apps ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No pending applications.', 'nextgencompanion' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $apps as $app ) : ?>
						<tr>
							<td><?php echo (int) $app->id; ?></td>
							<td><?php echo esc_html( $app->full_name ); ?></td>
							<td><?php echo esc_html( $app->email ); ?></td>
							<td><?php echo esc_html( $app->subjects ); ?></td>
							<td>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ngc-applications&approve=1&id=' . $app->id ), 'ngc_app_action' ) ); ?>"><?php esc_html_e( 'Approve', 'nextgencompanion' ); ?></a>
								|
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ngc-applications&reject=1&id=' . $app->id ), 'ngc_app_action' ) ); ?>"><?php esc_html_e( 'Reject', 'nextgencompanion' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Matches overview.
	 */
	public static function render_matches() {
		if ( ! current_user_can( 'ngc_manage_matches' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		global $wpdb;
		$table = NGC_Database::table( 'matches' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 50" );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Tutor Matches', 'nextgencompanion' ); ?></h1>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>ID</th>
						<th><?php esc_html_e( 'Subject', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Grade', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Status', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Score', 'nextgencompanion' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><?php echo (int) $row->id; ?></td>
						<td><?php echo esc_html( $row->subject ); ?></td>
						<td><?php echo esc_html( $row->grade ); ?></td>
						<td><?php echo esc_html( $row->status ); ?></td>
						<td><?php echo esc_html( $row->score ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Pending tutor payouts.
	 */
	public static function render_payouts() {
		if ( ! current_user_can( 'ngc_manage_payouts' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}

		if ( isset( $_GET['payout'], $_GET['tutor_id'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ngc_payout' ) ) {
			$gateway = ! empty( $_GET['gateway'] );
			$result  = NGC_Reviews::process_payout( (int) $_GET['tutor_id'], 0.0, ! $gateway );
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} else {
				$msg = $gateway
					? __( 'Payout record created (pending PayFast/EFT). Export batch below, then confirm after transfer.', 'nextgencompanion' )
					: __( 'Payout processed and marked paid.', 'nextgencompanion' );
				echo '<div class="notice notice-success"><p>' . esc_html( $msg ) . '</p></div>';
			}
		}

		if ( isset( $_GET['confirm_payout'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ngc_confirm_payout' ) ) {
			$result = NGC_Reviews::confirm_payout( (int) $_GET['confirm_payout'] );
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Payout confirmed and earnings settled.', 'nextgencompanion' ) . '</p></div>';
			}
		}

		if ( isset( $_GET['export_payfast'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ngc_export_payfast' ) ) {
			$status = sanitize_key( (string) ( $_GET['export_status'] ?? 'pending' ) );
			if ( class_exists( 'NGC_Payout_Export' ) ) {
				NGC_Payout_Export::send_download( $status );
			}
		}

		$pending_payouts = class_exists( 'NGC_Payout_Export' ) ? NGC_Payout_Export::rows_for_status( 'pending' ) : [];

		$tutors = get_users( [ 'role' => 'tutor', 'fields' => 'all' ] );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Tutor Payouts', 'nextgencompanion' ); ?></h1>
			<p>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ngc-payouts&export_payfast=1&export_status=pending' ), 'ngc_export_payfast' ) ); ?>">
					<?php esc_html_e( 'Export PayFast CSV (pending payouts)', 'nextgencompanion' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ngc-payouts&export_payfast=1&export_status=preview' ), 'ngc_export_payfast' ) ); ?>">
					<?php esc_html_e( 'Preview CSV (pending earnings)', 'nextgencompanion' ); ?>
				</a>
			</p>
			<?php if ( $pending_payouts ) : ?>
			<h2><?php esc_html_e( 'Pending gateway payouts', 'nextgencompanion' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Payout ID', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Tutor', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Amount', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'nextgencompanion' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $pending_payouts as $row ) : ?>
					<tr>
						<td><?php echo esc_html( (string) ( $row['payout_id'] ?? '' ) ); ?></td>
						<td><?php echo esc_html( (string) ( $row['recipient_name'] ?? '' ) ); ?></td>
						<td>R<?php echo esc_html( number_format( (float) ( $row['amount'] ?? 0 ), 2 ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ngc-payouts&confirm_payout=' . (int) ( $row['payout_id'] ?? 0 ) ), 'ngc_confirm_payout' ) ); ?>">
								<?php esc_html_e( 'Confirm paid', 'nextgencompanion' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
			<h2><?php esc_html_e( 'Pending earnings by tutor', 'nextgencompanion' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Tutor', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Pending', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'nextgencompanion' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $tutors as $tutor ) :
					$pending = NGC_Reviews::pending_payout_for_tutor( $tutor->ID );
					if ( $pending <= 0 ) {
						continue;
					}
					?>
					<tr>
						<td><?php echo esc_html( $tutor->display_name ); ?></td>
						<td>R<?php echo esc_html( number_format( $pending, 2 ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ngc-payouts&payout=1&tutor_id=' . $tutor->ID ), 'ngc_payout' ) ); ?>">
								<?php esc_html_e( 'Mark paid now', 'nextgencompanion' ); ?>
							</a>
							|
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ngc-payouts&payout=1&gateway=1&tutor_id=' . $tutor->ID ), 'ngc_payout' ) ); ?>">
								<?php esc_html_e( 'Create pending (PayFast)', 'nextgencompanion' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Health page with repair action.
	 */
	public static function render_health() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}

		if ( isset( $_GET['ngc_repair'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ngc_repair' ) ) {
			$result = NGC_Self_Healing::repair_all();
			$notice = class_exists( 'NGC_Audit_Presenter' )
				? NGC_Audit_Presenter::format_repair_notice( $result )
				: __( 'Repair completed.', 'nextgencompanion' );
			echo '<div class="notice notice-success"><p>' . esc_html( $notice ) . '</p></div>';
		}

		$checks   = NGC_Verification::run_checks();
		$scan     = class_exists( 'NGC_Health_Scanner' ) ? NGC_Health_Scanner::full_scan() : [];
		$features = $scan['platform_features'] ?? [];
		$errors   = class_exists( 'NGC_Exception_Log' ) ? NGC_Exception_Log::recent( 5 ) : [];
		$audit    = class_exists( 'NGC_Audit_Presenter' )
			? NGC_Audit_Presenter::unified_recent( 20 )
			: NGC_Audit::recent( 20 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'System Health', 'nextgencompanion' ); ?></h1>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ngc-health&ngc_repair=1&_wpnonce=' . wp_create_nonce( 'ngc_repair' ) ) ); ?>"><?php esc_html_e( 'Run self-healing repair', 'nextgencompanion' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'themes.php?page=bi-health' ) ); ?>"><?php esc_html_e( 'BeyondInfinity Health', 'nextgencompanion' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ngc-errors' ) ); ?>"><?php esc_html_e( 'Errors & Exceptions', 'nextgencompanion' ); ?></a>
			</p>

			<h2><?php esc_html_e( 'Platform features', 'nextgencompanion' ); ?></h2>
			<table class="widefat striped" style="max-width:720px">
				<tbody>
				<?php
				$feature_rows = [
					__( 'Smart matching engine', 'nextgencompanion' ) => ! empty( $features['smart_matching'] ) || NGC_Verification::check_pass( $checks, 'smart_matching' ),
					__( 'Form validation (JS/CSS)', 'nextgencompanion' ) => ! empty( $features['form_validation'] ) || NGC_Verification::check_pass( $checks, 'form_validation' ),
					__( 'Exception log dashboard', 'nextgencompanion' ) => ! empty( $features['exception_log'] ) || NGC_Verification::check_pass( $checks, 'exception_log' ),
					__( 'Theme live CPT helper', 'nextgencompanion' ) => ! empty( $features['theme_cpt_helper'] ),
					__( 'Published tutor CPT posts', 'nextgencompanion' ) => (int) ( $features['live_tutor_cpt'] ?? ( $checks['tutor_counts']['total'] ?? 0 ) ),
				];
				foreach ( $feature_rows as $label => $val ) :
					?>
					<tr>
						<td><?php echo esc_html( $label ); ?></td>
						<td>
							<?php
							if ( true === $val ) {
								echo '✓ ' . esc_html__( 'VERIFIED', 'nextgencompanion' );
							} elseif ( false === $val ) {
								echo '✗ ' . esc_html__( 'NOT READY', 'nextgencompanion' );
							} else {
								echo '<code>' . esc_html( (string) $val ) . '</code>';
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Core checks', 'nextgencompanion' ); ?></h2>
			<table class="widefat striped" style="max-width:720px">
				<tbody>
				<?php foreach ( $checks as $key => $val ) :
					if ( in_array( $key, [ 'ok', 'version' ], true ) ) {
						continue;
					}
					?>
					<tr>
						<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></td>
						<td><?php echo wp_kses_post( self::format_check_value( $val, $key ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( ! empty( $errors ) ) : ?>
				<h2><?php esc_html_e( 'Recent errors', 'nextgencompanion' ); ?></h2>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Time', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Type', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Message', 'nextgencompanion' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $errors as $row ) : ?>
						<tr>
							<td><?php echo esc_html( (string) ( $row['time'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( strtoupper( (string) ( $row['type'] ?? '' ) ) ); ?></td>
							<td><?php echo esc_html( (string) ( $row['message'] ?? '' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Scanner summary', 'nextgencompanion' ); ?></h2>
			<?php self::render_scanner_summary( $scan ); ?>

			<h2><?php esc_html_e( 'System-wide activity log', 'nextgencompanion' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Unified audit trail and system events — human-readable, no raw JSON.', 'nextgencompanion' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ngc-audit' ) ); ?>"><?php esc_html_e( 'Full audit log', 'nextgencompanion' ); ?></a>
				·
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ngc-system-log' ) ); ?>"><?php esc_html_e( 'System log', 'nextgencompanion' ); ?></a>
			</p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Source', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'When', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Actor', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Action', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Object', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Details', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Result', 'nextgencompanion' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $audit ) ) : ?>
					<tr><td colspan="7"><?php esc_html_e( 'No activity recorded yet.', 'nextgencompanion' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $audit as $entry ) : ?>
						<tr>
							<td><?php echo esc_html( (string) ( $entry['source'] ?? __( 'Audit', 'nextgencompanion' ) ) ); ?></td>
							<td><?php echo esc_html( (string) ( $entry['when'] ?? ( $entry['created_at'] ?? '—' ) ) ); ?></td>
							<td><?php echo esc_html( (string) ( $entry['actor'] ?? '—' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $entry['action'] ?? '—' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $entry['object'] ?? '—' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $entry['detail'] ?? '—' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $entry['result'] ?? '—' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Show admin notice when verification fails.
	 */
	public static function verification_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$checks = NGC_Verification::run_checks();
		if ( isset( $checks['ok'] ) && $checks['ok'] ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>';
		printf(
			/* translators: %s: admin page URL */
			esc_html__( 'NextGen Companion health check failed. %s', 'nextgencompanion' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=ngc-health' ) ) . '">' . esc_html__( 'View details', 'nextgencompanion' ) . '</a>'
		);
		echo '</p></div>';
	}

	/**
	 * Format a verification check value for admin tables.
	 *
	 * @param mixed $val Check value.
	 * @return string
	 */
	private static function format_check_value( $val, $key = '' ) {
		if ( is_array( $val ) && isset( $val['status'] ) ) {
			$icon = in_array( $val['status'], [ 'PASS' ], true ) ? '✓' : ( 'FAIL' === $val['status'] ? '✗' : '○' );
			$msg  = ! empty( $val['message'] ) ? ' — ' . esc_html( (string) $val['message'] ) : '';
			return esc_html( (string) $val['status'] ) . ' ' . $icon . $msg;
		}
		if ( 'tutor_counts' === $key && is_array( $val ) ) {
			return sprintf(
				esc_html__( 'Real: %1$d · Demo: %2$d · Total: %3$d', 'nextgencompanion' ),
				(int) ( $val['real'] ?? 0 ),
				(int) ( $val['demo'] ?? 0 ),
				(int) ( $val['total'] ?? 0 )
			);
		}
		if ( is_array( $val ) ) {
			$parts = [];
			foreach ( $val as $k => $v ) {
				if ( is_scalar( $v ) ) {
					$parts[] = esc_html( (string) $k ) . ': ' . esc_html( (string) $v );
				}
			}
			return $parts ? implode( ' · ', $parts ) : esc_html__( 'Configured', 'nextgencompanion' );
		}
		if ( is_bool( $val ) ) {
			return $val ? '✓ PASS' : '○ FAIL';
		}
		return esc_html( (string) $val );
	}

	/**
	 * Render health scanner output as readable rows (no JSON block).
	 *
	 * @param array<string, mixed> $scan Scanner payload.
	 */
	public static function render_scanner_summary( $scan ) {
		if ( empty( $scan ) ) {
			echo '<p>' . esc_html__( 'Scanner not available.', 'nextgencompanion' ) . '</p>';
			return;
		}
		$skip = [ 'ok', 'scanned_at' ];
		?>
		<table class="widefat striped" style="max-width:960px">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Area', 'nextgencompanion' ); ?></th>
					<th><?php esc_html_e( 'Status', 'nextgencompanion' ); ?></th>
					<th><?php esc_html_e( 'Summary', 'nextgencompanion' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $scan as $key => $val ) :
				if ( in_array( $key, $skip, true ) ) {
					continue;
				}
				$status = is_bool( $val ) ? ( $val ? __( 'OK', 'nextgencompanion' ) : __( 'Issue', 'nextgencompanion' ) ) : __( 'Info', 'nextgencompanion' );
				$summary = '';
				if ( is_array( $val ) ) {
					$bits = [];
					foreach ( $val as $sub_key => $sub_val ) {
						if ( is_bool( $sub_val ) ) {
							$bits[] = ucwords( str_replace( '_', ' ', (string) $sub_key ) ) . ': ' . ( $sub_val ? 'OK' : '—' );
						} elseif ( is_scalar( $sub_val ) ) {
							$bits[] = ucwords( str_replace( '_', ' ', (string) $sub_key ) ) . ': ' . $sub_val;
						}
					}
					$summary = implode( ' · ', $bits );
				} elseif ( is_scalar( $val ) ) {
					$summary = (string) $val;
				}
				?>
				<tr>
					<td><?php echo esc_html( ucwords( str_replace( '_', ' ', (string) $key ) ) ); ?></td>
					<td><?php echo esc_html( $status ); ?></td>
					<td><?php echo esc_html( $summary ?: '—' ); ?></td>
				</tr>
			<?php endforeach; ?>
			<?php if ( ! empty( $scan['scanned_at'] ) ) : ?>
				<tr>
					<td><?php esc_html_e( 'Scanned at', 'nextgencompanion' ); ?></td>
					<td><?php esc_html_e( 'Info', 'nextgencompanion' ); ?></td>
					<td><?php echo esc_html( (string) $scan['scanned_at'] ); ?></td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
	}
}
