<?php
/**
 * NextGen design system assets (from Styling/assets).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return string
 */
function bi_ngt_assets_uri() {
	return BI_URI . '/assets/ngt';
}

/**
 * @return string
 */
function bi_ngt_assets_dir() {
	return BI_DIR . '/assets/ngt';
}

/**
 * Whether the NGT visual skin should load.
 *
 * @return bool
 */
function bi_ngt_skin_enabled() {
	if ( is_admin() || bi_is_builder_edit_mode() ) {
		return false;
	}
	if ( function_exists( 'bi_prototype_blend_active' ) && bi_prototype_blend_active() ) {
		return true;
	}
	if ( function_exists( 'bi_uses_marketing_header_chrome' ) && bi_uses_marketing_header_chrome() ) {
		return true;
	}
	if ( function_exists( 'bi_visual_preset_uses_ngt_skin' ) && ! bi_visual_preset_uses_ngt_skin() ) {
		return false;
	}
	return (bool) apply_filters( 'bi_ngt_skin_enabled', true );
}

/**
 * Current page key for data-page + script routing.
 *
 * @return string
 */
function bi_ngt_page_key() {
	if ( is_front_page() ) {
		return 'home';
	}
	if ( is_singular( 'page' ) ) {
		return sanitize_key( get_post_field( 'post_name', get_queried_object_id() ) );
	}
	return '';
}

/**
 * Map page slug → NGT JS bundles (without .js).
 *
 * @return array<string, string[]>
 */
function bi_ngt_script_map() {
	return [
		'home'              => [ 'data', 'home' ],
		'find-a-tutor'      => [ 'data', 'tutors' ],
		'pricing'           => [ 'data', 'pricing', 'static' ],
		'about'             => [ 'static' ],
		'contact'           => [ 'static' ],
		'become-a-tutor'    => [ 'static' ],
		'guarantee'         => [ 'static' ],
		'safety-guide'      => [ 'static' ],
		'tutor-vetting'     => [ 'static' ],
		'privacy-policy'    => [ 'static' ],
		'terms'             => [ 'static' ],
		'blog'              => [ 'static' ],
		'onboarding'        => [ 'onboarding' ],
		'wordpress-setup'   => [ 'setup' ],
		'student-dashboard' => [ 'dashboard' ],
		'tutor-dashboard'   => [ 'tutor-dash' ],
		'admin-dashboard'   => [ 'admin-dash' ],
		'parent-dashboard'  => [ 'dashboard' ],
	];
}

add_action( 'wp_enqueue_scripts', 'bi_ngt_enqueue_assets', 12 );

/**
 * Enqueue NGT CSS/JS design system.
 */
