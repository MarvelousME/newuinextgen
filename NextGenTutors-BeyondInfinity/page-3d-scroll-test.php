<?php
/**
 * Template Name: 3D Scroll Test
 * Slug: 3d-scroll-test
 *
 * Demo page for NextGen 3D Scroll Manager presets + legacy animations.
 * Content is owned by the plugin shortcode when available.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="primary" class="ngt-3d-demo-page" data-ngt-3d-demo="1">
<?php
if ( shortcode_exists( 'ngt_3d_scroll_test' ) ) {
	echo do_shortcode( '[ngt_3d_scroll_test]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} elseif ( class_exists( 'NGT3D_Demo_Page' ) ) {
	NGT3D_Demo_Page::render();
} else {
	echo '<div class="ngt-container" style="padding:4rem 1.5rem"><p>';
	esc_html_e( 'Activate the NextGen 3D Scroll Manager plugin to load this demo.', 'beyondinfinity' );
	echo '</p></div>';
}
?>
</main>
<?php
get_footer();
