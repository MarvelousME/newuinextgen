<?php
/** Default theme content — merged from pages-to-review/dashboard.html */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero( __( 'Student Dashboard', 'beyondinfinity' ), __( 'This is your learning space.', 'beyondinfinity' ) );
?>

<section class="ngt-section">
  <div class="ngt-container bi-narrow">
    <?php if ( function_exists( 'bi_nbi_bento_shell' ) ) : ?>
      <?php bi_nbi_bento_shell( 'student' ); ?>
    <?php endif; ?>
    <?php bi_learner_dashboard_intro( 'student' ); ?>
    <?php if ( function_exists( 'ng_ui_component' ) && is_user_logged_in() ) : ?>
      <div class="ngt-animate" style="margin-bottom:24px">
        <?php if ( ! bi_dashboard_rest_available() ) : ?>
          <?php ng_ui_component( 'booking-list', [ 'limit' => 5 ] ); ?>
        <?php endif; ?>
        <?php ng_ui_component( 'achievement-badge', [ 'limit' => 8 ] ); ?>
      </div>
    <?php endif; ?>
    <?php
    bi_dashboard_panel( '[ngc_student_dashboard]', [
      __( 'Next session', 'beyondinfinity' ),
      __( 'Recent sessions', 'beyondinfinity' ),
      __( 'Subjects', 'beyondinfinity' ),
      __( 'Tutor details', 'beyondinfinity' ),
      __( 'Study goals', 'beyondinfinity' ),
      __( 'Progress', 'beyondinfinity' ),
      __( 'Achievements', 'beyondinfinity' ),
      __( 'Billing & invoices', 'beyondinfinity' ),
      __( 'Refer a friend', 'beyondinfinity' ),
    ] );
    ?>
    <div class="ngt-card ngt-animate bi-center" style="padding:40px;background:linear-gradient(135deg,var(--ngt-primary-light),var(--ngt-secondary-light))">
      <p style="font-size:1.15rem;margin:0;color:var(--ngt-text)"><?php esc_html_e( 'You are not behind. You are building your way forward with the right support.', 'beyondinfinity' ); ?></p>
      <div class="bi-hero__actions" style="justify-content:center;margin-top:20px">
        <a href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'Book a Session', 'beyondinfinity' ); ?></a>
        <a href="<?php echo esc_url( home_url( '/support' ) ); ?>" class="ngt-btn ngt-btn--outline"><?php esc_html_e( 'Get Help', 'beyondinfinity' ); ?></a>
      </div>
    </div>
  </div>
</section>
