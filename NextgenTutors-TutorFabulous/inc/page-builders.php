<?php
/**
 * Elementor and WPBakery Page Builder compatibility.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Current page ID for front page and singular pages.
 */
function bi_get_current_page_id() {
    if ( is_front_page() ) {
        $front = (int) get_option( 'page_on_front' );
        if ( $front ) {
            return $front;
        }
    }
    if ( is_singular() ) {
        return (int) get_queried_object_id();
    }
    return 0;
}

/**
 * Whether Elementor plugin is available.
 */
function bi_elementor_active() {
    return did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' );
}

/**
 * Whether WPBakery is available.
 */
function bi_wpbakery_active() {
    return defined( 'WPB_VC_VERSION' ) || class_exists( 'Vc_Manager', false );
}

/**
 * Page built with Elementor.
 */
function bi_is_elementor_built( $post_id = 0 ) {
    $post_id = $post_id ?: bi_get_current_page_id();
    if ( ! $post_id || ! bi_elementor_active() ) {
        return false;
    }
    if ( ! bi_elementor_has_content( $post_id ) ) {
        return false;
    }
    if ( method_exists( '\Elementor\Plugin', 'instance' ) ) {
        $plugin = \Elementor\Plugin::instance();
        if ( isset( $plugin->db ) && method_exists( $plugin->db, 'is_built_with_elementor' ) ) {
            return (bool) $plugin->db->is_built_with_elementor( $post_id );
        }
    }
    return 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true );
}

/**
 * Elementor page has saved builder data (not just empty editor meta).
 */
function bi_elementor_has_content( $post_id ) {
    $data = get_post_meta( $post_id, '_elementor_data', true );
    if ( empty( $data ) ) {
        return false;
    }
    if ( is_string( $data ) ) {
        $data = json_decode( $data, true );
    }
    return is_array( $data ) && ! empty( $data );
}

/**
 * Page built with WPBakery.
 */
function bi_is_wpbakery_built( $post_id = 0 ) {
    $post_id = $post_id ?: bi_get_current_page_id();
    if ( ! $post_id ) {
        return false;
    }
    if ( 'true' === get_post_meta( $post_id, '_wpb_vc_js_status', true ) ) {
        return true;
    }
    $post = get_post( $post_id );
    if ( ! $post || empty( $post->post_content ) ) {
        return false;
    }
    return has_shortcode( $post->post_content, 'vc_row' )
        || has_shortcode( $post->post_content, 'vc_section' )
        || ( false !== strpos( $post->post_content, '[vc_row' ) );
}

/**
 * Page uses Elementor or WPBakery content instead of theme default.
 */
function bi_page_uses_builder( $post_id = 0 ) {
    $post_id = $post_id ?: bi_get_current_page_id();
    if ( ! $post_id ) {
        return false;
    }
    if ( bi_is_elementor_built( $post_id ) || bi_is_wpbakery_built( $post_id ) ) {
        return true;
    }
    return bi_page_has_editor_content( $post_id );
}

/**
 * Non-empty block editor / classic content (not auto-generated).
 */
function bi_page_has_editor_content( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post ) {
        return false;
    }
    $content = trim( $post->post_content );
    if ( '' === $content ) {
        return false;
    }
    return (bool) strlen( trim( wp_strip_all_tags( $content ) ) );
}

/**
 * Ensure the main query contains the target page (front page edge cases).
 */
function bi_ensure_page_in_loop( $post_id = 0 ) {
    $post_id = $post_id ?: bi_get_current_page_id();
    if ( ! $post_id || have_posts() ) {
        return;
    }
    global $wp_query;
    $wp_query = new WP_Query(
        [
            'page_id'    => $post_id,
            'post_type'  => 'page',
            'post_status'=> get_post_status( $post_id ) ?: 'publish',
        ]
    );
}

/**
 * Elementor canvas / theme builder template — skip theme chrome.
 */
function bi_is_elementor_canvas_template( $post_id = 0 ) {
    $post_id = $post_id ?: bi_get_current_page_id();
    if ( ! $post_id ) {
        return false;
    }
    $slug = get_page_template_slug( $post_id );
    return in_array( $slug, [ 'elementor_canvas', 'elementor_header_footer' ], true );
}

