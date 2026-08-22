<?php
/**
 * Default site footer — reference chrome columns restyled for BeyondInfinity.
 *
 * Source: nextgen-tutors-theme assets/js/chrome.js buildFooter()
 * Contact values use live theme helpers (not demo placeholders).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<footer class="ngt-footer">
  <div class="ngt-container">
    <div class="ngt-footer__grid">
      <div class="ngt-footer__brand">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="bi-footer-logo">
          <?php if ( function_exists( 'bi_ngt_default_logo_url' ) && file_exists( BI_DIR . '/assets/ngt/img/logo.png' ) ) : ?>
            <img class="bi-footer-logo__img" src="<?php echo esc_url( bi_ngt_default_logo_url() ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="48" height="48" />
          <?php else : ?>
            <div class="bi-logo-mark" aria-hidden="true">NG</div>
          <?php endif; ?>
          <span class="bi-footer-logo__text">NextGen<span>Tutors</span></span>
        </a>
        <p><?php esc_html_e( "South Africa's premier online tutoring platform, connecting Grade 1–12 and varsity students with verified, SACE-registered tutors across all nine provinces.", 'beyondinfinity' ); ?></p>
        <p class="bi-footer-chip" aria-hidden="true"><?php esc_html_e( 'Proudly South African', 'beyondinfinity' ); ?></p>
      </div>

      <div>
        <h4 class="ngt-footer__heading"><?php esc_html_e( 'Explore', 'beyondinfinity' ); ?></h4>
        <?php bi_render_footer_link_list( 'explore' ); ?>
      </div>

      <div>
        <h4 class="ngt-footer__heading"><?php esc_html_e( 'Company', 'beyondinfinity' ); ?></h4>
        <?php bi_render_footer_link_list( 'company' ); ?>
      </div>

      <div>
        <h4 class="ngt-footer__heading"><?php esc_html_e( 'Get In Touch', 'beyondinfinity' ); ?></h4>
        <ul class="ngt-footer__links bi-footer-contact" data-testid="bi-footer-contact">
          <li class="bi-footer-contact__item">
            <span class="bi-footer-contact__icon" aria-hidden="true"><?php echo bi_ui_icon( 'phone', 18 ); // phpcs:ignore ?></span>
            <a data-testid="bi-footer-phone" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', bi_get_phone() ) ); ?>"><?php echo esc_html( bi_get_phone() ); ?></a>
          </li>
          <li class="bi-footer-contact__item">
            <span class="bi-footer-contact__icon" aria-hidden="true"><?php echo bi_ui_icon( 'mail', 18 ); // phpcs:ignore ?></span>
            <a data-testid="bi-footer-email" href="mailto:<?php echo esc_attr( bi_get_support_email() ); ?>"><?php echo esc_html( bi_get_support_email() ); ?></a>
          </li>
          <li class="bi-footer-contact__item" data-testid="bi-footer-service-area">
            <span class="bi-footer-contact__icon" aria-hidden="true"><?php echo bi_ui_icon( 'map-pin', 18 ); // phpcs:ignore ?></span>
            <span><?php echo esc_html( bi_get_service_area() ); ?></span>
          </li>
        </ul>
      </div>
    </div>

    <div class="ngt-footer__bottom">
      <p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'beyondinfinity' ); ?></p>
      <?php bi_render_footer_legal(); ?>
    </div>
  </div>
</footer>
