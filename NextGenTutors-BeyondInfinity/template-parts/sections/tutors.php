<?php
/**
 * Section: Meet Our Vetted Tutors. Loops ngt_get_tutors() into reusable cards.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tutors = ngt_get_tutors();
?>
<section class="ngt-tutors" data-reveal>
	<?php
	ngt_section_heading(
		__( 'Expert South African Educators', 'nextgen-tutors' ),
		__( 'Meet Our Vetted Tutors', 'nextgen-tutors' ),
		__( 'Every educator is ID-verified, SACE-registered, and hand-reviewed by our academic panel before they ever meet a learner.', 'nextgen-tutors' )
	);
	?>

	<?php
	// Prefer the live plugin marketplace carousel (real tutor CPT data) when available.
	if ( ngt_plugin_active() && shortcode_exists( 'ngc_tutor_carousel' ) ) {
		echo do_shortcode( '[ngc_tutor_carousel count="6"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by plugin.
	} else {
		?>
		<div class="ngt-tutor-grid">
			<?php
			foreach ( ngt_get_tutors() as $tutor ) {
				get_template_part(
					'template-parts/components/tutor-card',
					null,
					array( 'tutor' => $tutor )
				);
			}
			?>
		</div>
		<?php
	}
	?>

	<div class="ngt-tutors-foot">
		<?php ngt_cta_button( 'find-a-tutor', __( 'Browse All Tutors', 'nextgen-tutors' ) ); ?>
	</div>
</section>
