<?php
/**
 * Brand narrative sourced from NGT Design UI PDFs (2026).
 *
 * Sources:
 * - NGT-Design-UI.pdf / NGT-Design-UI-nore.pdf (Our Story, Mission & Values)
 * - NGT-Design-UI-more-info.pdf (trust / curriculum positioning)
 * - NGT-Design-UI-more.pdf (become-a-tutor earnings & steps)
 * - NGT-Design-UI-contact.pdf (contact departments)
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Absolute URL for the retired hero brand mark asset (kept for reference only).
 *
 * @return string
 */
function bi_hero_brand_mark_url() {
	return trailingslashit( BI_URI ) . 'assets/brand/nextgen-tutors-hero-mark.png';
}

/**
 * Hero brand mark removed sitewide — no-op kept so older callers stay safe.
 *
 * @param string $variant Unused.
 */
function bi_hero_brand_mark( $variant = 'page' ) {
	unset( $variant );
}

/**
 * Structured brand content from design PDFs.
 *
 * @return array<string, mixed>
 */
function bi_brand_content() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$cache = [
		'tagline'       => __( 'Next level learning', 'beyondinfinity' ),
		'positioning'   => __( "South Africa's most trusted online tutoring — connect with verified, qualified tutors across all subjects and grades, from Grade R to University.", 'beyondinfinity' ),
		'trust_line'    => __( '94% of students improve their grades within 3 months. Every tutor is ID verified, background checked, and SACE registered — your child\'s safety and success are our top priorities.', 'beyondinfinity' ),
		'story'         => [
			'eyebrow' => __( 'Our story', 'beyondinfinity' ),
			'title'   => __( 'Transforming education in South Africa', 'beyondinfinity' ),
			'lead'    => __( "We're building a future where every South African learner has access to quality, personalised tutoring.", 'beyondinfinity' ),
			'body'    => [
				__( 'NextGen Tutors was founded in 2020 by a team of South African educators who saw firsthand the challenges learners face in accessing quality educational support.', 'beyondinfinity' ),
				__( 'We recognised that traditional tutoring was expensive, inconsistent, and often difficult to access — especially for families outside major metros. Meanwhile, talented educators struggled to find flexible work opportunities.', 'beyondinfinity' ),
				__( 'Our platform bridges this gap, connecting verified tutors with learners across South Africa through a safe, affordable, and effective online — and in-person — platform.', 'beyondinfinity' ),
			],
		],
		'mission'       => [
			'eyebrow' => __( 'Mission & values', 'beyondinfinity' ),
			'title'   => __( 'Our Mission', 'beyondinfinity' ),
			'lead'    => __( 'Every South African student deserves access to a great tutor — regardless of where they live, what school they attend, or what their family can afford. Whether you\'re in Soweto or Stellenbosch, Durban or Polokwane — we\'ve got you covered.', 'beyondinfinity' ),
		],
		'values'        => [
			[
				'title' => __( 'Access', 'beyondinfinity' ),
				'text'  => __( 'Tutoring for every grade, every subject, all 9 provinces — regardless of location or economic background.', 'beyondinfinity' ),
			],
			[
				'title' => __( 'Quality', 'beyondinfinity' ),
				'text'  => __( 'Every tutor is vetted. Every session is guaranteed. Credentials, reviews and curriculum fit before the first lesson.', 'beyondinfinity' ),
			],
			[
				'title' => __( 'Fairness', 'beyondinfinity' ),
				'text'  => __( 'Transparent pricing for families. Tutors keep the majority of every session with clear platform fees.', 'beyondinfinity' ),
			],
			[
				'title' => __( 'Safety', 'beyondinfinity' ),
				'text'  => __( 'ID-verified tutors, secure payments, and a POPIA-aligned platform built for child safeguarding.', 'beyondinfinity' ),
			],
			[
				'title' => __( 'Empower educators', 'beyondinfinity' ),
				'text'  => __( 'We give South African teachers flexible income opportunities and professional development resources.', 'beyondinfinity' ),
			],
			[
				'title' => __( 'Proudly South African', 'beyondinfinity' ),
				'text'  => __( 'Built for CAPS, IEB and Cambridge by South Africans who understand local education challenges.', 'beyondinfinity' ),
			],
		],
		'trust_pillars' => [
			[ 'title' => __( 'ID verified', 'beyondinfinity' ), 'text' => __( 'Strict identity verification on every tutor profile.', 'beyondinfinity' ) ],
			[ 'title' => __( 'Background cleared', 'beyondinfinity' ), 'text' => __( 'Police clearance and professional references before teaching.', 'beyondinfinity' ) ],
			[ 'title' => __( 'SACE registered', 'beyondinfinity' ), 'text' => __( 'Certified, qualified educators where registration applies.', 'beyondinfinity' ) ],
			[ 'title' => __( 'SA curriculum experts', 'beyondinfinity' ), 'text' => __( 'CAPS, IEB and Cambridge specialists matched to your learner.', 'beyondinfinity' ) ],
		],
		'stats'         => [
			[ 'value' => '12,000+', 'label' => __( 'Students tutored', 'beyondinfinity' ) ],
			[ 'value' => '500+', 'label' => __( 'Verified tutors', 'beyondinfinity' ) ],
			[ 'value' => '95%', 'label' => __( 'Satisfaction rate', 'beyondinfinity' ) ],
			[ 'value' => '40,000+', 'label' => __( 'Sessions completed', 'beyondinfinity' ) ],
		],
		'become'        => [
			'title'   => __( 'Share your knowledge, earn income', 'beyondinfinity' ),
			'lead'    => __( "Join South Africa's most trusted tutoring platform. Flexible hours, competitive rates, verified students.", 'beyondinfinity' ),
			'perks'   => [
				[ 'title' => __( 'Competitive earnings', 'beyondinfinity' ), 'text' => __( 'Set your own rates. Average tutors earn R15,000–R25,000 per month.', 'beyondinfinity' ) ],
				[ 'title' => __( 'Flexible schedule', 'beyondinfinity' ), 'text' => __( 'Work mornings, evenings, or weekends — when it suits you.', 'beyondinfinity' ) ],
				[ 'title' => __( 'Work from anywhere', 'beyondinfinity' ), 'text' => __( 'Teach 100% online with a laptop and reliable internet.', 'beyondinfinity' ) ],
				[ 'title' => __( 'Verified students', 'beyondinfinity' ), 'text' => __( 'Payment is guaranteed before sessions begin.', 'beyondinfinity' ) ],
				[ 'title' => __( 'Resources & support', 'beyondinfinity' ), 'text' => __( 'Lesson plans, worksheets, and dedicated tutor support.', 'beyondinfinity' ) ],
				[ 'title' => __( 'Build your brand', 'beyondinfinity' ), 'text' => __( 'Reviews and ratings boost your visibility on the marketplace.', 'beyondinfinity' ) ],
			],
			'steps'   => [
				__( 'Submit application — qualifications, experience, and subjects.', 'beyondinfinity' ),
				__( 'Verification — ID, qualification, and background checks (2–3 business days).', 'beyondinfinity' ),
				__( 'Profile setup — photo, rates, and availability.', 'beyondinfinity' ),
				__( 'Training & onboarding — platform booking and best practices.', 'beyondinfinity' ),
				__( 'Start tutoring — first session typically within 48 hours.', 'beyondinfinity' ),
			],
			'income'  => [
				[ 'rate' => 'R300', 'sessions' => '10', 'monthly' => 'R12,000' ],
				[ 'rate' => 'R400', 'sessions' => '15', 'monthly' => 'R24,000' ],
				[ 'rate' => 'R500', 'sessions' => '20', 'monthly' => 'R40,000' ],
			],
		],
		'contact'       => [
			'title'       => __( 'Get in touch', 'beyondinfinity' ),
			'lead'        => __( "We're here to help with any questions about tutoring.", 'beyondinfinity' ),
			'departments' => [
				[ 'name' => __( 'Parent & student support', 'beyondinfinity' ), 'for' => __( 'Finding tutors, bookings, technical help', 'beyondinfinity' ), 'email' => 'support@nextgentutors.co.za' ],
				[ 'name' => __( 'Tutor applications', 'beyondinfinity' ), 'for' => __( 'Becoming a tutor, verification, onboarding', 'beyondinfinity' ), 'email' => 'tutors@nextgentutors.co.za' ],
				[ 'name' => __( 'Billing & payments', 'beyondinfinity' ), 'for' => __( 'Invoices, refunds, payment issues', 'beyondinfinity' ), 'email' => 'billing@nextgentutors.co.za' ],
				[ 'name' => __( 'Partnerships', 'beyondinfinity' ), 'for' => __( 'Schools, organisations, bulk bookings', 'beyondinfinity' ), 'email' => 'partners@nextgentutors.co.za' ],
			],
		],
		'source_note'   => __( 'Brand narrative adapted from NextGen Tutors Design UI materials (2026).', 'beyondinfinity' ),
	];

	/**
	 * Filter brand content catalogue.
	 *
	 * @param array<string, mixed> $cache Content.
	 */
	$cache = apply_filters( 'bi_brand_content', $cache );
	return $cache;
}

