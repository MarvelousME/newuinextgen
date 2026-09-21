<?php
/**
 * Single post.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( function_exists( 'bi_kinetic_shell_open' ) ) {
	bi_kinetic_shell_open();
} else {
	echo '<div class="ngt-container ngt-section">';
}
?>
<section class="ng-page-section ngt-section ng-page-section--glass ng-reveal">
	<div class="ng-container ng-container--boxed">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content', 'post' );

			the_post_navigation(
				array(
					'prev_text' => '<span class="ngt-navsub">' . esc_html__( 'Previous', 'nextgen-tutors' ) . '</span> %title',
					'next_text' => '<span class="ngt-navsub">' . esc_html__( 'Next', 'nextgen-tutors' ) . '</span> %title',
				)
			);

			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		endwhile;
		?>
	</div>
</section>
<?php
if ( function_exists( 'bi_kinetic_shell_close' ) ) {
	bi_kinetic_shell_close();
} else {
	echo '</div>';
}

get_footer();
