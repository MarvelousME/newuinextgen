<?php
/**
 * Default site footer.
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
          <div class="bi-logo-mark" aria-hidden="true">NG</div>
          <span class="bi-footer-logo__text">NextGen<span>Tutors</span></span>
        </a>
        <p><?php esc_html_e( 'Accessible one-on-one academic support for learners across all 9 provinces — Grade 1 to tertiary, online, in-person, and hybrid.', 'beyondinfinity' ); ?></p>
      </div>

      <div>
        <h4 class="ngt-footer__heading"><?php esc_html_e( 'Quick Links', 'beyondinfinity' ); ?></h4>
        <ul class="ngt-footer__links">
          <?php
          $links = [
            'Find a Tutor'   => '/find-a-tutor',
            'Become a Tutor' => '/become-a-tutor',
            'Pricing'        => '/pricing',
            'Guarantee'      => '/guarantee',
            'Blog'           => '/blog',
            'About'          => '/about',
            'Tutor Vetting'  => '/tutor-vetting',
            'Safety Guide'   => '/safety-guide',
            'Support'        => '/support',
            'Contact'        => '/contact',
          ];
          foreach ( $links as $label => $url ) {
            echo '<li><a href="' . esc_url( home_url( $url ) ) . '">' . esc_html( $label ) . '</a></li>';
          }
          ?>
        </ul>
      </div>

      <div>
        <h4 class="ngt-footer__heading"><?php esc_html_e( 'For Families & Tutors', 'beyondinfinity' ); ?></h4>
        <ul class="ngt-footer__links">
          <li><a href="<?php echo esc_url( home_url( '/register' ) ); ?>"><?php esc_html_e( 'Register', 'beyondinfinity' ); ?></a></li>
          <li><a href="<?php echo esc_url( home_url( '/login' ) ); ?>"><?php esc_html_e( 'Login', 'beyondinfinity' ); ?></a></li>
          <li><a href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>"><?php esc_html_e( 'Request a Tutor', 'beyondinfinity' ); ?></a></li>
          <li><a href="<?php echo esc_url( home_url( '/become-a-tutor' ) ); ?>"><?php esc_html_e( 'Apply as Tutor', 'beyondinfinity' ); ?></a></li>
        </ul>
      </div>

      <div>
        <h4 class="ngt-footer__heading"><?php esc_html_e( 'Contact', 'beyondinfinity' ); ?></h4>
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
      <p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'beyondinfinity' ); ?></p>
      <div class="bi-footer-legal">
        <a href="<?php echo esc_url( home_url( '/privacy-policy' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'beyondinfinity' ); ?></a>
        <a href="<?php echo esc_url( home_url( '/terms' ) ); ?>"><?php esc_html_e( 'Terms of Service', 'beyondinfinity' ); ?></a>
        <a href="<?php echo esc_url( home_url( '/child-safety' ) ); ?>"><?php esc_html_e( 'Child Safety', 'beyondinfinity' ); ?></a>
      </div>
    </div>
  </div>
</footer>
