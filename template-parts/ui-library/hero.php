<?php
/**
 * UI Library: Hero section.
 *
 * @var array $args { slug, def, items, ctx }
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$item = $args['items'][0] ?? [];
if ( ! empty( $item['empty'] ) ) {
	get_template_part( 'template-parts/ui-library/empty-state', null, [ 'item' => $item ] );
	return;
}

$title    = $item['title'] ?? '';
$subtitle = $item['subtitle'] ?? '';
$cta      = $item['cta'] ?? [];
$stats    = $item['stats'] ?? [];
?>
<section class="ng-ui-hero" aria-labelledby="ng-ui-hero-title">
	<div class="ng-container ng-ui-hero__inner">
		<div>
			<?php if ( $title ) : ?>
				<h1 id="ng-ui-hero-title" class="ng-ui-hero__title"><?php echo esc_html( $title ); ?></h1>
			<?php endif; ?>
			<?php if ( $subtitle ) : ?>
				<p class="ng-ui-hero__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $cta['label'] ) && ! empty( $cta['url'] ) ) : ?>
				<a class="ng-btn ng-btn--primary" href="<?php echo esc_url( $cta['url'] ); ?>">
					<?php echo esc_html( $cta['label'] ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $stats ) : ?>
				<div class="ng-ui-hero__stats" role="list">
					<?php foreach ( (array) $stats as $stat ) : ?>
						<div role="listitem">
							<div class="ng-ui-hero__stat-value"><?php echo esc_html( (string) ( $stat['value'] ?? '' ) ); ?></div>
							<div class="ng-ui-hero__stat-label"><?php echo esc_html( (string) ( $stat['label'] ?? '' ) ); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