/**
 * Render page builder content area.
 */
function bi_render_builder_content() {
    bi_ensure_page_in_loop();

    echo '<main id="primary" class="site-main bi-builder-content">';
    if ( have_posts() ) {
        while ( have_posts() ) {
            the_post();
            echo '<article id="post-' . esc_attr( (string) get_the_ID() ) . '" ';
            post_class( 'bi-builder-article' );
            echo '>';
            the_content();
            wp_link_pages( [
                'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'beyondinfinity' ),
                'after'  => '</div>',
            ] );
            echo '</article>';
        }
    }
    echo '</main>';
}

/**
 * Try Elementor Theme Builder location (Hello Elementor pattern).
 */
function bi_elementor_theme_location_handled() {
    if ( ! function_exists( 'elementor_theme_do_location' ) ) {
        return false;
    }
    // Prefer kinetic theme home over Theme Builder front_page when the page is not Elementor-built.
    if ( is_front_page()
        && function_exists( 'bi_use_kinetic_home' )
        && bi_use_kinetic_home()
        && ! bi_is_elementor_built( bi_get_current_page_id() )
    ) {
        return false;
    }
    if ( is_front_page() && elementor_theme_do_location( 'front_page' ) ) {
        return true;
    }
    if ( is_singular() && elementor_theme_do_location( 'single' ) ) {
        return true;
    }
    return false;
}

/**
 * Keep kinetic marketing home on the theme front-page.php — Elementor's
 * header-footer/canvas page templates otherwise swallow an empty shell.
 * Never override while Elementor/WPBakery editor or preview is active.
 */
add_filter( 'template_include', 'bi_prefer_kinetic_front_page_template', 99 );
function bi_prefer_kinetic_front_page_template( $template ) {
    if ( ! is_front_page() ) {
        return $template;
    }
    if ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) {
        return $template;
    }
    // Honour Elementor canvas / header-footer page templates on the live site.
    if ( function_exists( 'bi_is_elementor_canvas_template' ) && bi_is_elementor_canvas_template() ) {
        return $template;
    }
    if ( ! function_exists( 'bi_use_kinetic_home' ) || ! bi_use_kinetic_home() ) {
        return $template;
    }
    if ( function_exists( 'bi_should_show_theme_fallback' ) && ! bi_should_show_theme_fallback() ) {
        return $template;
    }
    $front = trailingslashit( get_stylesheet_directory() ) . 'front-page.php';
    if ( file_exists( $front ) ) {
        return $front;
    }
    return $template;
}

/**
 * Theme + page builder setup.
 */
add_action( 'after_setup_theme', 'bi_page_builder_theme_support', 15 );
function bi_page_builder_theme_support() {
    add_post_type_support( 'page', 'elementor' );

    if ( bi_elementor_active() ) {
        add_theme_support( 'elementor', [
            'page_title_selector' => '.bi-hero__title, .entry-title',
        ] );
    } else {
        add_theme_support( 'elementor' );
    }
}

add_action( 'elementor/theme/register_locations', 'bi_register_elementor_locations' );
function bi_register_elementor_locations( $elementor_theme_manager ) {
    if ( ! $elementor_theme_manager || ! method_exists( $elementor_theme_manager, 'register_all_core_location' ) ) {
        return;
    }
    $elementor_theme_manager->register_all_core_location();
}

add_action( 'vc_before_init', 'bi_wpbakery_theme_setup' );
function bi_wpbakery_theme_setup() {
    if ( function_exists( 'vc_set_as_theme' ) ) {
        vc_set_as_theme( true );
    }
    if ( function_exists( 'vc_set_default_editor_post_types' ) ) {
        vc_set_default_editor_post_types( [ 'page', 'post' ] );
    }
}

add_filter( 'body_class', 'bi_page_builder_body_classes' );
function bi_page_builder_body_classes( $classes ) {
    $post_id = bi_get_current_page_id();
    if ( bi_page_uses_builder( $post_id ) ) {
        $classes[] = 'bi-page-builder-active';
    }
    if ( bi_is_elementor_built( $post_id ) ) {
        $classes[] = 'bi-elementor-page';
    }
    if ( bi_is_wpbakery_built( $post_id ) ) {
        $classes[] = 'bi-wpbakery-page';
    }
    if ( bi_is_elementor_canvas_template( $post_id ) ) {
        $classes[] = 'bi-elementor-canvas';
    }
    return $classes;
}

