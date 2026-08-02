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

/**
 * Map a public login intent role to its dashboard path.
 *
 * @param string $role parent|student|tutor|admin.
 * @return string Absolute URL.
 */
function bi_role_dashboard_url( $role ) {
    $map = [
        'parent'  => '/parent-dashboard',
        'student' => '/student-dashboard',
        'tutor'   => '/tutor-dashboard',
        'admin'   => '/admin-dashboard',
    ];
    $path = $map[ $role ] ?? '/student-dashboard';
    return home_url( $path );
}

/**
 * Whether a URL points at a role dashboard (not a deep-link journey).
 *
 * @param string $url Candidate URL.
 * @return bool
 */
function bi_is_role_dashboard_url( $url ) {
    $path = (string) wp_parse_url( (string) $url, PHP_URL_PATH );
    if ( '' === $path ) {
        return false;
    }
    $path = untrailingslashit( $path );
    foreach ( [ 'parent-dashboard', 'student-dashboard', 'tutor-dashboard', 'admin-dashboard' ] as $slug ) {
        if ( str_ends_with( $path, '/' . $slug ) || $path === $slug ) {
            return true;
        }
    }
    return false;
}

/**
 * Canonical post-login home for a user based on their WordPress roles.
 *
 * @param WP_User $user User.
 * @return string
 */
function bi_user_role_home_url( WP_User $user ) {
    if ( in_array( 'administrator', $user->roles, true ) ) {
        return bi_role_dashboard_url( 'admin' );
    }
    if ( in_array( 'ngc_finance', $user->roles, true ) || in_array( 'ngc_support', $user->roles, true ) ) {
        return bi_role_dashboard_url( 'admin' );
    }
    if ( in_array( 'tutor', $user->roles, true ) ) {
        return bi_role_dashboard_url( 'tutor' );
    }
    if ( array_intersect( [ 'parent', 'parent_guardian' ], (array) $user->roles ) ) {
        return bi_role_dashboard_url( 'parent' );
    }
    if ( array_intersect( [ 'student', 'subscriber' ], (array) $user->roles ) ) {
        return bi_role_dashboard_url( 'student' );
    }
    return home_url( '/' );
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

    $role_home = bi_user_role_home_url( $user );

    // Honour deep-link journeys (checkout, booking, protected pages). Role-dashboard
    // redirects from the login intent cards are replaced by the user's real role home
    // so a parent who clicked "Student" still lands on the parent dashboard.
    $candidate = '';
    if ( ! empty( $requested ) ) {
        $candidate = bi_validate_internal_redirect( $requested, '' );
    } elseif ( ! empty( $_GET['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $candidate = bi_validate_internal_redirect( wp_unslash( $_GET['redirect_to'] ), '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    } elseif ( ! empty( $redirect_to ) ) {
        $candidate = bi_validate_internal_redirect( $redirect_to, '' );
    }

    if ( $candidate && ! bi_is_role_dashboard_url( $candidate ) ) {
        return $candidate;
    }

    return $role_home ? $role_home : $redirect_to;
}

/**
 * Bounce failed front-end logins back to the themed login page with a recoverable error.
 *
 * @param string $username Attempted username.
 */
add_action( 'wp_login_failed', 'bi_login_failed_redirect' );
function bi_login_failed_redirect( $username ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
    $referrer = wp_get_referer();
    if ( ! $referrer || false !== strpos( $referrer, 'wp-login.php' ) ) {
        return;
    }

    $login_path = wp_parse_url( home_url( '/login' ), PHP_URL_PATH );
    $ref_path   = wp_parse_url( $referrer, PHP_URL_PATH );
    if ( ! $login_path || ! $ref_path || untrailingslashit( $ref_path ) !== untrailingslashit( $login_path ) ) {
        return;
    }

    $args  = [ 'login' => 'failed' ];
    $query = wp_parse_url( $referrer, PHP_URL_QUERY );
    if ( $query ) {
        parse_str( $query, $params );
        if ( ! empty( $params['role'] ) ) {
            $args['role'] = sanitize_key( $params['role'] );
        }
        if ( ! empty( $params['redirect_to'] ) ) {
            $args['redirect_to'] = (string) $params['redirect_to'];
        }
    }

    wp_safe_redirect( add_query_arg( $args, home_url( '/login' ) ) );
    exit;
}
