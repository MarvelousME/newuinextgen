<?php
/**
 * Section: Hero carousel. Ports the React <HeroCarousel /> (4 slides).
 * Animation/auto-rotation handled by assets/js/theme.js (vanilla, replaces Framer Motion).
 *
 * @package NextGen_Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slides = array(
	array(
		'badge'  => __( 'ID-Verified SACE Educators', 'nextgen-tutors' ),
		'title'  => __( 'Find Verified Tutors Who', 'nextgen-tutors' ),
		'accent' => __( 'Actually Deliver Results', 'nextgen-tutors' ),
		'desc'   => __( 'ID-verified, background-checked, SACE-registered educators for every grade and subject. Helping South African learners grow in confidence and reach their target marks.', 'nextgen-tutors' ),
		'cta'    => __( 'Find Your Tutor →', 'nextgen-tutors' ),
		'route'  => 'find-a-tutor',
		'tone'   => 'emerald',
	),
	array(
		'badge'  => __( 'Unmatched Financial Value', 'nextgen-tutors' ),
		'title'  => __( 'Affordable Rates,', 'nextgen-tutors' ),
		'accent' => __( 'Superior Grade ROI', 'nextgen-tutors' ),
		'desc'   => __( 'At half the rates of premium competitors (starting at just R180–R320/hour), we deliver exceptional university-vetted tutors. Learners report a 94% average grade leap.', 'nextgen-tutors' ),
		'cta'    => __( 'See Pricing Matrix', 'nextgen-tutors' ),
		'route'  => 'pricing',
		'tone'   => 'emerald',
	),
	array(
		'badge'  => __( 'Grade 1 to Tertiary Expertise', 'nextgen-tutors' ),
		'title'  => __( 'CAPS, IEB & Cambridge', 'nextgen-tutors' ),
		'accent' => __( 'Curriculum Specialists', 'nextgen-tutors' ),
		'desc'   => __( 'Whether preparing for Trial exams, Matric NSC, or Varsity calculus, we match learners with expert subject strategists. Rapid tutor recommendations under 24 hours.', 'nextgen-tutors' ),
		'cta'    => __( 'Explore Vetting Process', 'nextgen-tutors' ),
		'route'  => 'vetting',
		'tone'   => 'indigo',
	),
	array(
		'badge'  => __( 'Risk-Free First Session Guarantee', 'nextgen-tutors' ),
		'title'  => __( 'First Lesson Free', 'nextgen-tutors' ),
		'accent' => __( 'If Unsatisfied', 'nextgen-tutors' ),
		'desc'   => __( 'If you are not fully satisfied after your initial lesson, we offer a full refund. Zero commitment, zero cash handling, safe platform-managed payment gateways.', 'nextgen-tutors' ),
		'cta'    => __( 'Find Your Tutor Now', 'nextgen-tutors' ),
		'route'  => 'find-a-tutor',
		'tone'   => 'rose',
	),
);
?>
<section class="ngt-hero" data-carousel data-interval="7000" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'NextGen highlights', 'nextgen-tutors' ); ?>">
	<div class="ngt-hero-bg" aria-hidden="true"></div>

	<div class="ngt-hero-track">
		<?php foreach ( $slides as $i => $s ) : ?>
			<div class="ngt-hero-slide<?php echo 0 === $i ? ' is-active' : ''; ?>" data-slide="<?php echo esc_attr( $i ); ?>" role="group" aria-roledescription="slide" <?php echo 0 === $i ? '' : 'aria-hidden="true"'; ?>>
				<div class="ngt-hero-content">
					<span class="ngt-hero-badge ngt-tone-<?php echo esc_attr( $s['tone'] ); ?>">★ <?php echo esc_html( $s['badge'] ); ?></span>
					<h1 class="ngt-hero-title">
						<?php echo esc_html( $s['title'] ); ?>
						<span class="ngt-hero-accent"><?php echo esc_html( $s['accent'] ); ?></span>
					</h1>
					<p class="ngt-hero-desc"><?php echo esc_html( $s['desc'] ); ?></p>
					<div class="ngt-hero-actions">
						<a class="ngt-btn ngt-btn-accent" href="<?php echo esc_url( ngt_route_url( $s['route'] ) ); ?>"><?php echo esc_html( $s['cta'] ); ?></a>
						<a class="ngt-btn ngt-btn-ghost" href="<?php echo esc_url( ngt_route_url( 'vetting' ) ); ?>"><?php esc_html_e( 'Learn About Our Vetting', 'nextgen-tutors' ); ?></a>
					</div>
				</div>
				<div class="ngt-hero-visual">
					<div class="ngt-hero-card">
						<div class="ngt-hero-card-head">
							<span class="ngt-live">● <?php esc_html_e( 'LIVE ONLINE TUTOR SESSION', 'nextgen-tutors' ); ?></span>
							<span class="ngt-hero-room">ROOM-ZA-0<?php echo esc_html( $i + 1 ); ?></span>
						</div>
						<div class="ngt-hero-board">
							<div class="ngt-hero-code">// <?php esc_html_e( 'Quadratic formula solved:', 'nextgen-tutors' ); ?></div>
							<div class="ngt-hero-formula">x = (-b ± √(b² - 4ac)) / 2a</div>
							<div class="ngt-hero-ring" aria-hidden="true"></div>
						</div>
						<div class="ngt-hero-tutorbar">
							<span class="ngt-hero-dot"></span>
							<div>
								<span class="ngt-hero-tutorname"><?php esc_html_e( 'Sipho Ndlovu (BSc Eng)', 'nextgen-tutors' ); ?></span>
								<span class="ngt-hero-tutorrole"><?php esc_html_e( 'CAPS Mathematics Specialist', 'nextgen-tutors' ); ?></span>
							</div>
						</div>
					</div>
					<span class="ngt-hero-badge-float">★★★★★ 9,000+ <?php esc_html_e( 'ACTIVES', 'nextgen-tutors' ); ?></span>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<button type="button" class="ngt-hero-nav ngt-hero-prev" data-prev aria-label="<?php esc_attr_e( 'Previous slide', 'nextgen-tutors' ); ?>">‹</button>
	<button type="button" class="ngt-hero-nav ngt-hero-next" data-next aria-label="<?php esc_attr_e( 'Next slide', 'nextgen-tutors' ); ?>">›</button>

	<div class="ngt-hero-dots" role="tablist" aria-label="<?php esc_attr_e( 'Choose slide', 'nextgen-tutors' ); ?>">
		<?php foreach ( $slides as $i => $s ) : ?>
			<button type="button" class="ngt-hero-dotbtn<?php echo 0 === $i ? ' is-active' : ''; ?>" data-dot="<?php echo esc_attr( $i ); ?>" role="tab" aria-label="<?php echo esc_attr( sprintf( __( 'Go to slide %d', 'nextgen-tutors' ), $i + 1 ) ); ?>"></button>
		<?php endforeach; ?>
	</div>
</section>
