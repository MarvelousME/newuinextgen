<?php
/**
 * Diagnostics admin screen.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Admin_Diagnostics {

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'ngt-3d-scroll' ) );
		}

		// Optional one-click setup.
		if ( isset( $_GET['ngt3d_setup'] ) && check_admin_referer( 'ngt3d_setup' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			NGT3D_Schema::install();
			NGT3D_Rule_Repository::seed_defaults();
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Schema installed and defaults seeded (if empty).', 'ngt-3d-scroll' ) . '</p></div>';
		}

		$status = NGT3D_Diagnostics::collect();

		NGT3D_Admin::page_header( __( 'Diagnostics', 'ngt-3d-scroll' ) );
		?>
		<hr class="wp-header-end" />

		<?php if ( ! empty( $_GET['ngt3d_cleared'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Diagnostics log cleared.', 'ngt-3d-scroll' ); ?></p></div>
		<?php endif; ?>

		<p>
			<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ngt-3d-scroll-diagnostics&ngt3d_setup=1' ), 'ngt3d_setup' ) ); ?>">
				<?php esc_html_e( 'Run Setup', 'ngt-3d-scroll' ); ?>
			</a>
		</p>

		<table class="widefat striped">
			<tbody>
				<tr><th><?php esc_html_e( 'Plugin version', 'ngt-3d-scroll' ); ?></th><td><?php echo esc_html( (string) $status['plugin_version'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Schema version', 'ngt-3d-scroll' ); ?></th><td><?php echo esc_html( (string) $status['schema_version'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Table exists', 'ngt-3d-scroll' ); ?></th><td><?php echo $status['table_exists'] ? 'yes' : 'no'; ?></td></tr>
				<tr><th><?php esc_html_e( 'Total rules', 'ngt-3d-scroll' ); ?></th><td><?php echo esc_html( (string) $status['total_rules'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Active rules', 'ngt-3d-scroll' ); ?></th><td><?php echo esc_html( (string) $status['active_rules'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'PHP / WP', 'ngt-3d-scroll' ); ?></th><td><?php echo esc_html( $status['php_version'] . ' / ' . $status['wp_version'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Companion active', 'ngt-3d-scroll' ); ?></th><td><?php echo ! empty( $status['companion_active'] ) ? 'yes' : 'no'; ?></td></tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Broken selectors', 'ngt-3d-scroll' ); ?></h2>
		<?php if ( empty( $status['broken_selectors'] ) ) : ?>
			<p><?php esc_html_e( 'None detected.', 'ngt-3d-scroll' ); ?></p>
		<?php else : ?>
			<ul>
				<?php foreach ( $status['broken_selectors'] as $item ) : ?>
					<li>
						#<?php echo esc_html( (string) $item['id'] ); ?>:
						<code><?php echo esc_html( $item['selector'] ); ?></code>
						— <?php echo esc_html( $item['error'] ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Duplicate page targets', 'ngt-3d-scroll' ); ?></h2>
		<?php if ( empty( $status['duplicate_page_targets'] ) ) : ?>
			<p><?php esc_html_e( 'None detected.', 'ngt-3d-scroll' ); ?></p>
		<?php else : ?>
			<ul>
				<?php foreach ( $status['duplicate_page_targets'] as $dup ) : ?>
					<li>
						<code><?php echo esc_html( $dup['key'] ); ?></code>
						(IDs: <?php echo esc_html( implode( ', ', array_map( 'strval', $dup['ids'] ) ) ); ?>)
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Log', 'ngt-3d-scroll' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ngt3d_clear_log" />
			<?php wp_nonce_field( 'ngt3d_clear_log', 'ngt3d_nonce' ); ?>
			<?php submit_button( __( 'Clear log', 'ngt-3d-scroll' ), 'secondary', '', false ); ?>
		</form>

		<?php if ( empty( $status['log'] ) ) : ?>
			<p><?php esc_html_e( 'Log is empty.', 'ngt-3d-scroll' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'ngt-3d-scroll' ); ?></th>
						<th><?php esc_html_e( 'Level', 'ngt-3d-scroll' ); ?></th>
						<th><?php esc_html_e( 'Message', 'ngt-3d-scroll' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_reverse( $status['log'] ) as $entry ) : ?>
						<tr>
							<td><?php echo esc_html( (string) ( $entry['time'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $entry['level'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $entry['message'] ?? '' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
		NGT3D_Admin::page_footer();
	}
}
