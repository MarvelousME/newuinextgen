<?php
/**
 * Template Name: Full Width (Elementor / WPBakery)
 * Elementor & WPBakery compatible — no sidebar, full-width content area.
 */
require_once get_stylesheet_directory() . '/inc/page-wrapper.php';
bi_render_page_template( function () {
    while ( have_posts() ) {
        the_post();
        echo '<div class="bi-full-width-content">';
        the_content();
        echo '</div>';
    }
} );
