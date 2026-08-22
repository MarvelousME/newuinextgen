<?php
/**
 * Page inventory — maps theme pages to pages-to-review sources and touchpoints.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Canonical page registry (slug => metadata).
 *
 * @return array<string, array<string, mixed>>
 */
function bi_pages_registry() {
    return [
        'home'              => [
            'template' => 'front-page.php',
            'default' => 'home.php',
            'source' => 'index.html',
            'type' => 'public',
            'config_defaults' => [
                'header_style' => 'transparent',
            ],
        ],
		'find-a-tutor'      => [ 'template' => 'page-find-tutor.php', 'default' => 'find-a-tutor.php', 'source' => 'find-a-tutor.html', 'shortcodes' => [ 'ngc_tutor_marketplace', 'ngc_find_tutor_form' ], 'type' => 'public' ],
        'become-a-tutor'    => [ 'template' => 'page-become-a-tutor.php', 'default' => 'become-a-tutor.php', 'source' => 'become-a-tutor.html', 'shortcodes' => [ 'ngc_become_tutor_form' ], 'type' => 'public' ],
        'about'             => [ 'template' => 'page-about.php', 'default' => 'about.php', 'source' => 'about.html', 'type' => 'public' ],
        'contact'           => [ 'template' => 'page-contact.php', 'default' => 'contact.php', 'source' => 'contact.html', 'shortcodes' => [ 'ngc_contact_support_form' ], 'type' => 'public' ],
        'support'           => [ 'template' => 'page-support.php', 'default' => 'support.php', 'source' => 'contact.html', 'shortcodes' => [ 'ngc_contact_support_form' ], 'type' => 'public' ],
        'blog'              => [ 'template' => 'page-blog.php', 'default' => 'blog.php', 'source' => 'blog.html', 'type' => 'public' ],
        'guarantee'         => [ 'template' => 'page-guarantee.php', 'default' => 'guarantee.php', 'source' => 'guarantee.html', 'type' => 'public' ],
        'register'          => [ 'template' => 'page-register.php', 'default' => 'register.php', 'source' => 'register (md)', 'shortcodes' => [ 'ngc_parent_register_child_form', 'ngc_student_register_form' ], 'type' => 'auth' ],
        'login'             => [ 'template' => 'page-login.php', 'default' => 'login.php', 'source' => 'login (md)', 'shortcodes' => [ 'ngc_login_form', 'ngc_forgot_password_form' ], 'type' => 'auth' ],
		'pricing'           => [ 'template' => 'page-pricing.php', 'default' => 'pricing.php', 'source' => 'pricing.html', 'type' => 'public' ],
		'subjects'          => [
			'template' => 'page-subject.php',
			'default'  => 'subject.php',
			'source'   => '—',
			'type'     => 'public',
			'note'     => 'Hub + child pages synced from NGC_Subjects_CMS',
		],
		'parent-checkout'   => [
            'template'   => 'page-parent-checkout.php',
            'default'    => 'parent-checkout.php',
            'source'     => '—',
            'shortcodes' => [ 'ngc_parent_checkout' ],
            'type'       => 'auth',
            'config_defaults' => [
                'force_theme_default' => 1,
                'show_page_title'     => 1,
            ],
        ],
        'tutor-vetting'     => [ 'template' => 'page-tutor-vetting.php', 'default' => 'tutor-vetting.php', 'source' => 'tutor-vetting.html', 'type' => 'trust' ],
        'safety-guide'      => [ 'template' => 'page-safety-guide.php', 'default' => 'safety-guide.php', 'source' => 'safety-guide.html', 'type' => 'trust' ],
        'thank-you'         => [ 'template' => 'page-thank-you.php', 'default' => 'thank-you.php', 'source' => '—', 'type' => 'utility' ],
        'privacy-policy'    => [ 'template' => 'page-privacy-policy.php', 'default' => 'privacy-policy.php', 'source' => 'privacy.html', 'type' => 'legal' ],
        'terms'             => [ 'template' => 'page-terms.php', 'default' => 'terms.php', 'source' => 'terms.html', 'type' => 'legal' ],
        'child-safety'      => [ 'template' => 'page-child-safety.php', 'default' => 'child-safety.php', 'source' => 'safety-guide.html', 'type' => 'legal' ],
        'parent-dashboard'  => [
            'template' => 'page-parent-dashboard.php',
            'default' => 'parent-dashboard.php',
            'source' => 'dashboard.html',
            'shortcodes' => [ 'ngc_parent_dashboard' ],
            'type' => 'dashboard',
            'config_defaults' => [
                'header_style'      => 'minimal',
                'footer_style'      => 'minimal',
                'hide_whatsapp_fab' => 1,
                'show_page_title'   => 0,
            ],
        ],
        'student-dashboard' => [
            'template' => 'page-student-dashboard.php',
            'default' => 'student-dashboard.php',
            'source' => 'dashboard.html',
            'shortcodes' => [ 'ngc_student_dashboard' ],
            'type' => 'dashboard',
            'config_defaults' => [
                'header_style'      => 'minimal',
                'footer_style'      => 'minimal',
                'hide_whatsapp_fab' => 1,
                'show_page_title'   => 0,
            ],
        ],
        'tutor-dashboard'   => [
            'template' => 'page-tutor-dashboard.php',
            'default' => 'tutor-dashboard.php',
            'source' => 'tutor-dashboard.html',
            'shortcodes' => [ 'ngc_tutor_dashboard' ],
            'type' => 'dashboard',
            'config_defaults' => [
                'header_style'      => 'minimal',
                'footer_style'      => 'minimal',
                'hide_whatsapp_fab' => 1,
                'show_page_title'   => 0,
            ],
        ],
        'admin-dashboard'   => [
            'template' => 'admin-dashboard.php',
            'default' => 'admin-dashboard.php',
            'source' => 'admin-dashboard (prototype)',
            'shortcodes' => [ 'ngc_admin_dashboard' ],
            'type' => 'dashboard',
            'config_defaults' => [
                'header_style'      => 'minimal',
                'footer_style'      => 'minimal',
                'hide_whatsapp_fab' => 1,
                'show_page_title'   => 0,
            ],
        ],
        'onboarding'        => [ 'template' => 'page-onboarding.php', 'default' => 'onboarding.php', 'source' => 'onboarding.html', 'shortcodes' => [ 'ngc_admin_dashboard' ], 'type' => 'admin' ],
        'wordpress-setup'   => [ 'template' => 'page-wordpress-setup.php', 'default' => 'wordpress-setup.php', 'source' => 'wordpress-setup.html', 'type' => 'admin' ],
    ];
}

