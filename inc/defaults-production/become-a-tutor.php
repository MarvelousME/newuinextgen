<?php
/** Default — Become a Tutor (pages-to-review/become-a-tutor.html) */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero(
    __( 'Share Your Knowledge. Build Your Income.', 'beyondinfinity' ),
    __( 'Join tutors across South Africa teaching online and in-person on NextGen Tutors. Set your own rates, choose your subjects, work when you want — with vetted students and reliable payouts.', 'beyondinfinity' )
);
$marketing_kpis = bi_real_marketing_kpis();
$policy_sla     = bi_policy_sla_labels();
$top_earnings   = (float) $marketing_kpis['top_monthly_earnings'];
$top_earnings_label = $top_earnings > 0 ? 'R' . number_format( $top_earnings, 0 ) : __( 'EMPTY STATE', 'beyondinfinity' );
$become = function_exists( 'bi_brand_content' ) ? ( bi_brand_content()['become'] ?? [] ) : [];
?>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="bi-stat-grid" style="margin-bottom:0">
      <?php foreach (
          [
              [ $top_earnings_label, __( 'Top monthly earnings', 'beyondinfinity' ), $top_earnings > 0 ? (string) (int) $top_earnings : '' ],
              [ __( 'Weekly', 'beyondinfinity' ), __( 'Platform payouts', 'beyondinfinity' ), '' ],
              [ $policy_sla['first_booking_target'], __( 'First booking target', 'beyondinfinity' ), preg_match( '/\d+/', (string) $policy_sla['first_booking_target'], $m ) ? $m[0] : '' ],
          ] as $s
      ) : ?>
        <div class="bi-stat-card ngt-animate">
          <div class="bi-stat-card__num"<?php if ( '' !== $s[2] ) : ?> data-bi-count="<?php echo esc_attr( $s[2] ); ?>"<?php endif; ?>><?php echo esc_html( $s[0] ); ?></div>
          <div class="bi-stat-card__label"><?php echo esc_html( $s[1] ); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ( ! empty( $become['perks'] ) ) : ?>
<section class="ngt-section ngt-section--alt">
  <div class="ngt-container">
    <div class="ngt-section__header bi-center ngt-animate">
      <p class="bi-eyebrow"><?php esc_html_e( 'Why tutor with NextGen', 'beyondinfinity' ); ?></p>
      <h2><?php echo esc_html( $become['title'] ?? __( 'Real income, real flexibility', 'beyondinfinity' ) ); ?></h2>
      <p><?php echo esc_html( $become['lead'] ?? '' ); ?></p>
    </div>
    <div class="bi-brand-values bi-grid-3 framer-grid" data-bi-stagger="slide-up">
      <?php foreach ( $become['perks'] as $i => $perk ) : ?>
        <article class="ngt-card bi-brand-value ngt-animate ngt-animate--delay-<?php echo (int) ( ( $i % 3 ) + 1 ); ?>">
          <h3><?php echo esc_html( $perk['title'] ); ?></h3>
          <p><?php echo esc_html( $perk['text'] ); ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ( ! empty( $become['income'] ) ) : ?>
<section class="ngt-section">
  <div class="ngt-container ngt-animate">
    <div class="ngt-section__header bi-center">
      <h2><?php esc_html_e( 'Estimated monthly income', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Based on 4 weeks per month. Join 500+ verified tutors earning while making a difference.', 'beyondinfinity' ); ?></p>
    </div>
    <div class="ngt-card" style="padding:8px 16px;overflow-x:auto">
      <table class="bi-brand-dept">
        <thead>
          <tr>
            <th><?php esc_html_e( 'Hourly rate', 'beyondinfinity' ); ?></th>
            <th><?php esc_html_e( 'Sessions / week', 'beyondinfinity' ); ?></th>
            <th><?php esc_html_e( 'Est. monthly income', 'beyondinfinity' ); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $become['income'] as $row ) : ?>
            <tr>
              <td><?php echo esc_html( $row['rate'] ); ?></td>
              <td><?php echo esc_html( $row['sessions'] ); ?></td>
              <td><strong><?php echo esc_html( $row['monthly'] ); ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container">
    <div class="bi-become-grid">
      <div class="ngt-card bi-become-visual ngt-animate mask-reveal" style="padding:0;overflow:hidden">
        <?php bi_theme_image( 'become_tutor', [ 'motion' => 'scale-in' ] ); ?>
        <div class="bi-become-earn"><?php echo esc_html( sprintf( __( 'Earn up to %s/mo', 'beyondinfinity' ), $top_earnings_label ) ); ?></div>
      </div>
      <div class="ngt-animate">
        <p class="bi-eyebrow"><?php esc_html_e( 'Platform tools', 'beyondinfinity' ); ?></p>
        <h2><?php esc_html_e( 'We handle billing and payouts', 'beyondinfinity' ); ?></h2>
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
    <div class="bi-trust-chip-row ngt-animate" role="note" aria-label="<?php esc_attr_e( 'Tutor payout information', 'beyondinfinity' ); ?>">
      <?php
      $payout_message = $top_earnings > 0
          ? sprintf( __( 'Top recorded monthly tutor earnings: %s · payouts tracked weekly', 'beyondinfinity' ), $top_earnings_label )
          : __( 'Weekly platform payouts with transparent earnings tracking', 'beyondinfinity' );
      bi_trust_chip( $payout_message, '', [ 'icon' => 'check' ] );
      ?>
    </div>
    <?php bi_shortcode_block( '[ngc_become_tutor_form]', __( 'Tutor Application', 'beyondinfinity' ) ); ?>
    <?php bi_safety_notice( 'tutor' ); ?>
  </div>
</section>

<?php
$step_items = ! empty( $become['steps'] )
	? array_map(
		static function ( $text, $i ) {
			$titles = [
				__( 'Submit application', 'beyondinfinity' ),
				__( 'Verification process', 'beyondinfinity' ),
				__( 'Profile setup', 'beyondinfinity' ),
				__( 'Training & onboarding', 'beyondinfinity' ),
				__( 'Start tutoring', 'beyondinfinity' ),
			];
			return [
				'title' => $titles[ $i ] ?? __( 'Next step', 'beyondinfinity' ),
				'text'  => $text,
			];
		},
		$become['steps'],
		array_keys( $become['steps'] )
	)
	: [
		[ 'title' => __( 'Apply Online', 'beyondinfinity' ), 'text' => __( 'Complete the application. Tell us your subjects, qualifications, and availability. Takes about 5 minutes.', 'beyondinfinity' ) ],
		[ 'title' => __( 'Vetting & Approval', 'beyondinfinity' ), 'text' => __( 'We verify your ID, qualifications, and conduct a background check. Most tutors are approved within 48 hours.', 'beyondinfinity' ) ],
		[ 'title' => __( 'Start Teaching', 'beyondinfinity' ), 'text' => __( 'Your profile goes live. Students book directly. You get paid on the platform payout schedule to your bank account.', 'beyondinfinity' ) ],
	];

bi_steps(
    $step_items,
    __( 'How It Works', 'beyondinfinity' ),
    __( 'From application to earning in simple steps.', 'beyondinfinity' )
);
?>
