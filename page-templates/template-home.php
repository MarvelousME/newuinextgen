<?php
/**
 * Template Name: NextGen Home (Marketing)
 * Template Post Type: page
 *
 * The full home composition ported from the React `home` route: hero carousel,
 * trust strip, WordPress/Elementor banner, mission/vision/purpose, vetted tutors,
 * services, how-it-works, impact showcase, and final CTA.
 *
 * Used by front-page.php and assignable to any page.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// When called directly as a page template (not via front-page include) load chrome.
$ngt_standalone = ( 'page-templates/template-home.php' === get_page_template_slug() ) && ! did_action( 'get_header' );
if ( $ngt_standalone ) {
	get_header();
}
?>

<div class="ngt-container ngt-home">

	<?php
	get_template_part( 'template-parts/sections/hero' );
	get_template_part( 'template-parts/sections/trust-strip' );
	get_template_part( 'template-parts/sections/wp-banner' );
	get_template_part( 'template-parts/sections/mission' );
	get_template_part( 'template-parts/sections/tutors' );
	get_template_part( 'template-parts/sections/services' );
	get_template_part( 'template-parts/sections/how-it-works' );
	get_template_part( 'template-parts/sections/impact' );

	// If this page has editor content, render it between the showcase and CTA.
	if ( is_page() && ! is_front_page() ) {
		while ( have_posts() ) :
			the_post();
			if ( trim( get_the_content() ) ) {
				echo '<section class="ngt-page-content ngt-prose">';
				the_content();
				echo '</section>';
			}
		endwhile;
	}

	get_template_part( 'template-parts/sections/final-cta' );
	?>

</div>

<?php
if ( $ngt_standalone ) {
	get_footer();
}
