<?php
/**
 * Template Name: NextGen App (Plugin-powered)
 * Template Post Type: page
 *
 * Renders an interactive app route (Login, Register, Dashboard, etc.) by calling
 * the BeyondInfinity-Companion shortcode mapped to the page slug. Degrades
 * gracefully when the plugin is inactive.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$ngt_slug = get_post_field( 'post_name', get_the_ID() );
	?>

	<section class="ngt-page-hero ngt-page-hero--compact">
		<div class="ngt-container">
			<span class="ngt-page-hero-eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			<h1 class="ngt-page-hero-title"><?php the_title(); ?></h1>
		</div>
	</section>

	<div class="ngt-container ngt-section">
		<div class="ngt-app-shell">
			<?php
			// Optional intro content from the page editor.
			if ( trim( get_the_content() ) ) {
				echo '<div class="ngt-prose ngt-app-intro">';
				the_content();
				echo '</div>';
			}
			ngt_render_route_app( $ngt_slug );
			?>
		</div>
	</div>

	<?php
endwhile;

get_footer();
