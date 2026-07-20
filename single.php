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
?>

<div class="ngt-container ngt-section">
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

<?php
get_footer();
