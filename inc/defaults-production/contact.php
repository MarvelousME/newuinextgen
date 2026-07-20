<?php
/** Default — Contact (pages-to-review/contact.html) */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero(
    __( 'Get In Touch', 'beyondinfinity' ),
    __( 'Our placement specialists help you find the perfect tutor — free of charge. We reply within one business day.', 'beyondinfinity' )
);
?>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="bi-become-grid">
      <div class="ngt-card bi-contact-card ngt-animate" style="padding:32px;background:linear-gradient(135deg,var(--ngt-primary-dark),var(--ngt-primary));color:#fff">
        <h2 style="color:#fff;margin-bottom:16px"><?php esc_html_e( 'Contact Details', 'beyondinfinity' ); ?></h2>
        <ul class="bi-bullets" style="color:rgba(255,255,255,.9)">
          <li><span><?php esc_html_e( 'Phone:', 'beyondinfinity' ); ?></span> <?php echo esc_html( bi_get_phone() ); ?></li>
          <li><span><?php esc_html_e( 'Support:', 'beyondinfinity' ); ?></span> <?php echo esc_html( bi_get_support_email() ); ?></li>
          <li><span><?php esc_html_e( 'Service area:', 'beyondinfinity' ); ?></span> <?php echo esc_html( bi_get_service_area() ); ?></li>
          <li><span><?php esc_html_e( 'Hours:', 'beyondinfinity' ); ?></span> <?php esc_html_e( 'Mon–Fri 08:00–18:00 SAST', 'beyondinfinity' ); ?></li>
        </ul>
        <a href="<?php echo esc_url( bi_whatsapp_url() ); ?>" class="ngt-btn ngt-btn--white" style="margin-top:20px" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp Us', 'beyondinfinity' ); ?></a>
      </div>
      <div>
        <?php bi_shortcode_block( '[ngc_contact_support_form]', __( 'Send a Message', 'beyondinfinity' ) ); ?>
      </div>
    </div>
  </div>
</section>

<section class="ngt-section ngt-section--alt bi-center">
  <div class="ngt-container ngt-animate">
    <p><?php esc_html_e( 'Need help choosing? Book a free placement call with our team.', 'beyondinfinity' ); ?></p>
    <a href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'Start Tutor Search', 'beyondinfinity' ); ?></a>
  </div>
</section>
