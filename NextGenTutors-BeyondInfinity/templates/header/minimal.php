<?php
/**
 * Minimal header for dashboards and focused flows.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<header class="ngt-nav ngt-nav--minimal" role="banner">
  <div class="ngt-container">
    <div class="ngt-nav__inner ngt-nav__inner--minimal">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ngt-nav__logo">
        <?php if ( has_custom_logo() ) :
          $logo_id  = get_theme_mod( 'custom_logo' );
          $logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
          echo '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" width="40" height="40" style="border-radius:8px">';
        else : ?>
          <div class="bi-logo-mark" aria-hidden="true">NG</div>
        <?php endif; ?>
        <div class="ngt-nav__logo-text">NextGen<span>Tutors</span></div>
      </a>
      <div class="ngt-nav__cta">
        <?php if ( function_exists( 'bi_scheme_toggle_button' ) ) { bi_scheme_toggle_button(); } ?>
        <a href="<?php echo esc_url( home_url( '/support' ) ); ?>" class="ngt-btn ngt-btn--outline ngt-btn--sm"><?php esc_html_e( 'Help', 'beyondinfinity' ); ?></a>
      </div>
    </div>
  </div>
</header>
