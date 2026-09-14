<?php
/**
 * Admin Add / Edit Rule screen.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Admin_Edit {

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'ngt-3d-scroll' ) );
		}

		$edit_id = absint( $_GET['edit'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$rule    = $edit_id ? NGT3D_Rule_Repository::get( $edit_id ) : null;

		$defaults = [
			'id'                => 0,
			'page_id'           => 0,
			'page_slug'         => 'home',
			'target_selector'   => '',
			'target_type'       => 'selector',
			'animation_names'   => '',
			'style_classes'     => '',
			'animation_options' => [],
			'sort_order'        => 10,
			'enabled'           => 1,
			'desktop_mode'      => 'full',
			'tablet_mode'       => 'reduced',
			'mobile_mode'       => 'reduced',
		];

		$data      = wp_parse_args( is_array( $rule ) ? $rule : [], $defaults );
		$registry  = NGT3D_Animation_Registry::all();
		$mode_opts = [ 'full', 'reduced', 'disabled' ];
		$title     = $edit_id
			? __( 'Edit Rule', 'ngt-3d-scroll' )
			: __( 'Add Rule', 'ngt-3d-scroll' );

		$options_json = wp_json_encode(
			is_array( $data['animation_options'] ) ? $data['animation_options'] : [],
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);

		NGT3D_Admin::page_header( $title );
		?>
		<hr class="wp-header-end" />

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ngt3d-edit-form">
			<input type="hidden" name="action" value="ngt3d_save_rule" />
			<input type="hidden" name="rule_id" value="<?php echo esc_attr( (string) $data['id'] ); ?>" />
			<?php wp_nonce_field( 'ngt3d_save_rule', 'ngt3d_nonce' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="page_slug"><?php esc_html_e( 'Page slug', 'ngt-3d-scroll' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="page_slug" name="page_slug" value="<?php echo esc_attr( $data['page_slug'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Use home, front-page, all, or a page slug.', 'ngt-3d-scroll' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="page_id"><?php esc_html_e( 'Page ID', 'ngt-3d-scroll' ); ?></label></th>
					<td>
						<input type="number" min="0" class="small-text" id="page_id" name="page_id" value="<?php echo esc_attr( (string) $data['page_id'] ); ?>" />
						<p class="description"><?php esc_html_e( '0 = resolve by slug only.', 'ngt-3d-scroll' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="target_selector"><?php esc_html_e( 'Target selector', 'ngt-3d-scroll' ); ?></label></th>
					<td>
						<input type="text" class="regular-text code" id="target_selector" name="target_selector" required value="<?php echo esc_attr( $data['target_selector'] ); ?>" placeholder="#hero" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="target_type"><?php esc_html_e( 'Target type', 'ngt-3d-scroll' ); ?></label></th>
					<td>
						<select id="target_type" name="target_type">
							<?php foreach ( [ 'selector', 'id', 'class', 'data-attr', 'element' ] as $type ) : ?>
								<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $data['target_type'], $type ); ?>><?php echo esc_html( $type ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="animation_names"><?php esc_html_e( 'Animations', 'ngt-3d-scroll' ); ?></label></th>
					<td>
						<input type="text" class="large-text code" id="animation_names" name="animation_names" value="<?php echo esc_attr( $data['animation_names'] ); ?>" placeholder="depth-hero,perspective-exit" />
						<p class="description"><?php esc_html_e( 'Comma-separated whitelist names from the Animation Library.', 'ngt-3d-scroll' ); ?></p>
						<details class="ngt3d-anim-picker">
							<summary><?php esc_html_e( 'Browse registered animations', 'ngt-3d-scroll' ); ?></summary>
							<ul>
								<?php foreach ( $registry as $name => $meta ) : ?>
									<li>
										<code><?php echo esc_html( $name ); ?></code>
										— <?php echo esc_html( (string) ( $meta['label'] ?? $name ) ); ?>
										<span class="description">(<?php echo esc_html( (string) ( $meta['cost'] ?? 'low' ) ); ?>)</span>
									</li>
								<?php endforeach; ?>
							</ul>
						</details>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="style_classes"><?php esc_html_e( 'Style classes', 'ngt-3d-scroll' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="style_classes" name="style_classes" value="<?php echo esc_attr( $data['style_classes'] ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="animation_options"><?php esc_html_e( 'Animation options (JSON)', 'ngt-3d-scroll' ); ?></label></th>
					<td>
						<textarea class="large-text code" rows="8" id="animation_options" name="animation_options"><?php echo esc_textarea( (string) $options_json ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sort_order"><?php esc_html_e( 'Sort order', 'ngt-3d-scroll' ); ?></label></th>
					<td>
						<input type="number" min="0" max="9999" class="small-text" id="sort_order" name="sort_order" value="<?php echo esc_attr( (string) $data['sort_order'] ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled', 'ngt-3d-scroll' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $data['enabled'] ) ); ?> />
							<?php esc_html_e( 'Rule is active', 'ngt-3d-scroll' ); ?>
						</label>
					</td>
				</tr>
				<?php foreach ( [ 'desktop_mode' => 'Desktop', 'tablet_mode' => 'Tablet', 'mobile_mode' => 'Mobile' ] as $field => $label ) : ?>
					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<select id="<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $field ); ?>">
								<?php foreach ( $mode_opts as $mode ) : ?>
									<option value="<?php echo esc_attr( $mode ); ?>" <?php selected( $data[ $field ], $mode ); ?>><?php echo esc_html( $mode ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<?php submit_button( $edit_id ? __( 'Update Rule', 'ngt-3d-scroll' ) : __( 'Create Rule', 'ngt-3d-scroll' ) ); ?>
			<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=ngt-3d-scroll' ) ); ?>">
				<?php esc_html_e( 'Cancel', 'ngt-3d-scroll' ); ?>
			</a>
		</form>
		<?php
		NGT3D_Admin::page_footer();
	}
}
