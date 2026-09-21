<?php
/**
 * Front page template (site homepage).
 * Elementor & WPBakery compatible.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once get_stylesheet_directory() . '/inc/page-wrapper.php';

bi_render_page_template( bi_home_default_path() );
