<?php
/**
 * Page Inspector admin screen.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Admin_Inspector {

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'ngt-3d-scroll' ) );
		}

		$page_slug = sanitize_key( wp_unslash( $_GET['page_slug'] ?? 'home' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page_id   = absint( $_GET['page_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 0 === $page_id && in_array( $page_slug, [ 'home', 'front-page' ], true ) ) {
			$page_id = (int) get_option( 'page_on_front', 0 );
		}

		$rules = NGT3D_Rule_Repository::get_for_page( $page_id, $page_slug );
		$pages = get_posts(
			[
				'post_type'      => 'page',
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		NGT3D_Admin::page_header( __( 'Page Inspector', 'ngt-3d-scroll' ) );
		?>
		<hr class="wp-header-end" />

		<form method="get" class="ngt3d-inspector-form">
			<input type="hidden" name="page" value="ngt-3d-scroll-inspector" />
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="page_slug"><?php esc_html_e( 'Page slug', 'ngt-3d-scroll' ); ?></label></th>
					<td>
						<input type="text" id="page_slug" name="page_slug" class="regular-text" value="<?php echo esc_attr( $page_slug ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="page_id"><?php esc_html_e( 'Page ID', 'ngt-3d-scroll' ); ?></label></th>
					<td>
						<input type="number" min="0" id="page_id" name="page_id" class="small-text" value="<?php echo esc_attr( (string) $page_id ); ?>" />
						<select onchange="document.getElementById('page_id').value=this.value; document.getElementById('page_slug').value=this.options[this.selectedIndex].dataset.slug || '';">
							<option value="0"><?php esc_html_e( '— Select a page —', 'ngt-3d-scroll' ); ?></option>
							<?php foreach ( $pages as $p ) : ?>
								<option
									value="<?php echo esc_attr( (string) $p->ID ); ?>"
									data-slug="<?php echo esc_attr( $p->post_name ); ?>"
									<?php selected( $page_id, (int) $p->ID ); ?>
								>
									<?php echo esc_html( $p->post_title . ' (' . $p->post_name . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Inspect', 'ngt-3d-scroll' ), 'secondary', '', false ); ?>
		</form>

		<h2>
			<?php
			printf(
				/* translators: 1: page slug, 2: page id */
				esc_html__( 'Active rules for %1$s (#%2$d)', 'ngt-3d-scroll' ),
				esc_html( $page_slug ),
				(int) $page_id
			);
			?>
		</h2>

		<?php if ( empty( $rules ) ) : ?>
			<p><?php esc_html_e( 'No enabled rules match this page.', 'ngt-3d-scroll' ); ?></p>
		<?php else : ?>
			<ol class="ngt3d-inspector-list">
				<?php foreach ( $rules as $rule ) : ?>
					<li>
						<strong><code><?php echo esc_html( $rule['target_selector'] ); ?></code></strong>
						→ <code><?php echo esc_html( $rule['animation_names'] ); ?></code>
						<span class="description">
							(sort <?php echo esc_html( (string) $rule['sort_order'] ); ?>,
							D:<?php echo esc_html( $rule['desktop_mode'] ); ?> /
							T:<?php echo esc_html( $rule['tablet_mode'] ); ?> /
							M:<?php echo esc_html( $rule['mobile_mode'] ); ?>)
						</span>
						—
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ngt-3d-scroll-add&edit=' . (int) $rule['id'] ) ); ?>">
							<?php esc_html_e( 'Edit', 'ngt-3d-scroll' ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
		<?php
		NGT3D_Admin::page_footer();
	}
}
