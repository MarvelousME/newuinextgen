<?php
/**
 * Tutor calendar template partial.
 *
 * @var array<string,mixed> $args
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tutor_id   = (int) ( $args['tutor_id'] ?? 0 );
$approved   = ! empty( $args['approved'] );
$suspended  = ! empty( $args['suspended'] );
$incomplete = ! empty( $args['incomplete'] );

if ( ! $tutor_id || ! $approved || $suspended || $incomplete ) {
	return;
}

$book_url   = function_exists( 'bi_tutor_booking_url' )
	? bi_tutor_booking_url( $tutor_id )
	: home_url( '/find-a-tutor/?ngc_tutor_id=' . $tutor_id );
$tutor_name = (string) get_the_title( $tutor_id );
?>
<div class="ngt-card ngt-animate bi-tutor-calendar-card">
	<h2 class="bi-tutor-calendar-card__title"><?php esc_html_e( 'Booking Calendar', 'beyondinfinity' ); ?></h2>
	<p class="bi-tutor-calendar-card__lead">
		<?php esc_html_e( 'View available and booked times. Private learner, parent and payment data is never shown publicly.', 'beyondinfinity' ); ?>
	</p>
	<?php echo do_shortcode( '[nextgen_tutor_calendar tutor_id="' . (int) $tutor_id . '" view="week" show_filters="yes"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="bi-hero__actions bi-tutor-calendar-card__actions">
		<a
			href="<?php echo esc_url( $book_url ); ?>"
			class="ngt-btn ngt-btn--primary bi-book-lesson-trigger"
			data-bi-booking-drawer="1"
			data-tutor-id="<?php echo esc_attr( (string) $tutor_id ); ?>"
			data-tutor-name="<?php echo esc_attr( $tutor_name ); ?>"
		><?php esc_html_e( 'Book lesson', 'beyondinfinity' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/contact/?ngc_tutor_id=' . (int) $tutor_id ) ); ?>" class="ngt-btn ngt-btn--outline"><?php esc_html_e( 'Request custom time', 'beyondinfinity' ); ?></a>
	</div>
</div>
