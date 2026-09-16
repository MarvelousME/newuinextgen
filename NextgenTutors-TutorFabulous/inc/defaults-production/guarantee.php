<?php
/** Default — 1st Lesson Guarantee (pages-to-review/guarantee.html) */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero(
    __( 'Not Completely Satisfied? You Don’t Pay.', 'beyondinfinity' ),
    __( 'Love the lesson — or your first hour is on us. Every first lesson with NextGen Tutors is risk-free.', 'beyondinfinity' ),
    'bi-hero--guarantee'
);
$marketing_kpis = bi_real_marketing_kpis();
$policy_sla     = bi_policy_sla_labels();
?>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="bi-stat-grid" style="margin-bottom:0">
      <?php foreach (
          [
              [ $marketing_kpis['satisfaction'], __( 'Successful match/payment outcome', 'beyondinfinity' ) ],
              [ $policy_sla['claim_window'], __( 'Claim window', 'beyondinfinity' ) ],
              [ $policy_sla['rematch_window'], __( 'New tutor matched', 'beyondinfinity' ) ],
          ] as $s
      ) : ?>
        <div class="bi-stat-card ngt-animate">
          <div class="bi-stat-card__num"><?php echo esc_html( $s[0] ); ?></div>
          <div class="bi-stat-card__label"><?php echo esc_html( $s[1] ); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php
bi_steps(
    [
        [ 'title' => __( 'Book Your First Session', 'beyondinfinity' ), 'text' => __( 'Choose a verified tutor, pick a slot, and pay securely through the platform.', 'beyondinfinity' ) ],
        [ 'title' => __( 'Attend the Full Hour', 'beyondinfinity' ), 'text' => __( 'Experience how the tutor explains, engages and adapts to your learner.', 'beyondinfinity' ) ],
        [ 'title' => __( 'Assess the Fit', 'beyondinfinity' ), 'text' => __( 'Does your child connect? Was the explanation clear? Do you want to continue?', 'beyondinfinity' ) ],
        [ 'title' => __( 'Decide Within 24 Hours', 'beyondinfinity' ), 'text' => __( 'Love it — great. Not quite right? Contact us for a better match or a full refund.', 'beyondinfinity' ) ],
    ],
    __( 'How The Guarantee Works', 'beyondinfinity' ),
    __( 'Simple, honest process', 'beyondinfinity' )
);
?>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate bi-center">
      <h2><?php esc_html_e( 'What Qualifies — and What Doesn’t', 'beyondinfinity' ); ?></h2>
    </div>
    <div class="bi-qual-grid">
      <div class="ngt-card bi-qual-card bi-qual-card--yes ngt-animate">
        <h3><?php esc_html_e( 'Qualifies for replacement or refund', 'beyondinfinity' ); ?></h3>
        <?php bi_bullets( [
            __( 'Teaching style mismatch — your child does not connect with how the tutor communicates', 'beyondinfinity' ),
            __( 'Difficulty level issues — too fast, too slow, or wrong grade pitch', 'beyondinfinity' ),
            __( 'Curriculum confusion — tutor not aligned with your school’s approach', 'beyondinfinity' ),
            __( 'Personality fit — the chemistry is not there', 'beyondinfinity' ),
            __( 'Technical problems that significantly impacted the session', 'beyondinfinity' ),
        ] ); ?>
      </div>
      <div class="ngt-card bi-qual-card bi-qual-card--no ngt-animate">
        <h3><?php esc_html_e( 'Does not qualify', 'beyondinfinity' ); ?></h3>
        <?php bi_bullets( [
            __( 'Forgotten sessions or scheduling conflicts on your end', 'beyondinfinity' ),
            __( 'Deciding tutoring is not right for your child (unrelated to tutor quality)', 'beyondinfinity' ),
            __( 'Claims made more than 24 hours after the session', 'beyondinfinity' ),
            __( 'Cancellations less than 4 hours before the session', 'beyondinfinity' ),
        ] ); ?>
      </div>
    </div>
  </div>
</section>

<section class="ngt-section">
  <div class="ngt-container bi-narrow">
    <div class="ngt-card ngt-animate" style="padding:32px">
      <h2><?php esc_html_e( 'How to Claim', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Email support within 24 hours of your first lesson with your booking reference and a short note on what did not work. We will rematch you within 48 hours or process a full refund.', 'beyondinfinity' ); ?></p>
      <p style="margin-bottom:0"><strong><?php esc_html_e( 'Reference:', 'beyondinfinity' ); ?></strong> <?php echo esc_html( bi_get_guarantee_code() ); ?></p>
      <div class="bi-hero__actions" style="margin-top:24px">
        <a href="<?php echo esc_url( 'mailto:' . bi_get_support_email() ); ?>" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'Email Support', 'beyondinfinity' ); ?></a>
        <a href="<?php echo esc_url( bi_whatsapp_url( __( 'I need to claim the first-lesson guarantee.', 'beyondinfinity' ) ) ); ?>" class="ngt-btn ngt-btn--outline" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp Us', 'beyondinfinity' ); ?></a>
      </div>
    </div>
  </div>
</section>
