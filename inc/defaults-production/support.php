<?php
/** Default — Support hub */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero(
    __( 'Help & Support', 'beyondinfinity' ),
    __( 'Answers for parents, students and tutors — plus direct access to our support team.', 'beyondinfinity' )
);
?>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="bi-grid-3">
      <?php foreach (
          [
              [ '📚', __( 'Parent Support', 'beyondinfinity' ), __( 'Matching, bookings, billing and child safety questions.', 'beyondinfinity' ), '/contact' ],
              [ '🎓', __( 'Tutor Support', 'beyondinfinity' ), __( 'Applications, vetting, payouts and session tools.', 'beyondinfinity' ), '/become-a-tutor' ],
              [ '💳', __( 'Billing & Refunds', 'beyondinfinity' ), __( 'Invoices, guarantee claims and payment issues.', 'beyondinfinity' ), '/guarantee' ],
          ] as $i => $card
      ) : ?>
        <a href="<?php echo esc_url( home_url( $card[3] ) ); ?>" class="ngt-card ngt-animate ngt-animate--delay-<?php echo $i + 1; ?>" style="padding:28px;text-decoration:none;color:inherit">
          <div style="font-size:2rem;margin-bottom:12px"><?php echo esc_html( $card[0] ); ?></div>
          <h3 style="margin-bottom:8px"><?php echo esc_html( $card[1] ); ?></h3>
          <p style="margin:0;color:var(--ngt-text-2)"><?php echo esc_html( $card[2] ); ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="ngt-section ngt-section--alt">
  <div class="ngt-container bi-narrow">
    <div class="ngt-section__header ngt-animate"><h2><?php esc_html_e( 'Common Questions', 'beyondinfinity' ); ?></h2></div>
    <?php foreach (
        [
            [ __( 'How fast can we get a tutor?', 'beyondinfinity' ), __( 'Most families receive suitable matches within 48 hours of submitting the intake form.', 'beyondinfinity' ) ],
            [ __( 'Is the first lesson really risk-free?', 'beyondinfinity' ), __( 'Yes — see our NextGen100 guarantee. Not satisfied? We rematch or refund within 24 hours of the session.', 'beyondinfinity' ) ],
            [ __( 'How are tutors vetted?', 'beyondinfinity' ), __( 'ID verification, qualification review, manual approval and ongoing monitoring. See the tutor vetting page for detail.', 'beyondinfinity' ) ],
        ] as $faq
    ) : ?>
      <details class="ngt-card bi-faq ngt-animate" style="padding:20px;margin-bottom:12px">
        <summary style="font-weight:700;cursor:pointer"><?php echo esc_html( $faq[0] ); ?></summary>
        <p style="margin:12px 0 0;color:var(--ngt-text-2)"><?php echo esc_html( $faq[1] ); ?></p>
      </details>
    <?php endforeach; ?>
  </div>
</section>

<section class="ngt-section">
  <div class="ngt-container bi-narrow">
    <?php bi_shortcode_block( '[ngc_contact_support_form]', __( 'Open a Support Ticket', 'beyondinfinity' ) ); ?>
    <div class="ngt-card ngt-animate bi-center" style="padding:24px;margin-top:24px">
      <p style="margin:0 0 16px"><?php esc_html_e( 'Prefer WhatsApp? Our team replies during business hours.', 'beyondinfinity' ); ?></p>
      <a href="<?php echo esc_url( bi_whatsapp_url() ); ?>" class="ngt-btn ngt-btn--secondary" target="_blank" rel="noopener"><?php esc_html_e( 'Chat on WhatsApp', 'beyondinfinity' ); ?></a>
    </div>
  </div>
</section>
