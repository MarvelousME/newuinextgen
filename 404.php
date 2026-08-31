<?php
/**
 * 404 — not found.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( function_exists( 'bi_kinetic_shell_open' ) ) {
	bi_kinetic_shell_open( 'error-404' );
} else {
	echo '<div class="ngt-container ngt-section">';
}
?>

<section class="ng-page-section ngt-section ng-page-section--glass ng-reveal">
	<div class="ng-container ng-container--boxed">
		<div class="ngt-404">
			<span class="ngt-404-code">404</span>
			<h1 class="ngt-404-title"><?php esc_html_e( 'This page took a study break.', 'nextgen-tutors' ); ?></h1>
			<p class="ngt-404-text"><?php esc_html_e( 'The page you are looking for could not be found. Let’s get you back on track.', 'nextgen-tutors' ); ?></p>
			<div class="ngt-404-actions">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ngt-btn ngt-btn-primary"><?php esc_html_e( 'Back to Home', 'nextgen-tutors' ); ?></a>
				<?php if ( function_exists( 'ngt_cta_button' ) ) : ?>
					<?php ngt_cta_button( 'find-a-tutor' ); ?>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/find-a-tutor/' ) ); ?>" class="ngt-btn ngt-btn-primary"><?php esc_html_e( 'Find a Tutor', 'nextgen-tutors' ); ?></a>
				<?php endif; ?>
			</div>
			<div class="ngt-404-search">
				<?php get_search_form(); ?>
			</div>
		</div>
	</div>
</section>

<?php
if ( function_exists( 'bi_kinetic_shell_close' ) ) {
	bi_kinetic_shell_close( 'error-404' );
} else {
	echo '</div>';
}

get_footer();