function bi_ngt_enqueue_assets() {
	if ( is_admin() ) {
		return;
	}

	$uri = bi_ngt_assets_uri();
	$ver = BI_VERSION;

	// Always load float-dock CSS so the right rail + back-to-top work site-wide.
	$float_css = bi_ngt_assets_dir() . '/css/floating.css';
	if ( file_exists( $float_css ) ) {
		wp_enqueue_style( 'bi-ngt-floating', $uri . '/css/floating.css', [ 'bi-style' ], $ver );
	}

	// Sticky header + FAB (sole CSS enqueue — after floating.css).
	wp_enqueue_style(
		'bi-sticky-ui',
		BI_URI . '/assets/css/bi-sticky-ui.css',
		file_exists( $float_css ) ? [ 'bi-nav-menu', 'bi-ngt-floating' ] : [ 'bi-nav-menu' ],
		$ver
	);
	wp_enqueue_script(
		'bi-sticky-ui',
		BI_URI . '/assets/js/bi-sticky-ui.js',
		[ 'bi-nav-menu' ],
		$ver,
		true
	);

	if ( ! bi_ngt_skin_enabled() ) {
		wp_enqueue_script( 'bi-ngt-wp-bridge', BI_URI . '/assets/js/ngt-wp-bridge.js', [], $ver, true );
		wp_enqueue_script( 'bi-ngt-floating', $uri . '/js/floating.js', [ 'bi-ngt-wp-bridge' ], $ver, true );
		return;
	}

	wp_enqueue_style(
		'bi-ngt-fonts',
		'https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400..800;1,400..700&family=Sanchez:ital@0;1&family=Space+Grotesk:wght@400;500;600;700&display=swap',
		[],
		null
	);

	$css_chain = [
		'bi-ngt-tokens'     => [ 'file' => 'css/tokens.css', 'deps' => [ 'bi-ngt-fonts' ] ],
		'bi-ngt-components' => [ 'file' => 'css/components.css', 'deps' => [ 'bi-ngt-tokens' ] ],
		'bi-ngt-pages'      => [ 'file' => 'css/pages.css', 'deps' => [ 'bi-ngt-components' ] ],
		'bi-ngt-content'    => [ 'file' => 'css/content.css', 'deps' => [ 'bi-ngt-pages' ] ],
		'bi-ngt-bridge'     => [ 'file' => '../css/ngt-wp-bridge.css', 'deps' => [ 'bi-ngt-floating', 'bi-style' ] ],
	];

	foreach ( $css_chain as $handle => $cfg ) {
		$path = ( 0 === strpos( $cfg['file'], '../' ) )
			? BI_DIR . '/assets/' . ltrim( $cfg['file'], '../' )
			: bi_ngt_assets_dir() . '/' . $cfg['file'];
		if ( ! file_exists( $path ) ) {
			continue;
		}
		$file_uri = ( 0 === strpos( $cfg['file'], '../' ) )
			? BI_URI . '/assets/' . ltrim( $cfg['file'], '../' )
			: $uri . '/' . $cfg['file'];
		wp_enqueue_style( $handle, $file_uri, $cfg['deps'], $ver );
	}

	bi_ngt_register_vendor_scripts();

	// Spiral page intro + GSAP (home auto-play + logo replay).
	wp_enqueue_script( 'bi-ngt-gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', [], '3.12.5', true );
	wp_enqueue_script( 'bi-ngt-scrolltrigger', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js', [ 'bi-ngt-gsap' ], '3.12.5', true );
	wp_enqueue_script( 'bi-ngt-wp-bridge', BI_URI . '/assets/js/ngt-wp-bridge.js', [ 'bi-ngt-lucide', 'bi-ngt-gsap' ], $ver, true );
	if ( file_exists( bi_ngt_assets_dir() . '/js/preloader.js' ) ) {
		wp_enqueue_script( 'bi-ngt-preloader', $uri . '/js/preloader.js', [ 'bi-ngt-gsap', 'bi-ngt-wp-bridge' ], $ver, true );
	}

	$page_key = bi_ngt_page_key();
	$map      = bi_ngt_script_map();
	$bundles  = $map[ $page_key ] ?? [];

	if ( is_page() && ! empty( get_post()->post_content ) && false !== strpos( get_post()->post_content, 'data-reveal' ) ) {
		if ( ! in_array( 'static', $bundles, true ) ) {
			$bundles[] = 'static';
		}
	}

	// Resolve the effective bundle list first so vendor scripts (GSAP) are
	// only fetched when a bundle that uses them will actually load (Phase 5).
	$effective = [];
	foreach ( array_unique( $bundles ) as $bundle ) {
		$file = bi_ngt_assets_dir() . '/js/' . $bundle . '.js';
		if ( ! file_exists( $file ) ) {
			continue;
		}
		// Production pages use Companion REST — skip static mock-data bundles.
		if ( function_exists( 'bi_ngt_skip_mock_data_scripts' ) && bi_ngt_skip_mock_data_scripts() ) {
			if ( in_array( $bundle, [ 'data', 'tutors', 'dashboard', 'tutor-dash', 'admin-dash', 'home' ], true ) ) {
				continue;
			}
		}
		// Static HTML home.js conflicts with kinetic homepage — skip when kinetic is active.
		if ( 'home' === $bundle && function_exists( 'bi_use_kinetic_home' ) && bi_use_kinetic_home() && is_front_page() ) {
			continue;
		}
		$effective[] = $bundle;
	}

	$deps = [ 'bi-ngt-wp-bridge' ];
	if ( in_array( 'static', $effective, true ) || in_array( 'home', $effective, true ) ) {
		$deps[] = 'bi-ngt-scrolltrigger';
	}

	foreach ( $effective as $bundle ) {
		$handle = 'bi-ngt-' . $bundle;
		wp_enqueue_script( $handle, $uri . '/js/' . $bundle . '.js', $deps, $ver, true );
		$deps[] = $handle;
	}

	// Float dock (back-to-top + right rail) on every front-end page.
	wp_enqueue_script( 'bi-ngt-floating', $uri . '/js/floating.js', [ 'bi-ngt-wp-bridge' ], $ver, true );
	if ( file_exists( bi_ngt_assets_dir() . '/js/chat.js' ) ) {
		wp_enqueue_script( 'bi-ngt-chat', $uri . '/js/chat.js', [ 'bi-ngt-floating' ], $ver, true );
	}

	wp_localize_script(
		'bi-ngt-wp-bridge',
		'NGT_WP',
		[
			'assetsUrl'  => trailingslashit( $uri ),
			'imgUrl'     => trailingslashit( $uri . '/img' ),
			'logo200Url' => trailingslashit( $uri . '/img' ) . 'logo-200.png',
			'homeUrl'    => home_url( '/' ),
			'pageKey'    => $page_key,
			'findUrl'    => home_url( '/find-a-tutor' ),
			'loginUrl'   => home_url( '/login' ),
			'contactUrl' => home_url( '/contact' ),
			'waNumber'   => preg_replace( '/\D+/', '', (string) bi_get_theme_option( 'whatsapp_number', bi_get_theme_option( 'bi_whatsapp', '27813340625' ) ) ),
			'supportUrl' => home_url( '/support/' ),
			'restUrl'    => esc_url_raw( rest_url( 'ngc/v1' ) ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
		]
	);
}

/**
 * Register Lucide (icons used across NGT components).
 */
function bi_ngt_register_vendor_scripts() {
	wp_register_script(
		'bi-ngt-lucide',
		'https://unpkg.com/lucide@latest/dist/umd/lucide.min.js',
		[],
		null,
		true
	);
	wp_enqueue_script( 'bi-ngt-lucide' );
}

add_filter( 'body_class', 'bi_ngt_body_class' );

/**
 * @param string[] $classes Body classes.
 * @return string[]
 */
function bi_ngt_body_class( $classes ) {
	if ( ! bi_ngt_skin_enabled() ) {
		return $classes;
	}
	$classes[] = 'bi-ngt-skin';
	$key = bi_ngt_page_key();
	if ( $key ) {
		$classes[] = 'ngt-page-' . $key;
	}
	return $classes;
}

add_action( 'wp_body_open', 'bi_ngt_print_data_page', 1 );

/**
 * Print data-page attribute for NGT page scripts.
 */
function bi_ngt_print_data_page() {
	if ( ! bi_ngt_skin_enabled() ) {
		return;
	}
	$key = bi_ngt_page_key();
	if ( $key ) {
		echo '<script>document.body.dataset.page="' . esc_js( $key ) . '";</script>';
	}
}

/**
 * Default logo URL from NGT assets.
 *
 * @return string
 */
function bi_ngt_default_logo_url() {
	return bi_ngt_assets_uri() . '/img/logo.png';
}

// Elementor-native authoring (widgets + seed + kinetic yield).
require_once __DIR__ . '/elementor-native.php';
