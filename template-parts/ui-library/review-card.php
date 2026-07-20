<?php
/**
 * UI Library: Review / testimonial cards.
 *
 * @var array $args
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = $args['items'] ?? [];
?>
<div class="ng-grid ng-ui ng-ui--review-card">
	<?php foreach ( $items as $row ) : ?>
		<?php if ( ! empty( $row['empty'] ) ) { continue; } ?>
		<blockquote class="ng-card">
			<div class="ng-card__body">
				<p>“<?php echo esc_html( $row['quote'] ?? '' ); ?>”</p>
				<footer>
					<cite><?php echo esc_html( $row['author'] ?? '' ); ?></cite>
					<?php if ( ! empty( $row['rating'] ) ) : ?>
						<span aria-label="<?php echo esc_attr( sprintf( __( '%d out of 5 stars', 'beyondinfinity' ), (int) $row['rating'] ) ); ?>">
							★ <?php echo esc_html( (string) $row['rating'] ); ?>
						</span>
					<?php endif; ?>
				</footer>
			</div>
		</blockquote>
	<?php endforeach; ?>
</div>
