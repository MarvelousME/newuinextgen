<?php
/**
 * Curated online tutoring stock imagery (Unsplash).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<int, array{url:string,alt:string,group:string}>
 */
function bi_tutoring_stock_images() {
	return [
		[
			'url'   => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1200&h=800&fit=crop',
			'alt'   => __( 'Students collaborating in an online tutoring session', 'beyondinfinity' ),
			'group' => 'collaboration',
		],
		[
			'url'   => 'https://images.unsplash.com/photo-1588196749597-9ff075ee6b5b?w=1200&h=800&fit=crop',
			'alt'   => __( 'Learner studying at home with laptop and notes', 'beyondinfinity' ),
			'group' => 'online',
		],
		[
			'url'   => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=1200&h=800&fit=crop',
			'alt'   => __( 'One-on-one tutoring with books and tablet', 'beyondinfinity' ),
			'group' => 'one-to-one',
		],
		[
			'url'   => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=1200&h=800&fit=crop',
			'alt'   => __( 'Video call tutoring session on laptop', 'beyondinfinity' ),
			'group' => 'online',
		],
		[
			'url'   => 'https://images.unsplash.com/photo-1434030214721-28140c9d90c0?w=1200&h=800&fit=crop',
			'alt'   => __( 'Student writing during a live lesson', 'beyondinfinity' ),
			'group' => 'study',
		],
		[
			'url'   => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1200&h=800&fit=crop',
			'alt'   => __( 'Young learners engaged in group study', 'beyondinfinity' ),
			'group' => 'collaboration',
		],
	];
}

/**
 * Render a responsive imagery strip for marketing pages.
 *
 * @param string $context Section key.
 */
function bi_render_tutoring_imagery_strip( $context = 'home' ) {
	$images = bi_tutoring_stock_images();
	if ( empty( $images ) ) {
		return;
	}
	$groups = [];
	foreach ( $images as $img ) {
		$groups[ $img['group'] ][] = $img;
	}
	?>
	<section class="ngt-imagery-strip ngt-imagery-strip--<?php echo esc_attr( $context ); ?>" aria-label="<?php esc_attr_e( 'Online tutoring highlights', 'beyondinfinity' ); ?>">
		<div class="ngt-container">
			<?php foreach ( $groups as $group => $items ) : ?>
				<div class="ngt-imagery-group" data-group="<?php echo esc_attr( $group ); ?>">
					<?php foreach ( array_slice( $items, 0, 2 ) as $img ) : ?>
						<figure class="ngt-imagery-card">
							<img src="<?php echo esc_url( $img['url'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>" loading="lazy" decoding="async" width="600" height="400" />
						</figure>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}
