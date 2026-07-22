<?php
/**
 * Default site header navigation.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<nav class="ngt-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary Navigation', 'beyondinfinity' ); ?>">
  <div class="ngt-container">
    <div class="ngt-nav__inner">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ngt-nav__logo">
        <?php if ( has_custom_logo() ) :
          $logo_id  = get_theme_mod( 'custom_logo' );
          $logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
          echo '<img class="ngt-nav__logo-img logo__img" src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" />';
        elseif ( function_exists( 'bi_ngt_default_logo_url' ) && file_exists( BI_DIR . '/assets/ngt/img/logo.png' ) ) : ?>
          <img class="ngt-nav__logo-img logo__img" src="<?php echo esc_url( bi_ngt_default_logo_url() ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
        <?php else : ?>
          <div class="bi-logo-mark" aria-hidden="true">NG</div>
        <?php endif; ?>
        <div class="ngt-nav__logo-text">NextGen<span>Tutors</span></div>
      </a>

      <?php bi_render_primary_nav_menu(); ?>

      <div class="ngt-nav__cta">
        <?php if ( function_exists( 'bi_scheme_toggle_button' ) ) { bi_scheme_toggle_button(); } ?>
        <a href="<?php echo esc_url( home_url( '/become-a-tutor' ) ); ?>" class="ngt-btn ngt-btn--outline" id="ngt-become-btn"><?php esc_html_e( 'Become a Tutor', 'beyondinfinity' ); ?></a>
        <a href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'Find a Tutor', 'beyondinfinity' ); ?></a>
      </div>

      <button class="ngt-nav__toggle" aria-label="<?php esc_attr_e( 'Toggle menu', 'beyondinfinity' ); ?>" aria-expanded="false">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
          <line x1="3" y1="6" x2="21" y2="6"/>
          <line x1="3" y1="12" x2="21" y2="12"/>
          <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
    </div>
  </div>
</nav>
