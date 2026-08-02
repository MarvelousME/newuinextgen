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
        <a href="<?php echo esc_url( home_url( $card[3] ) ); ?>" class="ngt-card ngt-animate ngt-animate--delay-<?php echo (int) ( $i + 1 ); ?> bi-pad-md bi-card-link">
          <div class="bi-icon-lg"><?php echo esc_html( $card[0] ); ?></div>
          <h3 class="bi-mb-xs"><?php echo esc_html( $card[1] ); ?></h3>
          <p class="bi-copy-flush bi-text-muted"><?php echo esc_html( $card[2] ); ?></p>
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
      <details class="ngt-card bi-faq ngt-animate">
        <summary><?php echo esc_html( $faq[0] ); ?></summary>
        <p><?php echo esc_html( $faq[1] ); ?></p>
      </details>
    <?php endforeach; ?>
  </div>
</section>

<section class="ngt-section">
  <div class="ngt-container bi-narrow">
    <?php
    if ( shortcode_exists( 'fluent_support_portal' ) ) {
        $mailbox_id = (int) get_option( 'ngc_fluent_support_mailbox_id', 0 );
        $sc         = '[fluent_support_portal show_logout="yes"';
        if ( $mailbox_id > 0 ) {
            $sc .= ' business_box_id="' . $mailbox_id . '"';
        }
        $sc .= ']';
        echo '<div class="ngt-section__header ngt-animate"><h2>' . esc_html__( 'Open a Support Ticket', 'beyondinfinity' ) . '</h2></div>';
        echo do_shortcode( $sc ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } else {
        bi_shortcode_block( '[ngc_contact_support_form]', __( 'Open a Support Ticket', 'beyondinfinity' ) );
    }
    ?>
    <div class="ngt-card ngt-animate bi-center bi-pad-sm bi-mt-md">
      <p class="bi-mb-md"><?php esc_html_e( 'Prefer WhatsApp? Our team replies during business hours.', 'beyondinfinity' ); ?></p>
      <a href="<?php echo esc_url( bi_whatsapp_url() ); ?>" class="ngt-btn ngt-btn--secondary" target="_blank" rel="noopener"><?php esc_html_e( 'Chat on WhatsApp', 'beyondinfinity' ); ?></a>
    </div>
  </div>
</section>
