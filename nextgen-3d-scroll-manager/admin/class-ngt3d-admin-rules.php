<?php
/**
 * Admin Rules list screen.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Admin_Rules {

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'ngt-3d-scroll' ) );
		}

		$search   = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page_num = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! NGT3D_Schema::table_exists() ) {
			NGT3D_Schema::install();
			NGT3D_Rule_Repository::seed_defaults();
		}

		$result = NGT3D_Rule_Repository::get_all(
			[
				'page'     => $page_num,
				'per_page' => 20,
				'search'   => $search,
				'orderby'  => 'sort_order',
				'order'    => 'ASC',
			]
		);

		$rules = $result['rules'];
		$total = $result['total'];
		$pages = max( 1, (int) ceil( $total / 20 ) );

		NGT3D_Admin::page_header( __( '3D Scroll Rules', 'ngt-3d-scroll' ) );
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ngt-3d-scroll-add' ) ); ?>" class="page-title-action">
			<?php esc_html_e( 'Add Rule', 'ngt-3d-scroll' ); ?>
		</a>
		<hr class="wp-header-end" />

		<form method="get" class="ngt3d-search-form">
			<input type="hidden" name="page" value="ngt-3d-scroll" />
			<p class="search-box">
				<label class="screen-reader-text" for="ngt3d-search"><?php esc_html_e( 'Search rules', 'ngt-3d-scroll' ); ?></label>
				<input type="search" id="ngt3d-search" name="s" value="<?php echo esc_attr( $search ); ?>" />
				<?php submit_button( __( 'Search', 'ngt-3d-scroll' ), '', '', false ); ?>
			</p>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ngt3d_bulk_action" />
			<?php wp_nonce_field( 'ngt3d_bulk', 'ngt3d_nonce' ); ?>

			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<select name="bulk_action">
						<option value=""><?php esc_html_e( 'Bulk actions', 'ngt-3d-scroll' ); ?></option>
						<option value="enable"><?php esc_html_e( 'Enable', 'ngt-3d-scroll' ); ?></option>
						<option value="disable"><?php esc_html_e( 'Disable', 'ngt-3d-scroll' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete', 'ngt-3d-scroll' ); ?></option>
					</select>
					<?php submit_button( __( 'Apply', 'ngt-3d-scroll' ), 'action', '', false ); ?>
				</div>
				<div class="tablenav-pages">
					<span class="displaying-num">
						<?php
						printf(
							/* translators: %d: rule count */
							esc_html( _n( '%d rule', '%d rules', $total, 'ngt-3d-scroll' ) ),
							(int) $total
						);
						?>
					</span>
				</div>
			</div>

			<table class="wp-list-table widefat fixed striped ngt3d-rules-table">
				<thead>
					<tr>
						<td class="manage-column column-cb check-column"><input type="checkbox" id="ngt3d-cb-all" /></td>
						<th><?php esc_html_e( 'ID', 'ngt-3d-scroll' ); ?></th>
						<th><?php esc_html_e( 'Page', 'ngt-3d-scroll' ); ?></th>
						<th><?php esc_html_e( 'Target', 'ngt-3d-scroll' ); ?></th>
						<th><?php esc_html_e( 'Animations', 'ngt-3d-scroll' ); ?></th>
						<th><?php esc_html_e( 'Sort', 'ngt-3d-scroll' ); ?></th>
						<th><?php esc_html_e( 'Devices', 'ngt-3d-scroll' ); ?></th>
						<th><?php esc_html_e( 'Status', 'ngt-3d-scroll' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rules ) ) : ?>
						<tr>
							<td colspan="8"><?php esc_html_e( 'No rules found. Add a rule or run setup from Diagnostics.', 'ngt-3d-scroll' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $rules as $rule ) : ?>
							<tr>
								<th scope="row" class="check-column">
									<input type="checkbox" name="rule_ids[]" value="<?php echo esc_attr( (string) $rule['id'] ); ?>" />
								</th>
								<td><?php echo esc_html( (string) $rule['id'] ); ?></td>
								<td>
									<code><?php echo esc_html( $rule['page_slug'] ?: '—' ); ?></code>
									<?php if ( ! empty( $rule['page_id'] ) ) : ?>
										<br /><span class="description">#<?php echo esc_html( (string) $rule['page_id'] ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<strong>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=ngt-3d-scroll-add&edit=' . (int) $rule['id'] ) ); ?>">
											<code><?php echo esc_html( $rule['target_selector'] ); ?></code>
										</a>
									</strong>
									<div class="row-actions">
										<span class="edit">
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=ngt-3d-scroll-add&edit=' . (int) $rule['id'] ) ); ?>">
												<?php esc_html_e( 'Edit', 'ngt-3d-scroll' ); ?>
											</a>
										</span>
									</div>
								</td>
								<td><code><?php echo esc_html( $rule['animation_names'] ); ?></code></td>
								<td><?php echo esc_html( (string) $rule['sort_order'] ); ?></td>
								<td>
									<span class="ngt3d-mode ngt3d-mode--<?php echo esc_attr( $rule['desktop_mode'] ); ?>">D:<?php echo esc_html( $rule['desktop_mode'] ); ?></span>
									<span class="ngt3d-mode ngt3d-mode--<?php echo esc_attr( $rule['tablet_mode'] ); ?>">T:<?php echo esc_html( $rule['tablet_mode'] ); ?></span>
									<span class="ngt3d-mode ngt3d-mode--<?php echo esc_attr( $rule['mobile_mode'] ); ?>">M:<?php echo esc_html( $rule['mobile_mode'] ); ?></span>
								</td>
								<td>
									<?php if ( $rule['enabled'] ) : ?>
										<span class="ngt3d-status ngt3d-status--on"><?php esc_html_e( 'Enabled', 'ngt-3d-scroll' ); ?></span>
									<?php else : ?>
										<span class="ngt3d-status ngt3d-status--off"><?php esc_html_e( 'Disabled', 'ngt-3d-scroll' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<?php
						echo wp_kses_post(
							paginate_links(
								[
									'base'      => add_query_arg( 'paged', '%#%' ),
									'format'    => '',
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
									'total'     => $pages,
									'current'   => $page_num,
								]
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		</form>
		<?php
		NGT3D_Admin::page_footer();
	}
}
