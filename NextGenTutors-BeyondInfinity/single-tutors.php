<?php
/**
 * Single tutor profile (tutors CPT).
 *
 * @package BeyondInfinity
 */

get_header();
?>
<main id="primary" class="site-main bi-theme-main bi-tutor-profile-main">
<?php
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		$tutor = bi_format_tutor_post( get_post() );
		bi_render_tutor_profile( $tutor );
	}
} else {
	$demo = function_exists( 'bi_get_demo_tutors' ) ? bi_get_demo_tutors( 1 ) : [];
	if ( ! empty( $demo[0] ) ) {
		$demo[0]['permalink'] = home_url( '/find-a-tutor/' );
		bi_render_tutor_profile( $demo[0] );
	} else {
		echo '<div class="ngt-container" style="padding:48px 0"><p>' . esc_html__( 'Tutor profile unavailable.', 'beyondinfinity' ) . '</p>';
		echo '<p><a class="ngt-btn ngt-btn--primary" href="' . esc_url( home_url( '/find-a-tutor/' ) ) . '">' . esc_html__( 'Browse tutors', 'beyondinfinity' ) . '</a></p></div>';
	}
}
?>
</main>
<?php
get_footer();
