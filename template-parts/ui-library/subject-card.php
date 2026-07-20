<?php
/**
 * UI Library: Subject card grid.
 *
 * @var array $args
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = $args['items'] ?? [];
?>
<div class="ng-grid ng-ui ng-ui--subject-card">
	<?php foreach ( $items as $row ) : ?>
		<?php if ( ! empty( $row['empty'] ) ) { continue; } ?>
		<a class="ng-card" href="<?php echo esc_url( $row['url'] ?? '#' ); ?>">
			<div class="ng-card__body">
				<h3><?php echo esc_html( $row['title'] ?? '' ); ?></h3>
				<?php if ( ! empty( $row['desc'] ) ) : ?>
					<p><?php echo esc_html( $row['desc'] ); ?></p>
				<?php endif; ?>
			</div>
		</a>
	<?php endforeach; ?>
</div>
