<?php
/**
 * Template part: single page content (default page.php).
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="ngt-page-hero">
	<div class="ngt-container">
		<span class="ngt-page-hero-eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
		<h1 class="ngt-page-hero-title"><?php the_title(); ?></h1>
	</div>
</section>

<div class="ngt-container ngt-section">
	<article <?php post_class( 'ngt-prose' ); ?>>
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
		?>
	</article>
</div>
