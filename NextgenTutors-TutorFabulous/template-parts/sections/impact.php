<?php
/**
 * Section: Academic impact showcase (dark, with animated counters).
 * Ports the "Our Academic Impact" gallery block. Counters animate via main.js.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stats = function_exists( 'bi_real_stat_cards' ) ? bi_real_stat_cards() : array();
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
				<span
					class="ngt-stat-num"
					<?php if ( (float) $s['count'] > 0 ) : ?>
						data-bi-count="<?php echo esc_attr( (string) $s['count'] ); ?>"
						data-bi-suffix="<?php echo esc_attr( $s['suffix'] ); ?>"
					<?php endif; ?>
				><?php echo esc_html( number_format_i18n( (float) $s['count'], is_float( $s['count'] ) ? 1 : 0 ) . $s['suffix'] ); ?></span>
				<span class="ngt-stat-label"><?php echo esc_html( $s['label'] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
</section>
