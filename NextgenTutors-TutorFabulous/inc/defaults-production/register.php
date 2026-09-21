<?php
/** Default theme content — used when no page builder content is present. */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$role = sanitize_key( $_GET['role'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( ! in_array( $role, [ 'parent', 'student', 'tutor' ], true ) ) {
    $role = '';
}

bi_hero( __( 'Register with NextGen Tutors', 'beyondinfinity' ), __( 'Choose the path that matches you — then complete a focused form.', 'beyondinfinity' ) );
?>

<section class="ngt-section">
  <div class="ngt-container bi-register">
    <div class="bi-role-selector" role="group" aria-label="<?php esc_attr_e( 'Choose how you want to register', 'beyondinfinity' ); ?>">
      <a
        href="<?php echo esc_url( add_query_arg( 'role', 'parent', get_permalink() ) ); ?>"
        class="bi-role-card<?php echo 'parent' === $role ? ' is-active' : ''; ?>"
        <?php echo 'parent' === $role ? 'aria-current="page"' : ''; ?>
        id="bi-role-parent"
      >
        <span class="bi-role-card__eyebrow"><?php esc_html_e( 'Families', 'beyondinfinity' ); ?></span>
        <h2 class="bi-role-card__title"><?php esc_html_e( 'I’m a Parent', 'beyondinfinity' ); ?></h2>
        <p class="bi-role-card__desc"><?php esc_html_e( 'Register a learner under 18 and manage bookings, invoices and safety controls.', 'beyondinfinity' ); ?></p>
      </a>
      <a
        href="<?php echo esc_url( add_query_arg( 'role', 'student', get_permalink() ) ); ?>"
        class="bi-role-card<?php echo 'student' === $role ? ' is-active' : ''; ?>"
        <?php echo 'student' === $role ? 'aria-current="page"' : ''; ?>
        id="bi-role-student"
      >
        <span class="bi-role-card__eyebrow"><?php esc_html_e( 'Adult learners', 'beyondinfinity' ); ?></span>
        <h2 class="bi-role-card__title"><?php esc_html_e( 'I’m a Student 18+', 'beyondinfinity' ); ?></h2>
        <p class="bi-role-card__desc"><?php esc_html_e( 'Register yourself for tutoring, track progress and book sessions.', 'beyondinfinity' ); ?></p>
      </a>
      <a
        href="<?php echo esc_url( home_url( '/become-a-tutor/' ) ); ?>"
        class="bi-role-card"
        id="bi-role-tutor"
      >
        <span class="bi-role-card__eyebrow"><?php esc_html_e( 'Educators', 'beyondinfinity' ); ?></span>
        <h2 class="bi-role-card__title"><?php esc_html_e( 'I’m a Tutor', 'beyondinfinity' ); ?></h2>
        <p class="bi-role-card__desc"><?php esc_html_e( 'Apply to join the vetted tutor network — earnings calculator included.', 'beyondinfinity' ); ?></p>
      </a>
    </div>

    <?php if ( '' === $role ) : ?>
      <div class="ngt-card bi-surface-card ngt-animate bi-register__hint" role="status">
        <p class="bi-copy-flush"><?php esc_html_e( 'Select a role above to open the matching registration form. This keeps the page short and reduces mistakes.', 'beyondinfinity' ); ?></p>
      </div>
    <?php elseif ( 'parent' === $role ) : ?>
      <div class="ngt-card ngt-animate bi-register__panel" aria-labelledby="bi-role-parent">
        <h2 class="bi-register__panel-title"><?php esc_html_e( 'Parent Registering a Child', 'beyondinfinity' ); ?></h2>
        <p class="bi-register__panel-lead"><?php esc_html_e( 'For parents or guardians registering a learner under 18.', 'beyondinfinity' ); ?></p>
        <div class="bi-trust-chip-row bi-trust-chip-row--start" role="note">
          <?php bi_trust_chip( __( 'Built around parent consent and child-safe tutoring', 'beyondinfinity' ), home_url( '/child-safety/' ) ); ?>
        </div>
        <?php bi_render_shortcode( '[ngc_parent_register_child_form]' ); ?>
        <?php bi_safety_notice( 'parent' ); ?>
      </div>
    <?php else : ?>
      <div class="ngt-card ngt-animate bi-register__panel" aria-labelledby="bi-role-student">
        <h2 class="bi-register__panel-title"><?php esc_html_e( 'Student 18+', 'beyondinfinity' ); ?></h2>
        <p class="bi-register__panel-lead"><?php esc_html_e( 'For students aged 18 or older registering themselves.', 'beyondinfinity' ); ?></p>
        <?php bi_render_shortcode( '[ngc_student_register_form]' ); ?>
      </div>
    <?php endif; ?>

    <div class="ngt-card bi-surface-card ngt-animate">
      <h3 class="bi-mb-xs"><?php esc_html_e( 'Account Activation', 'beyondinfinity' ); ?></h3>
      <p class="bi-copy-flush"><?php esc_html_e( 'After registration, you may be asked to verify your email address before accessing your dashboard.', 'beyondinfinity' ); ?></p>
    </div>
  </div>
</section>
