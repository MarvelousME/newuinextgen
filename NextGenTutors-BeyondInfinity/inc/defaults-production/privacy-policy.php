<?php
/** Default theme content — merged from pages-to-review/privacy.html */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero(
    __( 'Privacy Policy', 'beyondinfinity' ),
    __( 'NextGen Tutors is committed to protecting the privacy of personal information gathered in the course of doing business.', 'beyondinfinity' )
);
?>

<section class="ngt-section">
  <div class="ngt-container bi-legal ngt-animate">
    <?php bi_popia_badges(); ?>
    <p class="bi-legal-updated"><em><?php esc_html_e( 'Last updated:', 'beyondinfinity' ); ?> <?php echo esc_html( gmdate( 'F j, Y' ) ); ?></em></p>

    <div class="bi-legal-section">
      <h2><?php esc_html_e( 'Introduction', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'NextGen Tutors is committed to protecting the privacy of any and all personal information it gathers in the course of doing business. We collect only the essential information needed to perform the functions required by NextGen Tutors.', 'beyondinfinity' ); ?></p>
      <p><?php esc_html_e( 'We have taken steps to ensure information is stored in safe, secure locations with access only granted to employees who require it. NextGen Tutors will not sell or distribute any information obtained or provided by a client to a third party for marketing or other purposes.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><?php esc_html_e( 'Collection of Personal Information', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'NextGen Tutors collects personally identifiable information, such as:', 'beyondinfinity' ); ?></p>
      <?php bi_bullets( [
        __( 'Email address, name, home or work address and telephone number', 'beyondinfinity' ),
        __( 'Anonymous demographic information (postal code, age, gender, preferences)', 'beyondinfinity' ),
        __( 'Computer hardware and software information (IP address, browser type, domain names, access times)', 'beyondinfinity' ),
        __( 'Learner grade, subject needs, province/area and session history', 'beyondinfinity' ),
      ] ); ?>
      <p><?php esc_html_e( 'This information is used for the operation of the service, to maintain quality, and to provide general statistics regarding use of the Website.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><?php esc_html_e( 'Use of Personal Information', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'NextGen Tutors collects and uses your personal information to operate the Website, deliver requested services, match learners with tutors, manage bookings and payments, and meet legal obligations.', 'beyondinfinity' ); ?></p>
      <p><?php esc_html_e( 'NextGen Tutors does not sell, rent or lease its customer lists to third parties. Trusted partners may assist with statistical analysis, email, customer support or deliveries, but are prohibited from using your information for any other purpose.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><?php esc_html_e( 'Sharing with Tutors', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'We share relevant learner information with matched tutors only as needed to deliver tutoring. Service providers (e.g. payment processors) may process data on our behalf under contract.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><?php esc_html_e( 'Children’s Information', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Learners under 18 should register through a parent or guardian. See our Child Safety Policy for how we protect minors on the platform.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><?php esc_html_e( 'POPIA Compliance', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'NextGen Tutors adheres to the Protection of Personal Information Act (POPIA). We do not sell or share your data with third-party marketing firms.', 'beyondinfinity' ); ?></p>
      <p><?php esc_html_e( 'Data is used exclusively for matching learners with tutors, processing payments via secure gateways, and improving educational outcomes.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><?php esc_html_e( 'Academic Data', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Learner progress and lesson history are stored securely for parent review and quality control.', 'beyondinfinity' ); ?></p>
    </div>

    <div class="bi-legal-section">
      <h2><?php esc_html_e( 'Contact', 'beyondinfinity' ); ?></h2>
      <p><?php printf( esc_html__( 'For data requests or deletion, contact: %s', 'beyondinfinity' ), '<a href="mailto:privacy@nextgentutors.co.za">privacy@nextgentutors.co.za</a>' ); ?></p>
      <p><?php printf( esc_html__( 'General privacy enquiries: %s', 'beyondinfinity' ), '<a href="mailto:' . esc_attr( bi_get_email() ) . '">' . esc_html( bi_get_email() ) . '</a>' ); ?></p>
    </div>
  </div>
</section>
