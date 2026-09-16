<?php
/**
 * Section: How It Works (4-step path). Ports the React "Path to Academic Success".
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$steps = array(
	array( '01', __( 'Tell Us Your Needs', 'nextgen-tutors' ), __( 'Submit your learner’s grade, subject and goals in under two minutes.', 'nextgen-tutors' ) ),
	array( '02', __( 'Get Matched', 'nextgen-tutors' ), __( 'Receive 2–3 vetted tutor profiles with ratings and rates within 24 hours.', 'nextgen-tutors' ) ),
	array( '03', __( 'Risk-Free First Lesson', 'nextgen-tutors' ), __( 'Meet your tutor. Not the right fit? The first session is free.', 'nextgen-tutors' ) ),
	array( '04', __( 'Track Progress', 'nextgen-tutors' ), __( 'Follow session notes, feedback and grade improvement from your dashboard.', 'nextgen-tutors' ) ),
);
?>
<section class="ngt-how" data-reveal>
	<?php
	ngt_section_heading(
		'',
		__( 'The Path to Academic Success', 'nextgen-tutors' ),
		__( 'From first enquiry to measurable grade gains — a simple, supported journey.', 'nextgen-tutors' )
	);
	?>

	<div class="ngt-how-grid">
		<?php foreach ( $steps as $st ) : ?>
			<div class="ngt-how-step">
				<span class="ngt-how-num"><?php echo esc_html( $st[0] ); ?></span>
				<h3 class="ngt-how-title"><?php echo esc_html( $st[1] ); ?></h3>
				<p class="ngt-how-desc"><?php echo esc_html( $st[2] ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</section>
