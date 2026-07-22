<?php
/**
 * UI Library: Timeline.
 *
 * @var array $args { items|ctx }
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx   = $args['ctx'] ?? [];
$items = $args['items'] ?? [];
if ( empty( $items ) && ! empty( $ctx['steps'] ) && is_array( $ctx['steps'] ) ) {
	$items = $ctx['steps'];
}
$title = $ctx['title'] ?? __( 'What happens next', 'beyondinfinity' );

if ( empty( $items ) ) {
	return;
}
?>
<section class="bi-timeline" aria-label="<?php echo esc_attr( $title ); ?>">
	<?php if ( $title ) : ?>
		<h2 class="bi-timeline__title"><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>
	<ol class="bi-timeline__list">
		<?php foreach ( $items as $i => $step ) : ?>
			<?php
			$step_title = is_array( $step ) ? ( $step['title'] ?? '' ) : '';
			$step_text  = is_array( $step ) ? ( $step['text'] ?? '' ) : (string) $step;
			?>
			<li class="bi-timeline__item">
				<span class="bi-timeline__marker" aria-hidden="true"><?php echo esc_html( (string) ( $i + 1 ) ); ?></span>
				<div class="bi-timeline__body">
					<?php if ( $step_title ) : ?>
						<h3 class="bi-timeline__step-title"><?php echo esc_html( $step_title ); ?></h3>
					<?php endif; ?>
					<p class="bi-timeline__step-text"><?php echo esc_html( $step_text ); ?></p>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
</section>
