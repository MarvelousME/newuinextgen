<?php
/**
 * UI Library: Achievement / gamification badges.
 *
 * @var array $args
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = $args['items'] ?? [];
if ( empty( $items ) ) {
	return;
}
?>
<div class="ng-ui-badges ng-ui" role="list" aria-label="<?php esc_attr_e( 'Achievements', 'beyondinfinity' ); ?>">
	<?php foreach ( $items as $badge ) : ?>
		<?php if ( ! empty( $badge['empty'] ) ) { continue; } ?>
		<div class="ng-ui-badge" role="listitem" title="<?php echo esc_attr( $badge['title'] ?? '' ); ?>">
			<?php if ( ! empty( $badge['icon'] ) ) : ?>
				<img src="<?php echo esc_url( $badge['icon'] ); ?>" alt="" width="32" height="32" loading="lazy" />
			<?php else : ?>
				<span class="ng-ui-badge__glyph" aria-hidden="true">★</span>
			<?php endif; ?>
			<span class="ng-ui-badge__label"><?php echo esc_html( $badge['title'] ?? '' ); ?></span>
		</div>
	<?php endforeach; ?>
</div>
