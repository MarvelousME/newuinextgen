<?php
/**
 * BeyondInfinity Theme - functions.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BI_VERSION', '1.9.34' );
define( 'BI_DIR', get_stylesheet_directory() );
define( 'BI_URI', get_stylesheet_directory_uri() );
if ( ! defined( 'NGT_URI' ) ) {
    define( 'NGT_URI', BI_URI );
}
if ( ! defined( 'NGT_DIR' ) ) {
    define( 'NGT_DIR', BI_DIR );
}

require_once BI_DIR . '/inc/helpers.php';
require_once BI_DIR . '/inc/brand-content.php';
require_once BI_DIR . '/inc/companion.php';
require_once BI_DIR . '/inc/security.php';
require_once BI_DIR . '/inc/shortcodes-fallback.php';
require_once BI_DIR . '/inc/tutor-data.php';
require_once BI_DIR . '/inc/config/bootstrap.php';
require_once BI_DIR . '/inc/theme-switcher.php';
require_once BI_DIR . '/inc/loader.php';
require_once BI_DIR . '/inc/booking-drawer.php';
require_once BI_DIR . '/inc/enterprise-components.php';
require_once BI_DIR . '/inc/scheme.php';
require_once BI_DIR . '/inc/command-palette.php';
require_once BI_DIR . '/inc/motion.php';
require_once BI_DIR . '/inc/bi-3d.php';
require_once BI_DIR . '/inc/openwa.php';
require_once BI_DIR . '/inc/ui-icons.php';
require_once BI_DIR . '/inc/template-tags.php';
require_once BI_DIR . '/inc/nav-menu.php';
require_once BI_DIR . '/inc/tutoring-imagery.php';
require_once BI_DIR . '/inc/page-builders.php';
require_once BI_DIR . '/inc/page-wrapper.php';
require_once BI_DIR . '/inc/elementor-native.php';
require_once BI_DIR . '/inc/page-composer.php';
require_once BI_DIR . '/inc/builder-host.php';
require_once BI_DIR . '/inc/layout-manager.php';
require_once BI_DIR . '/inc/customizer.php';
require_once BI_DIR . '/inc/seo.php';
require_once BI_DIR . '/inc/roles.php';
require_once BI_DIR . '/inc/pages-registry.php';
require_once BI_DIR . '/inc/prototype-bodies.php';
require_once BI_DIR . '/inc/prototype-blend.php';
require_once BI_DIR . '/inc/content-groups.php';
require_once BI_DIR . '/inc/production-content.php';
require_once BI_DIR . '/inc/prototype-live-data.php';
require_once BI_DIR . '/inc/admin.php';
require_once BI_DIR . '/inc/admin-beyondinfinity.php';
require_once BI_DIR . '/inc/brand-style-kit.php';
require_once BI_DIR . '/inc/kinetic-home.php';
require_once BI_DIR . '/inc/kinetic-surface.php';
require_once BI_DIR . '/inc/kinetic-image-hover.php';
require_once BI_DIR . '/inc/nbi-infinity.php';
require_once BI_DIR . '/inc/workflows.php';
require_once BI_DIR . '/inc/widgets.php';
require_once BI_DIR . '/inc/ngt-assets.php';
require_once BI_DIR . '/inc/ui-library/bootstrap.php';

add_action( 'wp_enqueue_scripts', 'bi_enqueue_assets' );
function bi_enqueue_assets() {
    if ( is_admin() ) {
        return;
    }

    wp_enqueue_style(
        'bi-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap',
        [],
        null
    );

    $parent = wp_get_theme( 'hello-elementor' );
    if ( $parent->exists() ) {
        wp_enqueue_style(
            'hello-elementor-style',
            get_template_directory_uri() . '/style.css',
            [],
            $parent->get( 'Version' )
        );
    }

    $deps = $parent->exists() ? [ 'hello-elementor-style', 'bi-google-fonts' ] : [ 'bi-google-fonts' ];

    wp_enqueue_style( 'bi-style', get_stylesheet_uri(), $deps, BI_VERSION );
    wp_enqueue_style( 'bi-components', BI_URI . '/assets/css/components.css', [ 'bi-style' ], BI_VERSION );
    wp_enqueue_style( 'bi-hero-brand', BI_URI . '/assets/css/bi-hero-brand.css', [ 'bi-components' ], BI_VERSION );
    wp_enqueue_style( 'bi-sections', BI_URI . '/assets/css/sections.css', [ 'bi-style' ], BI_VERSION );
    wp_enqueue_style( 'bi-nav-menu', BI_URI . '/assets/css/nav-menu.css', [ 'bi-style' ], BI_VERSION );
    wp_enqueue_style( 'bi-ngt-toast', BI_URI . '/assets/css/ngt-toast.css', [ 'bi-style' ], BI_VERSION );
    wp_enqueue_style( 'bi-page-builders', BI_URI . '/assets/css/page-builders.css', [ 'bi-style' ], BI_VERSION );
    wp_enqueue_style( 'bi-nextgen-beyond-infinity-ui', BI_URI . '/assets/css/nextgen-beyond-infinity-ui.css', [ 'bi-style', 'bi-components', 'bi-page-builders' ], BI_VERSION );
    wp_enqueue_script( 'bi-nextgen-beyond-infinity-ui', BI_URI . '/assets/js/nextgen-beyond-infinity-ui.js', [], BI_VERSION, true );

    if ( ! bi_is_builder_edit_mode() ) {
        wp_enqueue_script( 'bi-main', BI_URI . '/assets/js/main.js', [ 'jquery' ], BI_VERSION, true );
        wp_enqueue_script( 'bi-nav-menu', BI_URI . '/assets/js/nav-menu.js', [], BI_VERSION, true );
        wp_enqueue_script( 'bi-ngt-toast', BI_URI . '/assets/js/ngt-toast.js', [], BI_VERSION, true );
        wp_localize_script( 'bi-main', 'biData', [
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'bi_nonce' ),
            'restRoot'   => esc_url_raw( rest_url() ),
            'restNonce'  => wp_create_nonce( 'wp_rest' ),
            'restNs'     => bi_rest_namespace(),
            'openwaNs'   => bi_openwa_namespace(),
            'siteUrl'    => home_url( '/' ),
            'ngcVersion' => bi_ngc_version(),
            'rates'      => [
                'online_short'   => (int) bi_get_theme_option( 'rate_online', 320 ),
                'online_long'    => max( 280, (int) bi_get_theme_option( 'rate_online', 320 ) - 20 ),
                'inperson_short' => (int) bi_get_theme_option( 'rate_inperson', 350 ),
                'inperson_long'  => max( 300, (int) bi_get_theme_option( 'rate_inperson', 350 ) - 30 ),
                'tertiary'       => (int) bi_get_theme_option( 'rate_tertiary', 500 ),
            ],
        ] );

        if ( bi_page_needs_carousel_assets() ) {
            wp_enqueue_script( 'bi-tutors-carousel', BI_URI . '/assets/js/tutors-carousel.js', [], BI_VERSION, true );
            wp_localize_script( 'bi-tutors-carousel', 'biCarousel', [
                'findUrl' => home_url( '/find-a-tutor' ),
            ] );
        }

        if ( bi_is_kinetic_home() ) {
            wp_enqueue_style( 'bi-kinetic-tokens', BI_URI . '/assets/css/kinetic-tokens.css', [ 'bi-style' ], BI_VERSION );
            wp_enqueue_style( 'bi-kinetic-home', BI_URI . '/assets/css/kinetic-home.css', [ 'bi-kinetic-tokens' ], BI_VERSION );
            wp_enqueue_style( 'bi-kinetic-image-hover', BI_URI . '/assets/css/kinetic-image-hover.css', [ 'bi-kinetic-home' ], BI_VERSION );
            wp_enqueue_style( 'bi-cinematic-hero', BI_URI . '/assets/css/bi-cinematic-hero.css', [ 'bi-kinetic-image-hover' ], BI_VERSION );
            wp_enqueue_script( 'bi-focus-trap', BI_URI . '/assets/js/bi-focus-trap.js', [], BI_VERSION, true );
            wp_enqueue_script( 'bi-kinetic-home', BI_URI . '/assets/js/kinetic-home.js', [ 'bi-focus-trap' ], BI_VERSION, true );
            wp_enqueue_script( 'bi-cinematic-video', BI_URI . '/assets/js/bi-cinematic-video.js', [], BI_VERSION, true );
            $layout_max = (int) apply_filters( 'ngt_content_width', 1280 );
            if ( $layout_max < 960 ) {
                $layout_max = 1280;
            }
            wp_add_inline_style(
                'bi-kinetic-home',
                sprintf( ':root{--ngi-layout-max:%dpx;}', $layout_max )
            );
        }
    }

    if ( bi_is_dashboard_page() && ! bi_is_builder_edit_mode() ) {
        wp_enqueue_style( 'bi-dashboard-mission', BI_URI . '/assets/css/dashboard-mission.css', [ 'bi-style' ], BI_VERSION );
        $slug = get_post_field( 'post_name', get_queried_object_id() );
        $type_map = [
            'parent-dashboard'  => 'parent',
            'student-dashboard' => 'student',
            'tutor-dashboard'   => 'tutor',
            'admin-dashboard'   => 'admin',
            'onboarding'        => 'admin',
        ];
        $dash_type = $type_map[ $slug ] ?? '';
        if ( $dash_type && bi_dashboard_rest_available() ) {
            bi_enqueue_dashboard_rest_for_type( $dash_type );
        }
    }

    $form_pages = [ 'find-a-tutor', 'become-a-tutor', 'contact', 'support', 'register', 'login' ];
    if ( is_page( $form_pages ) && ! bi_is_builder_edit_mode() ) {
        wp_enqueue_style( 'bi-ngc-forms', BI_URI . '/assets/css/ngc-forms.css', [ 'bi-style' ], BI_VERSION );
        wp_enqueue_style( 'bi-ngc-validation', BI_URI . '/assets/css/ngc-validation.css', [ 'bi-ngc-forms' ], BI_VERSION );
        wp_enqueue_script( 'bi-ngc-validation', BI_URI . '/assets/js/ngc-validation.js', [], BI_VERSION, true );
    }

    if ( is_page( 'login' ) && ! bi_is_builder_edit_mode() ) {
        wp_enqueue_script( 'bi-login', BI_URI . '/assets/js/bi-login.js', [], BI_VERSION, true );
    }

    if ( defined( 'NGC_PLUGIN_URL' ) && defined( 'NGC_VERSION' ) ) {
        wp_enqueue_style( 'ngc-button-processing', NGC_PLUGIN_URL . 'assets/css/ngc-button-processing.css', [], NGC_VERSION );
        wp_enqueue_script( 'ngc-button-processing', NGC_PLUGIN_URL . 'assets/js/ngc-button-processing.js', [], NGC_VERSION, true );
    }
}

function bi_enqueue_dashboard_rest_for_type( $type ) {
    if ( ! $type || bi_is_builder_edit_mode() ) {
        return;
    }
    if ( ! bi_dashboard_rest_available() && ! is_user_logged_in() ) {
        return;
    }
    if ( wp_script_is( 'bi-dashboard-rest', 'enqueued' ) ) {
        return;
    }
    // Chart.js is consumed by the analytics panels (paintCharts guards on window.Chart).
    wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], '4.4.1', true );
    // Single runtime source: prefer the Companion copy when the plugin is active.
    if ( defined( 'NGC_PLUGIN_URL' ) && defined( 'NGC_VERSION' ) && file_exists( WP_PLUGIN_DIR . '/' . dirname( NGC_PLUGIN_BASENAME ) . '/assets/js/dashboard-rest.js' ) ) {
        $src = NGC_PLUGIN_URL . 'assets/js/dashboard-rest.js';
        $ver = NGC_VERSION;
    } else {
        $src = BI_URI . '/assets/js/dashboard-rest.js';
        $ver = BI_VERSION;
    }
    wp_enqueue_script( 'bi-dashboard-rest', $src, [ 'chart-js' ], $ver, true );
    wp_localize_script( 'bi-dashboard-rest', 'biDashboard', bi_dashboard_rest_config( $type ) );
}

function bi_page_needs_carousel_assets() {
    if ( is_front_page() || is_page( [ 'home', 'find-a-tutor' ] ) ) {
        return true;
    }
    if ( is_active_widget( false, false, 'bi_tutors_carousel', true ) ) {
        return true;
    }
    global $post;
    if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'bi_tutors_carousel' ) ) {
        return true;
    }
    return (bool) apply_filters( 'bi_enqueue_carousel_assets', false );
}

function bi_is_builder_edit_mode() {
    // Request-level signals — available before Elementor finishes booting
    // (template_include and early enqueue hooks).
    if ( isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return true;
    }
    if ( isset( $_GET['action'] ) && 'elementor' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return true;
    }
    if ( isset( $_POST['action'] ) && in_array( (string) $_POST['action'], [ 'elementor_ajax', 'elementor_save' ], true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return true;
    }
    // REST / app editor bootstrap
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( (string) $_SERVER['REQUEST_URI'], '/elementor/' ) ) {
        return true;
    }

    if ( bi_elementor_active() && class_exists( '\Elementor\Plugin' ) ) {
        $plugin = \Elementor\Plugin::instance();
        if ( isset( $plugin->editor ) && method_exists( $plugin->editor, 'is_edit_mode' ) && $plugin->editor->is_edit_mode() ) {
            return true;
        }
        if ( isset( $plugin->preview ) && method_exists( $plugin->preview, 'is_preview_mode' ) && $plugin->preview->is_preview_mode() ) {
            return true;
        }
    }
    if ( function_exists( 'vc_is_frontend_editor' ) && vc_is_frontend_editor() ) {
        return true;
    }
    if ( function_exists( 'vc_is_page_editable' ) && vc_is_page_editable() ) {
        return true;
    }
    return false;
}

add_action( 'after_setup_theme', 'bi_theme_setup' );
function bi_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'gallery', 'caption', 'style', 'script' ] );
    add_theme_support( 'custom-logo', [
        'height' => 80, 'width' => 200, 'flex-height' => true, 'flex-width' => true,
    ] );
    add_theme_support( 'align-wide' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-styles' );
    register_nav_menus( [
        'primary'        => __( 'Primary Navigation', 'beyondinfinity' ),
        'footer-explore' => __( 'Footer Explore', 'beyondinfinity' ),
        'footer-company' => __( 'Footer Company', 'beyondinfinity' ),
        'footer-1'       => __( 'Footer Legal', 'beyondinfinity' ),
    ] );
}

add_filter( 'wp_resource_hints', 'bi_font_preconnect', 10, 2 );
function bi_font_preconnect( $urls, $relation_type ) {
    if ( 'preconnect' === $relation_type ) {
        $urls[] = 'https://fonts.googleapis.com';
        $urls[] = [ 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' ];
    }
    return $urls;
}

add_filter( 'the_content', 'bi_wpbakery_fix_shortcodes', 11 );
function bi_wpbakery_fix_shortcodes( $content ) {
    if ( bi_wpbakery_active() && function_exists( 'wpb_js_remove_wpautop' ) ) {
        return wpb_js_remove_wpautop( $content, true );
    }
    return $content;
}
