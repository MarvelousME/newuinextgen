<?php
/** Default theme content — used when no page builder content is present. */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$rate_online   = bi_format_rate( 'rate_online', 320 );
$rate_inperson = bi_format_rate( 'rate_inperson', 350 );
$rate_tertiary = bi_format_rate( 'rate_tertiary', 500 );
$online_num    = bi_rate_to_number( $rate_online );
$indicative_total = $online_num > 0 ? $online_num * 2 * 4 : 0;

bi_hero(
  __( 'Transparent Tutoring Rates', 'beyondinfinity' ),
  __( 'Top-tier educators at honest South African Rand rates — online, in-person, and tertiary support.', 'beyondinfinity' )
);
?>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="bi-grid-3 bi-price-grid" style="max-width:1100px;margin:0 auto">
      <div class="ngt-pricing-card bi-price-plan ngt-animate">
        <span class="bi-price-plan__tag"><?php esc_html_e( 'Online Classroom', 'beyondinfinity' ); ?></span>
        <h3><?php esc_html_e( 'Grade 1–12 Online', 'beyondinfinity' ); ?></h3>
        <p class="bi-price-plan__desc"><?php esc_html_e( 'Digital whiteboards, past-paper training and CAPS / IEB / Cambridge syllabi.', 'beyondinfinity' ); ?></p>
        <div class="ngt-pricing-card__price" data-bi-count="<?php echo esc_attr( (string) $online_num ); ?>"><?php echo esc_html( $rate_online ); ?></div>
        <p class="bi-calculator__note"><?php esc_html_e( 'per 1-hour lesson (1–3 month plan)', 'beyondinfinity' ); ?></p>
        <?php bi_bullets( [
          __( 'CAPS / IEB / Cambridge syllabi', 'beyondinfinity' ),
          __( 'Recorded session links on request', 'beyondinfinity' ),
          sprintf( __( 'Tutor payout is tracked in real reports (current reference: %s)', 'beyondinfinity' ), $rate_online ),
          sprintf( __( '3–12 month plan references live configured rate: %s', 'beyondinfinity' ), $rate_online ),
        ] ); ?>
        <a href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>" class="ngt-btn ngt-btn--outline ngt-btn--block" style="margin-top:20px"><?php esc_html_e( 'Choose Online', 'beyondinfinity' ); ?></a>
      </div>
      <div class="ngt-pricing-card ngt-pricing-card--featured bi-price-plan bi-price-plan--popular ngt-animate ngt-animate--delay-2" aria-label="<?php esc_attr_e( 'Most popular plan', 'beyondinfinity' ); ?>">
        <span class="bi-price-plan__tag bi-price-plan__tag--lime"><?php esc_html_e( 'Most Popular', 'beyondinfinity' ); ?></span>
        <h3 style="color:#fff"><?php esc_html_e( 'In-Person At Home', 'beyondinfinity' ); ?></h3>
        <p class="bi-price-plan__desc" style="color:rgba(255,255,255,.85)"><?php esc_html_e( 'A vetted tutor travels to your home with ID and clearance checks.', 'beyondinfinity' ); ?></p>
        <div class="ngt-pricing-card__price" style="font-size:2rem;color:#fff" data-bi-count="<?php echo esc_attr( (string) bi_rate_to_number( $rate_inperson ) ); ?>"><?php echo esc_html( $rate_inperson ); ?></div>
        <p style="color:rgba(255,255,255,.75)"><?php echo esc_html( sprintf( __( 'per hour (1–3 months) · %s for 3–12 months', 'beyondinfinity' ), $rate_inperson ) ); ?></p>
        <?php
        echo '<ul class="bi-bullets bi-bullets--light">';
        foreach ( [
          __( 'Gauteng & Cape suburb coverage', 'beyondinfinity' ),
          __( 'ID & police-clearance vetted', 'beyondinfinity' ),
          sprintf( __( 'Tutor payout uses live payout ledger (reference rate: %s)', 'beyondinfinity' ), $rate_inperson ),
          __( 'NextGen100 first-lesson guarantee', 'beyondinfinity' ),
        ] as $item ) {
            echo '<li>' . bi_bullet_mark( true ) . '<span>' . esc_html( $item ) . '</span></li>';
        }
        echo '</ul>';
        ?>
        <a href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>" class="ngt-btn ngt-btn--white ngt-btn--block" style="margin-top:20px"><?php esc_html_e( 'Book In-Person', 'beyondinfinity' ); ?></a>
      </div>
      <div class="ngt-pricing-card bi-price-plan ngt-animate ngt-animate--delay-3">
        <span class="bi-price-plan__tag"><?php esc_html_e( 'University Core', 'beyondinfinity' ); ?></span>
        <h3><?php esc_html_e( 'Tertiary Subjects', 'beyondinfinity' ); ?></h3>
        <p class="bi-price-plan__desc"><?php esc_html_e( 'Engineering, accounting, statistics and computer science specialists.', 'beyondinfinity' ); ?></p>
        <div class="ngt-pricing-card__price" data-bi-count="<?php echo esc_attr( (string) bi_rate_to_number( $rate_tertiary ) ); ?>"><?php echo esc_html( $rate_tertiary ); ?></div>
        <p class="bi-calculator__note"><?php esc_html_e( 'per lesson', 'beyondinfinity' ); ?></p>
        <?php bi_bullets( [
          __( 'Honours-level specialists', 'beyondinfinity' ),
          __( 'Financial maths & engineering', 'beyondinfinity' ),
          sprintf( __( 'Tutor payout references live configured tertiary rate: %s', 'beyondinfinity' ), $rate_tertiary ),
          __( 'Flexible scheduling slots', 'beyondinfinity' ),
        ] ); ?>
        <a href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>" class="ngt-btn ngt-btn--outline ngt-btn--block" style="margin-top:20px"><?php esc_html_e( 'Choose Tertiary', 'beyondinfinity' ); ?></a>
      </div>
    </div>

    <div class="bi-trust-chip-row ngt-animate" role="note" aria-label="<?php esc_attr_e( 'Booking protections', 'beyondinfinity' ); ?>">
      <?php bi_trust_chip( __( 'Every tutor passes 5-step vetting', 'beyondinfinity' ), home_url( '/tutor-vetting/' ) ); ?>
      <?php bi_trust_chip( __( 'First lesson covered by NextGen100', 'beyondinfinity' ), home_url( '/guarantee/' ), [ 'icon' => 'check' ] ); ?>
    </div>
    <div class="bi-trust-inject ngt-animate" role="note">
      <p>
        <?php
        printf(
          wp_kses(
            /* translators: 1: tutor-vetting URL, 2: guarantee URL */
            __( 'Every tutor passes our <a href="%1$s">5-step vetting</a>. Lessons are covered by the <a href="%2$s">NextGen100 first-lesson guarantee</a> — match again or get a full refund.', 'beyondinfinity' ),
            [ 'a' => [ 'href' => [] ] ]
          ),
          esc_url( home_url( '/tutor-vetting/' ) ),
          esc_url( home_url( '/guarantee/' ) )
        );
        ?>
      </p>
    </div>
  </div>
