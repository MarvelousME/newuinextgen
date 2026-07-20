<?php
/**
 * Template part: post in a list / single.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_single = is_singular( 'post' );
?>

<article <?php post_class( 'ngt-post-card' ); ?>>

	<?php if ( has_post_thumbnail() ) : ?>
		<a class="ngt-post-thumb" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( $is_single ? 'post-thumbnail' : 'ngt-card' ); ?>
		</a>
	<?php endif; ?>

	<div class="ngt-post-body">
		<?php
		if ( $is_single ) {
			the_title( '<h1 class="ngt-post-title">', '</h1>' );
		} else {
			the_title( '<h2 class="ngt-post-title"><a href="' . esc_url( get_permalink() ) . '">', '</a></h2>' );
		}
		ngt_post_meta();
		?>

		<div class="ngt-post-excerpt ngt-prose">
			<?php
			if ( $is_single ) {
				the_content();
				wp_link_pages(
					array(
						'before' => '<div class="ngt-page-links">' . esc_html__( 'Pages:', 'nextgen-tutors' ),
						'after'  => '</div>',
					)
				);
			} else {
				the_excerpt();
				echo '<a class="ngt-read-more" href="' . esc_url( get_permalink() ) . '">' . esc_html__( 'Read more →', 'nextgen-tutors' ) . '</a>';
			}
			?>
		</div>

		<?php if ( $is_single ) : ?>
			<footer class="ngt-post-footer">
				<?php
				$cats = get_the_category_list( ', ' );
				if ( $cats ) {
					echo '<span class="ngt-post-cats">' . wp_kses_post( $cats ) . '</span>';
				}
				?>
			</footer>
		<?php endif; ?>
	</div>
</article>
