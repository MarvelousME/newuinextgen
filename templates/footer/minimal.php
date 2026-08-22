<?php
/**
 * Minimal footer for dashboards + marketing when footer_style=minimal.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<footer class="ngt-footer ngt-footer--minimal">
  <div class="ngt-container">
    <ul class="ngt-footer__links bi-footer-contact bi-footer-contact--minimal" data-testid="bi-footer-contact">
      <li class="bi-footer-contact__item">
        <a data-testid="bi-footer-phone" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', bi_get_phone() ) ); ?>"><?php echo esc_html( bi_get_phone() ); ?></a>
      </li>
      <li class="bi-footer-contact__item">
        <a data-testid="bi-footer-email" href="mailto:<?php echo esc_attr( bi_get_support_email() ); ?>"><?php echo esc_html( bi_get_support_email() ); ?></a>
      </li>
      <li class="bi-footer-contact__item" data-testid="bi-footer-service-area">
        <span><?php echo esc_html( bi_get_service_area() ); ?></span>
      </li>
    </ul>
    <div class="ngt-footer__bottom">
      <p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>.</p>
      <?php bi_render_footer_legal( true ); ?>
    </div>
  </div>
</footer>
