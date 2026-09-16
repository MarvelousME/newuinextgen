<?php
/**
 * Template Name: Full Width (No Sidebar)
 * Template Post Type: page
 *
 * Used for the marketing sub-routes (Find a Tutor, Pricing, Vetting, Safety,
 * About, Contact). Renders a branded page hero from the title/excerpt, then the
 * page's editor content full-bleed, plus a shortcode-ready zone so booking/LMS
 * widgets (Amelia, GamiPress, forms) drop straight in.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<section class="ngt-page-hero">
		<div class="ngt-container">
			<span class="ngt-page-hero-eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			<h1 class="ngt-page-hero-title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="ngt-page-hero-sub"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<div class="ngt-container ngt-section">
		<article <?php post_class( 'ngt-fullwidth ngt-prose' ); ?>>
			<?php
			if ( has_post_thumbnail() ) {
				echo '<div class="ngt-feature-image">';
				the_post_thumbnail( 'ngt-card' );
				echo '</div>';
			}
			the_content();
			wp_link_pages(
				array(
					'before' => '<div class="ngt-page-links">' . esc_html__( 'Pages:', 'nextgen-tutors' ),
					'after'  => '</div>',
				)
			);

			// Integration contract: render the plugin shortcode mapped to this slug.
			ngt_render_route_app( get_post_field( 'post_name', get_the_ID() ) );
			?>
		</article>

		<?php get_template_part( 'template-parts/sections/final-cta' ); ?>
	</div>

	<?php
endwhile;

get_footer();
