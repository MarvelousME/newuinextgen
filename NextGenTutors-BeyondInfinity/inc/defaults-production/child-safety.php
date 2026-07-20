<?php
/** Default theme content — used when no page builder content is present. */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

bi_hero( __( 'Child Safety Policy', 'beyondinfinity' ), __( 'How NextGen Tutors protects learners under 18.', 'beyondinfinity' ) );
?>

<section class="ngt-section">
  <div class="ngt-container bi-legal ngt-animate">
    <h2><?php esc_html_e( 'Our Commitment', 'beyondinfinity' ); ?></h2>
    <p><?php esc_html_e( 'NextGen Tutors is committed to providing a safe environment for children and young people using our tutoring services.', 'beyondinfinity' ); ?></p>

    <h2><?php esc_html_e( 'Parent & Guardian Responsibility', 'beyondinfinity' ); ?></h2>
    <p><?php esc_html_e( 'Learners under 18 must be registered by a parent or legal guardian. Guardians are responsible for supervising tutoring arrangements, especially in-person sessions in the home.', 'beyondinfinity' ); ?></p>

    <h2><?php esc_html_e( 'Tutor Standards', 'beyondinfinity' ); ?></h2>
    <p><?php esc_html_e( 'Tutors undergo manual review before matching. We expect professional conduct, appropriate communication, and immediate reporting of any safeguarding concern.', 'beyondinfinity' ); ?></p>

    <h2><?php esc_html_e( 'Session Safety', 'beyondinfinity' ); ?></h2>
  <?php bi_bullets( [
    __( 'In-person sessions require a guardian present for minors.', 'beyondinfinity' ),
    __( 'Online sessions should occur in an appropriate shared space where possible.', 'beyondinfinity' ),
    __( 'Platform messaging should be used for scheduling and academic communication.', 'beyondinfinity' ),
    __( 'No tutor should request unnecessary personal information from a learner.', 'beyondinfinity' ),
  ] ); ?>

    <h2><?php esc_html_e( 'Reporting Concerns', 'beyondinfinity' ); ?></h2>
    <p><?php printf( esc_html__( 'Report any safeguarding concern immediately to %s or call %s.', 'beyondinfinity' ), '<a href="mailto:' . esc_attr( bi_get_support_email() ) . '">' . esc_html( bi_get_support_email() ) . '</a>', esc_html( bi_get_phone() ) ); ?></p>

    <p style="margin-top:32px"><a href="<?php echo esc_url( home_url( '/safety-guide' ) ); ?>" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'View Safety Guide', 'beyondinfinity' ); ?></a></p>
  </div>
</section>
