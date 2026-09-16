<?php
/**
 * Homepage kinetic section registry.
 *
 * @package TutorFabulous
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @return array<string, string> section id => option name
 */
function bi_home_sections_registry() {
    return apply_filters(
        'bi_filter_home_sections',
        [
            'trust'       => 'home_section_trust',
            'subjects'    => 'home_section_subjects',
            'journey'     => 'home_section_journey',
            'narrative'   => 'home_section_narrative',
            'highlights'  => 'home_section_highlights',
            'proof'       => 'home_section_proof',
            'video'       => 'home_section_video',
            'pathways'    => 'home_section_pathways',
            'image_hover' => 'home_section_image_hover',
            'tutors'      => 'home_section_tutors',
            'pricing'     => 'home_section_pricing',
            'reviews'     => 'home_section_reviews',
            'faq'         => 'home_section_faq',
        ]
    );
}

function bi_home_section_enabled( $section_id ) {
    if ( ! is_front_page() ) {
        return true;
    }
    $registry = bi_home_sections_registry();
    if ( ! isset( $registry[ $section_id ] ) ) {
        return true;
    }
    $option = $registry[ $section_id ];
    if ( class_exists( 'NGC_Section_CMS' ) && NGC_Section_CMS::CMS_DISABLED_MARKER === $option ) {
        return false;
    }
    return bi_theme_option_is_on( $option );
}

function bi_use_kinetic_home() {
	// Kinetic home clone used for 3D Scroll Manager preview (not the live front page).
	if ( bi_is_home_3d_preview() ) {
		return true;
	}
	if ( ! is_front_page() ) {
		return false;
	}
	$layout = bi_get_theme_option( 'home_layout', 'kinetic' );
	return 'classic' !== $layout;
}

/**
 * True on the /home-3d/ preview page only (live front page stays untouched).
 */
function bi_is_home_3d_preview() {
	return function_exists( 'is_page' ) && is_page( 'home-3d' );
}

/**
 * Whether home-3d should render NextGen 3D Filmstrip instead of carousel/tabs.
 */
function bi_home_3d_use_filmstrip() {
	return bi_is_home_3d_preview() && shortcode_exists( 'ngt_filmstrip' );
}

/**
 * Photography pack shared with the 3D Scroll lab (Home 2 / home-3d skin).
 *
 * @return array<string, string> Absolute URLs keyed by role.
 */
function bi_home_3d_media_pack() {
	$base = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/images/';
	$dir  = trailingslashit( get_stylesheet_directory() ) . 'assets/images/';

	$files = [
		'hero'      => 'hero-bg.jpg',
		'video'     => 'home-video.jpg',
		'about'     => 'about-feature.jpg',
		'become'    => 'become-tutor.jpg',
		'pricing'   => 'pricing-bg.jpg',
		'cta'       => 'cta-bg.jpg',
		'guarantee' => 'guarantee-bg.jpg',
	];

	$out = [];
	foreach ( $files as $key => $file ) {
		$path = $dir . $file;
		if ( is_readable( $path ) ) {
			$out[ $key ] = $base . $file;
		}
	}

	/**
	 * Filter Home 3D / Home 2 media URLs.
	 *
	 * @param array<string, string> $out Media URLs.
	 */
	return (array) apply_filters( 'bi_home_3d_media_pack', $out );
}

/**
 * Enqueue Home 2 cinematic skin on /home-3d/ only.
 */
function bi_home_3d_enqueue_skin() {
	if ( ! bi_is_home_3d_preview() ) {
		return;
	}

	$rel  = '/assets/css/home-3d-v2.css';
	$path = get_stylesheet_directory() . $rel;
	$ver  = is_readable( $path ) ? (string) filemtime( $path ) : ( defined( 'BI_VERSION' ) ? BI_VERSION : '1.0.0' );

	// Soft deps: kinetic handles may be registered later / missing — still load skin.
	$deps = [];
	foreach ( [ 'bi-kinetic-tokens', 'bi-kinetic-home', 'bi-style' ] as $handle ) {
		if ( wp_style_is( $handle, 'registered' ) || wp_style_is( $handle, 'enqueued' ) ) {
			$deps[] = $handle;
		}
	}

	wp_enqueue_style(
		'bi-home-3d-v2',
		get_stylesheet_directory_uri() . $rel,
		$deps,
		$ver
	);

	$media = bi_home_3d_media_pack();
	$css   = 'body.ngt-home-3d-preview{';
	$map   = [
		'hero'      => '--home3d-hero',
		'video'     => '--home3d-video',
		'about'     => '--home3d-about',
		'become'    => '--home3d-become',
		'pricing'   => '--home3d-pricing',
		'cta'       => '--home3d-cta',
		'guarantee' => '--home3d-guarantee',
	];
	foreach ( $map as $key => $var ) {
		if ( empty( $media[ $key ] ) ) {
			continue;
		}
		$css .= $var . ':url("' . esc_url_raw( $media[ $key ] ) . '");';
	}
	$css .= '}';
	wp_add_inline_style( 'bi-home-3d-v2', $css );
}
add_action( 'wp_enqueue_scripts', 'bi_home_3d_enqueue_skin', 55 );

function bi_format_rate( $option_name, $fallback = 320 ) {
    $amount = (int) bi_get_theme_option( $option_name, $fallback );
    return 'R' . number_format( max( 0, $amount ) );
}

function bi_should_show_page_title( $post_id = 0 ) {
    $post_id = $post_id ?: ( is_singular() ? get_queried_object_id() : 0 );
    return bi_theme_option_is_on( 'show_page_title', $post_id );
}

function bi_get_guarantee_code() {
    return sanitize_text_field( bi_get_theme_option( 'guarantee_code', 'NEXTGEN100' ) );
}

/**
 * Human-readable guarantee label (NEXTGEN100 → NextGen100).
 */
function bi_guarantee_label() {
    $code = strtoupper( bi_get_guarantee_code() );
    if ( preg_match( '/^NEXTGEN(\d+)$/', $code, $matches ) ) {
        return 'NextGen' . $matches[1];
    }
    return $code;
}
