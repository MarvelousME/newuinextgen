<?php
/** Default theme content — used when no page builder content is present. */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$hours = (int) get_theme_mod( 'bi_response_hours', 24 );
$type  = sanitize_text_field( $_GET['type'] ?? 'general' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$messages = [
  'tutor'   => __( 'Thank you for applying to become a tutor. Our team will review your profile and contact you with next steps.', 'beyondinfinity' ),
  'parent'  => __( 'Thank you for your tutor request. We are reviewing your learner’s needs and will suggest suitable matches shortly.', 'beyondinfinity' ),
  'contact' => __( 'Thank you for contacting NextGen Tutors. We have received your message.', 'beyondinfinity' ),
  'general' => __( 'Thank you. We have received your submission.', 'beyondinfinity' ),
];
$message = $messages[ $type ] ?? $messages['general'];
?>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="ngt-card bi-thankyou ngt-animate" style="padding:48px">
      <div class="bi-thankyou__icon" aria-hidden="true">✓</div>
      <h1 style="margin-bottom:16px"><?php esc_html_e( 'Submission Received', 'beyondinfinity' ); ?></h1>
      <p style="font-size:1.1rem;margin-bottom:24px"><?php echo esc_html( $message ); ?></p>
      <p><?php echo esc_html( sprintf( __( 'We aim to respond within %d hours. For urgent academic support, WhatsApp or call us.', 'beyondinfinity' ), $hours ) ); ?></p>
      <div class="bi-hero__actions" style="justify-content:center;margin-top:32px">
        <a href="<?php echo esc_url( bi_whatsapp_url() ); ?>" class="ngt-btn ngt-btn--secondary" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp Support', 'beyondinfinity' ); ?></a>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ngt-btn ngt-btn--outline"><?php esc_html_e( 'Back to Home', 'beyondinfinity' ); ?></a>
      </div>
    </div>
    <?php bi_steps(
      [
        __( 'Our team reviews your submission.', 'beyondinfinity' ),
        __( 'We contact you if we need more information.', 'beyondinfinity' ),
        __( 'You receive next steps or tutor matches.', 'beyondinfinity' ),
        __( 'Lessons begin online, in-person, or hybrid.', 'beyondinfinity' ),
      ],
      __( 'What Happens Next', 'beyondinfinity' )
    ); ?>
  </div>
</section>
