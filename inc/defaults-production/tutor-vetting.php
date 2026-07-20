<?php
/** Default theme content — used when no page builder content is present. */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero(
  __( 'How We Vet Tutors', 'beyondinfinity' ),
  __( 'Our tutor verification pipeline is data-tracked. Here is exactly how we find and verify South Africa\'s best tutors.', 'beyondinfinity' )
);
$marketing_kpis = bi_real_marketing_kpis();
$policy_sla     = bi_policy_sla_labels();
?>

<section class="ngt-section">
  <div class="ngt-container bi-narrow">
    <div class="ngt-card ngt-animate" style="padding:32px;margin-bottom:24px">
      <p><?php esc_html_e( 'When you choose a NextGen Tutor, you are choosing someone who has been thoroughly screened, tested and trained.', 'beyondinfinity' ); ?></p>
      <p style="margin-bottom:0"><?php esc_html_e( 'Trust is earned, not given — that is why we built a comprehensive verification system and put the results on every profile.', 'beyondinfinity' ); ?></p>
    </div>
    <?php
    bi_info_card( __( 'The NextGen Standard', 'beyondinfinity' ), [
      __( 'SACE-registered certified teachers and verified specialists', 'beyondinfinity' ),
      __( 'ID and criminal background checks via accredited SA agencies', 'beyondinfinity' ),
      __( 'Subject competency testing on CAPS, IEB and Cambridge', 'beyondinfinity' ),
      __( 'Live teaching trial before a single learner is matched', 'beyondinfinity' ),
    ] );
    ?>
  </div>
</section>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container bi-narrow">
    <div class="ngt-section__header ngt-animate"><h2><?php esc_html_e( 'The 5-Step Verification', 'beyondinfinity' ); ?></h2></div>
    <?php
    bi_vsteps( [
      [
        'title' => __( 'Application Review', 'beyondinfinity' ),
        'text'  => __( 'Detailed application covering educational background, subject specialisation, grade-level preferences, teaching philosophy and availability across all nine provinces.', 'beyondinfinity' ),
      ],
      [
        'title' => __( 'Documentation Verification', 'beyondinfinity' ),
        'text'  => __( 'SA ID or passport, university degrees and transcripts, SACE registration for certified teachers, and confirmed right to work in South Africa.', 'beyondinfinity' ),
      ],
      [
        'title' => __( 'Subject Competency Assessment', 'beyondinfinity' ),
        'text'  => __( 'Written subject tests on SA curriculum topics, problem-solving, deep CAPS / IEB / Cambridge knowledge, and modern pedagogical skills.', 'beyondinfinity' ),
      ],
      [
        'title' => __( 'Teaching Trial', 'beyondinfinity' ),
        'text'  => __( 'Mock sessions with our training team, student simulations, a communication assessment and an online-teaching technology check.', 'beyondinfinity' ),
      ],
      [
        'title' => __( 'Reference & Background Checks', 'beyondinfinity' ),
        'text'  => __( 'Professional and teaching references (with consent) plus a full criminal background-check clearance.', 'beyondinfinity' ),
      ],
    ] );
    ?>
  </div>
</section>

<section class="ngt-section">
  <div class="ngt-container bi-narrow">
    <div class="ngt-section__header ngt-animate">
      <h2><?php esc_html_e( 'Verification Badges on Every Profile', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'So parents know exactly what has been confirmed — at a glance.', 'beyondinfinity' ); ?></p>
    </div>
    <?php
    bi_badge_table( [
      [ 'badge' => __( 'ID Verified', 'beyondinfinity' ), 'desc' => __( 'South African identity confirmed with Home Affairs.', 'beyondinfinity' ) ],
      [ 'badge' => __( 'Degree Certified', 'beyondinfinity' ), 'desc' => __( 'University qualifications verified with the issuing institution.', 'beyondinfinity' ) ],
      [ 'badge' => __( 'SACE Registered', 'beyondinfinity' ), 'desc' => __( 'Certified teacher with the SA Council for Educators.', 'beyondinfinity' ) ],
      [ 'badge' => __( 'Background Cleared', 'beyondinfinity' ), 'desc' => __( 'Criminal background check passed via an accredited agency.', 'beyondinfinity' ) ],
      [ 'badge' => __( 'Trial Passed', 'beyondinfinity' ), 'desc' => __( 'Completed the live teaching assessment successfully.', 'beyondinfinity' ) ],
      [ 'badge' => __( 'Curriculum Trained', 'beyondinfinity' ), 'desc' => __( 'Completed NextGen\'s SA curriculum training programme.', 'beyondinfinity' ) ],
    ] );
    ?>
  </div>
