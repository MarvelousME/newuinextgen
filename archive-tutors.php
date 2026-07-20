<?php
/**
 * Tutor archive — AJAX marketplace with filters.
 *
 * @package BeyondInfinity
 */

get_header();

if ( function_exists( 'bi_page_open' ) ) {
	bi_page_open( 'tutor-marketplace' );
}
?>
<section class="ng-page-section ngt-section ng-reveal">
  <div class="ng-container">
    <header class="ng-page-heading">
      <p class="ng-page-heading__eyebrow"><?php esc_html_e( 'Tutor marketplace', 'beyondinfinity' ); ?></p>
      <h1 class="ng-page-heading__title"><?php esc_html_e( 'Find Your Tutor', 'beyondinfinity' ); ?></h1>
      <p class="ng-page-heading__subtitle"><?php esc_html_e( 'Filter by subject, grade, province, format and price — results update instantly.', 'beyondinfinity' ); ?></p>
    </header>
    <?php
    if ( shortcode_exists( 'ngc_tutor_marketplace' ) ) {
		echo do_shortcode( '[ngc_tutor_marketplace per_page="12"]' );
    } elseif ( function_exists( 'bi_render_tutor_directory' ) ) {
		bi_render_tutor_directory( 12 );
    } else {
		echo '<p>' . esc_html__( 'Activate NextGen Companion to browse the tutor marketplace.', 'beyondinfinity' ) . '</p>';
    }
    ?>
  </div>
</section>
<?php
if ( function_exists( 'bi_page_close' ) ) {
	bi_page_close( 'tutor-marketplace' );
}
get_footer();
