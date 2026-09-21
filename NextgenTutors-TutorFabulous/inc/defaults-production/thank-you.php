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

$timelines = [
  'tutor' => [
    [ 'title' => __( 'Application received', 'beyondinfinity' ), 'text' => __( 'Your profile is in the review queue.', 'beyondinfinity' ) ],
    [ 'title' => __( 'Vetting & verification', 'beyondinfinity' ), 'text' => __( 'We confirm ID, clearance and subject fit.', 'beyondinfinity' ) ],
    [ 'title' => __( 'Decision email', 'beyondinfinity' ), 'text' => __( 'You receive approval status and onboarding steps.', 'beyondinfinity' ) ],
    [ 'title' => __( 'Go live', 'beyondinfinity' ), 'text' => __( 'Set availability and start receiving matches.', 'beyondinfinity' ) ],
  ],
  'parent' => [
    [ 'title' => __( 'Request received', 'beyondinfinity' ), 'text' => __( 'We capture your learner’s subject and schedule needs.', 'beyondinfinity' ) ],
    [ 'title' => __( 'Tutor matching', 'beyondinfinity' ), 'text' => __( 'We shortlist vetted tutors who fit.', 'beyondinfinity' ) ],
    [ 'title' => __( 'Confirm & pay', 'beyondinfinity' ), 'text' => __( 'Choose a slot and complete secure PayFast checkout.', 'beyondinfinity' ) ],
    [ 'title' => __( 'First lesson', 'beyondinfinity' ), 'text' => __( 'Covered by the NextGen100 first-lesson guarantee.', 'beyondinfinity' ) ],
  ],
  'contact' => [
    [ 'title' => __( 'Message received', 'beyondinfinity' ), 'text' => __( 'Your enquiry is with the support team.', 'beyondinfinity' ) ],
    [ 'title' => __( 'Triage', 'beyondinfinity' ), 'text' => __( 'We route it to the right specialist.', 'beyondinfinity' ) ],
    [ 'title' => __( 'Reply', 'beyondinfinity' ), 'text' => sprintf( __( 'We aim to respond within %d hours.', 'beyondinfinity' ), $hours ) ],
    [ 'title' => __( 'Resolution', 'beyondinfinity' ), 'text' => __( 'We follow up until your question is answered.', 'beyondinfinity' ) ],
  ],
  'general' => [
    [ 'title' => __( 'Submission received', 'beyondinfinity' ), 'text' => __( 'Our team reviews your submission.', 'beyondinfinity' ) ],
    [ 'title' => __( 'Clarification (if needed)', 'beyondinfinity' ), 'text' => __( 'We contact you if we need more information.', 'beyondinfinity' ) ],
    [ 'title' => __( 'Next steps', 'beyondinfinity' ), 'text' => __( 'You receive next steps or tutor matches.', 'beyondinfinity' ) ],
    [ 'title' => __( 'Lessons begin', 'beyondinfinity' ), 'text' => __( 'Online, in-person, or hybrid — your choice.', 'beyondinfinity' ) ],
  ],
];
$timeline = $timelines[ $type ] ?? $timelines['general'];
?>

<section class="ngt-section">
  <div class="ngt-container">
    <div class="ngt-card bi-thankyou ngt-animate">
      <div class="bi-thankyou__icon" aria-hidden="true">✓</div>
      <h1 class="bi-thankyou__title"><?php esc_html_e( 'Submission Received', 'beyondinfinity' ); ?></h1>
      <p class="bi-thankyou__lead"><?php echo esc_html( $message ); ?></p>
      <p class="bi-thankyou__note"><?php echo esc_html( sprintf( __( 'We aim to respond within %d hours. For urgent academic support, WhatsApp or call us.', 'beyondinfinity' ), $hours ) ); ?></p>
      <div class="bi-hero__actions bi-thankyou__actions">
        <a href="<?php echo esc_url( bi_whatsapp_url() ); ?>" class="ngt-btn ngt-btn--secondary" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp Support', 'beyondinfinity' ); ?></a>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ngt-btn ngt-btn--outline"><?php esc_html_e( 'Back to Home', 'beyondinfinity' ); ?></a>
      </div>
    </div>

    <section class="bi-timeline ngt-animate" aria-labelledby="bi-timeline-title">
      <h2 id="bi-timeline-title" class="bi-timeline__title"><?php esc_html_e( 'What Happens Next', 'beyondinfinity' ); ?></h2>
      <ol class="bi-timeline__list">
        <?php foreach ( $timeline as $i => $step ) : ?>
          <li class="bi-timeline__item">
            <span class="bi-timeline__marker" aria-hidden="true"><?php echo esc_html( (string) ( $i + 1 ) ); ?></span>
            <div class="bi-timeline__body">
              <h3 class="bi-timeline__step-title"><?php echo esc_html( $step['title'] ); ?></h3>
              <p class="bi-timeline__step-text"><?php echo esc_html( $step['text'] ); ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
  </div>
</section>
