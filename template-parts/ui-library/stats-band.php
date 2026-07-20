<?php
/**
 * UI Library: Trust stats band.
 *
 * @var array $args
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bundle = $args['items'][0] ?? [];
$stats  = $bundle['items'] ?? ( is_array( $bundle ) && isset( $bundle[0]['label'] ) ? $bundle : [] );
if ( ! empty( $bundle['empty'] ) || empty( $stats ) ) {
	get_template_part( 'template-parts/ui-library/empty-state', null, [ 'item' => $bundle ] );
	return;
}
?>
<section class="ng-ui ng-ui--stats-band" aria-label="<?php esc_attr_e( 'Platform statistics', 'beyondinfinity' ); ?>">
	<div class="ng-container">
		<div class="ng-ui-kpi-grid">
			<?php foreach ( (array) $stats as $stat ) : ?>
				<div class="ng-ui-kpi">
					<div class="ng-ui-kpi__value"><?php echo esc_html( (string) ( $stat['value'] ?? '' ) ); ?></div>
					<div class="ng-ui-kpi__label"><?php echo esc_html( (string) ( $stat['label'] ?? '' ) ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
