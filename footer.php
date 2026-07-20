<?php
/**
 * BeyondInfinity - footer router
 *
 * @package BeyondInfinity
 */

$bi_elementor_footer = false;
if ( ! function_exists( 'bi_is_elementor_canvas_template' ) || ! bi_is_elementor_canvas_template() ) {
    if ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'footer' ) ) {
        $bi_elementor_footer = true;
    }
}

if ( ! $bi_elementor_footer ) {
    $footer_style = function_exists( 'bi_get_footer_style' ) ? bi_get_footer_style() : 'default';
    get_template_part( 'templates/footer/' . $footer_style );
}

bi_whatsapp_fab();
bi_sticky_mobile_cta();
wp_footer();
?>
</body>
</html>