/**
 * Render the Our Story / Mission / Values composition (PDF-sourced).
 *
 * @param array<string, mixed> $args Optional overrides.
 */
function bi_render_brand_story_sections( $args = [] ) {
	$c     = bi_brand_content();
	$story = $c['story'];
	$miss  = $c['mission'];
	$skip_story = ! empty( $args['skip_story'] );
	?>
	<?php if ( ! $skip_story ) : ?>
	<section class="bi-brand-story ngt-section" aria-labelledby="bi-brand-story-title">
		<div class="ngt-container bi-brand-story__grid">
			<div class="bi-brand-story__copy ngt-animate" data-bi-motion="slide-up">
				<p class="bi-eyebrow"><?php echo esc_html( $story['eyebrow'] ); ?></p>
				<h2 id="bi-brand-story-title" data-bi-slide-title><?php echo esc_html( $story['title'] ); ?></h2>
				<p class="bi-brand-story__lead"><?php echo esc_html( $story['lead'] ); ?></p>
				<?php foreach ( (array) $story['body'] as $para ) : ?>
					<p><?php echo esc_html( $para ); ?></p>
				<?php endforeach; ?>
			</div>
			<div class="bi-brand-story__aside ngt-animate" data-bi-motion="fade-in">
				<ul class="bi-brand-story__stats" role="list">
					<?php foreach ( (array) $c['stats'] as $stat ) : ?>
						<li>
							<strong><?php echo esc_html( $stat['value'] ); ?></strong>
							<span><?php echo esc_html( $stat['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<section class="bi-brand-mission ngt-section ngt-section--alt" aria-labelledby="bi-brand-mission-title">
		<div class="ngt-container">
			<header class="ngt-section__header bi-center ngt-animate">
				<p class="bi-eyebrow"><?php echo esc_html( $miss['eyebrow'] ); ?></p>
				<h2 id="bi-brand-mission-title" data-bi-slide-title><?php echo esc_html( $miss['title'] ); ?></h2>
				<p class="bi-brand-mission__lead"><?php echo esc_html( $miss['lead'] ); ?></p>
			</header>
			<div class="bi-brand-values bi-grid-3 framer-grid" data-bi-stagger="slide-up">
				<?php foreach ( (array) $c['values'] as $i => $value ) : ?>
					<article class="ngt-card bi-brand-value ngt-animate ngt-animate--delay-<?php echo (int) ( ( $i % 3 ) + 1 ); ?>">
						<h3><?php echo esc_html( $value['title'] ); ?></h3>
						<p><?php echo esc_html( $value['text'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="bi-brand-trust ngt-section" aria-labelledby="bi-brand-trust-title">
		<div class="ngt-container">
			<header class="ngt-section__header bi-center ngt-animate">
				<p class="bi-eyebrow"><?php esc_html_e( 'Why families choose NextGen', 'beyondinfinity' ); ?></p>
				<h2 id="bi-brand-trust-title"><?php esc_html_e( 'Safety, curriculum fit, and proof of progress', 'beyondinfinity' ); ?></h2>
				<p><?php echo esc_html( $c['trust_line'] ); ?></p>
			</header>
			<div class="bi-brand-trust__grid bi-grid-3 framer-grid">
				<?php foreach ( (array) $c['trust_pillars'] as $i => $pillar ) : ?>
					<article class="ngt-card bi-brand-trust__card ngt-animate">
						<h3><?php echo esc_html( $pillar['title'] ); ?></h3>
						<p><?php echo esc_html( $pillar['text'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
			<p class="bi-brand-source"><?php echo esc_html( $c['source_note'] ); ?></p>
		</div>
	</section>
	<?php
}
