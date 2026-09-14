<?php
/**
 * Import / Export admin screen.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Admin_Import_Export {

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'ngt-3d-scroll' ) );
		}

		$export   = NGT3D_Rule_Repository::export();
		$json     = wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		$imported = isset( $_GET['ngt3d_imported'] ) ? absint( $_GET['ngt3d_imported'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$errors   = isset( $_GET['ngt3d_errors'] ) ? absint( $_GET['ngt3d_errors'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		NGT3D_Admin::page_header( __( 'Import / Export', 'ngt-3d-scroll' ) );
		?>
		<hr class="wp-header-end" />

		<?php if ( null !== $imported ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					printf(
						/* translators: 1: imported count, 2: error count */
						esc_html__( 'Imported %1$d rule(s). Errors: %2$d.', 'ngt-3d-scroll' ),
						(int) $imported,
						(int) $errors
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<div class="ngt3d-two-col">
			<section>
				<h2><?php esc_html_e( 'Export', 'ngt-3d-scroll' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Copy or download the current rules as canonical JSON.', 'ngt-3d-scroll' ); ?></p>
				<textarea class="large-text code" rows="16" readonly id="ngt3d-export-json"><?php echo esc_textarea( (string) $json ); ?></textarea>
				<p>
					<button type="button" class="button" onclick="navigator.clipboard.writeText(document.getElementById('ngt3d-export-json').value)">
						<?php esc_html_e( 'Copy JSON', 'ngt-3d-scroll' ); ?>
					</button>
					<a
						class="button button-primary"
						download="ngt-3d-scroll-export.json"
						href="data:application/json;charset=utf-8,<?php echo rawurlencode( (string) $json ); ?>"
					>
						<?php esc_html_e( 'Download JSON', 'ngt-3d-scroll' ); ?>
					</a>
				</p>
			</section>

			<section>
				<h2><?php esc_html_e( 'Import', 'ngt-3d-scroll' ); ?></h2>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ngt3d_import" />
					<?php wp_nonce_field( 'ngt3d_import', 'ngt3d_nonce' ); ?>
					<p>
						<label for="ngt3d_import_file"><?php esc_html_e( 'Upload JSON file', 'ngt-3d-scroll' ); ?></label><br />
						<input type="file" id="ngt3d_import_file" name="ngt3d_import_file" accept="application/json,.json" />
					</p>
					<p>
						<label for="ngt3d_import_json"><?php esc_html_e( 'Or paste JSON', 'ngt-3d-scroll' ); ?></label>
						<textarea class="large-text code" rows="12" id="ngt3d_import_json" name="ngt3d_import_json" placeholder='{"schemaVersion":1,"3DScrollPages":[]}'></textarea>
					</p>
					<?php submit_button( __( 'Import Rules', 'ngt-3d-scroll' ) ); ?>
				</form>
			</section>
		</div>
		<?php
		NGT3D_Admin::page_footer();
	}
}
