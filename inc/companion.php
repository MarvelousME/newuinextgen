<?php
/**
 * Companion interop — detect the domain plugin without claiming it is active.
 *
 * NGC_VERSION is owned by NextGenTutors-Companion. The theme must not define it.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resolved platform API version string.
 *
 * @return string
 */
function bi_ngc_version() {
    if ( class_exists( 'NGC_Plugin', false ) && defined( 'NGC_VERSION' ) ) {
        return NGC_VERSION;
    }
    return BI_VERSION;
}

/**
 * True when NextGenTutors-Companion is actually booted.
 *
 * @return bool
 */
function bi_companion_active() {
    return class_exists( 'NGC_Plugin', false );
}

/**
 * True when dashboard REST endpoints may be reachable.
 *
 * @return bool
 */
function bi_dashboard_rest_available() {
    if ( ! is_user_logged_in() ) {
        return false;
    }
    if ( bi_companion_active() ) {
        return true;
    }
    return (bool) apply_filters( 'bi_dashboard_rest_available', false );
}

/**
 * REST namespace — ngc/v1 when companion owns routes, else ngt/v1 fallback.
 *
 * @return string
 */
function bi_rest_namespace() {
    if ( class_exists( 'NGC_Plugin', false ) ) {
        return 'ngc/v1';
    }
    return 'ngt/v1';
}

/**
 * Dashboard slug from ngc_* shortcode tag.
 *
 * @param string $shortcode Shortcode with or without brackets.
 * @return string parent|student|tutor|admin|''
 */
function bi_dashboard_type_from_shortcode( $shortcode ) {
    $tag = trim( str_replace( [ '[', ']' ], '', $shortcode ) );
    $map = [
        'ngc_parent_dashboard'  => 'parent',
        'ngc_student_dashboard' => 'student',
        'ngc_tutor_dashboard'   => 'tutor',
        'ngc_admin_dashboard'     => 'admin',
    ];
    return $map[ $tag ] ?? '';
}

/**
 * REST path for a dashboard type.
 *
 * @param string $type Dashboard type.
 * @return string
 */
function bi_dashboard_rest_path( $type ) {
    $paths = [
        'parent'  => '/dashboard/parent',
        'student' => '/dashboard/student',
        'tutor'   => '/dashboard/tutor',
        'admin'   => '/dashboard/admin',
    ];
    return $paths[ $type ] ?? '';
}

/**
 * Whether the current page is a role dashboard.
 *
 * @return bool
 */
function bi_is_dashboard_page() {
    if ( ! is_page() ) {
        return false;
    }
    $slug = get_post_field( 'post_name', get_queried_object_id() );
    return in_array( $slug, array_keys( bi_dashboard_page_map() ), true );
}

/**
 * Localized config for dashboard REST client.
 *
 * @param string $type Dashboard type.
 * @return array<string, mixed>
 */
function bi_dashboard_rest_config( $type ) {
    return [
        'restRoot'  => esc_url_raw( rest_url() ),
        'namespace' => bi_rest_namespace(),
        'path'      => bi_dashboard_rest_path( $type ),
        'nonce'     => wp_create_nonce( 'wp_rest' ),
        'type'      => $type,
        'version'   => bi_ngc_version(),
        'pages'     => [
            'findATutor'   => home_url( '/find-a-tutor' ),
            'becomeATutor' => home_url( '/become-a-tutor' ),
            'pricing'      => home_url( '/pricing' ),
            'contact'      => home_url( '/contact' ),
            'support'      => home_url( '/contact' ),
            'adminArea'    => current_user_can( 'manage_options' ) ? admin_url() : '',
        ],
        'i18n'      => [
            'loading'  => __( 'Loading your dashboard…', 'beyondinfinity' ),
            'error'    => __( 'Could not load dashboard data. Please refresh or contact support.', 'beyondinfinity' ),
            'retry'    => __( 'Try again', 'beyondinfinity' ),
            'empty'    => __( 'No data yet.', 'beyondinfinity' ),
            'emptySessions' => __( 'No sessions yet.', 'beyondinfinity' ),
            'noUpcoming' => __( 'No upcoming lesson yet.', 'beyondinfinity' ),
            'bookSession' => __( 'Book a session', 'beyondinfinity' ),
            'bookForChild' => __( 'Book for your child', 'beyondinfinity' ),
            'viewMarketplace' => __( 'View marketplace', 'beyondinfinity' ),
            'updateProfile' => __( 'Update profile', 'beyondinfinity' ),
            'joining' => __( 'Joining…', 'beyondinfinity' ),
            'joinFailed' => __( 'Unable to join lesson', 'beyondinfinity' ),
            'joinTooEarly' => __( 'This lesson is not open to join yet.', 'beyondinfinity' ),
            'joinTooLate' => __( 'The join window for this lesson has closed.', 'beyondinfinity' ),
            'joinPayment' => __( 'Payment is still required before you can join.', 'beyondinfinity' ),
            'joinClosed' => __( 'This lesson is no longer available.', 'beyondinfinity' ),
            'joinNotReady' => __( 'This lesson is not ready to join yet.', 'beyondinfinity' ),
            'applicationUpdate' => __( "We'll update you when there's news.", 'beyondinfinity' ),
            'sessions' => __( 'Recent sessions', 'beyondinfinity' ),
            'billing'  => __( 'Billing', 'beyondinfinity' ),
            'payouts'  => __( 'Payouts', 'beyondinfinity' ),
            'pending'  => __( 'Pending tutor applications', 'beyondinfinity' ),
        ],
    ];
}
