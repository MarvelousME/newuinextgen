<?php
/** Default theme content — used when no page builder content is present. */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero(
  __( 'Tutor Dashboard', 'beyondinfinity' ),
  __( 'Manage your tutoring work in one place.', 'beyondinfinity' )
);
?>

<section class="ngt-section">
  <div class="ngt-container bi-narrow">
    <?php if ( function_exists( 'bi_nbi_bento_shell' ) ) : ?>
      <?php bi_nbi_bento_shell( 'tutor' ); ?>
    <?php endif; ?>
    <?php bi_tutor_dashboard_intro(); ?>
    <?php if ( function_exists( 'ng_ui_component' ) && is_user_logged_in() ) : ?>
      <div class="ngt-animate bi-mb-lg">
        <?php ng_ui_component( 'booking-list', [ 'limit' => 5 ] ); ?>
      </div>
    <?php endif; ?>
    <?php
    bi_dashboard_panel( '[ngc_tutor_dashboard]', [
      __( 'Assigned students', 'beyondinfinity' ),
      __( 'Upcoming bookings', 'beyondinfinity' ),
      __( 'Subjects taught', 'beyondinfinity' ),
      __( 'Session notes', 'beyondinfinity' ),
      __( 'Availability', 'beyondinfinity' ),
      __( 'Reviews', 'beyondinfinity' ),
      __( 'Earnings summary', 'beyondinfinity' ),
      __( 'Payout status', 'beyondinfinity' ),
    ] );
    ?>
    <div class="bi-grid-2 bi-mt-md">
      <div class="ngt-card ngt-animate bi-pad-md">
        <h3 class="bi-mb-sm"><?php esc_html_e( 'Quick Actions', 'beyondinfinity' ); ?></h3>
        <div class="bi-stack-col">
          <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="ngt-btn ngt-btn--primary ngt-btn--block"><?php esc_html_e( 'Add Session Notes', 'beyondinfinity' ); ?></a>
          <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="ngt-btn ngt-btn--outline ngt-btn--block"><?php esc_html_e( 'Request Availability Change', 'beyondinfinity' ); ?></a>
        </div>
      </div>
      <div class="ngt-card ngt-animate bi-pad-md">
        <h3 class="bi-mb-sm"><?php esc_html_e( 'Profile', 'beyondinfinity' ); ?></h3>
        <p class="bi-mb-md bi-text-muted"><?php esc_html_e( 'Keep your profile updated so we can match you with the right students.', 'beyondinfinity' ); ?></p>
        <a href="<?php echo esc_url( home_url( '/become-a-tutor' ) ); ?>" class="ngt-btn ngt-btn--outline"><?php esc_html_e( 'Update Profile', 'beyondinfinity' ); ?></a>
      </div>
    </div>
  </div>
</section>
