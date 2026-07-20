<?php
/**
 * Kinetic surface — site-wide modern look (excludes dashboards).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the kinetic homepage layout is the active site default.
 */
function bi_kinetic_layout_enabled() {
	return 'kinetic' === bi_get_theme_option( 'home_layout', 'kinetic' );
}

/**
 * Inner pages that should inherit kinetic styling (not the full homepage bundle).
 */
function bi_uses_kinetic_surface() {
	if ( is_admin() || ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) ) {
		return false;
	}
	if ( ! bi_kinetic_layout_enabled() ) {
		return false;
	}
	if ( function_exists( 'bi_is_kinetic_home' ) && bi_is_kinetic_home() ) {
		return false;
	}
	if ( function_exists( 'bi_is_dashboard_page' ) && bi_is_dashboard_page() ) {
		return false;
	}
	if ( is_page() ) {
		$slug = function_exists( 'bi_page_slug' ) ? bi_page_slug() : (string) get_post_field( 'post_name', get_queried_object_id() );
		if ( function_exists( 'bi_dashboard_page_map' ) && isset( bi_dashboard_page_map()[ $slug ] ) ) {
			return false;
		}
		$type = function_exists( 'bi_page_type' ) ? bi_page_type( $slug ) : 'public';
		if ( 'dashboard' === $type ) {
			return false;
		}
		return in_array( $type, [ 'public', 'trust', 'legal', 'auth', 'utility', 'admin' ], true );
	}
	if ( is_post_type_archive( 'tutors' ) || is_singular( 'tutors' ) ) {
		return true;
	}
	return (bool) apply_filters( 'bi_uses_kinetic_surface', false );
}

/**
 * @param array<string, mixed> $classes Body classes.
 * @return array<string, mixed>
 */
function bi_kinetic_surface_body_class( $classes ) {
	if ( function_exists( 'bi_is_kinetic_home' ) && bi_is_kinetic_home() ) {
		$classes[] = 'bi-kinetic-home';
	}
	if ( bi_uses_kinetic_surface() ) {
		$classes[] = 'bi-kinetic-surface';
	}
	return $classes;
}
add_filter( 'body_class', 'bi_kinetic_surface_body_class', 12 );

/**
 * Enqueue kinetic tokens + bridge on inner marketing pages.
 */
function bi_kinetic_surface_assets() {
	if ( is_admin() || ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) ) {
		return;
	}

	$needs_tokens = bi_kinetic_layout_enabled() && (
		bi_uses_kinetic_surface()
		|| ( function_exists( 'bi_is_kinetic_home' ) && bi_is_kinetic_home() )
	);

	if ( ! $needs_tokens ) {
		return;
	}

	wp_enqueue_style(
		'bi-kinetic-tokens',
		BI_URI . '/assets/css/kinetic-tokens.css',
		[ 'bi-style' ],
		BI_VERSION
	);

	if ( ! bi_uses_kinetic_surface() ) {
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
