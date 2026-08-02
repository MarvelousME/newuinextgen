<?php
/** Default theme content — used when no page builder content is present. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$role = sanitize_key( $_GET['role'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( ! in_array( $role, [ 'parent', 'student', 'tutor' ], true ) ) {
	$role = '';
}

$login_failed = isset( $_GET['login'] ) && 'failed' === sanitize_key( wp_unslash( $_GET['login'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$role_copy = [
	'parent'  => [
		'title' => __( 'Continue as a Parent', 'beyondinfinity' ),
		'lead'  => __( 'Sign in to manage learners, bookings, invoices and safety controls.', 'beyondinfinity' ),
	],
	'student' => [
		'title' => __( 'Continue as a Student', 'beyondinfinity' ),
		'lead'  => __( 'Sign in to see your next lesson, progress and bookings.', 'beyondinfinity' ),
	],
	'tutor'   => [
		'title' => __( 'Continue as a Tutor', 'beyondinfinity' ),
		'lead'  => __( 'Sign in to your calendar, earnings and application status.', 'beyondinfinity' ),
	],
];

bi_hero(
	__( 'Login to Your Dashboard', 'beyondinfinity' ),
	__( 'Choose how you continue — we route you to the right home after you sign in.', 'beyondinfinity' )
);
?>

<section class="ngt-section">
  <div class="ngt-container bi-auth bi-narrow">
    <div class="bi-role-selector" role="group" aria-label="<?php esc_attr_e( 'Choose how you want to continue', 'beyondinfinity' ); ?>">
      <a
        href="<?php echo esc_url( add_query_arg( 'role', 'parent', get_permalink() ) ); ?>"
        class="bi-role-card<?php echo 'parent' === $role ? ' is-active' : ''; ?>"
        <?php echo 'parent' === $role ? 'aria-current="page"' : ''; ?>
        id="bi-login-role-parent"
      >
        <span class="bi-role-card__eyebrow"><?php esc_html_e( 'Families', 'beyondinfinity' ); ?></span>
        <h2 class="bi-role-card__title"><?php esc_html_e( 'I’m a Parent', 'beyondinfinity' ); ?></h2>
        <p class="bi-role-card__desc"><?php esc_html_e( 'Book tutors, manage learners and stay on top of invoices.', 'beyondinfinity' ); ?></p>
      </a>
      <a
        href="<?php echo esc_url( add_query_arg( 'role', 'student', get_permalink() ) ); ?>"
        class="bi-role-card<?php echo 'student' === $role ? ' is-active' : ''; ?>"
        <?php echo 'student' === $role ? 'aria-current="page"' : ''; ?>
        id="bi-login-role-student"
      >
        <span class="bi-role-card__eyebrow"><?php esc_html_e( 'Adult learners', 'beyondinfinity' ); ?></span>
        <h2 class="bi-role-card__title"><?php esc_html_e( 'I’m a Student 18+', 'beyondinfinity' ); ?></h2>
        <p class="bi-role-card__desc"><?php esc_html_e( 'Track sessions, homework and your learning streak.', 'beyondinfinity' ); ?></p>
      </a>
      <a
        href="<?php echo esc_url( add_query_arg( 'role', 'tutor', get_permalink() ) ); ?>"
        class="bi-role-card<?php echo 'tutor' === $role ? ' is-active' : ''; ?>"
        <?php echo 'tutor' === $role ? 'aria-current="page"' : ''; ?>
        id="bi-login-role-tutor"
      >
        <span class="bi-role-card__eyebrow"><?php esc_html_e( 'Educators', 'beyondinfinity' ); ?></span>
        <h2 class="bi-role-card__title"><?php esc_html_e( 'I’m a Tutor', 'beyondinfinity' ); ?></h2>
        <p class="bi-role-card__desc"><?php esc_html_e( 'Open your calendar, payouts and application status.', 'beyondinfinity' ); ?></p>
      </a>
    </div>

    <?php if ( '' === $role ) : ?>
      <div class="ngt-card bi-surface-card ngt-animate bi-register__hint" role="status">
        <p class="bi-copy-flush"><?php esc_html_e( 'Select a path above to open the sign-in form. After you authenticate, we send you to the dashboard that matches your account role.', 'beyondinfinity' ); ?></p>
      </div>
    <?php else : ?>
      <div class="ngt-card ngt-animate bi-register__panel bi-login__panel" aria-labelledby="bi-login-role-<?php echo esc_attr( $role ); ?>">
        <h2 class="bi-register__panel-title"><?php echo esc_html( $role_copy[ $role ]['title'] ); ?></h2>
        <p class="bi-register__panel-lead"><?php echo esc_html( $role_copy[ $role ]['lead'] ); ?></p>

        <?php if ( $login_failed ) : ?>
          <div class="ngc-form-error-summary bi-login__error" role="alert" tabindex="-1">
            <p class="ngc-form-error-summary__title"><?php esc_html_e( 'Sign-in didn’t work', 'beyondinfinity' ); ?></p>
            <ul>
              <li><?php esc_html_e( 'Check your email/username and password, then try again.', 'beyondinfinity' ); ?></li>
              <li><?php esc_html_e( 'Caps Lock can change your password — toggle it off if needed.', 'beyondinfinity' ); ?></li>
              <li><a href="#bi-login-forgot"><?php esc_html_e( 'Reset your password', 'beyondinfinity' ); ?></a> <?php esc_html_e( 'if you’ve forgotten it — reset emails usually arrive within a few minutes.', 'beyondinfinity' ); ?></li>
            </ul>
          </div>
        <?php endif; ?>

        <?php bi_shortcode_block( '[ngc_login_form]', __( 'Sign In', 'beyondinfinity' ) ); ?>

        <p class="bi-login__switch">
          <?php
          printf(
              /* translators: %s: register URL */
              esc_html__( 'New here? %s', 'beyondinfinity' ),
              '<a href="' . esc_url( add_query_arg( 'role', $role, home_url( '/register/' ) ) ) . '">' . esc_html__( 'Create an account', 'beyondinfinity' ) . '</a>'
          );
          ?>
        </p>
      </div>

      <div id="bi-login-forgot" class="ngt-card bi-surface-card ngt-animate bi-login__forgot">
        <h2 class="bi-login__forgot-title"><?php esc_html_e( 'Forgot Password', 'beyondinfinity' ); ?></h2>
        <p class="bi-login__forgot-lead"><?php esc_html_e( 'Request a reset link. Expect the email within a few minutes — check spam if it hasn’t arrived after 10 minutes.', 'beyondinfinity' ); ?></p>
        <?php bi_render_shortcode( '[ngc_forgot_password_form]' ); ?>
      </div>
    <?php endif; ?>
  </div>
</section>
