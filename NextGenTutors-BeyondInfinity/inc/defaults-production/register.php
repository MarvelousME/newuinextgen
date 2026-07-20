<?php
/** Default theme content — used when no page builder content is present. */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero( __( 'Register with NextGen Tutors', 'beyondinfinity' ), __( 'Choose the registration path that matches you.', 'beyondinfinity' ) );
?>

<section class="ngt-section">
  <div class="ngt-container" style="max-width:1000px">
    <div class="bi-grid-2">
      <div class="ngt-card ngt-animate" style="padding:32px">
        <h2 style="margin-bottom:12px"><?php esc_html_e( 'Parent Registering a Child', 'beyondinfinity' ); ?></h2>
        <p style="margin-bottom:24px"><?php esc_html_e( 'For parents or guardians registering a learner under 18.', 'beyondinfinity' ); ?></p>
        <?php bi_render_shortcode( '[ngc_parent_register_child_form]' ); ?>
        <?php bi_safety_notice( 'parent' ); ?>
      </div>
      <div class="ngt-card ngt-animate ngt-animate--delay-2" style="padding:32px">
        <h2 style="margin-bottom:12px"><?php esc_html_e( 'Student 18+', 'beyondinfinity' ); ?></h2>
        <p style="margin-bottom:24px"><?php esc_html_e( 'For students aged 18 or older registering themselves.', 'beyondinfinity' ); ?></p>
        <?php bi_render_shortcode( '[ngc_student_register_form]' ); ?>
      </div>
    </div>
    <div class="ngt-card bi-surface-card ngt-animate">
      <h3 style="margin-bottom:8px"><?php esc_html_e( 'Account Activation', 'beyondinfinity' ); ?></h3>
      <p style="margin:0"><?php esc_html_e( 'After registration, you may be asked to verify your email address before accessing your dashboard.', 'beyondinfinity' ); ?></p>
    </div>
  </div>
</section>
