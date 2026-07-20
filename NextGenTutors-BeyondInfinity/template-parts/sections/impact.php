<?php
/**
 * Section: Academic impact showcase (dark, with animated counters).
 * Ports the "Our Academic Impact" gallery block. Counters animate via theme.js.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stats = array(
	array( 94, '%', __( 'Average grade improvement', 'nextgen-tutors' ) ),
	array( 9000, '+', __( 'Active learners & families', 'nextgen-tutors' ) ),
	array( 500, '+', __( 'Vetted educators nationwide', 'nextgen-tutors' ) ),
	array( 24, 'h', __( 'Average match turnaround', 'nextgen-tutors' ) ),
);
?>
<section class="ngt-impact" data-reveal>
	<div class="ngt-impact-glow" aria-hidden="true"></div>

	<div class="ngt-impact-left">
		<span class="ngt-eyebrow ngt-eyebrow-light"><?php esc_html_e( 'Our Academic Impact', 'nextgen-tutors' ); ?></span>
		<h2 class="ngt-impact-title"><?php esc_html_e( 'Real results for South African learners.', 'nextgen-tutors' ); ?></h2>
		<p class="ngt-impact-desc"><?php esc_html_e( 'We measure success by marks moved and confidence built — not just hours booked. Here is what the NextGen network delivers.', 'nextgen-tutors' ); ?></p>
		<?php ngt_cta_button( 'find-a-tutor', __( 'Start Today', 'nextgen-tutors' ) ); ?>
	</div>

	<div class="ngt-impact-stats">
		<?php foreach ( $stats as $s ) : ?>
			<div class="ngt-stat">
				<span class="ngt-stat-num" data-counter="<?php echo esc_attr( $s[0] ); ?>" data-suffix="<?php echo esc_attr( $s[1] ); ?>">0<?php echo esc_html( $s[1] ); ?></span>
				<span class="ngt-stat-label"><?php echo esc_html( $s[2] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
</section>
