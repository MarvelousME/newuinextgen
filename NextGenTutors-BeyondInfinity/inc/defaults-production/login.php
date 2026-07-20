<?php
/** Default theme content — used when no page builder content is present. */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero( __( 'Login to Your Dashboard', 'beyondinfinity' ), __( 'Access your NextGen Tutors account.', 'beyondinfinity' ) );
?>

<section class="ngt-section">
  <div class="ngt-container bi-narrow">
    <?php bi_shortcode_block( '[ngc_login_form]', __( 'Sign In', 'beyondinfinity' ) ); ?>
    <div class="ngt-card ngt-animate" style="margin-top:32px;padding:32px">
      <h2 style="margin-bottom:16px"><?php esc_html_e( 'Forgot Password', 'beyondinfinity' ); ?></h2>
      <?php bi_render_shortcode( '[ngc_forgot_password_form]' ); ?>
    </div>
  </div>
</section>
