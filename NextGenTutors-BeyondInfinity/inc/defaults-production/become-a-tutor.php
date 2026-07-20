<?php
/** Default — Become a Tutor (pages-to-review/become-a-tutor.html) */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero(
    __( 'Want to Become a Tutor?', 'beyondinfinity' ),
    __( 'Set your rates and hours, get matched with learners, and grow a reputation backed by verified reviews. We handle billing and payouts.', 'beyondinfinity' )
);
$marketing_kpis = bi_real_marketing_kpis();
$policy_sla     = bi_policy_sla_labels();
$top_earnings   = (float) $marketing_kpis['top_monthly_earnings'];
$top_earnings_label = $top_earnings > 0 ? 'R' . number_format( $top_earnings, 0 ) : __( 'EMPTY STATE', 'beyondinfinity' );
?>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="bi-stat-grid" style="margin-bottom:0">
      <?php foreach (
          [
              [ $top_earnings_label, __( 'Top monthly earnings', 'beyondinfinity' ) ],
              [ __( 'Weekly', 'beyondinfinity' ), __( 'Platform payouts', 'beyondinfinity' ) ],
              [ $policy_sla['first_booking_target'], __( 'First booking target', 'beyondinfinity' ) ],
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

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container">
    <div class="bi-become-grid">
      <div class="ngt-card bi-become-visual ngt-animate mask-reveal" style="padding:0;overflow:hidden">
        <?php bi_theme_image( 'become_tutor', [ 'motion' => 'scale-in' ] ); ?>
        <div class="bi-become-earn"><?php echo esc_html( sprintf( __( 'Earn up to %s/mo', 'beyondinfinity' ), $top_earnings_label ) ); ?></div>
      </div>
      <div class="ngt-animate">
        <p class="bi-eyebrow"><?php esc_html_e( 'Why tutor with us', 'beyondinfinity' ); ?></p>
        <h2><?php esc_html_e( 'Real Income, Real Flexibility', 'beyondinfinity' ); ?></h2>
        <p><?php esc_html_e( 'Whether you are a varsity student, qualified teacher or industry expert, NextGen gives you learners and tools to build a thriving practice.', 'beyondinfinity' ); ?></p>
        <?php bi_bullets( [
            sprintf( __( 'Typical tutor payout tracks live rates: %s to %s per hour', 'beyondinfinity' ), bi_format_rate( 'rate_online', 320 ), bi_format_rate( 'rate_tertiary', 500 ) ),
            __( 'Accept only the slots that fit your schedule', 'beyondinfinity' ),
            __( 'SACE verification and reviews build trust fast', 'beyondinfinity' ),
        ] ); ?>
      </div>
    </div>
  </div>
</section>

<section class="ngt-section">
  <div class="ngt-container bi-narrow">
    <?php bi_shortcode_block( '[ngc_become_tutor_form]', __( 'Tutor Application', 'beyondinfinity' ) ); ?>
    <?php bi_safety_notice( 'tutor' ); ?>
  </div>
</section>

<?php
bi_steps(
    [
        [ 'title' => __( 'Apply online', 'beyondinfinity' ), 'text' => __( 'Submit subjects, qualifications and availability.', 'beyondinfinity' ) ],
        [ 'title' => __( 'Vetting & clearance', 'beyondinfinity' ), 'text' => __( 'ID, qualifications and police clearance reviewed manually.', 'beyondinfinity' ) ],
        [ 'title' => __( 'Go live', 'beyondinfinity' ), 'text' => __( 'Approved tutors appear in search and receive matches.', 'beyondinfinity' ) ],
        [ 'title' => __( 'Teach & earn', 'beyondinfinity' ), 'text' => __( 'Confirm sessions and receive reliable platform payouts.', 'beyondinfinity' ) ],
    ],
    __( 'Tutor Journey', 'beyondinfinity' )
);
?>
