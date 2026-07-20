<?php
/**
 * Archive (category, tag, date, author).
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="ngt-container ngt-section">

	<?php if ( have_posts() ) : ?>

		<header class="ngt-archive-head">
			<?php
			the_archive_title( '<h1 class="ngt-archive-title">', '</h1>' );
			the_archive_description( '<div class="ngt-archive-desc">', '</div>' );
			?>
		</header>

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
			<h1><?php esc_html_e( 'Nothing found', 'nextgen-tutors' ); ?></h1>
			<?php get_search_form(); ?>
		</div>

	<?php endif; ?>

</div>

<?php
get_footer();
