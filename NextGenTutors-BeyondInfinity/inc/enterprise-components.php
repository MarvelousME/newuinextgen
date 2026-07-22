<?php
/**
 * Enterprise UI primitives (Phase 3) — badges, stepper, skeleton, dialog, timeline, comparison.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'bi_enterprise_enqueue', 28 );
add_filter( 'ngc_ui_component_definitions', 'bi_enterprise_register_components' );
add_action( 'admin_enqueue_scripts', 'bi_enterprise_admin_bridge', 20 );

/**
 * Front-end enterprise stylesheet + dialog/stepper scripts.
 */
function bi_enterprise_enqueue() {
	if ( is_admin() || bi_is_builder_edit_mode() ) {
		return;
	}

	wp_enqueue_style( 'bi-enterprise', BI_URI . '/assets/css/bi-enterprise.css', [ 'bi-style', 'bi-components' ], BI_VERSION );

	wp_register_script( 'bi-focus-trap', BI_URI . '/assets/js/bi-focus-trap.js', [], BI_VERSION, true );
	wp_register_script( 'bi-dialog', BI_URI . '/assets/js/bi-dialog.js', [ 'bi-focus-trap' ], BI_VERSION, true );
	wp_register_script( 'bi-stepper', BI_URI . '/assets/js/bi-stepper.js', [], BI_VERSION, true );

	// Dialog is lightweight and used by booking + forms; load globally.
	wp_enqueue_script( 'bi-focus-trap' );
	wp_enqueue_script( 'bi-dialog' );

	if ( is_page( [ 'find-a-tutor', 'become-a-tutor', 'onboarding', 'register' ] ) ) {
		wp_enqueue_script( 'bi-stepper' );
	}
}

/**
 * Register new CMS component definitions (theme partials, no new business data).
 *
 * @param array<string, array<string, mixed>> $defs Existing defs.
 * @return array<string, array<string, mixed>>
 */
function bi_enterprise_register_components( $defs ) {
	$defs['timeline'] = [
		'label'    => __( 'Timeline', 'beyondinfinity' ),
		'provider' => '',
		'partial'  => 'timeline',
		'pages'    => [ 'thank-you', 'tutor-vetting', 'onboarding' ],
		'assets'   => [],
	];
	$defs['comparison-card'] = [
		'label'    => __( 'Comparison card', 'beyondinfinity' ),
		'provider' => '',
		'partial'  => 'comparison-card',
		'pages'    => [ 'pricing', 'become-a-tutor' ],
		'assets'   => [],
	];
	$defs['status-badge'] = [
		'label'    => __( 'Status badge', 'beyondinfinity' ),
		'provider' => '',
		'partial'  => 'status-badge',
		'pages'    => [ '*' ],
		'assets'   => [],
	];
	$defs['skeleton'] = [
		'label'    => __( 'Skeleton loader', 'beyondinfinity' ),
		'provider' => '',
		'partial'  => 'skeleton',
		'pages'    => [ '*' ],
		'assets'   => [],
	];
	return $defs;
}

/**
 * When Companion AI Suite (or other admin screens) need dialog styles,
 * expose the enterprise CSS handle for them to depend on via filter.
 *
 * @param string $hook Current admin page hook.
 */
function bi_enterprise_admin_bridge( $hook ) {
	if ( false === strpos( (string) $hook, 'ngc-' ) ) {
		return;
	}
	wp_register_style( 'bi-enterprise', BI_URI . '/assets/css/bi-enterprise.css', [], BI_VERSION );
}

/**
 * Render a status badge (PHP helper).
 *
 * @param string $label Visible label.
 * @param string $state success|warning|error|info|neutral|approved|pending|…
 * @return string
 */
function bi_status_badge( $label, $state = 'neutral' ) {
	$state = sanitize_key( $state );
	return sprintf(
		'<span class="ngt-badge ngt-badge--%1$s"><span class="ngt-badge__dot" aria-hidden="true"></span>%2$s</span>',
		esc_attr( $state ),
		esc_html( $label )
	);
}

/**
 * Skeleton markup helper.
 *
 * @param string $variant text|title|avatar|card|kpi.
 * @param int    $count   Repeat count.
 * @return string
 */
function bi_skeleton( $variant = 'text', $count = 1 ) {
	$variant = sanitize_key( $variant );
	$html    = '';
	for ( $i = 0; $i < max( 1, (int) $count ); $i++ ) {
		$html .= '<span class="ngt-skeleton ngt-skeleton--' . esc_attr( $variant ) . '" aria-hidden="true"></span>';
	}
	return $html;
}
