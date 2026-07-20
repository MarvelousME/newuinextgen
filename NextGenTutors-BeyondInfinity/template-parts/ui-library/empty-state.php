<?php
/**
 * UI Library: Empty state.
 *
 * @var array $args { item }
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$item = $args['item'] ?? [];
?>
<div class="ng-ui-empty" role="status">
	<p class="ng-ui-empty__title"><?php echo esc_html( $item['title'] ?? __( 'Nothing to show yet', 'beyondinfinity' ) ); ?></p>
	<?php if ( ! empty( $item['message'] ) ) : ?>
		<p><?php echo esc_html( $item['message'] ); ?></p>
	<?php endif; ?>
</div>
