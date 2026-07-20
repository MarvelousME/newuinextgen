<?php
/**
 * Dashboard access control.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Restrict redirects to this site (prevents open redirect via login).
 *
 * @param string $url      Candidate URL.
 * @param string $fallback Fallback when invalid.
 * @return string
 */
function bi_validate_internal_redirect( $url, $fallback = '' ) {
    $fallback = $fallback ? $fallback : home_url( '/' );
    $url      = esc_url_raw( (string) $url );
    if ( ! $url ) {
        return $fallback;
    }
    $validated = wp_validate_redirect( $url, $fallback );
    return $validated ? $validated : $fallback;
}

function bi_dashboard_page_map() {
    return [
        'parent-dashboard'  => [ 'parent', 'parent_guardian', 'administrator' ],
        'student-dashboard' => [ 'student', 'subscriber', 'administrator' ],
        'tutor-dashboard'   => [ 'tutor', 'administrator' ],
        'admin-dashboard'   => [ 'administrator', 'ngc_finance', 'ngc_support' ],
        'onboarding'        => [ 'administrator', 'ngc_support' ],
        'wordpress-setup'   => [ 'administrator' ],
    ];
}

add_action( 'template_redirect', 'bi_protect_dashboard_pages' );
function bi_protect_dashboard_pages() {
    if ( ! is_page() ) {
        return;
    }

    $slug = get_post_field( 'post_name', get_queried_object_id() );
    $map  = bi_dashboard_page_map();

    if ( ! isset( $map[ $slug ] ) ) {
        return;
    }

    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( get_permalink() ), home_url( '/login' ) ) );
        exit;
    }

    $user    = wp_get_current_user();
    $roles   = (array) $user->roles;
    $allowed = $map[ $slug ];

    if ( ! array_intersect( $roles, $allowed ) ) {
        wp_safe_redirect( home_url( '/' ) );
        exit;
    }
}

add_filter( 'login_redirect', 'bi_login_redirect', 10, 3 );
function bi_login_redirect( $redirect_to, $requested, $user ) {
    if ( is_wp_error( $user ) || ! $user instanceof WP_User ) {
        return $redirect_to;
    }

    if ( ! empty( $_GET['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return bi_validate_internal_redirect( wp_unslash( $_GET['redirect_to'] ), home_url( '/' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    if ( in_array( 'administrator', $user->roles, true ) ) {
        return home_url( '/admin-dashboard' );
    }
    if ( in_array( 'ngc_finance', $user->roles, true ) || in_array( 'ngc_support', $user->roles, true ) ) {
        return home_url( '/admin-dashboard' );
    }
    if ( in_array( 'tutor', $user->roles, true ) ) {
        return home_url( '/tutor-dashboard' );
    }
    if ( array_intersect( [ 'parent', 'parent_guardian' ], $user->roles, true ) ) {
        return home_url( '/parent-dashboard' );
    }
    if ( array_intersect( [ 'student', 'subscriber' ], $user->roles, true ) ) {
        return home_url( '/student-dashboard' );
    }

    return $redirect_to;
}
