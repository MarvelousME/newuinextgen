<?php
/**
 * Shared option list values.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @param array<string, string> $list
 * @return array<string, string>
 */
function bi_list_prepend_inherit( $list ) {
    return array_merge( [ 'inherit' => __( 'Inherit', 'beyondinfinity' ) ], $list );
}

function bi_get_list_yesno( $prepend_inherit = false ) {
    $list = [
        'yes' => __( 'Yes', 'beyondinfinity' ),
        'no'  => __( 'No', 'beyondinfinity' ),
    ];
    return $prepend_inherit ? bi_list_prepend_inherit( $list ) : $list;
}

function bi_get_list_onoff( $prepend_inherit = false ) {
    $list = [
        'on'  => __( 'On', 'beyondinfinity' ),
        'off' => __( 'Off', 'beyondinfinity' ),
    ];
    return $prepend_inherit ? bi_list_prepend_inherit( $list ) : $list;
}

function bi_get_list_color_schemes() {
    return apply_filters( 'bi_filter_list_color_schemes', [
        'default'  => __( 'NextGen Default', 'beyondinfinity' ),
        'ocean'    => __( 'Ocean', 'beyondinfinity' ),
        'midnight' => __( 'Midnight', 'beyondinfinity' ),
    ] );
}

function bi_get_list_visual_presets() {
    $list = [];
    foreach ( bi_get_visual_presets() as $id => $preset ) {
        $list[ $id ] = $preset['title'] ?? $id;
    }
    return apply_filters( 'bi_filter_list_visual_presets', $list );
}

function bi_get_list_theme_switcher_visibility() {
    return apply_filters( 'bi_filter_list_theme_switcher_visibility', [
        'admins' => __( 'Administrators only', 'beyondinfinity' ),
        'public' => __( 'All visitors', 'beyondinfinity' ),
    ] );
}

function bi_get_list_header_styles( $prepend_inherit = false ) {
    $list = apply_filters( 'bi_filter_list_header_styles', [
        'default'     => __( 'Default', 'beyondinfinity' ),
        'transparent' => __( 'Transparent', 'beyondinfinity' ),
        'minimal'     => __( 'Minimal (dashboard)', 'beyondinfinity' ),
    ] );
    return $prepend_inherit ? bi_list_prepend_inherit( $list ) : $list;
}

function bi_get_list_footer_styles( $prepend_inherit = false ) {
    $list = apply_filters( 'bi_filter_list_footer_styles', [
        'default' => __( 'Default', 'beyondinfinity' ),
        'minimal' => __( 'Minimal', 'beyondinfinity' ),
    ] );
    return $prepend_inherit ? bi_list_prepend_inherit( $list ) : $list;
}

function bi_get_list_body_styles( $prepend_inherit = false ) {
    $list = [
        'wide'  => __( 'Full width', 'beyondinfinity' ),
        'boxed' => __( 'Boxed', 'beyondinfinity' ),
    ];
    return $prepend_inherit ? bi_list_prepend_inherit( $list ) : $list;
}

function bi_get_list_home_layouts() {
    return [
        'kinetic' => __( 'Kinetic homepage', 'beyondinfinity' ),
        'classic' => __( 'Classic (legacy sections)', 'beyondinfinity' ),
    ];
}
