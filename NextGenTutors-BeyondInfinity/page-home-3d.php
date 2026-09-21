<?php
/**
 * Template Name: Home 3D Preview
 * Slug: home-3d
 *
 * Full kinetic homepage copy with curated 3D Scroll Manager presets.
 * Does not replace the live front page.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="primary" class="ngt-home-3d-preview" data-ngt-home-3d="1">
	<div class="ngt-home-3d-preview__banner" role="status">
		<div class="ngt-container">
			<strong><?php esc_html_e( 'Home 3D Preview', 'beyondinfinity' ); ?></strong>
			<span><?php esc_html_e( 'Same homepage content with curated scroll animations. Live home is unchanged.', 'beyondinfinity' ); ?></span>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'View live home', 'beyondinfinity' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ngt-3d-scroll' ) ); ?>"><?php esc_html_e( 'Edit 3D rules', 'beyondinfinity' ); ?></a>
		</div>
	</div>
	<?php get_template_part( 'template-parts/pages/home' ); ?>
</main>
<?php
get_footer();
