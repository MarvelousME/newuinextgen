<?php
/**
 * UI Library: Upcoming bookings list.
 *
 * @var array $args
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = $args['items'] ?? [];
if ( empty( $items ) || ! empty( $items[0]['empty'] ) ) {
	get_template_part( 'template-parts/ui-library/empty-state', null, [ 'item' => $items[0] ?? [] ] );
	return;
}
?>
<div class="ng-ui-booking-list ng-ui" role="list" aria-label="<?php esc_attr_e( 'Upcoming lessons', 'beyondinfinity' ); ?>">
	<?php foreach ( $items as $row ) : ?>
		<?php if ( ! empty( $row['empty'] ) ) { continue; } ?>
		<article class="ng-ui-booking-row" role="listitem">
			<?php if ( ! empty( $row['peerImage'] ) ) : ?>
				<img class="ng-ui-booking-row__avatar" src="<?php echo esc_url( $row['peerImage'] ); ?>" alt="" width="40" height="40" loading="lazy" />
			<?php endif; ?>
			<div class="ng-ui-booking-row__body">
				<strong><?php echo esc_html( $row['peerName'] ?? '' ); ?></strong>
				<span class="ng-ui-booking-row__meta">
					<?php echo esc_html( $row['subject'] ?? '' ); ?>
					<?php if ( ! empty( $row['createdAt'] ) ) : ?>
						· <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row['createdAt'] ) ); ?>
					<?php endif; ?>
				</span>
			</div>
			<span class="ng-chip"><?php echo esc_html( $row['statusLabel'] ?? $row['status'] ?? '' ); ?></span>
		</article>
	<?php endforeach; ?>
</div>
