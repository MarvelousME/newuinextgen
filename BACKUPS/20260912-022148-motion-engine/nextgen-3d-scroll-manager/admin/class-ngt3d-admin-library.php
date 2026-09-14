<?php
/**
 * Animation Library admin screen.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Admin_Library {

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'ngt-3d-scroll' ) );
		}

		$registry = NGT3D_Animation_Registry::all();

		NGT3D_Admin::page_header( __( 'Animation Library', 'ngt-3d-scroll' ) );
		?>
		<hr class="wp-header-end" />
		<p class="description">
			<?php esc_html_e( 'Only registered animation names can run on the frontend. Unknown names are ignored.', 'ngt-3d-scroll' ); ?>
		</p>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'ngt-3d-scroll' ); ?></th>
					<th><?php esc_html_e( 'Label', 'ngt-3d-scroll' ); ?></th>
					<th><?php esc_html_e( 'Engine', 'ngt-3d-scroll' ); ?></th>
					<th><?php esc_html_e( 'Cost', 'ngt-3d-scroll' ); ?></th>
					<th><?php esc_html_e( 'Default devices', 'ngt-3d-scroll' ); ?></th>
					<th><?php esc_html_e( 'Recommended targets', 'ngt-3d-scroll' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $registry as $name => $meta ) : ?>
					<tr>
						<td><code><?php echo esc_html( $name ); ?></code></td>
						<td>
							<strong><?php echo esc_html( (string) ( $meta['label'] ?? $name ) ); ?></strong>
							<p class="description"><?php echo esc_html( (string) ( $meta['description'] ?? '' ) ); ?></p>
						</td>
						<td><?php echo esc_html( (string) ( $meta['engine'] ?? '—' ) ); ?></td>
						<td><?php echo esc_html( (string) ( $meta['cost'] ?? '—' ) ); ?></td>
						<td>
							D:<?php echo esc_html( (string) ( $meta['desktop_mode'] ?? 'full' ) ); ?> /
							T:<?php echo esc_html( (string) ( $meta['tablet_mode'] ?? 'reduced' ) ); ?> /
							M:<?php echo esc_html( (string) ( $meta['mobile_mode'] ?? 'reduced' ) ); ?>
						</td>
						<td>
							<?php
							$rec = isset( $meta['recommended'] ) && is_array( $meta['recommended'] )
								? implode( ', ', $meta['recommended'] )
								: '—';
							echo esc_html( $rec );
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		NGT3D_Admin::page_footer();
	}
}
