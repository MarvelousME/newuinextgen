<?php
/**
 * Kinetic surface — site-wide kinetic-ui default (dashboards use command surface).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kinetic-ui is the default design system for the public site.
 */
function bi_kinetic_layout_enabled() {
	return (bool) apply_filters( 'bi_kinetic_layout_enabled', true );
}

/**
 * Views that use the dedicated homepage kinetic bundle (not the inner-page bridge).
 */
function bi_is_kinetic_home_request() {
	return function_exists( 'bi_is_kinetic_home' ) && bi_is_kinetic_home();
}

/**
 * Whether kinetic-ui should NOT apply on this request.
 */
function bi_is_kinetic_ui_excluded() {
	if ( is_admin() || ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) ) {
		return true;
	}
	if ( bi_is_kinetic_home_request() ) {
		return true;
	}
	if ( function_exists( 'bi_is_dashboard_page' ) && bi_is_dashboard_page() ) {
		return true;
	}
	if ( is_page() ) {
		$slug = function_exists( 'bi_page_slug' ) ? bi_page_slug() : (string) get_post_field( 'post_name', get_queried_object_id() );
		if ( function_exists( 'bi_dashboard_page_map' ) && isset( bi_dashboard_page_map()[ $slug ] ) ) {
			return true;
		}
		if ( function_exists( 'bi_page_type' ) && in_array( bi_page_type( $slug ), [ 'dashboard', 'admin' ], true ) ) {
			return true;
		}
	}
	return (bool) apply_filters( 'bi_kinetic_ui_excluded', false );
}

/**
 * Inner pages and archives that inherit kinetic-ui styling by default.
 */
function bi_uses_kinetic_surface() {
	if ( bi_is_kinetic_ui_excluded() ) {
		return false;
	}
	if ( ! bi_kinetic_layout_enabled() ) {
		return false;
	}
	return (bool) apply_filters( 'bi_uses_kinetic_surface', true );
}

/**
 * @param array<string, mixed> $classes Body classes.
 * @return array<string, mixed>
 */
function bi_kinetic_surface_body_class( $classes ) {
	if ( bi_is_kinetic_home_request() ) {
		$classes[] = 'bi-kinetic-home';
	}
	if ( bi_uses_kinetic_surface() ) {
		$classes[] = 'bi-kinetic-surface';
	}
	return $classes;
}
add_filter( 'body_class', 'bi_kinetic_surface_body_class', 12 );

/**
 * Enqueue kinetic tokens + bridge on marketing and content views.
 */
function bi_kinetic_surface_assets() {
	if ( is_admin() || ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) ) {
		return;
	}

	$needs_home   = bi_is_kinetic_home_request();
	$needs_surface = bi_uses_kinetic_surface();

	if ( ! $needs_home && ! $needs_surface ) {
		return;
	}

	wp_enqueue_style(
		'bi-kinetic-tokens',
		BI_URI . '/assets/css/kinetic-tokens.css',
		[ 'bi-style' ],
		BI_VERSION
	);

	if ( ! $needs_surface ) {
		return;
	}

	wp_enqueue_style(
		'bi-kinetic-bridge',
		BI_URI . '/assets/css/kinetic-bridge.css',
		[ 'bi-kinetic-tokens', 'bi-page-composer' ],
		BI_VERSION
	);
	wp_enqueue_script(
		'bi-kinetic-page',
		BI_URI . '/assets/js/kinetic-page.js',
		[ 'bi-page-composer' ],
		BI_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'bi_kinetic_surface_assets', 36 );

/**
 * Open kinetic page shell for generic templates (single, archive, 404).
 *
 * @param string $slug Logical page slug for data attributes.
 */
function bi_kinetic_shell_open( $slug = '' ) {
	$slug = $slug ?: ( function_exists( 'bi_page_slug' ) ? bi_page_slug() : 'content' );
	if ( function_exists( 'bi_page_open' ) ) {
		bi_page_open( $slug );
		return;
	}
	echo '<div class="ng-page bi-blended-layout" data-page-slug="' . esc_attr( $slug ) . '">';
	echo '<div class="ng-page__canvas" aria-hidden="true"></div>';
	echo '<div class="bi-theme-content framer-frame ng-page__body">';
}

/**
 * Close kinetic page shell.
 *
 * @param string $slug Logical page slug.
 */
function bi_kinetic_shell_close( $slug = '' ) {
	$slug = $slug ?: ( function_exists( 'bi_page_slug' ) ? bi_page_slug() : 'content' );
	if ( function_exists( 'bi_page_close' ) ) {
		bi_page_close( $slug );
		return;
	}
	echo '</div></div>';
}
