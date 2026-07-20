<?php
/**
 * Section: WordPress & Elementor PRO conversion banner.
 * Ports the gradient banner from the React home route.
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$specs = array(
	__( 'Pixel-faithful Elementor templates', 'nextgen-tutors' ),
	__( 'GamiPress rewards & ranking engine', 'nextgen-tutors' ),
	__( 'Amelia booking & scheduling widgets', 'nextgen-tutors' ),
	__( 'SMTP transactional email automation', 'nextgen-tutors' ),
);
?>
<section class="ngt-wpbanner" data-reveal>
	<div class="ngt-wpbanner-glow ngt-wpbanner-glow-1" aria-hidden="true"></div>
	<div class="ngt-wpbanner-glow ngt-wpbanner-glow-2" aria-hidden="true"></div>

	<div class="ngt-wpbanner-inner">
		<div class="ngt-wpbanner-text">
			<span class="ngt-eyebrow ngt-eyebrow-light"><?php esc_html_e( 'WordPress + Elementor PRO Ready', 'nextgen-tutors' ); ?></span>
			<h2 class="ngt-wpbanner-title"><?php esc_html_e( 'Built to drop into your existing WordPress stack.', 'nextgen-tutors' ); ?></h2>
			<p class="ngt-wpbanner-desc"><?php esc_html_e( 'This theme mirrors the NextGen design system and leaves shortcode-ready zones for your booking, LMS and gamification plugins — no design rework required.', 'nextgen-tutors' ); ?></p>

			<div class="ngt-wpbanner-specs">
				<?php foreach ( $specs as $spec ) : ?>
					<span class="ngt-wpbanner-spec">✓ <?php echo esc_html( $spec ); ?></span>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="ngt-wpbanner-cta">
			<div class="ngt-wpbanner-port">
				<span class="ngt-port-label">[ngt_workspace]</span>
				<a href="<?php echo esc_url( ngt_route_url( 'contact' ) ); ?>" class="ngt-btn ngt-btn-rose ngt-btn-block"><?php esc_html_e( 'Launch Elementor Workspace', 'nextgen-tutors' ); ?></a>
			</div>
		</div>
	</div>
</section>
