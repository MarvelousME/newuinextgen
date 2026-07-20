<?php
/**
 * UI Library: Dashboard KPI widgets.
 *
 * @var array $args
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$payload = $args['items'][0] ?? [];
$kpis    = $payload['kpis'] ?? $payload['widgets'] ?? [];
if ( ! empty( $payload['empty'] ) || empty( $kpis ) ) {
	return;
}
?>
<div class="ng-ui-kpi-grid ng-ui ng-ui--dashboard-kpi">
	<?php foreach ( (array) $kpis as $kpi ) : ?>
		<div class="ng-ui-kpi">
			<div class="ng-ui-kpi__value"><?php echo esc_html( (string) ( $kpi['value'] ?? '' ) ); ?></div>
			<div class="ng-ui-kpi__label"><?php echo esc_html( (string) ( $kpi['label'] ?? '' ) ); ?></div>
		</div>
	<?php endforeach; ?>
</div>