/**
 * CPT / archive touchpoints (not in page-map).
 *
 * @return array<string, array<string, string>>
 */
function bi_cpt_touchpoints() {
    return [
        'tutors' => [
            'source'   => 'tutor-profile.html',
            'template' => 'single-tutors.php',
            'note'     => 'Single tutor profile — not a WordPress page slug',
        ],
    ];
}

/**
 * Load and validate content/page-map.json.
 *
 * @return array<int, array<string, mixed>>|WP_Error
 */
function bi_load_page_map() {
    $map_file = BI_DIR . '/content/page-map.json';
    if ( ! file_exists( $map_file ) ) {
        return new WP_Error( 'bi_no_map', __( 'page-map.json not found.', 'beyondinfinity' ) );
    }

    $pages = json_decode( (string) file_get_contents( $map_file ), true );
    if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $pages ) ) {
        return new WP_Error(
            'bi_bad_map',
            sprintf(
                /* translators: %s: JSON error message */
                __( 'Invalid page map JSON: %s', 'beyondinfinity' ),
                json_last_error_msg()
            )
        );
    }

    return $pages;
}

/**
 * Compare page-map slugs/templates with bi_pages_registry().
 *
 * @return array<int, string> Drift messages (empty = in sync).
 */
function bi_page_map_registry_drift() {
    $pages = bi_load_page_map();
    if ( is_wp_error( $pages ) ) {
        return [ $pages->get_error_message() ];
    }

    $registry   = bi_pages_registry();
    $map_slugs  = array_filter( array_column( $pages, 'slug' ) );
    $reg_slugs  = array_keys( $registry );
    $drift      = [];

    foreach ( array_diff( $map_slugs, $reg_slugs ) as $slug ) {
        $drift[] = sprintf( 'Slug in page-map but not registry: %s', $slug );
    }
    foreach ( array_diff( $reg_slugs, $map_slugs ) as $slug ) {
        if ( 'home' === $slug ) {
            continue;
        }
        $drift[] = sprintf( 'Slug in registry but not page-map: %s', $slug );
    }

    $map_by_slug = [];
    foreach ( $pages as $row ) {
        if ( ! empty( $row['slug'] ) ) {
            $map_by_slug[ $row['slug'] ] = $row;
        }
    }

    foreach ( $registry as $slug => $meta ) {
        if ( ! isset( $map_by_slug[ $slug ] ) ) {
            continue;
        }
        $expected = $meta['template'] ?? '';
        $actual   = $map_by_slug[ $slug ]['template'] ?? '';
        if ( ! empty( $map_by_slug[ $slug ]['is_front'] ) ) {
            $actual = 'front-page.php';
            $expected = 'front-page.php';
        }
        if ( $expected && $actual && 'default' !== $actual && $expected !== $actual ) {
            $drift[] = sprintf( 'Template mismatch for %s: map=%s registry=%s', $slug, $actual, $expected );
        }
    }

    return $drift;
}

/**
 * Audit filesystem touchpoints for every registered page.
 *
 * @return array<int, array<string, mixed>>
 */
function bi_pages_touchpoint_audit() {
    $map = bi_load_page_map();
    if ( is_wp_error( $map ) ) {
        return [];
    }
    $slugs_in_map = array_column( $map, 'slug' );

    $rows = [];
    foreach ( bi_pages_registry() as $slug => $meta ) {
        $template = BI_DIR . '/' . ( $meta['template'] ?? '' );
        $default  = BI_DIR . '/inc/defaults/' . ( $meta['default'] ?? '' );
        $shortcodes = $meta['shortcodes'] ?? [];
        $sc_ok      = true;
        $sc_missing = [];
        foreach ( $shortcodes as $tag ) {
            if ( ! shortcode_exists( $tag ) ) {
                $sc_ok        = false;
                $sc_missing[] = $tag;
            }
        }

        $rows[] = [
            'slug'            => $slug,
            'source'          => $meta['source'] ?? '—',
            'type'            => $meta['type'] ?? 'public',
            'in_page_map'     => in_array( $slug, $slugs_in_map, true ),
            'template_ok'     => file_exists( $template ),
            'default_ok'      => file_exists( $default ),
            'shortcodes_ok'   => empty( $shortcodes ) ? null : $sc_ok,
            'shortcodes_miss' => $sc_missing,
            'seo_meta'        => isset( bi_page_meta_descriptions()[ $slug ] ),
            'config_defaults' => ! empty( $meta['config_defaults'] ),
            'config_defaults_keys' => array_keys( $meta['config_defaults'] ?? [] ),
        ];
    }
    return $rows;
}
