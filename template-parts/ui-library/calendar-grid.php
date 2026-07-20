<?php
/**
 * UI Library: Tutor calendar slot grid.
 *
 * @var array $args
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = $args['items'] ?? [];
if ( empty( $items ) || ( isset( $items[0]['empty'] ) && $items[0]['empty'] ) ) {
	get_template_part( 'template-parts/ui-library/empty-state', null, [ 'item' => $items[0] ?? [] ] );
	return;
}
?>
<div class="ng-ui-calendar ng-ui" role="grid" aria-label="<?php esc_attr_e( 'Available lesson slots', 'beyondinfinity' ); ?>">
	<?php foreach ( $items as $slot ) : ?>
		<button type="button" class="ng-ui-calendar__slot" data-date="<?php echo esc_attr( $slot['date'] ?? '' ); ?>" data-start="<?php echo esc_attr( $slot['start_time'] ?? '' ); ?>">
			<span class="ng-ui-calendar__date"><?php echo esc_html( $slot['date'] ?? '' ); ?></span>
			<span class="ng-ui-calendar__time"><?php echo esc_html( $slot['start_time'] ?? '' ); ?></span>
		</button>
	<?php endforeach; ?>
</div>
