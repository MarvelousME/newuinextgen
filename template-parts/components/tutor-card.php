<?php
/**
 * Component: tutor card. Ports the React ThreeDCard tutor tile.
 *
 * @param array $args { tutor }  // tutor = associative array from ngt_get_tutors()
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$t = isset( $args['tutor'] ) ? $args['tutor'] : array();
if ( empty( $t ) ) {
	return;
}

$subjects = isset( $t['subjects'] ) ? (array) $t['subjects'] : array();
?>
<article class="ngt-card ngt-card--tutor ngt-tutor-card ngt-tilt" data-tilt tabindex="0">
	<div class="ngt-tutor-top">
		<img class="ngt-tutor-avatar"
			src="<?php echo esc_url( $t['avatar'] ); ?>"
			alt="<?php echo esc_attr( $t['name'] ); ?>"
			loading="lazy" referrerpolicy="no-referrer" width="64" height="64" />
		<div class="ngt-tutor-id">
			<h3 class="ngt-tutor-name"><?php echo esc_html( $t['name'] ); ?></h3>
			<span class="ngt-tutor-loc"><?php echo esc_html( $t['location'] ); ?></span>
		</div>
		<span class="ngt-vetted" title="<?php esc_attr_e( 'ID-verified & SACE vetted', 'nextgen-tutors' ); ?>">✓ <?php esc_html_e( 'Vetted', 'nextgen-tutors' ); ?></span>
	</div>

	<div class="ngt-tutor-subjects">
		<?php foreach ( $subjects as $subject ) : ?>
			<span class="ngt-chip"><?php echo esc_html( $subject ); ?></span>
		<?php endforeach; ?>
		<span class="ngt-chip ngt-chip-muted"><?php echo esc_html( $t['grades'] ); ?></span>
	</div>

	<p class="ngt-tutor-bio"><?php echo esc_html( $t['bio'] ); ?></p>

	<div class="ngt-tutor-foot">
		<div class="ngt-tutor-rating">
			<span class="ngt-stars" aria-hidden="true"><?php echo esc_html( ngt_stars( $t['rating'] ) ); ?></span>
			<span class="ngt-rating-num"><?php echo esc_html( number_format_i18n( $t['rating'], 1 ) ); ?></span>
			<span class="ngt-reviews">(<?php echo esc_html( $t['reviews'] ); ?>)</span>
		</div>
		<div class="ngt-tutor-rate">
			<span class="ngt-rate-amount">R<?php echo esc_html( $t['rate'] ); ?></span>
			<span class="ngt-rate-unit"><?php esc_html_e( '/ hour', 'nextgen-tutors' ); ?></span>
		</div>
	</div>

	<a class="ngt-btn ngt-btn--primary ngt-tutor-cta" href="<?php echo esc_url( ngt_route_url( 'find-a-tutor' ) ); ?>">
		<?php esc_html_e( 'Request This Tutor', 'nextgen-tutors' ); ?>
	</a>
</article>
