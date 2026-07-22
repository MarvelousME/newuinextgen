<?php
/**
 * UI Library: Status badge.
 *
 * @var array $args { ctx|item }
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx   = $args['ctx'] ?? [];
$item  = $args['item'] ?? [];
$label = $ctx['label'] ?? ( $item['label'] ?? __( 'Status', 'beyondinfinity' ) );
$state = $ctx['state'] ?? ( $item['state'] ?? 'neutral' );

echo bi_status_badge( $label, $state ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
