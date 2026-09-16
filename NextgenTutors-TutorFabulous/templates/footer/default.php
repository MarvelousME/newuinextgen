<?php
/**
 * Default site footer.
 *
 * Quick Links / Families / Legal columns render from WP menus
 * (Appearance → Menus → locations footer-1, footer-2, footer-legal).
 *
 * @package TutorFabulous
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
        <?php
        bi_render_footer_nav_menu(
          'footer-1',
          [
            __( 'Find a Tutor', 'beyondinfinity' )   => '/find-a-tutor',
            __( 'Become a Tutor', 'beyondinfinity' ) => '/become-a-tutor',
            __( 'Pricing', 'beyondinfinity' )        => '/pricing',
            __( 'Guarantee', 'beyondinfinity' )      => '/guarantee',
            __( 'Blog', 'beyondinfinity' )           => '/blog',
            __( 'About', 'beyondinfinity' )          => '/about',
            __( 'Tutor Vetting', 'beyondinfinity' )  => '/tutor-vetting',
            __( 'Safety Guide', 'beyondinfinity' )   => '/safety-guide',
            __( 'Support', 'beyondinfinity' )        => '/support',
            __( 'Contact', 'beyondinfinity' )        => '/contact',
          ]
        );
        ?>
      </div>

      <div>
        <h4 class="ngt-footer__heading"><?php esc_html_e( 'For Families & Tutors', 'beyondinfinity' ); ?></h4>
        <?php
        bi_render_footer_nav_menu(
          'footer-2',
          [
            __( 'Register', 'beyondinfinity' )       => '/register',
            __( 'Login', 'beyondinfinity' )          => '/login',
            __( 'Request a Tutor', 'beyondinfinity' ) => '/find-a-tutor',
            __( 'Apply as Tutor', 'beyondinfinity' )  => '/become-a-tutor',
          ]
        );
        ?>
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
        <?php bi_render_footer_legal_menu(); ?>
      </div>
    </div>
  </div>
</footer>
