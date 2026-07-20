<?php
/**
 * Section: Trust strip (stat row under the hero). Ports the React Trust Strip.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = array(
	array( __( 'Ages', 'nextgen-tutors' ), __( 'Grade 1 – Tertiary Support', 'nextgen-tutors' ), false ),
	array( __( 'Curricula', 'nextgen-tutors' ), __( 'CAPS, IEB & Cambridge', 'nextgen-tutors' ), false ),
	array( __( 'Formats', 'nextgen-tutors' ), __( 'Online, In-Person & Hybrid', 'nextgen-tutors' ), false ),
	array( __( 'Finance', 'nextgen-tutors' ), __( 'Platform-Managed Payments', 'nextgen-tutors' ), false ),
	array( __( 'Availability', 'nextgen-tutors' ), __( 'Across All 9 Provinces', 'nextgen-tutors' ), true ),
);
?>
<section class="ngt-trust" data-reveal>
	<div class="ngt-trust-grid">
		<?php foreach ( $items as $it ) : ?>
			<div class="ngt-trust-item">
				<span class="ngt-trust-label"><?php echo esc_html( $it[0] ); ?></span>
				<span class="ngt-trust-value<?php echo $it[2] ? ' is-accent' : ''; ?>"><?php echo esc_html( $it[1] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
</section>
