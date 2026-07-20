<?php
/**
 * Dual-mode page template wrapper (theme default OR page builder content).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Path to bundled default homepage content.
 */
function bi_home_default_path() {
    return BI_DIR . '/inc/defaults/home.php';
}

/**
 * Render a page template with builder fallback.
 *
 * @param callable|string $default_callback Callable or path to inc/defaults file.
 */
function bi_render_page_template( $default_callback ) {
    $post_id = bi_get_current_page_id();

    if ( bi_is_elementor_canvas_template( $post_id ) ) {
        bi_ensure_page_in_loop( $post_id );
        while ( have_posts() ) {
            the_post();
            the_content();
        }
        return;
    }

    get_header();

    if ( bi_should_show_theme_fallback( $post_id ) ) {
        echo '<main id="primary" class="site-main bi-theme-main">';
        bi_render_theme_default( $default_callback );
        echo '</main>';
    } elseif ( bi_elementor_theme_location_handled() ) {
        // Elementor Theme Builder rendered the page body.
    } else {
        bi_render_builder_content();
    }

    get_footer();
}

/**
 * Output bundled theme default sections.
 *
 * @param callable|string $default_callback Callable or path to inc/defaults file.
 */
function bi_render_theme_default( $default_callback ) {
	$slug = function_exists( 'bi_page_slug' ) ? bi_page_slug() : get_post_field( 'post_name', get_queried_object_id() );
	if ( function_exists( 'bi_page_open' ) ) {
		bi_page_open( $slug );
	} else {
		echo '<div class="bi-theme-content framer-frame ng-section">';
	}
    if ( is_callable( $default_callback ) ) {
        call_user_func( $default_callback );
    } elseif ( is_string( $default_callback ) && file_exists( $default_callback ) ) {
        include $default_callback;
    }
	if ( function_exists( 'bi_page_close' ) ) {
		bi_page_close( $slug );
	} else {
		echo '</div>';
	}
}

/**
 * Show theme default when builder output would be empty.
 */
function bi_should_show_theme_fallback( $post_id = 0 ) {
    $post_id = $post_id ?: bi_get_current_page_id();

    if ( $post_id && bi_theme_option_is_on( 'force_theme_default', $post_id ) ) {
        return true;
    }

    if ( ! $post_id ) {
        return true;
    }

    if ( bi_is_elementor_built( $post_id ) || bi_is_wpbakery_built( $post_id ) ) {
        return false;
    }

    return ! bi_page_has_editor_content( $post_id );
}

/**
 * Generic page loop for page.php.
 */
function bi_render_generic_page() {
    bi_render_page_template( function () {
        $post_id = get_the_ID();
        while ( have_posts() ) {
            the_post();
			echo '<section class="ng-page-section ngt-section ng-page-section--glass ng-reveal"><div class="ng-container ng-container--boxed">';
            echo '<article ';
            post_class( 'ngt-card bi-generic-page' );
            echo ' style="padding:40px">';
            if ( bi_should_show_page_title( $post_id ) ) {
                echo '<h1 class="entry-title">' . esc_html( get_the_title() ) . '</h1>';
            }
            echo '<div class="entry-content">';
            the_content();
            echo '</div></article></div></section>';
        }
    } );
}
