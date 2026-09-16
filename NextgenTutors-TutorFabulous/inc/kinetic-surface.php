<?php
/**
 * Kinetic surface — site-wide kinetic-ui (marketing + dashboards).
 *
 * @package TutorFabulous
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
 * Views that use the dedicated homepage kinetic markup (.ngi-*).
 */
function bi_is_kinetic_home_request() {
	return function_exists( 'bi_is_kinetic_home' ) && bi_is_kinetic_home();
}

/**
 * Whether kinetic-ui should NOT apply (admin / builder only).
 * Dashboards are included for kinetic restyle.
 */
function bi_is_kinetic_ui_excluded() {
	if ( is_admin() || ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) ) {
		return true;
	}
	return (bool) apply_filters( 'bi_kinetic_ui_excluded', false );
}

/**
 * Full kinetic visual system (home + marketing + dashboards).
 */
function bi_uses_kinetic_ui() {
	if ( bi_is_kinetic_ui_excluded() ) {
		return false;
	}
	if ( ! bi_kinetic_layout_enabled() ) {
		return false;
	}
	return (bool) apply_filters( 'bi_uses_kinetic_ui', true );
}

/**
 * Dashboard kinetic mode.
 */
function bi_uses_kinetic_dashboard() {
	if ( ! bi_uses_kinetic_ui() ) {
		return false;
	}
	if ( function_exists( 'bi_is_dashboard_page' ) && bi_is_dashboard_page() ) {
		return true;
	}
	if ( is_page() && function_exists( 'bi_page_type' ) && 'dashboard' === bi_page_type() ) {
		return true;
	}
	return false;
}

/**
 * Inner marketing pages that need the .ng-page / .ngt-* bridge adapter.
 * Home uses .ngi-* markup; dashboards use .ngi-page--dashboard.
 */
function bi_uses_kinetic_surface() {
	if ( ! bi_uses_kinetic_ui() ) {
		return false;
	}
	if ( bi_is_kinetic_home_request() ) {
		return false;
	}
	return (bool) apply_filters( 'bi_uses_kinetic_surface', true );
}

/**
 * @param array<string, mixed> $classes Body classes.
 * @return array<string, mixed>
 */
function bi_kinetic_surface_body_class( $classes ) {
	if ( bi_uses_kinetic_ui() ) {
		$classes[] = 'bi-kinetic-ui';
	}
	if ( bi_is_kinetic_home_request() ) {
		$classes[] = 'bi-kinetic-home';
	}
	if ( bi_uses_kinetic_dashboard() ) {
		$classes[] = 'bi-kinetic-dashboard';
		$classes[] = 'bi-mission-dashboard';
	}
	if ( bi_uses_kinetic_surface() ) {
		$classes[] = 'bi-kinetic-surface';
	}
	return $classes;
}
add_filter( 'body_class', 'bi_kinetic_surface_body_class', 12 );

/**
 * Enqueue full kinetic home stack on all marketing + dashboard views.
 */
function bi_kinetic_surface_assets() {
	if ( ! bi_uses_kinetic_ui() ) {
		return;
	}

	wp_enqueue_style(
		'bi-kinetic-tokens',
		BI_URI . '/assets/css/kinetic-tokens.css',
		[ 'bi-style' ],
		BI_VERSION
	);
	wp_enqueue_style(
		'bi-kinetic-home',
		BI_URI . '/assets/css/kinetic-home.css',
		[ 'bi-kinetic-tokens' ],
		BI_VERSION
	);
	wp_enqueue_style(
		'bi-kinetic-image-hover',
		BI_URI . '/assets/css/kinetic-image-hover.css',
		[ 'bi-kinetic-home' ],
		BI_VERSION
	);
	wp_enqueue_style(
		'bi-cinematic-hero',
		BI_URI . '/assets/css/bi-cinematic-hero.css',
		[ 'bi-kinetic-image-hover' ],
		BI_VERSION
	);

	wp_enqueue_script( 'bi-focus-trap', BI_URI . '/assets/js/bi-focus-trap.js', [], BI_VERSION, true );
	wp_enqueue_script(
		'bi-kinetic-home',
		BI_URI . '/assets/js/kinetic-home.js',
		[ 'bi-focus-trap' ],
		BI_VERSION,
		true
	);
	wp_enqueue_script(
		'bi-cinematic-video',
		BI_URI . '/assets/js/bi-cinematic-video.js',
		[],
		BI_VERSION,
		true
	);

	$layout_max = (int) apply_filters( 'ngt_content_width', 1280 );
	if ( $layout_max < 960 ) {
		$layout_max = 1280;
	}
	wp_add_inline_style(
		'bi-kinetic-home',
		sprintf( ':root{--ngi-layout-max:%dpx;}', $layout_max )
	);

	// Scroll / form control enhancements on every kinetic view (home + inner + dashboards).
	$page_js_deps = [ 'bi-kinetic-home' ];
	if ( wp_script_is( 'bi-page-composer', 'registered' ) || wp_script_is( 'bi-page-composer', 'enqueued' ) ) {
		$page_js_deps[] = 'bi-page-composer';
	}
	wp_enqueue_script(
		'bi-kinetic-page',
		BI_URI . '/assets/js/kinetic-page.js',
		$page_js_deps,
		BI_VERSION,
		true
	);

	if ( bi_uses_kinetic_surface() ) {
		$bridge_deps = [ 'bi-kinetic-home' ];
		if ( wp_style_is( 'bi-page-composer', 'registered' ) || wp_style_is( 'bi-page-composer', 'enqueued' ) ) {
			$bridge_deps[] = 'bi-page-composer';
		}
		wp_enqueue_style(
			'bi-kinetic-bridge',
			BI_URI . '/assets/css/kinetic-bridge.css',
			$bridge_deps,
			BI_VERSION
		);
	}

	if ( bi_uses_kinetic_dashboard() ) {
		wp_enqueue_style(
			'bi-dashboard-mission',
			BI_URI . '/assets/css/dashboard-mission.css',
			[ 'bi-kinetic-home' ],
			BI_VERSION
		);
		wp_enqueue_style(
			'bi-kinetic-dashboard',
			BI_URI . '/assets/css/kinetic-dashboard.css',
			[ 'bi-dashboard-mission', 'bi-kinetic-home' ],
			BI_VERSION
		);
	}
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
