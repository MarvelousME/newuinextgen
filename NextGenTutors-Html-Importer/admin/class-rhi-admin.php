<?php
/**
 * Admin screen — REVAMP HTML Content Importer.
 *
 * @package RevampHtmlImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI and AJAX handlers.
 */
class RHI_Admin {

	const MENU_SLUG = 'revamp-html-importer';

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'wp_ajax_rhi_scan', [ __CLASS__, 'ajax_scan' ] );
		add_action( 'wp_ajax_rhi_import', [ __CLASS__, 'ajax_import' ] );
		add_action( 'wp_ajax_rhi_rollback', [ __CLASS__, 'ajax_rollback' ] );
	}

	public static function register_menu() {
		add_management_page(
			__( 'REVAMP HTML Content Importer', 'revamp-html-importer' ),
			__( 'REVAMP HTML Importer', 'revamp-html-importer' ),
			'manage_options',
			self::MENU_SLUG,
			[ __CLASS__, 'render_page' ]
		);
	}

	/**
	 * @param string $hook Hook suffix.
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'tools_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'rhi-admin', RHI_PLUGIN_URL . 'assets/admin.css', [], RHI_VERSION );
		wp_enqueue_style( 'rhi-beyond-infinity-admin-ui', RHI_PLUGIN_URL . 'assets/nextgen-beyond-infinity-admin.css', [ 'rhi-admin' ], RHI_VERSION );
		wp_enqueue_script( 'rhi-admin', RHI_PLUGIN_URL . 'assets/admin.js', [ 'jquery' ], RHI_VERSION, true );
		wp_enqueue_script( 'rhi-beyond-infinity-admin-js', RHI_PLUGIN_URL . 'assets/nextgen-beyond-infinity-admin.js', [], RHI_VERSION, true );
		wp_localize_script(
			'rhi-admin',
			'rhiAdmin',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'rhi_admin' ),
				'i18n'    => [
					'scanning'  => __( 'Scanning…', 'revamp-html-importer' ),
					'importing' => __( 'Importing…', 'revamp-html-importer' ),
					'error'     => __( 'Request failed.', 'revamp-html-importer' ),
				],
			]
		);
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'revamp-html-importer' ) );
		}

		$dir     = get_option( 'rhi_source_directory', class_exists( 'RHI_Source_Resolver' ) ? RHI_Source_Resolver::resolve( false ) : '' );
		$report  = RHI_Logger::get_report();
		$log     = array_slice( array_reverse( RHI_Logger::get_log() ), 0, 30 );
		$scanned = get_transient( 'rhi_last_scan' );
		?>
		<div class="wrap rhi-wrap">
			<h1><?php esc_html_e( 'REVAMP HTML Content Importer', 'revamp-html-importer' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Import static HTML page content into WordPress. Default mode is dry-run — no pages are modified until you disable it.', 'revamp-html-importer' ); ?></p>

			<div class="rhi-panel">
				<h2><?php esc_html_e( 'Source Directory', 'revamp-html-importer' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="rhi-directory"><?php esc_html_e( 'HTML directory path', 'revamp-html-importer' ); ?></label></th>
						<td>
							<input type="text" id="rhi-directory" class="large-text code" value="<?php echo esc_attr( $dir ); ?>" />
							<p class="description"><?php esc_html_e( 'Absolute path to webpages-content folder on the server. Docker default: /var/www/html/wp-content/ngt-html-source', 'revamp-html-importer' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Options', 'revamp-html-importer' ); ?></th>
						<td>
							<label><input type="checkbox" id="rhi-dry-run" checked /> <?php esc_html_e( 'Dry run (preview only)', 'revamp-html-importer' ); ?></label><br>
							<label><input type="checkbox" id="rhi-force" /> <?php esc_html_e( 'Force update (ignore hash & page builder warning)', 'revamp-html-importer' ); ?></label><br>
							<label><input type="checkbox" id="rhi-publish" /> <?php esc_html_e( 'Publish pages (default: draft)', 'revamp-html-importer' ); ?></label>
						</td>
					</tr>
				</table>
				<p class="submit">
					<button type="button" class="button button-primary" id="rhi-scan-btn"><?php esc_html_e( 'Scan Directory', 'revamp-html-importer' ); ?></button>
					<button type="button" class="button" id="rhi-import-confident-btn" disabled><?php esc_html_e( 'Import All Confident Matches (≥80%)', 'revamp-html-importer' ); ?></button>
					<button type="button" class="button button-secondary" id="rhi-import-selected-btn" disabled><?php esc_html_e( 'Import Selected', 'revamp-html-importer' ); ?></button>
					<button type="button" class="button button-link-delete" id="rhi-rollback-btn"><?php esc_html_e( 'Rollback All Backups', 'revamp-html-importer' ); ?></button>
				</p>
			</div>

			<div id="rhi-status" class="rhi-status" aria-live="polite"></div>

			<div class="rhi-panel">
				<h2><?php esc_html_e( 'Mapping Preview', 'revamp-html-importer' ); ?></h2>
				<table class="widefat striped rhi-mapping-table" id="rhi-mapping-table">
					<thead>
						<tr>
							<td class="check-column"><input type="checkbox" id="rhi-select-all" /></td>
							<th><?php esc_html_e( 'HTML File', 'revamp-html-importer' ); ?></th>
							<th><?php esc_html_e( 'Detected Title', 'revamp-html-importer' ); ?></th>
							<th><?php esc_html_e( 'WP Slug', 'revamp-html-importer' ); ?></th>
							<th><?php esc_html_e( 'WP Page', 'revamp-html-importer' ); ?></th>
							<th><?php esc_html_e( 'Confidence', 'revamp-html-importer' ); ?></th>
							<th><?php esc_html_e( 'Action', 'revamp-html-importer' ); ?></th>
							<th><?php esc_html_e( 'Notes', 'revamp-html-importer' ); ?></th>
						</tr>
					</thead>
					<tbody id="rhi-mapping-body">
						<?php if ( is_array( $scanned ) && $scanned ) : ?>
							<?php self::render_mapping_rows( $scanned ); ?>
						<?php else : ?>
							<tr><td colspan="8"><?php esc_html_e( 'Run a scan to preview mappings.', 'revamp-html-importer' ); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<?php if ( $report ) : ?>
			<div class="rhi-panel">
				<h2><?php esc_html_e( 'Last Import Report', 'revamp-html-importer' ); ?></h2>
				<pre class="rhi-report"><?php echo esc_html( wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre>
			</div>
			<?php endif; ?>

			<div class="rhi-panel">
				<h2><?php esc_html_e( 'Error Log', 'revamp-html-importer' ); ?></h2>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Time', 'revamp-html-importer' ); ?></th><th><?php esc_html_e( 'Level', 'revamp-html-importer' ); ?></th><th><?php esc_html_e( 'Message', 'revamp-html-importer' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $log as $entry ) : ?>
						<tr>
							<td><?php echo esc_html( $entry['time'] ?? '' ); ?></td>
							<td><?php echo esc_html( $entry['level'] ?? '' ); ?></td>
							<td><?php echo esc_html( $entry['message'] ?? '' ); ?></td>
						</tr>
					<?php endforeach; ?>
					<?php if ( ! $log ) : ?><tr><td colspan="3"><?php esc_html_e( 'No log entries yet.', 'revamp-html-importer' ); ?></td></tr><?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * @param array<int, array<string, mixed>> $files Files.
	 */
	public static function render_mapping_rows( $files ) {
		foreach ( $files as $file ) {
			$conf = (int) ( $file['confidence'] ?? 0 );
			$class = 'rhi-confidence';
			if ( $conf >= 80 ) {
				$class .= ' rhi-confidence--high';
			} elseif ( $conf >= 60 ) {
				$class .= ' rhi-confidence--medium';
			} else {
				$class .= ' rhi-confidence--low';
			}
			$rel = esc_attr( $file['relative_path'] ?? '' );
			?>
			<tr data-file="<?php echo $rel; ?>">
				<th scope="row" class="check-column"><input type="checkbox" class="rhi-file-check" value="<?php echo $rel; ?>" /></th>
				<td><code><?php echo esc_html( $file['relative_path'] ?? '' ); ?></code></td>
				<td><?php echo esc_html( $file['detected_title'] ?? $file['title'] ?? '' ); ?></td>
				<td><code><?php echo esc_html( $file['suggested_slug'] ?? '' ); ?></code></td>
				<td><?php echo esc_html( $file['wp_page_title'] ? $file['wp_page_title'] . ' (#' . (int) $file['wp_page_id'] . ')' : '—' ); ?></td>
				<td><span class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( (string) $conf ); ?>%</span></td>
				<td><strong><?php echo esc_html( $file['action'] ?? '' ); ?></strong></td>
				<td><?php echo esc_html( $file['notes'] ?? '' ); ?></td>
			</tr>
			<?php
		}
	}

	public static function ajax_scan() {
		self::verify_ajax();
		$dir = sanitize_text_field( wp_unslash( $_POST['directory'] ?? '' ) );
		if ( ! $dir ) {
			wp_send_json_error( [ 'message' => __( 'Directory path required.', 'revamp-html-importer' ) ] );
		}
		update_option( 'rhi_source_directory', $dir, false );
		$result = RHI_Scanner::scan( $dir );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}
		set_transient( 'rhi_last_scan', $result, HOUR_IN_SECONDS );
		ob_start();
		self::render_mapping_rows( $result );
		$html = ob_get_clean();
		wp_send_json_success( [
			'count' => count( $result ),
			'rows'  => $html,
			'files' => $result,
		] );
	}

	public static function ajax_import() {
		self::verify_ajax();
		$files = get_transient( 'rhi_last_scan' );
		if ( ! is_array( $files ) || ! $files ) {
			wp_send_json_error( [ 'message' => __( 'Run a scan first.', 'revamp-html-importer' ) ] );
		}
		$selected = isset( $_POST['files'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['files'] ) ) : null;
		$options  = [
			'dry_run'        => ! empty( $_POST['dry_run'] ),
			'force'          => ! empty( $_POST['force'] ),
			'publish'        => ! empty( $_POST['publish'] ),
			'files'          => $selected,
			'min_confidence' => isset( $_POST['min_confidence'] ) ? (int) $_POST['min_confidence'] : 80,
		];
		$report = RHI_Importer::run( $files, $options );
		wp_send_json_success( [ 'report' => $report ] );
	}

	public static function ajax_rollback() {
		self::verify_ajax();
		$result = RHI_Rollback::rollback_all();
		wp_send_json_success( [ 'result' => $result ] );
	}

	private static function verify_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied.', 'revamp-html-importer' ) ] );
		}
		check_ajax_referer( 'rhi_admin', 'nonce' );
	}
}
