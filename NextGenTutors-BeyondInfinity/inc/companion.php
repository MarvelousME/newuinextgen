<?php
/**
 * Companion interop — NGC_VERSION bridge and REST fallback detection.
 *
 * When NextGenTutors-Companion is not installed, NGT_VERSION from the active
 * NextGen Tutors theme/platform is mirrored to NGC_VERSION so dashboards
 * and detection logic share one contract.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'after_setup_theme', 'bi_bridge_ngc_version', 0 );
/**
 * Mirror NGT_VERSION → NGC_VERSION until NextGenTutors-Companion defines it.
 */
function bi_bridge_ngc_version() {
    if ( defined( 'NGC_VERSION' ) ) {
        return;
    }
    if ( defined( 'NGT_VERSION' ) ) {
        define( 'NGC_VERSION', NGT_VERSION );
    }
}

/**
 * Resolved platform API version string.
 *
 * @return string
 */
function bi_ngc_version() {
    if ( defined( 'NGC_VERSION' ) ) {
        return NGC_VERSION;
    }
    if ( defined( 'NGT_VERSION' ) ) {
        return NGT_VERSION;
    }
    return BI_VERSION;
}

/**
 * True when the companion plugin or bridged NGT platform is available.
 *
 * @return bool
 */
function bi_companion_active() {
    if ( class_exists( 'NGC_Plugin', false ) ) {
        return true;
    }
    if ( defined( 'NGC_VERSION' ) && defined( 'NGT_VERSION' ) ) {
        return true;
    }
    if ( defined( 'NGC_VERSION' ) && function_exists( 'ngt_dashboard_student_payload' ) ) {
        return true;
    }
    return false;
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
    if ( function_exists( 'ngt_rest_dashboard_student' ) ) {
        return true;
    }
    if ( defined( 'NGT_VERSION' ) ) {
        return true;
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
            'findATutor' => home_url( '/find-a-tutor' ),
            'contact'    => home_url( '/contact' ),
            'support'    => home_url( '/contact' ),
        ],
        'i18n'      => [
            'loading'  => __( 'Loading your dashboard…', 'beyondinfinity' ),
            'error'    => __( 'Could not load dashboard data. Please refresh or contact support.', 'beyondinfinity' ),
            'empty'    => __( 'No data yet.', 'beyondinfinity' ),
            'sessions' => __( 'Recent sessions', 'beyondinfinity' ),
            'billing'  => __( 'Billing', 'beyondinfinity' ),
            'payouts'  => __( 'Payouts', 'beyondinfinity' ),
            'pending'  => __( 'Pending tutor applications', 'beyondinfinity' ),
        ],
    ];
}
