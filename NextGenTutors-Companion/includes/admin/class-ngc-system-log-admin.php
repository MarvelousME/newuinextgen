<?php
/**
 * System Log admin screen — charts, drill-down table, export/import.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI for unified system logging.
 */
class NGC_System_Log_Admin {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ], 21 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'admin_post_ngc_system_log_export', [ __CLASS__, 'handle_export' ] );
		add_action( 'admin_post_ngc_system_log_import', [ __CLASS__, 'handle_import' ] );
	}

	/**
	 * Register admin menu.
	 */
	public static function register_menu() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'ngc_view_audit' ) ) {
			return;
		}
		add_submenu_page(
			'ngc-operations',
			__( 'System Log', 'nextgencompanion' ),
			__( 'System Log', 'nextgencompanion' ),
			'ngc_view_audit',
			'ngc-system-log',
			[ __CLASS__, 'render_page' ]
		);
	}

	/**
	 * @param string $hook Admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'ngc-system-log' ) ) {
			return;
		}
		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
			[],
			'4.4.1',
			true
		);
		wp_enqueue_style(
			'ngc-system-log',
			NGC_PLUGIN_URL . 'assets/css/ngc-system-log.css',
			[],
			NGC_VERSION
		);
		wp_enqueue_script(
			'ngc-system-log',
			NGC_PLUGIN_URL . 'assets/js/ngc-system-log.js',
			[ 'jquery', 'chartjs' ],
			NGC_VERSION,
			true
		);
		wp_localize_script(
			'ngc-system-log',
			'ngcSystemLog',
			[
				'restUrl'   => rest_url( 'ngc/v1/platform/system-log' ),
				'statsUrl'  => rest_url( 'ngc/v1/platform/system-log/stats' ),
				'exportUrl' => admin_url( 'admin-post.php' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'exportNonce' => wp_create_nonce( 'ngc_system_log_export' ),
				'i18n'      => [
					'copied'      => __( 'Copied to clipboard', 'nextgencompanion' ),
					'copyFailed'  => __( 'Copy failed', 'nextgencompanion' ),
					'selectRow'   => __( 'Select at least one row', 'nextgencompanion' ),
					'exporting'   => __( 'Exporting…', 'nextgencompanion' ),
				],
			]
		);
	}

	/**
	 * Render admin page shell (data loaded via REST + Chart.js).
	 */
	public static function render_page() {
		if ( ! current_user_can( 'ngc_view_audit' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		?>
		<div class="wrap ngc-system-log-wrap">
			<h1><?php esc_html_e( 'System Log', 'nextgencompanion' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Unified platform logging — workflows, integrations, audit, errors, and fleet events.', 'nextgencompanion' ); ?></p>

			<div class="ngc-sl-toolbar">
				<input type="search" id="ngc-sl-q" placeholder="<?php esc_attr_e( 'Search message, context…', 'nextgencompanion' ); ?>" />
				<select id="ngc-sl-level">
					<option value=""><?php esc_html_e( 'All levels', 'nextgencompanion' ); ?></option>
					<?php foreach ( NGC_System_Log::LEVELS as $lvl ) : ?>
						<option value="<?php echo esc_attr( $lvl ); ?>"><?php echo esc_html( ucfirst( $lvl ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="date" id="ngc-sl-from" />
				<input type="date" id="ngc-sl-to" />
				<button type="button" class="button button-primary" id="ngc-sl-refresh"><?php esc_html_e( 'Refresh', 'nextgencompanion' ); ?></button>
			</div>

			<div class="ngc-sl-charts">
				<div class="ngc-sl-chart-card"><h3><?php esc_html_e( 'By level', 'nextgencompanion' ); ?></h3><canvas id="ngc-sl-chart-level" height="200"></canvas></div>
				<div class="ngc-sl-chart-card"><h3><?php esc_html_e( 'By channel', 'nextgencompanion' ); ?></h3><canvas id="ngc-sl-chart-channel" height="200"></canvas></div>
				<div class="ngc-sl-chart-card"><h3><?php esc_html_e( 'By source', 'nextgencompanion' ); ?></h3><canvas id="ngc-sl-chart-source" height="200"></canvas></div>
				<div class="ngc-sl-chart-card ngc-sl-chart-wide"><h3><?php esc_html_e( 'Volume over time', 'nextgencompanion' ); ?></h3><canvas id="ngc-sl-chart-day" height="120"></canvas></div>
			</div>

			<div class="ngc-sl-actions">
				<button type="button" class="button" id="ngc-sl-copy-selected"><?php esc_html_e( 'Copy selected', 'nextgencompanion' ); ?></button>
				<div class="ngc-sl-export-group">
					<span><?php esc_html_e( 'Export:', 'nextgencompanion' ); ?></span>
					<button type="button" class="button ngc-sl-export" data-format="csv">CSV</button>
					<button type="button" class="button ngc-sl-export" data-format="excel">Excel</button>
					<button type="button" class="button ngc-sl-export" data-format="pdf">PDF</button>
					<button type="button" class="button ngc-sl-export" data-format="json">JSON</button>
					<label class="button"><input type="checkbox" id="ngc-sl-export-selected-only" /> <?php esc_html_e( 'Selected only', 'nextgencompanion' ); ?></label>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="ngc-sl-import-form">
					<?php wp_nonce_field( 'ngc_system_log_import' ); ?>
					<input type="hidden" name="action" value="ngc_system_log_import" />
					<input type="file" name="import_file" accept=".csv,text/csv" required />
					<?php submit_button( __( 'Import CSV', 'nextgencompanion' ), 'secondary', '', false ); ?>
				</form>
			</div>

			<p id="ngc-sl-total" class="ngc-sl-total"></p>
			<div class="ngc-sl-table-wrap">
				<table class="widefat striped ngc-sl-table" id="ngc-sl-table">
					<thead>
						<tr>
							<th class="check-column"><input type="checkbox" id="ngc-sl-select-all" /></th>
							<th><?php esc_html_e( 'ID', 'nextgencompanion' ); ?></th>
							<th><?php esc_html_e( 'Time', 'nextgencompanion' ); ?></th>
							<th><?php esc_html_e( 'Level', 'nextgencompanion' ); ?></th>
							<th><?php esc_html_e( 'Channel', 'nextgencompanion' ); ?></th>
							<th><?php esc_html_e( 'Source', 'nextgencompanion' ); ?></th>
							<th><?php esc_html_e( 'Message', 'nextgencompanion' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'nextgencompanion' ); ?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
			<div class="ngc-sl-pagination">
				<button type="button" class="button" id="ngc-sl-prev"><?php esc_html_e( 'Previous', 'nextgencompanion' ); ?></button>
				<span id="ngc-sl-page-info"></span>
				<button type="button" class="button" id="ngc-sl-next"><?php esc_html_e( 'Next', 'nextgencompanion' ); ?></button>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle export download.
	 */
	public static function handle_export() {
		if ( ! current_user_can( 'ngc_view_audit' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_system_log_export' );

		$format = sanitize_key( $_POST['format'] ?? 'csv' );
		$args   = [
			'q'     => sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) ),
			'level' => sanitize_key( $_POST['level'] ?? '' ),
			'from'  => sanitize_text_field( wp_unslash( $_POST['from'] ?? '' ) ),
			'to'    => sanitize_text_field( wp_unslash( $_POST['to'] ?? '' ) ),
			'limit' => 5000,
		];
		if ( ! empty( $_POST['ids'] ) ) {
			$args['ids'] = array_map( 'intval', explode( ',', sanitize_text_field( wp_unslash( $_POST['ids'] ) ) ) );
		}

		$rows    = NGC_System_Log_Service::flatten_for_export( NGC_System_Log_Service::search( $args ) );
		$columns = [ 'id', 'uuid', 'level', 'channel', 'source', 'message', 'context', 'user_id', 'correlation_id', 'ip_address', 'created_at' ];
		$content = NGC_Export_Formats::render( $rows, $columns, $format );
		$ext     = NGC_Export_Formats::extension( $format );
		$name    = 'ngc-system-log-' . gmdate( 'Ymd-His' ) . '.' . $ext;

		header( 'Content-Type: ' . NGC_Export_Formats::mime_type( $format ) );
		header( 'Content-Disposition: attachment; filename="' . $name . '"' );
		header( 'Content-Length: ' . strlen( $content ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $content;
		exit;
	}

	/**
	 * Handle CSV import.
	 */
	public static function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_system_log_import' );

		if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
			wp_safe_redirect( add_query_arg( 'ngc_sl_import', 'missing', admin_url( 'admin.php?page=ngc-system-log' ) ) );
			exit;
		}

		$fh = fopen( $_FILES['import_file']['tmp_name'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $fh ) {
			wp_safe_redirect( add_query_arg( 'ngc_sl_import', 'error', admin_url( 'admin.php?page=ngc-system-log' ) ) );
			exit;
		}

		$headers = fgetcsv( $fh );
		$parsed  = [];
		while ( ( $line = fgetcsv( $fh ) ) !== false ) {
			$row = [];
			foreach ( (array) $headers as $i => $key ) {
				$row[ sanitize_key( $key ) ] = $line[ $i ] ?? '';
			}
			$parsed[] = $row;
		}
		fclose( $fh );

		$result = NGC_System_Log_Service::import_rows( $parsed );
		NGC_System_Log::info( 'system_log', 'import', 'System log CSV import', $result );

		wp_safe_redirect(
			add_query_arg(
				[
					'ngc_sl_import' => 'ok',
					'imported'      => $result['imported'],
				],
				admin_url( 'admin.php?page=ngc-system-log' )
			)
		);
		exit;
	}
}
