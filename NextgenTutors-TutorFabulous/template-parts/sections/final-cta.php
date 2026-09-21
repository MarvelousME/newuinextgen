<?php
/**
 * Section: Final CTA band. Ports the indigo "Your learner does not need to struggle alone."
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="ngt-finalcta" data-reveal>
	<h2 class="ngt-finalcta-title"><?php esc_html_e( 'Your learner does not need to struggle alone.', 'nextgen-tutors' ); ?></h2>
	<p class="ngt-finalcta-sub"><?php esc_html_e( 'Match with a vetted South African educator today — risk-free first lesson, transparent pricing, real results.', 'nextgen-tutors' ); ?></p>
	<div class="ngt-finalcta-actions">
		<?php
		ngt_cta_button( 'find-a-tutor', __( 'Get a Tutor Today', 'nextgen-tutors' ) );
		printf(
			'<a class="ngt-btn ngt-btn-ghost-dark" href="%1$s">%2$s</a>',
			esc_url( ngt_route_url( 'become-a-tutor' ) ),
			esc_html( ngt_mod( 'ngt_cta_secondary' ) )
		);
		?>
	</div>
</section>
