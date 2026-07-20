<?php
/**
 * Reusable plugin cards + table.
 *
 * @package NextGenCorePluginManager
 *
 * @var array<string, array<string, mixed>> $scan
 * @var bool                               $readonly
 * @var string                             $view_mode Optional: discovery|missing|default
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$view_mode = $view_mode ?? 'default';
$readonly  = ! empty( $readonly );

$filtered = $scan;
if ( 'missing' === $view_mode ) {
	$filtered = array_filter(
		$scan,
		static function ( $row ) {
			$s = (string) ( $row['health_status'] ?? '' );
			return in_array( $s, [ 'MISSING', 'MANUAL_REQUIRED' ], true );
		}
	);
}
?>
<section class="ngcpm-section ngcpm-section--plugins" data-plugin-list>
	<?php if ( 'default' === $view_mode ) : ?>
		<div class="ngcpm-section__head">
			<h2 class="ngcpm-panel__title"><?php esc_html_e( 'Plugin status', 'nextgentutors-plugin-manager' ); ?></h2>
			<div class="ngcpm-view-toggle" role="group" aria-label="<?php esc_attr_e( 'View mode', 'nextgentutors-plugin-manager' ); ?>">
				<button type="button" class="ngcpm-chip is-active" data-view-mode="cards"><?php esc_html_e( 'Cards', 'nextgentutors-plugin-manager' ); ?></button>
				<button type="button" class="ngcpm-chip" data-view-mode="table"><?php esc_html_e( 'Table', 'nextgentutors-plugin-manager' ); ?></button>
			</div>
		</div>
	<?php endif; ?>

	<div class="ngcpm-cards" role="list">
		<?php foreach ( $filtered as $slug => $row ) :
			$badge    = (string) ( $row['health_status'] ?? 'MISSING' );
			$required = ! empty( $row['required'] );
			$filter   = implode(
				' ',
				[
					$required ? 'required' : 'optional',
					strtolower( $badge ),
					empty( $row['installed'] ) ? 'missing' : 'installed',
					! empty( $row['active'] ) ? 'active' : 'inactive',
					'MANUAL_REQUIRED' === $badge ? 'manual' : '',
				]
			);
			?>
			<article class="ngcpm-card" role="listitem" data-slug="<?php echo esc_attr( $slug ); ?>" data-status="<?php echo esc_attr( $badge ); ?>" data-filter-tags="<?php echo esc_attr( trim( $filter ) ); ?>" data-name="<?php echo esc_attr( strtolower( (string) ( $row['name'] ?? $slug ) ) ); ?>">
				<header class="ngcpm-card__head">
					<div class="ngcpm-card__icon"><?php echo NGCPM_UI::icon( 'puzzle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<div class="ngcpm-card__info">
						<h3 class="ngcpm-card__title"><?php echo esc_html( $row['name'] ?? $slug ); ?></h3>
						<p class="ngcpm-card__meta">
							<code><?php echo esc_html( $row['slug'] ?? $slug ); ?></code>
							· <?php echo esc_html( $row['source_type'] ?? '' ); ?>
							· <?php echo $required ? esc_html__( 'Required', 'nextgentutors-plugin-manager' ) : esc_html__( 'Optional', 'nextgentutors-plugin-manager' ); ?>
						</p>
					</div>
					<span class="<?php echo esc_attr( NGCPM_UI::badge_class( $badge ) ); ?>"><?php echo esc_html( $badge ); ?></span>
				</header>
				<div class="ngcpm-card__progress">
					<div class="ngcpm-card__progress-bar" style="width:<?php echo esc_attr( ! empty( $row['active'] ) ? '100' : ( ! empty( $row['installed'] ) ? '60' : '0' ) ); ?>%"></div>
				</div>
				<details class="ngcpm-card__details">
					<summary><?php esc_html_e( 'Details', 'nextgentutors-plugin-manager' ); ?></summary>
					<ul class="ngcpm-card__facts">
						<li><?php esc_html_e( 'Version:', 'nextgentutors-plugin-manager' ); ?> <code><?php echo esc_html( $row['installed_version'] ?: '—' ); ?></code> / <?php echo esc_html( $row['required_version'] ?? '' ); ?></li>
						<?php if ( ! empty( $row['notes'] ) ) : ?><li><?php echo esc_html( $row['notes'] ); ?></li><?php endif; ?>
					</ul>
				</details>
				<?php if ( ! $readonly ) : ?>
				<footer class="ngcpm-card__actions">
					<?php if ( empty( $row['installed'] ) && ! empty( $row['can_auto_install'] ) ) : ?>
						<button type="button" class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--primary" data-action="install" data-slug="<?php echo esc_attr( $slug ); ?>"><?php echo NGCPM_UI::icon( 'download' ); // phpcs:ignore ?><span><?php esc_html_e( 'Install', 'nextgentutors-plugin-manager' ); ?></span></button>
					<?php elseif ( 'MANUAL_REQUIRED' === $badge && empty( $row['required'] ) ) : ?>
						<p class="ngcpm-hint"><?php echo esc_html( $row['notes'] ?? __( 'Optional premium plugin — dismiss, upload a zip, or search WordPress.org.', 'nextgentutors-plugin-manager' ) ); ?></p>
						<button type="button" class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--ghost" data-action="dismiss-optional" data-slug="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Not needed — dismiss', 'nextgentutors-plugin-manager' ); ?></button>
						<button type="button" class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--ghost" data-action="open-add-plugin"><?php esc_html_e( 'Add different plugin', 'nextgentutors-plugin-manager' ); ?></button>
						<button type="button" class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--ghost" data-action="show-manual" data-slug="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Manual install instructions', 'nextgentutors-plugin-manager' ); ?></button>
					<?php elseif ( 'MANUAL_REQUIRED' === $badge ) : ?>
						<p class="ngcpm-hint"><?php echo esc_html( $row['notes'] ?? __( 'Premium/manual — upload zip to offline-packages or Add Plugin.', 'nextgentutors-plugin-manager' ) ); ?></p>
						<button type="button" class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--ghost" data-action="show-manual" data-slug="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Manual install instructions', 'nextgentutors-plugin-manager' ); ?></button>
					<?php endif; ?>
					<?php if ( ! empty( $row['can_activate'] ) ) : ?>
						<button type="button" class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--secondary" data-action="activate" data-slug="<?php echo esc_attr( $slug ); ?>"><?php echo NGCPM_UI::icon( 'power' ); // phpcs:ignore ?><span><?php esc_html_e( 'Activate', 'nextgentutors-plugin-manager' ); ?></span></button>
					<?php endif; ?>
					<?php if ( ! empty( $row['can_deactivate'] ) && empty( $row['required'] ) ) : ?>
						<button type="button" class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--ghost" data-action="deactivate" data-slug="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Deactivate', 'nextgentutors-plugin-manager' ); ?></button>
						<button type="button" class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--danger" data-action="uninstall" data-slug="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Uninstall', 'nextgentutors-plugin-manager' ); ?></button>
					<?php endif; ?>
					<?php if ( ! empty( $row['is_skipped'] ) ) : ?>
						<button type="button" class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--ghost" data-action="restore-optional" data-slug="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Restore to list', 'nextgentutors-plugin-manager' ); ?></button>
					<?php elseif ( ! empty( $row['can_dismiss'] ) && empty( $row['installed'] ) ) : ?>
						<button type="button" class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--ghost" data-action="dismiss-optional" data-slug="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Not needed', 'nextgentutors-plugin-manager' ); ?></button>
					<?php endif; ?>
					<?php if ( ! empty( $row['setup_url'] ) && ! empty( $row['active'] ) ) : ?>
						<a class="ngcpm-btn ngcpm-btn--sm ngcpm-btn--ghost" href="<?php echo esc_url( $row['setup_url'] ); ?>"><?php esc_html_e( 'Configure', 'nextgentutors-plugin-manager' ); ?></a>
					<?php endif; ?>
				</footer>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</div>

	<div class="ngcpm-table-wrap">
		<table class="ngcpm-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Plugin', 'nextgentutors-plugin-manager' ); ?></th>
					<th><?php esc_html_e( 'Status', 'nextgentutors-plugin-manager' ); ?></th>
					<th><?php esc_html_e( 'Version', 'nextgentutors-plugin-manager' ); ?></th>
					<th><?php esc_html_e( 'Source', 'nextgentutors-plugin-manager' ); ?></th>
					<?php if ( ! $readonly ) : ?><th><?php esc_html_e( 'Actions', 'nextgentutors-plugin-manager' ); ?></th><?php endif; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $filtered as $slug => $row ) : ?>
					<tr data-slug="<?php echo esc_attr( $slug ); ?>" data-filter-tags="<?php echo esc_attr( trim( ( ! empty( $row['required'] ) ? 'required' : 'optional' ) . ' ' . strtolower( $row['health_status'] ?? '' ) ) ); ?>" data-name="<?php echo esc_attr( strtolower( (string) ( $row['name'] ?? $slug ) ) ); ?>">
						<td><strong><?php echo esc_html( $row['name'] ?? $slug ); ?></strong></td>
						<td><span class="<?php echo esc_attr( NGCPM_UI::badge_class( $row['health_status'] ?? '' ) ); ?>"><?php echo esc_html( $row['health_status'] ?? '' ); ?></span></td>
						<td><code><?php echo esc_html( ( $row['installed_version'] ?? '' ) ?: '—' ); ?></code></td>
						<td><?php echo esc_html( $row['source_type'] ?? '' ); ?></td>
						<?php if ( ! $readonly ) : ?>
						<td class="ngcpm-table__actions">
							<?php if ( empty( $row['installed'] ) && ! empty( $row['can_auto_install'] ) ) : ?>
								<button type="button" class="ngcpm-btn ngcpm-btn--xs" data-action="install" data-slug="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Install', 'nextgentutors-plugin-manager' ); ?></button>
							<?php endif; ?>
							<?php if ( ! empty( $row['can_activate'] ) ) : ?>
								<button type="button" class="ngcpm-btn ngcpm-btn--xs" data-action="activate" data-slug="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Activate', 'nextgentutors-plugin-manager' ); ?></button>
							<?php endif; ?>
						</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</section>
