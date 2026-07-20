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
?>
<div class="ngt-card ngt-animate" style="padding:28px;margin-bottom:24px">
	<h2 style="margin-bottom:12px"><?php esc_html_e( 'Booking Calendar', 'beyondinfinity' ); ?></h2>
	<p style="margin:0 0 16px;color:var(--ngt-text-2)">
		<?php esc_html_e( 'View available and booked times. Private learner, parent and payment data is never shown publicly.', 'beyondinfinity' ); ?>
	</p>
	<?php echo do_shortcode( '[nextgen_tutor_calendar tutor_id="' . (int) $tutor_id . '" view="week" show_filters="yes"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="bi-hero__actions" style="margin-top:14px">
		<a href="<?php echo esc_url( home_url( '/find-a-tutor?ngc_tutor_id=' . (int) $tutor_id ) ); ?>" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'Book lesson', 'beyondinfinity' ); ?></a>
		<a href="<?php echo esc_url( home_url( '/contact?ngc_tutor_id=' . (int) $tutor_id ) ); ?>" class="ngt-btn ngt-btn--outline"><?php esc_html_e( 'Request custom time', 'beyondinfinity' ); ?></a>
	</div>
</div>

