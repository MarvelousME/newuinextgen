<?php
/** Default theme content — merged from pages-to-review/terms.html */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero(
    __( 'Terms of Service', 'beyondinfinity' ),
    __( 'These terms regulate your use of nextgentutors.co.za and create a legally binding contract between you and NextGen Tutors.', 'beyondinfinity' )
);
?>

<section class="ngt-section">
  <div class="ngt-container bi-legal ngt-animate">
    <p class="bi-legal-updated"><em><?php esc_html_e( 'Last updated:', 'beyondinfinity' ); ?> <?php echo esc_html( gmdate( 'F j, Y' ) ); ?></em></p>

    <div class="bi-legal-section">
      <h2><span class="bi-legal-num">1</span> <?php esc_html_e( 'Introduction', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'These Terms regulate your general use of nextgentutors.co.za including all sub-domains and create a legally binding contract between NextGen Tutors ("us" or "we") and you. Do not use our Website if you do not agree to these Terms.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><span class="bi-legal-num">2</span> <?php esc_html_e( 'Services Provided', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'NextGen Tutors provides a platform to match high school learners (Gr 8–12) with qualified tutors in Johannesburg and online nationally.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><span class="bi-legal-num">3</span> <?php esc_html_e( 'Academic Policy', 'beyondinfinity' ); ?></h2>
      <?php bi_bullets( [
        sprintf(
          /* translators: %s: guarantee label */
          __( '%s guarantee: if you are not satisfied with your first matched tutor session, we will rematch you or refund that session.', 'beyondinfinity' ),
          bi_guarantee_label()
        ),
        __( 'All tutors are vetted for ID, police clearance and academic credentials. Parents remain responsible for supervising in-home sessions.', 'beyondinfinity' ),
      ] ); ?>
    </div>

    <div class="bi-legal-section">
      <h2><span class="bi-legal-num">4</span> <?php esc_html_e( 'Platform Role', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'NextGen Tutors facilitates matching, booking, and payment between families and tutors. We are not the employer of tutors and do not guarantee specific academic outcomes or grade passes.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><span class="bi-legal-num">5</span> <?php esc_html_e( 'Other Applicable Terms', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Your use of the Website is also subject to our Privacy Policy and Child Safety Policy. Additional terms may apply for specific products or services.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><span class="bi-legal-num">6</span> <?php esc_html_e( 'Accounts', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Users must provide accurate information. Parents or guardians are responsible for accounts registered on behalf of learners under 18.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><span class="bi-legal-num">7</span> <?php esc_html_e( 'Use of Our Website', 'beyondinfinity' ); ?></h2>
      <?php bi_bullets( [
        __( 'You may not distribute content from our Website without prior consent', 'beyondinfinity' ),
        __( 'You may not use crawlers or spiders to search our Website', 'beyondinfinity' ),
        __( 'You may not frame our Website or deep-link in a way that suggests you own our intellectual property', 'beyondinfinity' ),
      ] ); ?>
    </div>

    <div class="bi-legal-section">
      <h2><span class="bi-legal-num">8</span> <?php esc_html_e( 'Payment & Billing', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'All payments must go through the NextGen Tutors platform. Paying tutors directly is a breach of these terms. Rates depend on grade level and delivery format (in-person vs online). Invoices are generated monthly or per session for ad-hoc bookings.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><span class="bi-legal-num">9</span> <?php esc_html_e( 'Cancellations & Refunds', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Cancellations must be made at least 24 hours in advance to receive a full credit. Recurring weekly tutoring requires one month\'s notice to terminate.', 'beyondinfinity' ); ?></p>
      <p><?php esc_html_e( 'Our first-lesson guarantee applies as described on the Guarantee page. Contact support for billing disputes.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><span class="bi-legal-num">10</span> <?php esc_html_e( 'Conduct & Safety', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'All users must follow our Safety Guide. Harassment, unsafe behaviour, or off-platform payment arrangements may result in account suspension.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><span class="bi-legal-num">11</span> <?php esc_html_e( 'Intellectual Property', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'All content and material on the Website is owned by NextGen Tutors. You are not authorised to copy, reproduce or use any content without our prior written consent.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><span class="bi-legal-num">12</span> <?php esc_html_e( 'Limitation of Liability', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'NextGen Tutors is a platform provider and is not liable for outcomes between tutors and students beyond platform maintenance and payment processing, to the extent permitted by South African law.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><span class="bi-legal-num">13</span> <?php esc_html_e( 'Law and Disputes', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'These Terms are governed by South African law. You consent that the Magistrate\'s Court will have jurisdiction even if proceedings are otherwise beyond its jurisdiction.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><span class="bi-legal-num">14</span> <?php esc_html_e( 'Contact', 'beyondinfinity' ); ?></h2>
      <p><?php printf( esc_html__( 'Questions: %s', 'beyondinfinity' ), '<a href="mailto:' . esc_attr( bi_get_support_email() ) . '">' . esc_html( bi_get_support_email() ) . '</a>' ); ?></p>
    </div>
  </div>
</section>
