<?php
/**
 * UI Library: Comparison card.
 *
 * @var array $args { ctx }
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx    = $args['ctx'] ?? [];
$left   = $ctx['left'] ?? [];
$right  = $ctx['right'] ?? [];
$left_t = $left['title'] ?? __( 'Option A', 'beyondinfinity' );
$right_t = $right['title'] ?? __( 'Option B', 'beyondinfinity' );
$left_items  = $left['items'] ?? [];
$right_items = $right['items'] ?? [];
?>
<div class="ngt-compare">
	<div class="ngt-compare__col">
		<h3><?php echo esc_html( $left_t ); ?></h3>
		<?php if ( $left_items ) : ?>
			<ul>
				<?php foreach ( $left_items as $item ) : ?>
					<li><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<div class="ngt-compare__vs" aria-hidden="true">VS</div>
	<div class="ngt-compare__col">
		<h3><?php echo esc_html( $right_t ); ?></h3>
		<?php if ( $right_items ) : ?>
			<ul>
				<?php foreach ( $right_items as $item ) : ?>
					<li><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</div>
