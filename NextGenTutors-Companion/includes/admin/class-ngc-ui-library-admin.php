<?php
/**
 * Admin: Import & Merge UI Library content.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NextGen UI Library → Import & Merge screen.
 */
class NGC_UI_Library_Admin {

	/**
	 * Hook admin menu.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ], 35 );
		add_action( 'admin_post_ngc_ui_import', [ __CLASS__, 'handle_import' ] );
	}

	/**
	 * Register submenu under NGC operations.
	 */
	public static function menu() {
		$parent = function_exists( 'ngt_admin_parent' ) ? ngt_admin_parent() : 'ngt-admin';
		add_submenu_page(
			$parent,
			__( 'UI Library Import', 'nextgencompanion' ),
			__( 'UI Import & Merge', 'nextgencompanion' ),
			'manage_options',
			'ngc-ui-import',
			[ __CLASS__, 'render' ]
		);
	}

	/**
	 * Render admin page.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$scan = NGC_UI_Import_Scanner::scan();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'NextGen UI Library — Import & Merge', 'nextgencompanion' ); ?></h1>
			<p><?php esc_html_e( 'Scan research artifacts, preview sections, and map content to CMS fields. Dynamic tutor/pricing/review values are never imported — only editable copy.', 'nextgencompanion' ); ?></p>

			<h2><?php esc_html_e( 'Artifact scan', 'nextgencompanion' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Source', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Found', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Sections', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Risks', 'nextgencompanion' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $scan['html'] as $row ) : ?>
					<tr>
						<td><code><?php echo esc_html( $row['label'] ?? '' ); ?></code></td>
						<td><?php echo ! empty( $row['found'] ) ? '✓' : '—'; ?></td>
						<td><?php echo esc_html( implode( ', ', array_slice( (array) ( $row['sections'] ?? [] ), 0, 6 ) ) ); ?></td>
						<td><?php echo esc_html( implode( ', ', (array) ( $row['risks'] ?? [] ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Duplicate headings', 'nextgencompanion' ); ?></h2>
			<?php if ( empty( $scan['duplicates'] ) ) : ?>
				<p><?php esc_html_e( 'No duplicates detected in inventory.', 'nextgencompanion' ); ?></p>
			<?php else : ?>
				<ul>
					<?php foreach ( array_slice( $scan['duplicates'], 0, 20 ) as $dupe ) : ?>
						<li>
							<strong><?php echo esc_html( $dupe['item'] ?? '' ); ?></strong>
							— <?php echo esc_html( ( $dupe['source_a'] ?? '' ) . ' / ' . ( $dupe['source_b'] ?? '' ) ); ?>
							(<?php echo esc_html( (string) ( $dupe['similarity'] ?? '' ) ); ?>)
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Data providers', 'nextgencompanion' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Provider', 'nextgencompanion' ); ?></th>
						<th><?php esc_html_e( 'Available', 'nextgencompanion' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $scan['providers'] as $p ) : ?>
					<tr>
						<td><code><?php echo esc_html( $p['provider'] ?? '' ); ?></code></td>
						<td><?php echo ! empty( $p['available'] ) ? '✓' : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Dual UI library mode', 'nextgencompanion' ); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'ngc_ui_library' );
				if ( class_exists( 'NGC_NGT_UI_Bridge' ) ) {
					NGC_NGT_UI_Bridge::render_mode_field();
				}
				submit_button( __( 'Save UI mode', 'nextgencompanion' ) );
				?>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ngc_ui_import' ); ?>
				<input type="hidden" name="action" value="ngc_ui_import" />
				<p>
					<label>
						<input type="checkbox" name="commit" value="1" />
						<?php esc_html_e( 'Commit import (unchecked = dry-run only)', 'nextgencompanion' ); ?>
					</label>
				</p>
				<?php submit_button( __( 'Run import dry-run', 'nextgencompanion' ) ); ?>
			</form>

			<p><a href="<?php echo esc_url( rest_url( 'ngc/v1/ui-library/verify' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'REST verification JSON', 'nextgencompanion' ); ?></a></p>
		</div>
		<?php
	}

	/**
	 * Handle import POST.
	 */
	public static function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'nextgencompanion' ) );
		}
		check_admin_referer( 'ngc_ui_import' );

		$commit = ! empty( $_POST['commit'] );
		$log    = NGC_UI_Import_Scanner::import_items( [], $commit );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => 'ngc-ui-import',
					'ngc_msg' => rawurlencode( wp_json_encode( $log ) ),
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
