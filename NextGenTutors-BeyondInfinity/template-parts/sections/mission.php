<?php
/**
 * Section: Mission / Vision / Purpose. Ports the 3-card grid.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cards = array(
	array(
		'tone'  => 'indigo',
		'label' => __( 'Our Mission', 'nextgen-tutors' ),
		'title' => __( 'Empowering Access', 'nextgen-tutors' ),
		'desc'  => __( 'To make exceptional one-on-one academic support accessible and affordable to every learner in South Africa.', 'nextgen-tutors' ),
	),
	array(
		'tone'  => 'emerald',
		'label' => __( 'Our Vision', 'nextgen-tutors' ),
		'title' => __( 'Unlocking Potential', 'nextgen-tutors' ),
		'desc'  => __( 'A South Africa where no learner’s potential is limited by what their school alone can offer — where every child has a champion in their corner.', 'nextgen-tutors' ),
	),
	array(
		'tone'  => 'slate',
		'label' => __( 'Our Purpose', 'nextgen-tutors' ),
		'title' => __( 'Bridging Gaps', 'nextgen-tutors' ),
		'desc'  => __( 'Closing the academic divide with vetted educators, transparent pricing, and a safe, platform-managed experience for families.', 'nextgen-tutors' ),
	),
);
?>
<section class="ngt-mission" data-reveal>
	<?php foreach ( $cards as $c ) : ?>
		<div class="ngt-mission-card ngt-mission-<?php echo esc_attr( $c['tone'] ); ?>">
			<span class="ngt-mission-label"><?php echo esc_html( $c['label'] ); ?></span>
			<h3 class="ngt-mission-title"><?php echo esc_html( $c['title'] ); ?></h3>
			<p class="ngt-mission-desc"><?php echo esc_html( $c['desc'] ); ?></p>
		</div>
	<?php endforeach; ?>
</section>
