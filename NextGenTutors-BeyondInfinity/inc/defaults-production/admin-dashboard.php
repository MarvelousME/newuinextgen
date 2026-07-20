<?php
/** Default theme content — used when no page builder content is present. */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero( __( 'Mission Control', 'beyondinfinity' ), __( 'Monitor the NextGen Tutors platform.', 'beyondinfinity' ) );
?>

<section class="ngt-section">
  <div class="ngt-container" style="max-width:1000px">
    <?php if ( function_exists( 'bi_nbi_bento_shell' ) ) : ?>
      <?php bi_nbi_bento_shell( 'admin' ); ?>
    <?php endif; ?>
    <div class="bi-control-plane-bar ngt-animate">
      <div>
        <h2><?php esc_html_e( 'NextGen Control Plane', 'beyondinfinity' ); ?></h2>
        <p style="margin:4px 0 0;opacity:.85;font-size:13px"><?php esc_html_e( 'Mission dashboard — workflows, bookings, CRM and platform health.', 'beyondinfinity' ); ?></p>
      </div>
      <span class="bi-brand-chip"><?php echo esc_html( bi_ngc_version() ); ?></span>
    </div>
    <?php
    bi_dashboard_panel( '[ngc_admin_dashboard]', [
      __( 'Platform health', 'beyondinfinity' ),
      __( 'Registrations', 'beyondinfinity' ),
      __( 'Tutor applications', 'beyondinfinity' ),
      __( 'Student requests', 'beyondinfinity' ),
      __( 'Bookings', 'beyondinfinity' ),
      __( 'CRM activity', 'beyondinfinity' ),
      __( 'Workflow runs', 'beyondinfinity' ),
      __( 'AI insights', 'beyondinfinity' ),
      __( 'Audit logs', 'beyondinfinity' ),
      __( 'Demo readiness', 'beyondinfinity' ),
    ] );
    ?>
    <div class="ngt-card ngt-animate bi-center" style="padding:28px;background:linear-gradient(135deg,var(--ngt-primary),var(--ngt-secondary));color:#fff">
      <p style="margin:0 0 16px;color:#fff"><?php esc_html_e( 'Run verification, review alerts, and keep the platform ready for parents, students, tutors, and client demos.', 'beyondinfinity' ); ?></p>
      <?php if ( current_user_can( 'manage_options' ) ) : ?>
        <a href="<?php echo esc_url( home_url( '/wordpress-setup' ) ); ?>" class="ngt-btn ngt-btn--white"><?php esc_html_e( 'WordPress Setup Guide', 'beyondinfinity' ); ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>
