<?php
/**
 * UI Library: Pricing cards.
 *
 * @var array $args
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = $args['items'] ?? [];
?>
<div class="ng-grid ng-ui ng-ui--pricing-card">
	<?php foreach ( $items as $row ) : ?>
		<?php if ( ! empty( $row['empty'] ) ) { continue; } ?>
		<article class="ng-card">
			<div class="ng-card__body">
				<h3><?php echo esc_html( $row['title'] ?? '' ); ?></h3>
				<?php if ( isset( $row['price'] ) && '' !== (string) $row['price'] ) : ?>
					<p class="ng-ui-kpi__value">R<?php echo esc_html( (string) $row['price'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $row['mode'] ) ) : ?>
					<p class="ng-chip"><?php echo esc_html( $row['mode'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $row['cta_url'] ) ) : ?>
					<a class="ng-btn ng-btn--primary" href="<?php echo esc_url( $row['cta_url'] ); ?>">
						<?php esc_html_e( 'View package', 'beyondinfinity' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</article>
	<?php endforeach; ?>
</div>
