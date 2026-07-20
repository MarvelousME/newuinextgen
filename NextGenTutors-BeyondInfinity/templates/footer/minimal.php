<?php
/**
 * Minimal footer for dashboards.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<footer class="ngt-footer ngt-footer--minimal">
  <div class="ngt-container">
    <div class="ngt-footer__bottom">
      <p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>.</p>
      <div class="bi-footer-legal">
        <a href="<?php echo esc_url( home_url( '/support' ) ); ?>"><?php esc_html_e( 'Support', 'beyondinfinity' ); ?></a>
        <a href="<?php echo esc_url( home_url( '/privacy-policy' ) ); ?>"><?php esc_html_e( 'Privacy', 'beyondinfinity' ); ?></a>
      </div>
    </div>
  </div>
</footer>
