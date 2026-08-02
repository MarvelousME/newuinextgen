<?php
/**
 * Booking drawer — progressive enhancement over calendar slot CTAs.
 *
 * Keeps tutor + slot context in an in-place panel instead of navigating
 * away to a blank find-a-tutor page (audit friction §4.1).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'bi_booking_drawer_assets', 40 );
// Priority 5 keeps the markup ahead of wp_print_footer_scripts (priority 20).
add_action( 'wp_footer', 'bi_booking_drawer_markup', 5 );

/**
 * Pages that may surface tutor calendar slots.
 *
 * @return bool
 */
function bi_booking_drawer_needed() {
	if ( is_admin() || bi_is_builder_edit_mode() ) {
		return false;
	}
	if ( is_singular( 'tutors' ) || is_page( [ 'find-a-tutor', 'parent-checkout' ] ) ) {
		return true;
	}
	global $post;
	if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'nextgen_tutor_calendar' ) ) {
		return true;
	}
	return (bool) apply_filters( 'bi_booking_drawer_needed', false );
}

/**
 * Enqueue drawer assets when calendar CTAs may appear.
 */
function bi_booking_drawer_assets() {
	if ( ! bi_booking_drawer_needed() ) {
		return;
	}

	wp_enqueue_script( 'bi-focus-trap', BI_URI . '/assets/js/bi-focus-trap.js', [], BI_VERSION, true );
	wp_enqueue_style( 'bi-booking-drawer', BI_URI . '/assets/css/bi-booking-drawer.css', [ 'bi-style' ], BI_VERSION );
	wp_enqueue_script(
		'bi-booking-drawer',
		BI_URI . '/assets/js/bi-booking-drawer.js',
		[ 'bi-focus-trap' ],
		BI_VERSION,
		true
	);
}

/**
 * Drawer shell — single instance in the footer.
 */
function bi_booking_drawer_markup() {
	if ( ! bi_booking_drawer_needed() ) {
		return;
	}

	$continue = home_url( '/find-a-tutor/' );
	if ( is_user_logged_in() ) {
		$continue = home_url( '/parent-checkout/' );
	}

	$login = home_url( '/login/' );
	$guarantee = home_url( '/guarantee/' );
	?>
	<div
		id="bi-booking-drawer"
		class="bi-booking-drawer"
		role="presentation"
		aria-hidden="true"
		data-continue-url="<?php echo esc_url( $continue ); ?>"
		data-login-url="<?php echo esc_url( $login ); ?>"
		data-logged-in="<?php echo is_user_logged_in() ? '1' : '0'; ?>"
	>
		<div
			class="bi-booking-drawer__panel"
			role="dialog"
			aria-modal="true"
			aria-labelledby="bi-booking-drawer-title"
			tabindex="-1"
		>
			<div class="bi-booking-drawer__head">
				<h2 id="bi-booking-drawer-title" data-bi-bd-title><?php esc_html_e( 'Confirm this time', 'beyondinfinity' ); ?></h2>
				<button type="button" class="bi-booking-drawer__close" data-bi-bd-close aria-label="<?php esc_attr_e( 'Close booking panel', 'beyondinfinity' ); ?>">×</button>
			</div>
			<div data-bi-bd-meta>
				<p><?php esc_html_e( 'Select an available slot to continue.', 'beyondinfinity' ); ?></p>
			</div>
			<p class="bi-booking-drawer__trust" data-bi-bd-trust>
				<?php
				printf(
					/* translators: %s: guarantee page URL */
					wp_kses(
						__( 'Every tutor passes our 5-step vetting. Lessons are covered by the <a href="%s">NextGen100 first-lesson guarantee</a>.', 'beyondinfinity' ),
						[ 'a' => [ 'href' => [] ] ]
					),
					esc_url( $guarantee )
				);
				?>
			</p>
			<div class="bi-booking-drawer__actions">
				<a href="<?php echo esc_url( $continue ); ?>" class="ngt-btn ngt-btn--primary" data-bi-bd-continue>
					<?php esc_html_e( 'Continue to booking', 'beyondinfinity' ); ?>
				</a>
				<a href="<?php echo esc_url( $login ); ?>" class="ngt-btn ngt-btn--outline" data-bi-bd-login>
					<?php esc_html_e( 'Sign in to continue', 'beyondinfinity' ); ?>
				</a>
				<button type="button" class="ngt-btn ngt-btn--outline" data-bi-bd-close>
					<?php esc_html_e( 'Pick a different time', 'beyondinfinity' ); ?>
				</button>
			</div>
		</div>
	</div>
	<?php
}
