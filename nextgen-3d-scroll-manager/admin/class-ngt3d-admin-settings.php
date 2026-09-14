<?php
/**
 * Settings admin screen.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Admin_Settings {

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'ngt-3d-scroll' ) );
		}

		$settings = NGT3D_Runtime_Config::get_settings();

		NGT3D_Admin::page_header( __( 'Settings', 'ngt-3d-scroll' ) );
		?>
		<hr class="wp-header-end" />

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ngt3d_save_settings" />
			<?php wp_nonce_field( 'ngt3d_settings', 'ngt3d_nonce' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable 3D Scrolling Engine', 'ngt-3d-scroll' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="engine_enabled" value="1" <?php checked( ! isset( $settings['engine_enabled'] ) || ! empty( $settings['engine_enabled'] ) ); ?> />
							<?php esc_html_e( 'Global kill switch — when off, no NGT3D runtime/assets load and homepage returns to unenhanced state.', 'ngt-3d-scroll' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Debug mode', 'ngt-3d-scroll' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="debug_mode" value="1" <?php checked( ! empty( $settings['debug_mode'] ) ); ?> />
							<?php esc_html_e( 'Enable frontend debug markers / console output', 'ngt-3d-scroll' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Lenis smooth scroll', 'ngt-3d-scroll' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="lenis_enabled" value="1" <?php checked( ! empty( $settings['lenis_enabled'] ) ); ?> />
							<?php esc_html_e( 'Enable Lenis (when available)', 'ngt-3d-scroll' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="lenis_duration"><?php esc_html_e( 'Lenis duration', 'ngt-3d-scroll' ); ?></label></th>
					<td>
						<input type="number" step="0.1" min="0.1" max="5" class="small-text" id="lenis_duration" name="lenis_duration" value="<?php echo esc_attr( (string) $settings['lenis_duration'] ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="lenis_easing"><?php esc_html_e( 'Lenis easing', 'ngt-3d-scroll' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="lenis_easing" name="lenis_easing" value="<?php echo esc_attr( (string) $settings['lenis_easing'] ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="default_perspective"><?php esc_html_e( 'Default perspective', 'ngt-3d-scroll' ); ?></label></th>
					<td>
						<input type="number" min="400" max="3000" class="small-text" id="default_perspective" name="default_perspective" value="<?php echo esc_attr( (string) $settings['default_perspective'] ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'FPS safeguard', 'ngt-3d-scroll' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="fps_safeguard" value="1" <?php checked( ! empty( $settings['fps_safeguard'] ) ); ?> />
							<?php esc_html_e( 'Auto-reduce effects when FPS drops', 'ngt-3d-scroll' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fps_threshold"><?php esc_html_e( 'FPS threshold', 'ngt-3d-scroll' ); ?></label></th>
					<td>
						<input type="number" min="10" max="60" class="small-text" id="fps_threshold" name="fps_threshold" value="<?php echo esc_attr( (string) $settings['fps_threshold'] ); ?>" />
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'ngt-3d-scroll' ) ); ?>
		</form>
		<?php
		NGT3D_Admin::page_footer();
	}
}
