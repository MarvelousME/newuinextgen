<?php
/**
 * Workflow integration admin screens.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI for workflow triggers, integrations, templates, logs.
 */
class NGC_Workflow_Admin {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menus' ], 59 );
		add_action( 'admin_init', [ __CLASS__, 'handle_actions' ] );
	}

	/**
	 * Register workflow admin menus.
	 */
	public static function register_menus() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$parent = 'ngc-workflows';
		add_menu_page(
			__( 'Workflows', 'nextgencompanion' ),
			__( 'Workflows', 'nextgencompanion' ),
			'manage_options',
			$parent,
			[ __CLASS__, 'render_trigger_manager' ],
			'dashicons-randomize',
			57
		);

		$pages = [
			[ 'ngc-workflow-triggers', __( 'Trigger Manager', 'nextgencompanion' ), 'render_trigger_manager' ],
			[ 'ngc-workflow-fluentcrm', __( 'FluentCRM Status', 'nextgencompanion' ), 'render_fluentcrm' ],
			[ 'ngc-workflow-amelia', __( 'Amelia Status', 'nextgencompanion' ), 'render_amelia' ],
			[ 'ngc-workflow-masterstudy', __( 'MasterStudy Status', 'nextgencompanion' ), 'render_masterstudy' ],
			[ 'ngc-workflow-emails', __( 'Email Templates', 'nextgencompanion' ), 'render_emails' ],
			[ 'ngc-workflow-logs', __( 'Workflow Logs', 'nextgencompanion' ), 'render_logs' ],
			[ 'ngc-workflow-retries', __( 'Retry Queue', 'nextgencompanion' ), 'render_retries' ],
			[ 'ngc-workflow-verification', __( 'Verification', 'nextgencompanion' ), 'render_verification' ],
			[ 'ngc-workflow-integrate', __( 'Integrate Specs', 'nextgencompanion' ), 'render_integrate_specs' ],
		];

		foreach ( $pages as $page ) {
			add_submenu_page( $parent, $page[1], $page[1], 'manage_options', $page[0], [ __CLASS__, $page[2] ] );
		}
	}

	/**
	 * Handle POST/GET actions.
	 */
	public static function handle_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['ngc_save_amelia_key'] ) && check_admin_referer( 'ngc_workflow_settings' ) ) {
			$key = sanitize_text_field( wp_unslash( $_POST['ngc_amelia_api_key'] ?? '' ) );
			update_option( 'ngc_amelia_api_key', $key, false );
			update_option( 'ngc_amelia_default_service_id', (int) ( $_POST['ngc_amelia_default_service_id'] ?? 0 ), false );
			if ( $key && class_exists( 'NGC_Amelia_Bootstrap' ) && NGC_Amelia_Bootstrap::DIRECT_MODE_KEY !== $key ) {
				delete_option( 'ngc_amelia_direct_mode' );
			}
		}

		if ( isset( $_GET['ngc_test_email'], $_GET['template'] ) && check_admin_referer( 'ngc_test_email' ) ) {
			$key = sanitize_key( wp_unslash( $_GET['template'] ) );
			$email = new NGC_Email_Adapter();
			$result = $email->send_admin(
				$key,
				NGC_Workflow_Email_Templates::merge_context(
					[
						'first_name' => 'Test',
						'last_name'  => 'User',
						'email'      => get_option( 'admin_email' ),
						'workflow'   => 'TEST',
					]
				)
			);
			set_transient( 'ngc_admin_notice', $result['ok'] ? 'test_email_sent' : 'test_email_failed', 30 );
		}

		if ( isset( $_GET['ngc_retry_workflow'], $_GET['retry_id'] ) && check_admin_referer( 'ngc_retry_workflow' ) ) {
			NGC_Workflow_Retry_Queue::retry( sanitize_text_field( wp_unslash( $_GET['retry_id'] ) ) );
			set_transient( 'ngc_admin_notice', 'retry_queued', 30 );
		}

		if ( isset( $_GET['ngc_bootstrap_crm'] ) && check_admin_referer( 'ngc_bootstrap_crm' ) ) {
			$adapter = new NGC_Fluentcrm_Adapter();
			$adapter->bootstrap_assets();
			set_transient( 'ngc_admin_notice', 'crm_bootstrapped', 30 );
		}

		if ( isset( $_POST['ngc_integrate_import'] ) && check_admin_referer( 'ngc_integrate_specs' ) ) {
			$result = NGC_Workflow_Spec_Registry::import_from_integrate_dir( true );
			set_transient( 'ngc_admin_notice', $result['ok'] ? 'integrate_imported' : 'integrate_import_failed', 30 );
		}

		if ( isset( $_POST['ngc_catalog_import'] ) && check_admin_referer( 'ngc_integrate_specs' ) ) {
			$result = class_exists( 'NGC_Content_Pack_Bridge' ) ? NGC_Content_Pack_Bridge::import_catalog_specs() : [ 'ok' => false ];
			set_transient( 'ngc_catalog_import_result', $result, 60 );
		}

		if ( isset( $_POST['ngc_automatorwp_import'] ) && check_admin_referer( 'ngc_integrate_specs' ) ) {
			$result = class_exists( 'NGC_AutomatorWP_Importer' ) ? NGC_AutomatorWP_Importer::import_from_v2_catalog( true ) : [ 'ok' => false ];
			set_transient( 'ngc_automatorwp_import_result', $result, 60 );
		}

		if ( isset( $_POST['ngc_integrate_delete'], $_POST['spec_id'] ) && check_admin_referer( 'ngc_integrate_specs' ) ) {
			NGC_Workflow_Spec_Registry::delete( sanitize_key( wp_unslash( $_POST['spec_id'] ) ) );
			set_transient( 'ngc_admin_notice', 'integrate_deleted', 30 );
		}

		if ( isset( $_POST['ngc_integrate_execute'], $_POST['integrate_event'] ) && check_admin_referer( 'ngc_integrate_specs' ) ) {
			$event  = sanitize_text_field( wp_unslash( $_POST['integrate_event'] ) );
			$result = NGC_Workflow_Orchestrator::execute_integrate_event( $event, [ 'source' => 'admin' ] );
			set_transient( 'ngc_integrate_execute_result', $result, 60 );
		}
	}

	/**
	 * Trigger manager overview.
	 */
	public static function render_trigger_manager() {
		self::wrap_start( __( 'Workflow Trigger Manager', 'nextgencompanion' ) );
		$stats = NGC_Workflow_Orchestrator::stats();
		?>
		<p><?php esc_html_e( 'Registration workflows execute via NGC_Workflow_Orchestrator when forms are submitted or tutors are approved/rejected.', 'nextgencompanion' ); ?></p>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Workflow', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Trigger', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Status', 'nextgencompanion' ); ?></th></tr></thead>
			<tbody>
			<?php
			$rows = [
				[ 'WF-TUTOR-REGISTERED', 'become_tutor form' ],
				[ 'WF-TUTOR-APPROVED', 'Admin approve / REST' ],
				[ 'WF-TUTOR-REJECTED', 'Admin reject / REST' ],
				[ 'WF-TUTOR-RESUBMITTED', 'REST resubmit' ],
				[ 'WF-PARENT-REGISTERED', 'parent_register form' ],
				[ 'WF-STUDENT-REGISTERED', 'student_register form' ],
				[ 'WF-CHILD-REGISTERED', 'parent_register child_name field' ],
			];
			foreach ( $rows as $row ) :
				?>
				<tr>
					<td><code><?php echo esc_html( $row[0] ); ?></code></td>
					<td><?php echo esc_html( $row[1] ); ?></td>
					<td><?php esc_html_e( 'Active', 'nextgencompanion' ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p>
			<strong><?php esc_html_e( 'Runs:', 'nextgencompanion' ); ?></strong>
			<?php echo (int) $stats['completed']; ?> <?php esc_html_e( 'completed', 'nextgencompanion' ); ?> /
			<?php echo (int) $stats['failed']; ?> <?php esc_html_e( 'failed', 'nextgencompanion' ); ?> /
			<?php echo (int) $stats['total']; ?> <?php esc_html_e( 'total', 'nextgencompanion' ); ?>
		</p>
		<?php
		self::wrap_end();
	}

	/**
	 * FluentCRM status screen.
	 */
	public static function render_fluentcrm() {
		self::wrap_start( __( 'FluentCRM Integration Status', 'nextgencompanion' ) );
		$adapter = new NGC_Fluentcrm_Adapter();
		$verify  = $adapter->verify();
		self::render_verify_table( $verify );
		?>
		<p>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ngc-workflow-fluentcrm&ngc_bootstrap_crm=1' ), 'ngc_bootstrap_crm' ) ); ?>">
				<?php esc_html_e( 'Bootstrap lists & tags', 'nextgencompanion' ); ?>
			</a>
		</p>
		<?php
		self::wrap_end();
	}

	/**
	 * Amelia status screen.
	 */
	public static function render_amelia() {
		self::wrap_start( __( 'Amelia Integration Status', 'nextgencompanion' ) );
		$adapter = new NGC_Amelia_Adapter();
		self::render_verify_table( $adapter->verify() );
		?>
		<form method="post">
			<?php wp_nonce_field( 'ngc_workflow_settings' ); ?>
			<table class="form-table">
				<tr>
					<th><label for="ngc_amelia_api_key"><?php esc_html_e( 'Amelia API key', 'nextgencompanion' ); ?></label></th>
					<td><input type="text" class="regular-text" name="ngc_amelia_api_key" id="ngc_amelia_api_key" value="<?php echo esc_attr( get_option( 'ngc_amelia_api_key', '' ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="ngc_amelia_default_service_id"><?php esc_html_e( 'Default service ID', 'nextgencompanion' ); ?></label></th>
					<td><input type="number" name="ngc_amelia_default_service_id" id="ngc_amelia_default_service_id" value="<?php echo esc_attr( get_option( 'ngc_amelia_default_service_id', 0 ) ); ?>" /></td>
				</tr>
			</table>
			<p class="description"><?php esc_html_e( 'Amelia employee creation runs on WF-TUTOR-APPROVED only. Requires Amelia Elite API.', 'nextgencompanion' ); ?></p>
			<?php submit_button( __( 'Save Amelia settings', 'nextgencompanion' ), 'primary', 'ngc_save_amelia_key' ); ?>
		</form>
		<?php
		self::wrap_end();
	}

	/**
	 * MasterStudy status screen.
	 */
	public static function render_masterstudy() {
		self::wrap_start( __( 'MasterStudy LMS Integration Status', 'nextgencompanion' ) );
		$adapter = new NGC_Masterstudy_Adapter();
		self::render_verify_table( $adapter->verify() );
		self::wrap_end();
	}

	/**
	 * Email template manager.
	 */
	public static function render_emails() {
		self::wrap_start( __( 'Email Template Manager', 'nextgencompanion' ) );
		$templates = NGC_Workflow_Email_Templates::all();
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Key', 'nextgencompanion' ); ?></th>
					<th><?php esc_html_e( 'Subject', 'nextgencompanion' ); ?></th>
					<th><?php esc_html_e( 'Trigger', 'nextgencompanion' ); ?></th>
					<th><?php esc_html_e( 'Recipient', 'nextgencompanion' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'nextgencompanion' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $templates as $key => $tpl ) : ?>
				<tr>
					<td><code><?php echo esc_html( $key ); ?></code></td>
					<td><?php echo esc_html( $tpl['subject'] ?? '' ); ?></td>
					<td><?php echo esc_html( $tpl['trigger'] ?? '' ); ?></td>
					<td><?php echo esc_html( $tpl['recipient'] ?? '' ); ?></td>
					<td>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ngc-workflow-emails&ngc_test_email=1&template=' . $key ), 'ngc_test_email' ) ); ?>">
							<?php esc_html_e( 'Test send', 'nextgencompanion' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		self::wrap_end();
	}

	/**
	 * Workflow run logs.
	 */
	public static function render_logs() {
		self::wrap_start( __( 'Workflow Logs', 'nextgencompanion' ) );
		global $wpdb;
		$table = NGC_Database::table( 'workflow_runs' );
		$rows  = [];
		if ( $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 100" );
		}
		?>
		<table class="widefat striped">
			<thead><tr><th>ID</th><th><?php esc_html_e( 'Workflow', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Status', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'When', 'nextgencompanion' ); ?></th></tr></thead>
			<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No workflow runs logged yet.', 'nextgencompanion' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><?php echo (int) $row->id; ?></td>
						<td><code><?php echo esc_html( $row->workflow_key ); ?></code></td>
						<td><?php echo esc_html( $row->status ); ?></td>
						<td><?php echo esc_html( $row->created_at ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
		self::wrap_end();
	}

	/**
	 * Failed workflow retry queue.
	 */
	public static function render_retries() {
		self::wrap_start( __( 'Failed Workflow Retry Queue', 'nextgencompanion' ) );
		$queue = NGC_Workflow_Retry_Queue::all();
		?>
		<table class="widefat striped">
			<thead><tr><th>ID</th><th><?php esc_html_e( 'Workflow', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Step', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Attempts', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Error', 'nextgencompanion' ); ?></th><th></th></tr></thead>
			<tbody>
			<?php if ( empty( $queue ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'Queue empty.', 'nextgencompanion' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $queue as $item ) : ?>
					<tr>
						<td><?php echo esc_html( substr( $item['id'] ?? '', 0, 8 ) ); ?></td>
						<td><?php echo esc_html( $item['workflow'] ?? '' ); ?></td>
						<td><?php echo esc_html( $item['step'] ?? '' ); ?></td>
						<td><?php echo (int) ( $item['attempts'] ?? 0 ); ?></td>
						<td><?php echo esc_html( $item['error'] ?? '' ); ?></td>
						<td>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ngc-workflow-retries&ngc_retry_workflow=1&retry_id=' . rawurlencode( $item['id'] ?? '' ) ), 'ngc_retry_workflow' ) ); ?>">
								<?php esc_html_e( 'Retry', 'nextgencompanion' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
		self::wrap_end();
	}

	/**
	 * Verification dashboard.
	 */
	public static function render_verification() {
		self::wrap_start( __( 'Verification Dashboard', 'nextgencompanion' ) );
		$report = NGC_Workflow_Orchestrator::adapters()['verification']->run_all_checks();
		$ok     = ! empty( $report['ok'] );
		?>
		<p class="ngc-verify-summary <?php echo $ok ? 'is-pass' : 'is-pending'; ?>">
			<strong><?php echo $ok ? esc_html__( 'All checks passed', 'nextgencompanion' ) : esc_html__( 'Some checks need attention', 'nextgencompanion' ); ?></strong>
		</p>
		<table class="widefat striped ngc-verify-matrix">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Check', 'nextgencompanion' ); ?></th>
					<th><?php esc_html_e( 'Status', 'nextgencompanion' ); ?></th>
					<th><?php esc_html_e( 'Details', 'nextgencompanion' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $report as $key => $check ) : ?>
				<?php if ( in_array( $key, [ 'ok', 'version' ], true ) || ! is_array( $check ) ) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<?php
				$status = (string) ( $check['status'] ?? '' );
				$msg    = (string) ( $check['message'] ?? '' );
				$row_class = 'FAIL' === $status ? 'is-fail' : ( 'PASS' === $status ? 'is-pass' : 'is-pending' );
				?>
				<tr class="ngc-verify-row <?php echo esc_attr( $row_class ); ?>">
					<td><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', (string) $key ) ) ); ?></strong></td>
					<td><?php echo esc_html( $status ); ?></td>
					<td><?php echo esc_html( $msg ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		self::wrap_end();
	}

	/**
	 * @param string $title Title.
	 */
	private static function wrap_start( $title ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		$notice = get_transient( 'ngc_admin_notice' );
		if ( $notice ) {
			delete_transient( 'ngc_admin_notice' );
			echo '<div class="notice notice-success"><p>' . esc_html( $notice ) . '</p></div>';
		}
		echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1>';
		echo '<style>.ngc-checklist{list-style:none;margin:0;padding:0;display:grid;gap:6px}.ngc-checklist__item{display:flex;align-items:center;gap:8px;padding:6px 10px;border-radius:6px;background:#f8fafc;border:1px solid #e2e8f0}.ngc-checklist__item.is-ready{border-color:#86efac;background:#ecfdf5}.ngc-checklist__item.is-missing{border-color:#bfdbfe;background:#eff6ff}.ngc-checklist__state{margin-left:auto;font-size:12px;font-weight:600;color:#475569}.ngc-verify-row.is-fail td{color:#b91c1c}.ngc-verify-row.is-pass td{color:#047857}.ngc-verify-row.is-pending td{color:#1d4ed8}</style>';
	}

	/**
	 * Close wrap.
	 */
	private static function wrap_end() {
		echo '</div>';
	}

	/**
	 * @param array<string, mixed> $verify Verification result.
	 */
	private static function render_verify_table( $verify ) {
		$active = ! empty( $verify['active'] );
		$status = (string) ( $verify['status'] ?? '' );
		?>
		<table class="widefat striped ngc-verify-table">
			<tbody>
			<tr>
				<th><?php esc_html_e( 'Active', 'nextgencompanion' ); ?></th>
				<td>
					<span class="ngc-status-pill <?php echo $active ? 'is-pass' : 'is-pending'; ?>">
						<?php echo $active ? '✓ ' . esc_html__( 'Yes', 'nextgencompanion' ) : '○ ' . esc_html__( 'No', 'nextgencompanion' ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Status', 'nextgencompanion' ); ?></th>
				<td><?php echo esc_html( $status ); ?></td>
			</tr>
			<?php if ( ! empty( $verify['lists'] ) && is_array( $verify['lists'] ) ) : ?>
				<tr>
					<th><?php esc_html_e( 'Lists', 'nextgencompanion' ); ?></th>
					<td><?php self::render_status_checklist( $verify['lists'], __( 'CRM list', 'nextgencompanion' ) ); ?></td>
				</tr>
			<?php endif; ?>
			<?php if ( ! empty( $verify['tags'] ) && is_array( $verify['tags'] ) ) : ?>
				<tr>
					<th><?php esc_html_e( 'Tags', 'nextgencompanion' ); ?></th>
					<td><?php self::render_status_checklist( $verify['tags'], __( 'CRM tag', 'nextgencompanion' ) ); ?></td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Human-readable checklist for boolean maps (no JSON).
	 *
	 * @param array<string, bool> $items    Key => ready flag.
	 * @param string              $singular Item label prefix.
	 */
	private static function render_status_checklist( $items, $singular ) {
		if ( empty( $items ) ) {
			echo '<p class="description">' . esc_html__( 'No items configured.', 'nextgencompanion' ) . '</p>';
			return;
		}
		echo '<ul class="ngc-checklist">';
		foreach ( $items as $label => $ready ) {
			$ready = (bool) $ready;
			echo '<li class="ngc-checklist__item ' . ( $ready ? 'is-ready' : 'is-missing' ) . '">';
			echo '<span class="ngc-checklist__icon" aria-hidden="true">' . ( $ready ? '✓' : '○' ) . '</span>';
			echo '<span class="ngc-checklist__label">' . esc_html( (string) $label ) . '</span>';
			echo '<span class="ngc-checklist__state">' . esc_html( $ready ? __( 'Ready', 'nextgencompanion' ) : __( 'Missing', 'nextgencompanion' ) ) . '</span>';
			echo '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Integrate JSON spec manager — import, list, execute via orchestrator.
	 */
	public static function render_integrate_specs() {
		self::wrap_start( __( 'Integrate Workflow Specs', 'nextgencompanion' ) );
		$verify = NGC_Workflow_Spec_Registry::verify();
		$specs  = NGC_Workflow_Spec_Registry::all();
		$exec   = get_transient( 'ngc_integrate_execute_result' );
		if ( $exec ) {
			delete_transient( 'ngc_integrate_execute_result' );
		}
		?>
		<p><?php esc_html_e( 'JSON workflow specifications from integrate/ are imported into the database and executed through NGC_Workflow_Orchestrator.', 'nextgencompanion' ); ?></p>
		<p>
			<strong><?php esc_html_e( 'Loaded:', 'nextgencompanion' ); ?></strong>
			<?php echo esc_html( (string) $verify['specs'] ); ?>
			&nbsp;|&nbsp;
			<strong><?php esc_html_e( 'Stored:', 'nextgencompanion' ); ?></strong>
			<?php echo esc_html( (string) ( $verify['stored'] ?? 0 ) ); ?>
			&nbsp;|&nbsp;
			<strong><?php esc_html_e( 'Events:', 'nextgencompanion' ); ?></strong>
			<?php echo esc_html( (string) $verify['events'] ); ?>
		</p>
		<form method="post" style="margin-bottom:20px;display:flex;gap:8px;flex-wrap:wrap;">
			<?php wp_nonce_field( 'ngc_integrate_specs' ); ?>
			<button type="submit" name="ngc_integrate_import" class="button button-primary"><?php esc_html_e( 'Import from integrate/', 'nextgencompanion' ); ?></button>
			<button type="submit" name="ngc_catalog_import" class="button"><?php esc_html_e( 'Import content-pack catalog', 'nextgencompanion' ); ?></button>
			<button type="submit" name="ngc_automatorwp_import" class="button"><?php esc_html_e( 'Seed AutomatorWP from v2 JSON', 'nextgencompanion' ); ?></button>
		</form>
		<?php
		$catalog = get_transient( 'ngc_catalog_import_result' );
		if ( $catalog ) {
			delete_transient( 'ngc_catalog_import_result' );
			echo '<div class="notice notice-' . ( ! empty( $catalog['ok'] ) ? 'success' : 'warning' ) . ' inline"><p>';
			printf(
				esc_html__( 'Content catalog: %1$d imported, %2$d skipped.', 'nextgencompanion' ),
				(int) ( $catalog['imported'] ?? 0 ),
				(int) ( $catalog['skipped'] ?? 0 )
			);
			echo '</p></div>';
		}
		$awp = get_transient( 'ngc_automatorwp_import_result' );
		if ( $awp ) {
			delete_transient( 'ngc_automatorwp_import_result' );
			echo '<div class="notice notice-' . ( ! empty( $awp['ok'] ) ? 'success' : 'warning' ) . ' inline"><p>';
			printf(
				esc_html__( 'AutomatorWP: %1$d created, %2$d skipped.', 'nextgencompanion' ),
				(int) ( $awp['created'] ?? 0 ),
				(int) ( $awp['skipped'] ?? 0 )
			);
			echo '</p></div>';
		}
		?>
		<?php if ( $exec ) : ?>
			<div class="notice notice-<?php echo ! empty( $exec['ok'] ) ? 'success' : 'error'; ?> inline"><p>
				<?php
				printf(
					/* translators: 1: event 2: ok/fail */
					esc_html__( 'Executed %1$s — %2$s', 'nextgencompanion' ),
					esc_html( (string) ( $exec['event'] ?? '' ) ),
					! empty( $exec['ok'] ) ? esc_html__( 'OK', 'nextgencompanion' ) : esc_html__( 'Failed', 'nextgencompanion' )
				);
				?>
			</p></div>
		<?php endif; ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'nextgencompanion' ); ?></th>
					<th><?php esc_html_e( 'Name', 'nextgencompanion' ); ?></th>
					<th><?php esc_html_e( 'Events', 'nextgencompanion' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'nextgencompanion' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $specs as $spec ) : ?>
				<tr>
					<td><code><?php echo esc_html( (string) ( $spec['id'] ?? '' ) ); ?></code></td>
					<td><?php echo esc_html( (string) ( $spec['name'] ?? '' ) ); ?></td>
					<td><?php echo esc_html( implode( ', ', (array) ( $spec['events'] ?? [] ) ) ); ?></td>
					<td>
						<form method="post" style="display:inline-flex;gap:8px;flex-wrap:wrap;align-items:center;">
							<?php wp_nonce_field( 'ngc_integrate_specs' ); ?>
							<select name="integrate_event">
								<?php foreach ( (array) ( $spec['events'] ?? [] ) as $event ) : ?>
									<option value="<?php echo esc_attr( (string) $event ); ?>"><?php echo esc_html( (string) $event ); ?></option>
								<?php endforeach; ?>
							</select>
							<button type="submit" name="ngc_integrate_execute" class="button"><?php esc_html_e( 'Execute', 'nextgencompanion' ); ?></button>
							<button type="submit" name="ngc_integrate_delete" class="button" onclick="return confirm('<?php echo esc_js( __( 'Delete stored copy only?', 'nextgencompanion' ) ); ?>');"><?php esc_html_e( 'Delete store', 'nextgencompanion' ); ?></button>
							<input type="hidden" name="spec_id" value="<?php echo esc_attr( (string) ( $spec['id'] ?? '' ) ); ?>" />
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		self::wrap_end();
	}
}
