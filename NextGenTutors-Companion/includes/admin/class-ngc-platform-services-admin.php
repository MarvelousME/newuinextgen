<?php
/**
 * Admin screens for platform services pillars.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Platform services admin UI.
 */
class NGC_Platform_Services_Admin {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menus' ], 20 );
		add_action( 'admin_post_ngc_run_export', [ __CLASS__, 'handle_export' ] );
		add_action( 'admin_post_ngc_run_repair', [ __CLASS__, 'handle_repair' ] );
		add_action( 'admin_post_ngc_save_ai_settings', [ __CLASS__, 'handle_ai_settings' ] );
	}

	/**
	 * Register admin menus.
	 */
	public static function register_menus() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_submenu_page( function_exists('ngt_admin_parent') ? ngt_admin_parent() : 'ngt-admin', __( 'Gamification', 'nextgencompanion' ), __( 'Gamification', 'nextgencompanion' ), 'manage_options', 'ngc-gamification', [ __CLASS__, 'render_gamification' ] );
		add_submenu_page( function_exists('ngt_admin_parent') ? ngt_admin_parent() : 'ngt-admin', __( 'Export Engine', 'nextgencompanion' ), __( 'Exports', 'nextgencompanion' ), 'manage_options', 'ngc-exports', [ __CLASS__, 'render_exports' ] );
		add_submenu_page( function_exists('ngt_admin_parent') ? ngt_admin_parent() : 'ngt-admin', __( 'Audit Log', 'nextgencompanion' ), __( 'Audit Log', 'nextgencompanion' ), 'ngc_view_audit', 'ngc-audit', [ __CLASS__, 'render_audit' ] );
		add_submenu_page( function_exists('ngt_admin_parent') ? ngt_admin_parent() : 'ngt-admin', __( 'AI Diagnostics', 'nextgencompanion' ), __( 'AI Diagnostics', 'nextgencompanion' ), 'manage_options', 'ngc-ai-diagnostics', [ __CLASS__, 'render_diagnostics' ] );
	}

	/**
	 * Gamification admin page.
	 */
	public static function render_gamification() {
		$boards = NGC_Leaderboard_Engine::board_keys();
		$board  = sanitize_key( $_GET['board'] ?? 'overall' );
		$rows   = NGC_Leaderboard_Engine::get( $board, 'all_time', 25 );
		if ( empty( $rows ) ) {
			$rows = NGC_Leaderboard_Engine::compute( $board, 'all_time', 25 );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Gamification Platform', 'nextgencompanion' ); ?></h1>
			<p><?php esc_html_e( 'GamiPress integration + internal scoring, achievements, and leaderboards.', 'nextgencompanion' ); ?></p>
			<p><strong>GamiPress:</strong> <?php echo NGC_Gamipress_Adapter::is_active() ? esc_html__( 'Active', 'nextgencompanion' ) : esc_html__( 'Not active (internal engine only)', 'nextgencompanion' ); ?></p>
			<form method="get"><input type="hidden" name="page" value="ngc-gamification" />
				<select name="board"><?php foreach ( $boards as $b ) : ?><option value="<?php echo esc_attr( $b ); ?>" <?php selected( $board, $b ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $b ) ) ); ?></option><?php endforeach; ?></select>
				<?php submit_button( __( 'View Leaderboard', 'nextgencompanion' ), 'secondary', '', false ); ?>
			</form>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Rank', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'User', 'nextgencompanion' ); ?></th><th><?php esc_html_e( 'Score', 'nextgencompanion' ); ?></th></tr></thead><tbody>
			<?php foreach ( $rows as $row ) : ?><tr><td><?php echo esc_html( (string) $row['rank_position'] ); ?></td><td><?php echo esc_html( (string) ( $row['meta']['display_name'] ?? $row['user_id'] ) ); ?></td><td><?php echo esc_html( (string) $row['score'] ); ?></td></tr><?php endforeach; ?>
			</tbody></table>
		</div>
		<?php
	}

	/**
	 * Export admin page.
	 */
	public static function render_exports() {
		$datasets = NGC_Export_Engine::datasets();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Export Engine', 'nextgencompanion' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ngc_run_export' ); ?>
				<input type="hidden" name="action" value="ngc_run_export" />
				<table class="form-table">
					<tr><th><?php esc_html_e( 'Dataset', 'nextgencompanion' ); ?></th><td><select name="dataset"><?php foreach ( $datasets as $ds ) : ?><option value="<?php echo esc_attr( $ds ); ?>"><?php echo esc_html( $ds ); ?></option><?php endforeach; ?></select></td></tr>
					<tr><th><?php esc_html_e( 'Format', 'nextgencompanion' ); ?></th><td><select name="format"><option value="csv">CSV</option><option value="json">JSON</option><option value="pdf">PDF</option><option value="excel">Excel</option></select></td></tr>
					<tr><th><?php esc_html_e( 'From', 'nextgencompanion' ); ?></th><td><input type="date" name="from" /></td></tr>
					<tr><th><?php esc_html_e( 'To', 'nextgencompanion' ); ?></th><td><input type="date" name="to" /></td></tr>
					<tr><th><?php esc_html_e( 'Background', 'nextgencompanion' ); ?></th><td><label><input type="checkbox" name="background" value="1" /> <?php esc_html_e( 'Queue background export with email delivery', 'nextgencompanion' ); ?></label></td></tr>
				</table>
				<?php submit_button( __( 'Run Export', 'nextgencompanion' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Audit admin page.
	 */
	public static function render_audit() {
		if ( ! current_user_can( 'ngc_view_audit' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		$q    = sanitize_text_field( $_GET['q'] ?? '' );
		$rows = NGC_Audit_Service::search( [ 'q' => $q, 'limit' => 100 ] );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Audit Framework', 'nextgencompanion' ); ?></h1>
			<p class="description"><?php esc_html_e( 'System-wide audit trail — workflows, auth, integrations, and platform events.', 'nextgencompanion' ); ?></p>
			<form method="get"><input type="hidden" name="page" value="ngc-audit" />
				<input type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php esc_attr_e( 'Search actions, objects…', 'nextgencompanion' ); ?>" />
				<?php submit_button( __( 'Search', 'nextgencompanion' ), 'secondary', '', false ); ?>
			</form>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'When', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Actor', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Action', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Object', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Details', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Result', 'nextgencompanion' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No audit entries found.', 'nextgencompanion' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( NGC_Audit_Presenter::format_time( $row['created_at'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( NGC_Audit_Presenter::human_actor( $row ) ); ?></td>
							<td><?php echo esc_html( NGC_Audit_Presenter::human_action( $row['action'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( NGC_Audit_Presenter::human_object( $row ) ); ?></td>
							<td><?php echo esc_html( NGC_Audit_Presenter::human_detail( $row ) ); ?></td>
							<td><?php echo esc_html( NGC_Audit_Presenter::human_result( $row['result'] ?? 'success' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * AI diagnostics admin page.
	 */
	public static function render_diagnostics() {
		$scan      = NGC_Health_Scanner::full_scan();
		$settings  = NGC_Ai_Provider_Registry::get_settings();
		$models    = $settings['models'] ?? [];
		$selected  = $settings['diagnostics_model_id'] ?? '';
		$ai_suite  = admin_url( 'admin.php?page=ngc-ai-suite' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'AI Diagnostics Platform', 'nextgencompanion' ); ?></h1>
			<p class="description">
				<?php
				printf(
					/* translators: %s: AI Suite admin URL */
					esc_html__( 'Health scans and repair plans. LLM-assisted analysis uses models from %s (single BYOK store).', 'nextgencompanion' ),
					'<a href="' . esc_url( $ai_suite ) . '">' . esc_html__( 'NextGen → AI Suite', 'nextgencompanion' ) . '</a>'
				);
				?>
			</p>
			<h2><?php esc_html_e( 'Health Scan', 'nextgencompanion' ); ?></h2>
			<?php NGC_Admin::render_scanner_summary( $scan ); ?>
			<h2><?php esc_html_e( 'Diagnostics model', 'nextgencompanion' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ngc_save_ai_settings' ); ?>
				<input type="hidden" name="action" value="ngc_save_ai_settings" />
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Model for health LLM', 'nextgencompanion' ); ?></th>
						<td>
							<select name="diagnostics_model_id">
								<option value=""><?php esc_html_e( '— Auto (first model with key) —', 'nextgencompanion' ); ?></option>
								<?php foreach ( $models as $model ) : ?>
									<option value="<?php echo esc_attr( $model['id'] ); ?>" <?php selected( $selected, $model['id'] ); ?>>
										<?php echo esc_html( $model['label'] . ( $model['has_key'] ? '' : ' (no key)' ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<?php if ( empty( $models ) ) : ?>
								<p class="description"><?php esc_html_e( 'No models yet.', 'nextgencompanion' ); ?> <a href="<?php echo esc_url( $ai_suite ); ?>"><?php esc_html_e( 'Add a model in AI Suite', 'nextgencompanion' ); ?></a></p>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save diagnostics model', 'nextgencompanion' ) ); ?>
			</form>
			<h2><?php esc_html_e( 'Self-Healing Repair', 'nextgencompanion' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ngc_run_repair' ); ?>
				<input type="hidden" name="action" value="ngc_run_repair" />
				<label><input type="checkbox" name="dry_run" value="1" checked /> <?php esc_html_e( 'Dry run first', 'nextgencompanion' ); ?></label><br><br>
				<label><input type="checkbox" name="approved" value="1" /> <?php esc_html_e( 'I approve executing repairs', 'nextgencompanion' ); ?></label><br><br>
				<?php submit_button( __( 'Run Repair Engine', 'nextgencompanion' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}

	/** Handle export form. */
	public static function handle_export() {
		check_admin_referer( 'ngc_run_export' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		$config = [
			'dataset' => sanitize_key( $_POST['dataset'] ?? 'bookings' ),
			'format'  => sanitize_key( $_POST['format'] ?? 'csv' ),
			'filters' => array_filter( [
				'from' => sanitize_text_field( $_POST['from'] ?? '' ),
				'to'   => sanitize_text_field( $_POST['to'] ?? '' ),
			] ),
		];
		if ( ! empty( $_POST['background'] ) ) {
			NGC_Export_Scheduler::queue( $config );
			wp_safe_redirect( add_query_arg( 'exported', 'queued', admin_url( 'admin.php?page=ngc-exports' ) ) );
			exit;
		}
		$result = NGC_Export_Engine::run_export( $config );
		if ( ! empty( $result['url'] ) ) {
			wp_safe_redirect( $result['url'] );
			exit;
		}
		wp_safe_redirect( add_query_arg( 'exported', 'failed', admin_url( 'admin.php?page=ngc-exports' ) ) );
		exit;
	}

	/** Handle repair form. */
	public static function handle_repair() {
		check_admin_referer( 'ngc_run_repair' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		$result = NGC_Repair_Engine::execute( [
			'dry_run'  => ! empty( $_POST['dry_run'] ),
			'approved' => ! empty( $_POST['approved'] ),
		] );
		set_transient( 'ngc_repair_result', $result, 300 );
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-ai-diagnostics&repaired=1' ) );
		exit;
	}

	/** Handle AI settings form. */
	public static function handle_ai_settings() {
		check_admin_referer( 'ngc_save_ai_settings' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		NGC_Ai_Provider_Registry::save_settings( [
			'diagnostics_model_id' => sanitize_key( $_POST['diagnostics_model_id'] ?? '' ),
		] );
		wp_safe_redirect( admin_url( 'admin.php?page=ngc-ai-diagnostics&saved=1' ) );
		exit;
	}
}