</section>

<?php if ( function_exists( 'ng_ui_component' ) ) : ?>
<section class="ngt-section ngt-section--alt">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate">
      <h2><?php esc_html_e( 'Package Overview', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Compare lesson packages sourced from your pricing catalog.', 'beyondinfinity' ); ?></p>
    </div>
    <?php ng_ui_component( 'pricing-card', [ 'limit' => 6 ] ); ?>
  </div>
</section>
<?php endif; ?>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container bi-narrow">
    <div class="ngt-section__header ngt-animate">
      <h2><?php esc_html_e( 'Online vs In-Person', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Same vetted tutors. Choose the format that fits your household.', 'beyondinfinity' ); ?></p>
    </div>
    <?php
    if ( function_exists( 'ng_ui_component' ) ) {
      ng_ui_component(
        'comparison-card',
        [
          'left'  => [
            'title' => __( 'Online', 'beyondinfinity' ),
            'items' => [
              __( 'Live lessons — digital whiteboards; parents may observe. Product session recording is not offered.', 'beyondinfinity' ),
              __( 'CAPS / IEB / Cambridge coverage', 'beyondinfinity' ),
              sprintf( __( 'From %s / hour', 'beyondinfinity' ), $rate_online ),
            ],
          ],
          'right' => [
            'title' => __( 'In-person', 'beyondinfinity' ),
            'items' => [
              __( 'Tutor travels to your home', 'beyondinfinity' ),
              __( 'ID & police-clearance vetted', 'beyondinfinity' ),
              sprintf( __( 'From %s / hour · NextGen100 guarantee', 'beyondinfinity' ), $rate_inperson ),
            ],
          ],
        ]
      );
    }
    ?>
  </div>
</section>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container">
    <div class="ngt-section__header ngt-animate"><h2><?php esc_html_e( 'Estimate Your Monthly Cost', 'beyondinfinity' ); ?></h2></div>
    <div class="ngt-card bi-calculator ngt-animate" style="padding:32px">
      <div class="bi-calculator__grid">
        <div class="ngt-form-group">
          <label for="bi-calc-format"><?php esc_html_e( 'Lesson Format', 'beyondinfinity' ); ?></label>
          <select id="bi-calc-format">
            <option value="online_short"><?php esc_html_e( 'Online (1–3 months)', 'beyondinfinity' ); ?></option>
            <option value="online_long"><?php esc_html_e( 'Online (3–12 months)', 'beyondinfinity' ); ?></option>
            <option value="inperson_short"><?php esc_html_e( 'In-Person (1–3 months)', 'beyondinfinity' ); ?></option>
            <option value="inperson_long"><?php esc_html_e( 'In-Person (3–12 months)', 'beyondinfinity' ); ?></option>
            <option value="tertiary"><?php esc_html_e( 'Tertiary', 'beyondinfinity' ); ?></option>
          </select>
        </div>
        <div class="ngt-form-group">
          <label for="bi-calc-lessons"><?php esc_html_e( 'Lessons per week', 'beyondinfinity' ); ?></label>
          <select id="bi-calc-lessons">
            <option value="1">1</option>
            <option value="2" selected>2</option>
            <option value="3">3</option>
          </select>
        </div>
        <div class="ngt-form-group">
          <label for="bi-calc-weeks"><?php esc_html_e( 'Weeks per month', 'beyondinfinity' ); ?></label>
          <select id="bi-calc-weeks">
            <option value="4" selected>4</option>
          </select>
        </div>
      </div>
      <div class="bi-calculator__result">
        <p style="margin:0"><?php esc_html_e( 'Estimated monthly total', 'beyondinfinity' ); ?></p>
        <div class="bi-calculator__amount" id="bi-calc-total"><?php echo esc_html( $indicative_total > 0 ? 'R' . number_format( $indicative_total, 0 ) : __( 'EMPTY STATE', 'beyondinfinity' ) ); ?></div>
        <p class="bi-calculator__note"><?php esc_html_e( 'Indicative estimate only. Book 12+ sessions/month for volume savings. Final pricing confirmed on booking.', 'beyondinfinity' ); ?></p>
      </div>
    </div>
  </div>
</section>

<section class="ngt-section">
  <div class="ngt-container bi-narrow">
    <div class="ngt-section__header ngt-animate"><h2><?php esc_html_e( 'Pricing Questions', 'beyondinfinity' ); ?></h2></div>
    <?php
    bi_faq_list( [
      [
        'q' => __( 'Are there any hidden fees or contracts?', 'beyondinfinity' ),
        'a' => __( 'Never. You pay per session or per package via PayFast — no lock-in contracts, no joining fees. Bundles lower your hourly rate.', 'beyondinfinity' ),
      ],
      [
        'q' => __( 'How does the volume discount work?', 'beyondinfinity' ),
        'a' => sprintf( __( 'Book 12 or more sessions in a month and your applied hourly rate follows the live configured pricing table (current online baseline: %s).', 'beyondinfinity' ), $rate_online ),
      ],
      [
        'q' => __( 'What is your cancellation policy?', 'beyondinfinity' ),
        'a' => __( 'We require 24 hours notice to reschedule or cancel. Cancellations under 4 hours apply partial payout, tracked in the platform payout ledger and finance dashboard.', 'beyondinfinity' ),
      ],
      [
        'q' => __( 'Is the first lesson really guaranteed?', 'beyondinfinity' ),
        'a' => __( 'Yes — our NextGen100 guarantee means if you are not satisfied with your first lesson, we will match you with another tutor or refund you completely.', 'beyondinfinity' ),
      ],
    ] );
    ?>
    <div class="ngt-card ngt-animate bi-center" style="padding:40px;margin-top:32px">
      <h2 style="margin-bottom:12px"><?php esc_html_e( 'Payments', 'beyondinfinity' ); ?></h2>
      <p style="margin:0;font-size:1.1rem"><?php esc_html_e( 'Parents pay NextGen Tutors. The platform pays tutors. One invoice. No cash handling.', 'beyondinfinity' ); ?></p>
    </div>
    <div class="bi-center" style="margin-top:32px">
      <a href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>" class="ngt-btn ngt-btn--primary ngt-btn--lg btn-ripple"><?php esc_html_e( 'Get a Tutor Today', 'beyondinfinity' ); ?></a>
    </div>
  </div>
</section>

<?php
bi_parallax_cta(
    __( 'Ready to invest in your child\'s future?', 'beyondinfinity' ),
    __( 'Book a Free Assessment', 'beyondinfinity' ),
    home_url( '/find-a-tutor' ),
    'pricing_bg'
);