</section>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate"><h2><?php esc_html_e( 'Quality, Measured', 'beyondinfinity' ); ?></h2></div>
    <?php
    bi_metric_grid( [
      [ 'value' => $marketing_kpis['acceptance_rate'], 'label' => __( 'Acceptance Rate', 'beyondinfinity' ) ],
      [ 'value' => $marketing_kpis['average_rating'], 'label' => __( 'Average Tutor Rating', 'beyondinfinity' ) ],
      [ 'value' => $marketing_kpis['credential_accuracy'], 'label' => __( 'Credential Accuracy', 'beyondinfinity' ) ],
      [ 'value' => $policy_sla['background_refresh'], 'label' => __( 'Background Refresh', 'beyondinfinity' ) ],
    ] );
    ?>
  </div>
</section>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate"><h2><?php esc_html_e( 'Built For Our Curricula', 'beyondinfinity' ); ?></h2></div>
    <?php
    bi_value_cards( [
      [ 'icon' => '🇿🇦', 'title' => __( 'The CAPS Curriculum', 'beyondinfinity' ), 'text' => __( 'National Curriculum & Assessment Policy Statement, grade-specific milestones and full NSC examination preparation.', 'beyondinfinity' ) ],
      [ 'icon' => '📘', 'title' => __( 'IEB Schools', 'beyondinfinity' ), 'text' => __( 'Independent Examinations Board standards with a focus on critical thinking, analysis and university entrance.', 'beyondinfinity' ) ],
      [ 'icon' => '🌍', 'title' => __( 'Cambridge International', 'beyondinfinity' ), 'text' => __( 'AS & A-Level preparation aligned to international benchmarks and global progression pathways.', 'beyondinfinity' ) ],
    ] );
    ?>
  </div>
</section>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container bi-narrow">
    <div class="bi-grid-2">
      <?php
      bi_info_card( __( 'How to Verify Your Tutor', 'beyondinfinity' ), [
        __( 'Review all badges and credentials on the tutor profile', 'beyondinfinity' ),
        __( 'Request to see original certificates during your first session', 'beyondinfinity' ),
        __( 'Use the first lesson to assess fit before committing', 'beyondinfinity' ),
        __( 'Ask directly about their SA teaching experience', 'beyondinfinity' ),
      ] );
      bi_info_card( __( 'Red Flags to Watch For', 'beyondinfinity' ), [
        __( 'Reluctance to share credentials', 'beyondinfinity' ),
        __( 'Vague teaching-experience claims', 'beyondinfinity' ),
        __( 'Pressure to pay outside the platform', 'beyondinfinity' ),
        __( 'Unverified badges or unclear sources', 'beyondinfinity' ),
      ], 'alert' );
      ?>
    </div>
    <div class="bi-center" style="margin-top:32px;display:flex;gap:12px;flex-wrap:wrap;justify-content:center">
      <a href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>" class="ngt-btn ngt-btn--primary ngt-btn--lg"><?php esc_html_e( 'Find a Tutor', 'beyondinfinity' ); ?></a>
      <a href="<?php echo esc_url( home_url( '/become-a-tutor' ) ); ?>" class="ngt-btn ngt-btn--outline ngt-btn--lg"><?php esc_html_e( 'Become a Tutor', 'beyondinfinity' ); ?></a>
    </div>
  </div>
</section>
