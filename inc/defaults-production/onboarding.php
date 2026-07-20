<?php
/** Default — Admin onboarding overview (pages-to-review/onboarding.html) */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero( __( 'Onboarding Management', 'beyondinfinity' ), __( 'Configure steps, track progress and certify tutors and staff.', 'beyondinfinity' ) );
$marketing_kpis = bi_real_marketing_kpis();
?>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="bi-dash-kpi-grid">
      <?php foreach (
          [
              [ $marketing_kpis['onboarding_total'], __( 'Total enrolled', 'beyondinfinity' ) ],
              [ $marketing_kpis['onboarding_completion'], __( 'Avg completion', 'beyondinfinity' ) ],
              [ $marketing_kpis['onboarding_overdue'], __( 'Overdue (>7 days)', 'beyondinfinity' ) ],
              [ $marketing_kpis['onboarding_certified'], __( 'Fully certified', 'beyondinfinity' ) ],
          ] as $kpi
      ) : ?>
        <div class="bi-dash-kpi ngt-card ngt-animate">
          <div class="bi-dash-kpi__value"><?php echo esc_html( $kpi[0] ); ?></div>
          <div class="bi-dash-kpi__label"><?php echo esc_html( $kpi[1] ); ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="ngt-card ngt-animate" style="padding:28px;margin-bottom:24px">
      <h2><?php esc_html_e( 'Standard Tutor Onboarding Steps', 'beyondinfinity' ); ?></h2>
      <?php bi_bullets( [
          __( 'Profile setup and photo upload', 'beyondinfinity' ),
          __( 'SA ID document upload', 'beyondinfinity' ),
          __( 'Qualifications and SACE registration', 'beyondinfinity' ),
          __( 'Police clearance certificate', 'beyondinfinity' ),
          __( 'Subject competency assessment', 'beyondinfinity' ),
          __( 'Teaching trial session', 'beyondinfinity' ),
      ] ); ?>
      <p style="margin-top:16px;font-size:.875rem;color:var(--ngt-text-3)"><?php esc_html_e( 'Live onboarding tables and notifications load via platform REST when the companion plugin is active.', 'beyondinfinity' ); ?></p>
    </div>

    <?php
    bi_dashboard_panel( '[ngc_admin_dashboard]', [
        __( 'Team progress table', 'beyondinfinity' ),
        __( 'Step configuration', 'beyondinfinity' ),
        __( 'Department completion', 'beyondinfinity' ),
        __( 'Notification audit log', 'beyondinfinity' ),
    ] );
    ?>
  </div>
</section>
