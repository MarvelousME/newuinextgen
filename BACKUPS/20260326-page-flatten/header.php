<?php
/**
 * BeyondInfinity - header router
 *
 * @package BeyondInfinity
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="bi-skip-link" href="#primary"><?php esc_html_e( 'Skip to content', 'beyondinfinity' ); ?></a>

<?php
$bi_elementor_header = false;
if ( ! function_exists( 'bi_is_elementor_canvas_template' ) || ! bi_is_elementor_canvas_template() ) {
    if ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'header' ) ) {
        $bi_elementor_header = true;
    }
}

if ( ! $bi_elementor_header ) {
    $header_style = function_exists( 'bi_get_header_style' ) ? bi_get_header_style() : 'transparent';
    get_template_part( 'templates/header/' . $header_style );
}
