<?php
/**
 * Subject landing — CMS-driven body with Find a Tutor CTA.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = '';
if ( is_singular( 'page' ) ) {
	$slug = (string) get_post_meta( get_the_ID(), 'ngc_subject_slug', true );
	if ( ! $slug ) {
		$slug = (string) get_post_field( 'post_name', get_the_ID() );
	}
}

$subject = null;
if ( $slug && class_exists( 'NGC_Subjects_CMS' ) ) {
	$subject = NGC_Subjects_CMS::get( $slug );
}

$title = $subject['title'] ?? get_the_title();
$lead  = $subject['page_lead'] ?? ( $subject['body'] ?? '' );
$body  = $subject['page_content'] ?? '';
$bullets = array_values( (array) ( $subject['bullets'] ?? [] ) );
$find_url = $slug
	? add_query_arg( 'subject', $slug, home_url( '/find-a-tutor/' ) )
	: home_url( '/find-a-tutor/' );

bi_hero(
	$title,
	$lead ?: __( 'Vetted tutors for this subject across CAPS, IEB and Cambridge.', 'beyondinfinity' ),
	'bi-hero--subject'
);
?>

<section class="ngt-section">
	<div class="ngt-container bi-narrow">
		<?php if ( $body ) : ?>
			<?php foreach ( preg_split( "/\n{2,}/", (string) $body ) as $para ) : ?>
				<?php $para = trim( (string) $para ); if ( ! $para ) { continue; } ?>
				<p class="ngt-animate"><?php echo esc_html( $para ); ?></p>
			<?php endforeach; ?>
		<?php else : ?>
			<?php
			while ( have_posts() ) {
				the_post();
				the_content();
			}
			?>
		<?php endif; ?>

		<?php if ( $bullets ) : ?>
			<div class="ngi-bullet-grid" style="margin-top:24px">
				<?php foreach ( $bullets as $bullet ) : ?>
					<div class="ngi-bullet ngt-animate"><?php echo esc_html( (string) $bullet ); ?></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<p style="margin-top:32px">
			<a class="ngt-btn ngt-btn--primary" href="<?php echo esc_url( $find_url ); ?>">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: subject title */
						__( 'Find a %s tutor', 'beyondinfinity' ),
						$title
					)
				);
				?>
			</a>
		</p>
	</div>
</section>
