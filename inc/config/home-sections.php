<?php
/**
 * Homepage kinetic section registry.
 *
 * @package BeyondInfinity
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
    return is_front_page() && 'kinetic' === bi_get_theme_option( 'home_layout', 'kinetic' );
}

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
