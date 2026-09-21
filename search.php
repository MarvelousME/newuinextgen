<?php
/**
 * Search results.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( function_exists( 'bi_kinetic_shell_open' ) ) {
	bi_kinetic_shell_open( 'search' );
} else {
	echo '<div class="ngt-container ngt-section">';
}
?>

<section class="ng-page-section ngt-section ng-page-section--glass ng-reveal">
	<div class="ng-container ng-container--boxed">
		<header class="ngt-archive-head">
			<h1 class="ngt-archive-title">
				<?php
				printf(
					/* translators: %s: search query */
					esc_html__( 'Search results for "%s"', 'nextgen-tutors' ),
					esc_html( get_search_query() )
				);
				?>
			</h1>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="ngt-post-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', 'post' );
				endwhile;
				?>
			</div>
			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => esc_html__( 'Previous', 'nextgen-tutors' ),
					'next_text' => esc_html__( 'Next', 'nextgen-tutors' ),
				)
			);
			?>
		<?php else : ?>
			<div class="ngt-empty">
				<p><?php esc_html_e( 'No results matched your search. Try different keywords.', 'nextgen-tutors' ); ?></p>
				<?php get_search_form(); ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php
if ( function_exists( 'bi_kinetic_shell_close' ) ) {
	bi_kinetic_shell_close( 'search' );
} else {
	echo '</div>';
}

get_footer();
