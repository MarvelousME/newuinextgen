<?php
/**
 * Local plugin zip directory panel.
 *
 * @package NextGenCorePluginManager
 *
 * @var array<string, mixed> $local_packages
 * @var bool                 $readonly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$local_packages = $local_packages ?? NGCPM_Local_Packages::public_status();
$primary_dir    = (string) ( $local_packages['primary_dir'] ?? NGCPM_Settings::local_zip_dir() );
$bundle_dir     = (string) ( $local_packages['bundle_dir'] ?? trailingslashit( NGCPM_PLUGIN_DIR ) . 'offline-packages' );
$inventory      = (array) ( $local_packages['inventory'] ?? [] );
$pending_count  = (int) ( $local_packages['pending_count'] ?? 0 );
$zip_count      = (int) ( $local_packages['zip_count'] ?? count( $inventory ) );
$writable       = ! empty( $local_packages['writable'] );
?>
<section class="ngcpm-panel ngcpm-local-packages" data-local-packages>
	<div class="ngcpm-section__head">
		<h2 class="ngcpm-panel__title"><?php esc_html_e( 'Local plugin packages', 'nextgentutors-plugin-manager' ); ?></h2>
		<?php if ( ! $readonly && $pending_count > 0 ) : ?>
			<button type="button" class="ngcpm-btn ngcpm-btn--primary ngcpm-btn--sm" data-action="install-local-packages">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of plugins */
						_n( 'Install %d from local zips', 'Install %d from local zips', $pending_count, 'nextgentutors-plugin-manager' ),
						$pending_count
					)
				);
				?>
			</button>
		<?php endif; ?>
	</div>

	<p class="ngcpm-muted">
		<?php esc_html_e( 'Drop plugin .zip files in the directory below. The Plugin Manager detects them automatically and installs on page load (when auto-install is enabled) or when you start the Install Queue.', 'nextgentutors-plugin-manager' ); ?>
	</p>

	<div class="ngcpm-local-packages__paths">
		<div class="ngcpm-local-packages__path">
			<strong><?php esc_html_e( 'Primary directory', 'nextgentutors-plugin-manager' ); ?></strong>
			<code class="ngcpm-local-packages__code" data-copy-path><?php echo esc_html( $primary_dir ); ?></code>
			<span class="ngcpm-badge <?php echo $writable ? 'ngcpm-badge--ready' : 'ngcpm-badge--warning'; ?>">
				<?php echo $writable ? esc_html__( 'Writable', 'nextgentutors-plugin-manager' ) : esc_html__( 'Not writable', 'nextgentutors-plugin-manager' ); ?>
			</span>
		</div>
		<div class="ngcpm-local-packages__path">
			<strong><?php esc_html_e( 'Bundled fallback', 'nextgentutors-plugin-manager' ); ?></strong>
			<code class="ngcpm-local-packages__code"><?php echo esc_html( $bundle_dir ); ?></code>
		</div>
	</div>

	<?php if ( $zip_count > 0 ) : ?>
		<ul class="ngcpm-local-packages__list">
			<?php foreach ( $inventory as $item ) : ?>
				<li class="ngcpm-local-packages__item">
					<span class="ngcpm-local-packages__file"><?php echo esc_html( (string) ( $item['file'] ?? '' ) ); ?></span>
					<?php if ( ! empty( $item['matched_name'] ) ) : ?>
						<span class="ngcpm-muted">→ <?php echo esc_html( (string) $item['matched_name'] ); ?></span>
						<?php if ( ! empty( $item['active'] ) ) : ?>
							<span class="ngcpm-badge ngcpm-badge--ready"><?php esc_html_e( 'Active', 'nextgentutors-plugin-manager' ); ?></span>
						<?php elseif ( ! empty( $item['installed'] ) ) : ?>
							<span class="ngcpm-badge ngcpm-badge--inactive"><?php esc_html_e( 'Installed', 'nextgentutors-plugin-manager' ); ?></span>
						<?php elseif ( ! empty( $item['pending'] ) ) : ?>
							<span class="ngcpm-badge ngcpm-badge--info"><?php esc_html_e( 'Ready to install', 'nextgentutors-plugin-manager' ); ?></span>
						<?php endif; ?>
					<?php else : ?>
						<span class="ngcpm-badge ngcpm-badge--warning"><?php esc_html_e( 'Unmatched', 'nextgentutors-plugin-manager' ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p class="ngcpm-hint">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: directory path */
					__( 'No .zip files found yet. Copy plugin archives into %s', 'nextgentutors-plugin-manager' ),
					$primary_dir
				)
			);
			?>
		</p>
	<?php endif; ?>
</section>
