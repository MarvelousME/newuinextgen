<?php
/**
 * Dynamic CSS from color schemes.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_enqueue_scripts', 'bi_enqueue_dynamic_theme_css_file', 100 );
add_action( 'wp_head', 'bi_print_dynamic_theme_css', 99 );

function bi_dynamic_css_file_path() {
    $upload = wp_upload_dir();
    if ( ! empty( $upload['error'] ) ) {
        return '';
    }
    return trailingslashit( $upload['basedir'] ) . 'beyondinfinity/custom.css';
}

function bi_dynamic_css_file_url() {
    $upload = wp_upload_dir();
    if ( ! empty( $upload['error'] ) ) {
        return '';
    }
    return trailingslashit( $upload['baseurl'] ) . 'beyondinfinity/custom.css';
}

function bi_enqueue_dynamic_theme_css_file() {
    if ( is_admin() || bi_is_builder_edit_mode() ) {
        return;
    }
    $path = bi_dynamic_css_file_path();
    if ( $path && file_exists( $path ) ) {
        wp_enqueue_style( 'bi-dynamic-theme', bi_dynamic_css_file_url(), [], (string) filemtime( $path ) );
    }
}

function bi_print_dynamic_theme_css() {
    if ( is_admin() || bi_is_builder_edit_mode() ) {
        return;
    }
    if ( bi_dynamic_css_file_path() && file_exists( bi_dynamic_css_file_path() ) ) {
        return;
    }
    $css = bi_get_dynamic_theme_css();
    if ( $css ) {
        echo '<style id="bi-dynamic-theme-css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

function bi_get_dynamic_theme_css() {
    $tokens = bi_get_active_color_tokens();
    if ( empty( $tokens ) ) {
        return '';
    }
    $rules = [];
    foreach ( $tokens as $prop => $value ) {
        $rules[] = esc_attr( $prop ) . ':' . esc_attr( $value );
    }
    $body_extra = '';
    if ( 'boxed' === bi_get_theme_option( 'body_style', 'wide' ) ) {
        $body_extra = 'max-width:1200px;margin-left:auto;margin-right:auto;box-shadow:0 0 0 1px var(--ngt-border);';
    }
    $scheme = esc_attr( bi_get_theme_option( 'color_scheme', 'default' ) );
    $css = 'html[data-bi-scheme="' . $scheme . '"]{' . implode( ';', $rules ) . '}';
    $css .= 'body.bi-body-boxed .bi-theme-content{' . $body_extra . '}';
    $css .= 'html[data-bi-scheme="' . $scheme . '"]{--bi-scheme-name:"' . $scheme . '"}';
    return apply_filters( 'bi_filter_dynamic_css', $css );
}

function bi_customizer_save_css() {
    $path = bi_dynamic_css_file_path();
    if ( ! $path ) {
        return;
    }
    $dir = dirname( $path );
    if ( ! file_exists( $dir ) ) {
        wp_mkdir_p( $dir );
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    file_put_contents( $path, bi_get_dynamic_theme_css() );
}

/**
 * Become-a-tutor style header chrome — transparent NGT nav over hero/pagehead.
 */
function bi_uses_marketing_header_chrome() {
	if ( is_admin() || ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) ) {
		return false;
	}
	if ( function_exists( 'bi_is_dashboard_page' ) && bi_is_dashboard_page() ) {
		return false;
	}
	if ( function_exists( 'bi_page_type' ) && in_array( bi_page_type(), [ 'dashboard', 'admin' ], true ) ) {
		return false;
	}
	if ( 'minimal' === bi_get_header_style() ) {
		return false;
	}
	return 'transparent' === bi_get_header_style();
}

add_filter( 'body_class', 'bi_config_body_classes' );
function bi_config_body_classes( $classes ) {
    $classes[] = 'bi-scheme-' . sanitize_html_class( bi_get_theme_option( 'color_scheme', 'default' ) );
    if ( 'boxed' === bi_get_theme_option( 'body_style', 'wide' ) ) {
        $classes[] = 'bi-body-boxed';
    }
    $classes[] = 'bi-header-' . sanitize_html_class( bi_get_header_style() );
    $classes[] = 'bi-footer-' . sanitize_html_class( bi_get_footer_style() );
    if ( bi_nav_on_hero() ) {
        $classes[] = 'bi-nav-on-hero';
    }
    if ( bi_uses_marketing_header_chrome() ) {
        $classes[] = 'bi-marketing-header';
    }
    return $classes;
}

/**
 * Legacy home-only white nav tokens (disabled when marketing header chrome is active).
 */
function bi_nav_on_hero() {
    if ( bi_uses_marketing_header_chrome() ) {
        return false;
    }
    return is_front_page()
        && function_exists( 'bi_use_kinetic_home' )
        && bi_use_kinetic_home()
        && 'transparent' === bi_get_header_style();
}

/**
 * Persist transparent header as the global default (matches become-a-tutor).
 */
function bi_ensure_global_header_style_default() {
	$mod = get_theme_mod( 'header_style', null );
	if ( null === $mod || '' === $mod || 'default' === $mod ) {
		set_theme_mod( 'header_style', 'transparent' );
		if ( function_exists( 'bi_storage_isset' ) && bi_storage_isset( 'options', 'header_style' ) ) {
			bi_storage_set_array2( 'options', 'header_style', 'val', 'transparent' );
		}
	}
}
