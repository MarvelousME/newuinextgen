<?php
/**
 * UI Library: Skeleton loader.
 *
 * @var array $args { ctx }
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx     = $args['ctx'] ?? [];
$variant = $ctx['variant'] ?? 'card';
$count   = (int) ( $ctx['count'] ?? 3 );
$label   = $ctx['label'] ?? __( 'Loading…', 'beyondinfinity' );
?>
<div class="ngt-skeleton-grid" role="status" aria-busy="true" aria-label="<?php echo esc_attr( $label ); ?>">
	<?php echo bi_skeleton( $variant, $count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<span class="screen-reader-text"><?php echo esc_html( $label ); ?></span>
</div>