add_filter( 'bi_show_whatsapp_fab', 'bi_hide_fab_on_canvas' );
add_filter( 'bi_show_sticky_cta', 'bi_hide_fab_on_canvas' );
function bi_hide_fab_on_canvas( $show ) {
    if ( bi_is_elementor_canvas_template() ) {
        return false;
    }
    return $show;
}

/**
 * Allow shortcodes inside builder text widgets.
 */
add_action( 'init', 'bi_builder_shortcode_support' );
function bi_builder_shortcode_support() {
    if ( bi_wpbakery_active() && function_exists( 'vc_set_shortcodes_templates_dir' ) ) {
        add_filter( 'wpb_js_composer_shortcodes_template_dir', function () {
            return BI_DIR . '/vc_templates';
        } );
    }
}

/**
 * Prevent theme JS/CSS conflicts in Elementor editor + preview iframe.
 */
add_action( 'elementor/editor/before_enqueue_scripts', 'bi_elementor_editor_compat' );
add_action( 'elementor/preview/enqueue_styles', 'bi_elementor_editor_compat' );
function bi_elementor_editor_compat() {
    $handles = [
        'bi-main',
        'bi-kinetic-home',
        'bi-cinematic-video',
        'bi-loader',
        'bi-ngt-floating',
        'bi-ngt-chat',
        'bi-3d',
        'nbi-infinity',
        'bi-page-composer',
    ];
    foreach ( $handles as $handle ) {
        wp_dequeue_script( $handle );
    }
}

add_action( 'vc_backend_editor_enqueue_js_css', 'bi_vc_backend_dequeue_theme_js' );
add_action( 'vc_frontend_editor_enqueue_js_css', 'bi_vc_backend_dequeue_theme_js' );
function bi_vc_backend_dequeue_theme_js() {
    wp_dequeue_script( 'bi-main' );
}

/**
 * Elementor only prints elementorFrontendConfig when it rendered page content
 * (`_has_elementor_in_page`). Addons (Animation Addons, Fluent Forms, ShopBuild,
 * Hello Elementor) still enqueue frontend.min.js on kinetic/theme pages, which
 * then throws ReferenceError. Print the config whenever that handle is queued.
 */
add_action( 'wp_footer', 'bi_ensure_elementor_frontend_config', 5 );
function bi_ensure_elementor_frontend_config() {
    if ( ! class_exists( '\Elementor\Plugin' ) ) {
        return;
    }

    $queued = wp_script_is( 'elementor-frontend', 'enqueued' )
        || wp_script_is( 'elementor-frontend', 'registered' );
    if ( ! $queued ) {
        return;
    }

    $frontend = \Elementor\Plugin::$instance->frontend ?? null;
    if ( ! $frontend ) {
        return;
    }

    // Elementor's own wp_footer (priority 10) will print config when it rendered content.
    if ( method_exists( $frontend, 'has_elementor_in_page' ) && $frontend->has_elementor_in_page() ) {
        return;
    }

    $scripts = wp_scripts();
    $before  = $scripts->get_data( 'elementor-frontend', 'before' );
    $extra   = $scripts->get_data( 'elementor-frontend', 'data' );
    $has_cfg = ( is_array( $before ) && implode( '', $before ) && false !== strpos( implode( '', $before ), 'elementorFrontendConfig' ) )
        || ( is_string( $extra ) && false !== strpos( $extra , 'elementorFrontendConfig' ) );
    if ( $has_cfg ) {
        return;
    }

    if ( ! wp_script_is( 'elementor-frontend', 'enqueued' ) ) {
        wp_enqueue_script( 'elementor-frontend' );
    }

    if ( method_exists( $frontend, 'enqueue_scripts' ) ) {
        $frontend->enqueue_scripts();
        return;
    }

    if ( method_exists( $frontend, 'print_config' ) ) {
        $frontend->print_config( 'elementor-frontend' );
    }
}
