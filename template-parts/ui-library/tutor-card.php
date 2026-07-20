<?php
/**
 * UI Library: Tutor card — delegates to existing component when possible.
 *
 * @var array $args { slug, def, items, ctx }
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = $args['items'] ?? [];
?>
<div class="ng-grid ng-ui ng-ui--tutor-card">
	<?php
	foreach ( $items as $row ) :
		if ( ! empty( $row['empty'] ) ) {
			continue;
		}
		$tutor = [
			'name'     => $row['name'] ?? '',
			'avatar'   => $row['photo'] ?? '',
			'subjects' => $row['subjects'] ?? [],
			'grades'   => $row['grades'] ?? '',
			'location' => $row['location'] ?? '',
			'bio'      => $row['bio'] ?? '',
			'rating'   => $row['rating'] ?? 0,
			'reviews'  => $row['reviews'] ?? 0,
			'rate'     => $row['price'] ?? 0,
			'vetted'   => ! empty( $row['vetted'] ),
		];

		if ( locate_template( 'template-parts/components/tutor-card.php' ) ) {
			get_template_part( 'template-parts/components/tutor-card', null, [ 'tutor' => $tutor ] );
		} else {
			?>
			<article class="ng-card ng-ui-tutor-card">
				<div class="ng-card__body">
					<div class="ng-ui-tutor-card__top">
						<?php if ( $tutor['avatar'] ) : ?>
							<img class="ng-ui-tutor-card__avatar" src="<?php echo esc_url( $tutor['avatar'] ); ?>" alt="" loading="lazy" width="64" height="64" />
						<?php endif; ?>
						<div>
							<h3 class="ng-ui-tutor-card__name"><?php echo esc_html( $tutor['name'] ); ?></h3>
							<p class="ng-ui-tutor-card__meta"><?php echo esc_html( $tutor['location'] ); ?></p>
						</div>
					</div>
					<div class="ng-ui-tutor-card__foot">
						<span>★ <?php echo esc_html( number_format_i18n( (float) $tutor['rating'], 1 ) ); ?></span>
						<?php if ( $tutor['rate'] ) : ?>
							<strong>R<?php echo esc_html( (string) $tutor['rate'] ); ?></strong>
						<?php endif; ?>
					</div>
				</div>
			</article>
			<?php
		}
	endforeach;

	if ( empty( array_filter( $items, static fn( $r ) => empty( $r['empty'] ) ) ) ) {
		get_template_part( 'template-parts/ui-library/empty-state', null, [ 'item' => $items[0] ?? [] ] );
	}
	?>
</div>
