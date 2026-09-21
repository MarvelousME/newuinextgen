<?php
/**
 * Production page rendering — real Companion data, not static prototype bodies.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether static HTML prototype bodies should render (design preview only).
 *
 * @return bool
 */
function bi_use_prototype_bodies() {
	return (bool) apply_filters(
		'bi_use_prototype_bodies',
		defined( 'BI_USE_PROTOTYPE_BODIES' ) && BI_USE_PROTOTYPE_BODIES
	);
}

/**
 * Directory of production-ready default PHP (live data, shortcodes, REST).
 *
 * @return string
 */
function bi_production_defaults_dir() {
	return BI_DIR . '/inc/defaults-production';
}

/**
 * Render a theme page default: production (default) or prototype preview.
 *
 * @param string $slug           Registry slug (e.g. find-a-tutor).
 * @param string $prototype_file Prototype filename (e.g. find-a-tutor-body.php).
 */
function bi_render_page_default( $slug, $prototype_file = '' ) {
	if ( bi_use_prototype_bodies() && $prototype_file ) {
		if ( function_exists( 'bi_include_prototype_body' ) ) {
			bi_include_prototype_body( $prototype_file );
		}
		if ( function_exists( 'bi_render_registry_shortcodes' ) ) {
			bi_render_registry_shortcodes( $slug );
		}
		if ( 'find-a-tutor' === $slug && shortcode_exists( 'ngc_tutor_marketplace' ) ) {
			echo '<section class="ng-page-section ngt-section bi-prototype-marketplace"><div class="ng-container">';
			echo do_shortcode( '[ngc_tutor_marketplace per_page="12"]' );
			echo '</div></section>';
		}
		return;
	}

	if ( function_exists( 'bi_should_render_prototype_blend' ) && bi_should_render_prototype_blend( $slug ) ) {
		$file = $prototype_file ?: ( function_exists( 'bi_get_prototype_file_for_slug' ) ? bi_get_prototype_file_for_slug( $slug ) : '' );
		if ( function_exists( 'bi_render_blended_prototype' ) ) {
			bi_render_blended_prototype( $slug, $file );
			return;
		}
	}

	bi_render_production_default( $slug );
}

/**
 * Include production default file for a page slug.
 *
 * @param string $slug Registry slug.
 */
function bi_render_production_default( $slug ) {
	$registry = function_exists( 'bi_pages_registry' ) ? bi_pages_registry() : [];
	$default  = $registry[ $slug ]['default'] ?? '';

	if ( $default ) {
		$path = bi_production_defaults_dir() . '/' . $default;
		if ( file_exists( $path ) ) {
			include $path;
			return;
		}
	}

	// Minimal fallback when production file missing.
	if ( function_exists( 'bi_render_registry_shortcodes' ) ) {
		bi_render_registry_shortcodes( $slug );
	}
}

/**
 * Pages that must not load static NGT mock-data JS bundles.
 *
 * @return string[]
 */
function bi_ngt_mock_data_page_slugs() {
	return [
		'home',
		'find-a-tutor',
		'student-dashboard',
		'parent-dashboard',
		'tutor-dashboard',
		'admin-dashboard',
	];
}

/**
 * Whether the current page should skip NGT data.js / tutors.js / dashboard.js mock bundles.
 *
 * @return bool
 */
function bi_ngt_skip_mock_data_scripts() {
	if ( bi_use_prototype_bodies() ) {
		return false;
	}
	$key = function_exists( 'bi_ngt_page_key' ) ? bi_ngt_page_key() : '';
	return $key && in_array( $key, bi_ngt_mock_data_page_slugs(), true );
}
