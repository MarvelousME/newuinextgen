<?php
/** Default theme content — merged from pages-to-review/dashboard.html */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero( __( 'Parent Dashboard', 'beyondinfinity' ), __( 'Welcome to your family learning hub.', 'beyondinfinity' ) );
?>

<section class="ngt-section">
  <div class="ngt-container bi-narrow">
    <?php if ( function_exists( 'bi_nbi_bento_shell' ) ) : ?>
      <?php bi_nbi_bento_shell( 'parent' ); ?>
    <?php endif; ?>
    <?php bi_learner_dashboard_intro( 'parent' ); ?>
    <?php if ( function_exists( 'ng_ui_component' ) && is_user_logged_in() ) : ?>
      <div class="ngt-animate" style="margin-bottom:24px">
        <?php ng_ui_component( 'booking-list', [ 'limit' => 5 ] ); ?>
      </div>
    <?php endif; ?>
    <?php
    bi_dashboard_panel( '[ngc_parent_dashboard]', [
      __( 'Child profiles', 'beyondinfinity' ),
      __( 'Assigned tutors', 'beyondinfinity' ),
      __( 'Upcoming lessons', 'beyondinfinity' ),
      __( 'Payment status', 'beyondinfinity' ),
      __( 'Lesson history', 'beyondinfinity' ),
      __( 'Progress notes', 'beyondinfinity' ),
      __( 'Billing & invoices', 'beyondinfinity' ),
      __( 'Support messages', 'beyondinfinity' ),
    ] );
    ?>
    <div class="ngt-card bi-reassurance ngt-animate bi-center">
      <p style="margin:0 0 20px"><?php esc_html_e( 'Book another lesson or contact support if your learner needs urgent assistance.', 'beyondinfinity' ); ?></p>
      <div class="bi-hero__actions" style="justify-content:center;margin:0">
        <a href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'Book a Lesson', 'beyondinfinity' ); ?></a>
        <?php if ( is_user_logged_in() && shortcode_exists( 'ngc_parent_checkout' ) ) : ?>
        <a href="<?php echo esc_url( home_url( '/parent-checkout/' ) ); ?>" class="ngt-btn ngt-btn--secondary"><?php esc_html_e( 'Pay for Lesson', 'beyondinfinity' ); ?></a>
        <?php endif; ?>
        <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="ngt-btn ngt-btn--outline"><?php esc_html_e( 'Contact Support', 'beyondinfinity' ); ?></a>
      </div>
    </div>
  </div>
</section>
