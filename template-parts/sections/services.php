<?php
/**
 * Section: Tailored Academic Support Services (4-up). Ports the services grid.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$services = array(
	array( 'pin', __( 'In-Person Tutoring', 'nextgen-tutors' ), __( 'Vetted educators travel to you across Johannesburg and major suburbs for focused, distraction-free sessions.', 'nextgen-tutors' ) ),
	array( 'check', __( 'Online Live Sessions', 'nextgen-tutors' ), __( 'Interactive whiteboard lessons nationwide — same vetted tutors, no travel, fully recorded for revision.', 'nextgen-tutors' ) ),
	array( 'clock', __( 'Flexible Scheduling', 'nextgen-tutors' ), __( 'Book around school, sport and exams. Reschedule freely through your parent dashboard.', 'nextgen-tutors' ) ),
	array( 'award', __( 'Exam & Matric Prep', 'nextgen-tutors' ), __( 'Targeted CAPS, IEB and Cambridge exam strategy, past-paper drills, and trial preparation.', 'nextgen-tutors' ) ),
);

/**
 * Tiny inline icon set (replaces lucide-react).
 */
$ngt_icon = function ( $name ) {
	$icons = array(
		'pin'   => '<path d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
		'check' => '<path d="m20 6-11 11-5-5"/>',
		'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'award' => '<circle cx="12" cy="8" r="5"/><path d="M8.5 12.5 7 21l5-3 5 3-1.5-8.5"/>',
	);
	$p = isset( $icons[ $name ] ) ? $icons[ $name ] : '';
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
};
?>
<section class="ngt-services" data-reveal>
	<?php
	ngt_section_heading(
		'',
		__( 'Tailored Academic Support Services', 'nextgen-tutors' ),
		__( 'One platform for every learning format — matched to your learner’s grade, subject and pace.', 'nextgen-tutors' )
	);
	?>

	<div class="ngt-services-grid">
		<?php foreach ( $services as $s ) : ?>
			<div class="ngt-service-card">
				<div class="ngt-service-icon"><?php echo $ngt_icon( $s[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. ?></div>
				<h3 class="ngt-service-title"><?php echo esc_html( $s[1] ); ?></h3>
				<p class="ngt-service-desc"><?php echo esc_html( $s[2] ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</section>
