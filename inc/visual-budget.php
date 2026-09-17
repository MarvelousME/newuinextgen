<?php
/**
 * Wave A visual budget — page weight, hero variants, asset gates, module loader.
 *
 * @package TutorFabulous
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slugs that must stay LIGHT (no cinematic / NGT skin / 3D / motion JS).
 *
 * @return string[]
 */
function bi_visual_weight_light_slugs() {
	return (array) apply_filters(
		'bi_visual_weight_light_slugs',
		[
			'login',
			'register',
			'parent-checkout',
			'privacy-policy',
			'privacy',
			'terms',
			'child-safety',
			'wordpress-setup',
			'thank-you',
		]
	);
}

/**
 * Slugs that may load the full cinematic / NGT / 3D stack.
 *
 * @return string[]
 */
function bi_visual_weight_heavy_slugs() {
	return (array) apply_filters(
		'bi_visual_weight_heavy_slugs',
		[
			'home',
			'home-scroll',
			'kinetic-home',
			'home-3d',
			'3d-scroll-test',
		]
	);
}

/**
 * Default hero variant per page slug.
 *
 * @return array<string, string>
 */
function bi_hero_variant_map() {
	return (array) apply_filters(
		'bi_hero_variant_map',
		[
			'home'              => 'cinematic',
			'home-scroll'       => 'cinematic',
			'kinetic-home'      => 'cinematic',
			'home-3d'           => 'cinematic',
			'3d-scroll-test'    => 'cinematic',
			'find-a-tutor'      => 'search',
			'tutor-marketplace' => 'search',
			'become-a-tutor'    => 'conversion',
			'pricing'           => 'conversion',
			'about'             => 'editorial',
			'blog'              => 'editorial',
			'guarantee'         => 'trust',
			'tutor-vetting'     => 'trust',
			'safety-guide'      => 'trust',
			'contact'           => 'utility',
			'support'           => 'utility',
			'thank-you'         => 'utility',
			'login'             => 'auth',
			'register'          => 'auth',
			'parent-checkout'   => 'utility',
			'parent-dashboard'  => 'dashboard',
			'student-dashboard' => 'dashboard',
			'tutor-dashboard'   => 'dashboard',
			'admin-dashboard'   => 'dashboard',
			'onboarding'        => 'dashboard',
			'wordpress-setup'   => 'dashboard',
			'privacy-policy'    => 'legal',
			'privacy'           => 'legal',
			'terms'             => 'legal',
			'child-safety'      => 'legal',
		]
	);
}

/**
 * Current request page slug for budget decisions.
 *
 * @return string
 */
function bi_visual_budget_slug() {
	if ( function_exists( 'bi_page_slug' ) ) {
		return sanitize_key( (string) bi_page_slug() );
	}
	if ( is_front_page() ) {
		return 'home';
	}
	if ( is_singular() ) {
		return sanitize_key( (string) get_post_field( 'post_name', get_queried_object_id() ) );
	}
	return '';
}

/**
 * LIGHT | MEDIUM | HEAVY
 *
 * @param string $slug Optional slug override.
 * @return string
 */
function bi_page_visual_weight( $slug = '' ) {
	$slug = sanitize_key( $slug ?: bi_visual_budget_slug() );

	if ( is_front_page() || in_array( $slug, bi_visual_weight_heavy_slugs(), true ) ) {
		return (string) apply_filters( 'bi_page_visual_weight', 'HEAVY', $slug );
	}

	if ( in_array( $slug, bi_visual_weight_light_slugs(), true ) ) {
		return (string) apply_filters( 'bi_page_visual_weight', 'LIGHT', $slug );
	}

	$type = function_exists( 'bi_page_type' ) ? bi_page_type( $slug ) : '';
	if ( in_array( $type, [ 'legal', 'auth' ], true ) ) {
		return (string) apply_filters( 'bi_page_visual_weight', 'LIGHT', $slug );
	}

	return (string) apply_filters( 'bi_page_visual_weight', 'MEDIUM', $slug );
}

/**
 * Named hero variant for this request (or explicit override).
 *
 * @param string $slug Optional slug override.
 * @return string
 */
function bi_hero_variant( $slug = '' ) {
	$slug    = sanitize_key( $slug ?: bi_visual_budget_slug() );
	$map     = bi_hero_variant_map();
	$variant = isset( $map[ $slug ] ) ? (string) $map[ $slug ] : '';

	if ( '' === $variant ) {
		$type = function_exists( 'bi_page_type' ) ? bi_page_type( $slug ) : 'public';
		$by_type = [
			'public'    => 'editorial',
			'trust'     => 'trust',
			'legal'     => 'legal',
			'auth'      => 'auth',
			'dashboard' => 'dashboard',
			'utility'   => 'utility',
			'admin'     => 'dashboard',
		];
		$variant = $by_type[ $type ] ?? 'editorial';
	}

	$allowed = [ 'cinematic', 'editorial', 'search', 'trust', 'conversion', 'utility', 'auth', 'dashboard', 'legal' ];
	if ( ! in_array( $variant, $allowed, true ) ) {
		$variant = 'editorial';
	}

	return (string) apply_filters( 'bi_hero_variant', $variant, $slug );
}

/**
 * Cinematic MP4, NBI constellation, aurora layers.
 *
 * @return bool
 */
function bi_allows_cinematic_assets() {
	$allow = 'HEAVY' === bi_page_visual_weight();
	return (bool) apply_filters( 'bi_allows_cinematic_assets', $allow );
}

/**
 * NGT lime skin + GSAP page bundles.
 *
 * @return bool
 */
function bi_allows_ngt_motion() {
	$allow = 'HEAVY' === bi_page_visual_weight();
	return (bool) apply_filters( 'bi_allows_ngt_motion', $allow );
}

/**
 * Three.js / tilt / 3D scroll stack.
 *
 * @return bool
 */
function bi_allows_3d_assets() {
	$slug  = bi_visual_budget_slug();
	$allow = 'HEAVY' === bi_page_visual_weight() && in_array( $slug, [ 'home-3d', '3d-scroll-test', 'home-scroll', 'kinetic-home' ], true );
	if ( is_front_page() && 'HEAVY' === bi_page_visual_weight() ) {
		// Front page keeps existing kinetic-home 3D flags (carousel / customizer).
		$allow = true;
	}
	return (bool) apply_filters( 'bi_allows_3d_assets', $allow, $slug );
}

/**
 * Motion JS (Anime / motion.js). CSS pack still loads.
 *
 * @return bool
 */
function bi_allows_motion_js() {
	$allow = 'LIGHT' !== bi_page_visual_weight();
	return (bool) apply_filters( 'bi_allows_motion_js', $allow );
}

/**
 * Load a shared Wave A module from template-parts/modules/{slug}.php.
 *
 * @param string               $slug Module basename without .php.
 * @param array<string, mixed> $args Passed to the partial as $args.
 */
function bi_tf_module( $slug, $args = [] ) {
	$slug = sanitize_file_name( (string) $slug );
	if ( '' === $slug ) {
		return;
	}
	$rel = 'template-parts/modules/' . $slug;
	$path = trailingslashit( BI_DIR ) . $rel . '.php';
	if ( ! is_readable( $path ) ) {
		return;
	}
	get_template_part( $rel, null, is_array( $args ) ? $args : [] );
}
