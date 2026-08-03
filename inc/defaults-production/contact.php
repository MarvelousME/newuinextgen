<?php
/**
 * Default — Contact (from NGT-Design-UI-contact.pdf).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brand   = function_exists( 'bi_brand_content' ) ? bi_brand_content() : [];
$contact = $brand['contact'] ?? [];

bi_hero(
	$contact['title'] ?? __( 'Get in touch', 'beyondinfinity' ),
	$contact['lead'] ?? __( "We're here to help with any questions about tutoring.", 'beyondinfinity' )
);
?>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="bi-become-grid">
      <div class="ngt-card bi-contact-card ngt-animate" data-testid="bi-contact-card" style="padding:32px;background:linear-gradient(135deg,var(--ngt-primary-dark),var(--ngt-primary));color:#fff">
        <h2 style="color:#fff;margin-bottom:16px"><?php esc_html_e( 'Contact details', 'beyondinfinity' ); ?></h2>
        <ul class="bi-bullets" style="color:rgba(255,255,255,.9)">
          <li data-testid="bi-contact-phone"><span><?php esc_html_e( 'Phone:', 'beyondinfinity' ); ?></span> <?php echo esc_html( bi_get_phone() ); ?></li>
          <li data-testid="bi-contact-email"><span><?php esc_html_e( 'Support:', 'beyondinfinity' ); ?></span> <?php echo esc_html( bi_get_support_email() ); ?></li>
          <li data-testid="bi-contact-service-area"><span><?php esc_html_e( 'Service area:', 'beyondinfinity' ); ?></span> <?php echo esc_html( bi_get_service_area() ); ?></li>
          <li><span><?php esc_html_e( 'Hours:', 'beyondinfinity' ); ?></span> <?php esc_html_e( 'Mon–Fri 08:00–18:00 SAST', 'beyondinfinity' ); ?></li>
        </ul>
        <a href="<?php echo esc_url( bi_whatsapp_url() ); ?>" class="ngt-btn ngt-btn--white" data-testid="bi-contact-whatsapp" style="margin-top:20px" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp Us', 'beyondinfinity' ); ?></a>
      </div>
      <div>
        <?php bi_shortcode_block( '[ngc_contact_support_form]', __( 'Send a message', 'beyondinfinity' ) ); ?>
      </div>
    </div>
  </div>
</section>

<?php if ( ! empty( $contact['departments'] ) ) : ?>
<section class="ngt-section ngt-section--alt">
  <div class="ngt-container ngt-animate">
    <div class="ngt-section__header bi-center">
      <h2><?php esc_html_e( 'Contact by department', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Reach the right team directly for faster support.', 'beyondinfinity' ); ?></p>
    </div>
    <div class="ngt-card" style="padding:8px 16px;overflow-x:auto">
      <table class="bi-brand-dept">
        <thead>
          <tr>
            <th><?php esc_html_e( 'Department', 'beyondinfinity' ); ?></th>
            <th><?php esc_html_e( 'What it\'s for', 'beyondinfinity' ); ?></th>
            <th><?php esc_html_e( 'Email', 'beyondinfinity' ); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $contact['departments'] as $dept ) : ?>
            <tr>
              <td><?php echo esc_html( $dept['name'] ); ?></td>
              <td><?php echo esc_html( $dept['for'] ); ?></td>
              <td><a href="mailto:<?php echo esc_attr( $dept['email'] ); ?>"><?php echo esc_html( $dept['email'] ); ?></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="bi-brand-source"><?php echo esc_html( $brand['source_note'] ?? '' ); ?></p>
  </div>
</section>
<?php endif; ?>

<section class="ngt-section bi-center">
  <div class="ngt-container ngt-animate">
    <p><?php esc_html_e( 'Need help choosing? Book a free placement call with our team.', 'beyondinfinity' ); ?></p>
    <a href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'Start tutor search', 'beyondinfinity' ); ?></a>
  </div>
</section>
