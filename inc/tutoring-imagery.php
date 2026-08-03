<?php
/**
 * Narrative tutoring content + curated Unsplash imagery (3D scroll).
 * Copy distilled from NextGen UI/UX HTML design packs.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stock images matched to the online tutoring narrative.
 *
 * @return array<int, array{url:string,alt:string,group:string}>
 */
function bi_tutoring_stock_images() {
	return [
		[
			'url'   => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1400&q=80',
			'alt'   => __( 'Learners collaborating during an online tutoring session', 'beyondinfinity' ),
			'group' => 'collaboration',
		],
		[
			'url'   => 'https://images.unsplash.com/photo-1588196749597-9ff075ee6b5b?auto=format&fit=crop&w=1400&q=80',
			'alt'   => __( 'Student studying at home with laptop and notes', 'beyondinfinity' ),
			'group' => 'online',
		],
		[
			'url'   => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1400&q=80',
			'alt'   => __( 'Live video tutoring on a laptop', 'beyondinfinity' ),
			'group' => 'online',
		],
		[
			'url'   => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=1400&q=80',
			'alt'   => __( 'Tutor teaching students in a supportive classroom', 'beyondinfinity' ),
			'group' => 'one-to-one',
		],
		[
			'url'   => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=1400&q=80',
			'alt'   => __( 'Young learner reading and taking notes', 'beyondinfinity' ),
			'group' => 'study',
		],
		[
			'url'   => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1400&q=80',
			'alt'   => __( 'Students preparing together for exams', 'beyondinfinity' ),
			'group' => 'collaboration',
		],
		[
			'url'   => 'https://images.unsplash.com/photo-1434030214721-28140c9d90c0?auto=format&fit=crop&w=1400&q=80',
			'alt'   => __( 'Learner writing during a live lesson', 'beyondinfinity' ),
			'group' => 'study',
		],
		[
			'url'   => 'https://images.unsplash.com/photo-1427504494785-c400db9b9908?auto=format&fit=crop&w=1400&q=80',
			'alt'   => __( 'Focused student with books in a quiet study space', 'beyondinfinity' ),
			'group' => 'one-to-one',
		],
	];
}

/**
 * Story panels — informal SA tutoring narrative from design HTML packs.
 *
 * @return array<int, array<string, string>>
 */
function bi_tutoring_narrative_panels() {
	$images = bi_tutoring_stock_images();
	$pick   = static function ( $i ) use ( $images ) {
		return $images[ $i % count( $images ) ];
	};

	return apply_filters(
		'bi_tutoring_narrative_panels',
		[
			[
				'eyebrow' => __( 'Real talk for parents', 'beyondinfinity' ),
				'title'   => __( 'School feels hard. Finding help shouldn’t.', 'beyondinfinity' ),
				'body'    => __( 'Browse verified South African tutors by subject, grade and province. CAPS, IEB and Cambridge — from Grade R to varsity. Book when it suits your family.', 'beyondinfinity' ),
				'chip'    => __( 'ID · SACE · background-checked', 'beyondinfinity' ),
				'image'   => $pick( 0 ),
			],
			[
				'eyebrow' => __( 'How a lesson actually feels', 'beyondinfinity' ),
				'title'   => __( 'Live audio + video. Whiteboard. Real progress.', 'beyondinfinity' ),
				'body'    => __( 'Join online sessions from the lounge or a quiet desk. Shared whiteboards, patient tutors, and notes you can revisit — so homework stops being a nightly battle.', 'beyondinfinity' ),
				'chip'    => __( 'Online · hybrid · in-person', 'beyondinfinity' ),
				'image'   => $pick( 2 ),
			],
			[
				'eyebrow' => __( 'From first search to first session', 'beyondinfinity' ),
				'title'   => __( 'Match → book → learn. Minutes, not months.', 'beyondinfinity' ),
				'body'    => __( 'Search, read reviews, pick a slot, pay securely. Confirmation lands fast, and your session link is ready before the lesson. No mystery apps. No awkward waiting.', 'beyondinfinity' ),
				'chip'    => __( 'First lesson risk-free*', 'beyondinfinity' ),
				'image'   => $pick( 1 ),
			],
			[
				'eyebrow' => __( 'Proudly South African', 'beyondinfinity' ),
				'title'   => __( 'Built for CAPS desks and NSC nerves.', 'beyondinfinity' ),
				'body'    => __( 'Local curricula, local tutors, measurable reports parents can actually use. Ninety-four percent of learners improve grades within three months of consistent tutoring.', 'beyondinfinity' ),
				'chip'    => __( 'CAPS · IEB · Cambridge', 'beyondinfinity' ),
				'image'   => $pick( 3 ),
			],
			[
				'eyebrow' => __( 'Tutors — this one’s for you', 'beyondinfinity' ),
				'title'   => __( 'Share what you know. Earn on your clock.', 'beyondinfinity' ),
				'body'    => __( 'Flexible hours, competitive rates, verified students, payments before sessions. Apply, get verified, set your profile — most tutors see a booking within 48 hours of going live.', 'beyondinfinity' ),
				'chip'    => __( 'Work from anywhere', 'beyondinfinity' ),
				'image'   => $pick( 5 ),
			],
		]
	);
}

/**
 * Simple imagery strip (legacy).
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

/**
 * Full narrative section with 3D scroll panels.
 */
function bi_render_tutoring_narrative_scroll() {
	$panels = bi_tutoring_narrative_panels();
	if ( empty( $panels ) ) {
		return;
	}
	$find   = function_exists( 'bi_page_url' ) ? bi_page_url( 'find-a-tutor' ) : home_url( '/find-a-tutor/' );
	$become = function_exists( 'bi_page_url' ) ? bi_page_url( 'become-a-tutor' ) : home_url( '/become-a-tutor/' );
	?>
	<section class="bi-narrative-3d" id="tutoring-story" data-bi-narrative-3d aria-labelledby="bi-narrative-3d-title">
		<div class="bi-narrative-3d__intro ngi-wrap">
			<p class="ngi-eyebrow"><?php esc_html_e( 'The NextGen story', 'beyondinfinity' ); ?></p>
			<h2 id="bi-narrative-3d-title" class="ngi-heading" data-bi-slide-title><?php esc_html_e( 'Online tutoring that feels human — not corporate.', 'beyondinfinity' ); ?></h2>
			<p class="bi-narrative-3d__lead"><?php esc_html_e( 'Straight talk for South African families: verified tutors, live lessons, and progress you can see.', 'beyondinfinity' ); ?></p>
		</div>

		<div class="bi-narrative-3d__stage">
			<?php foreach ( $panels as $i => $panel ) :
				$img = $panel['image'] ?? [];
				$url = (string) ( $img['url'] ?? '' );
				$alt = (string) ( $img['alt'] ?? '' );
				$odd = ( $i % 2 ) === 1;
				?>
				<article class="bi-narrative-3d__panel<?php echo $odd ? ' bi-narrative-3d__panel--flip' : ''; ?>" data-bi-narrative-panel>
					<div class="bi-narrative-3d__inner ngi-wrap">
						<div class="bi-narrative-3d__copy">
							<p class="bi-narrative-3d__eyebrow"><?php echo esc_html( (string) ( $panel['eyebrow'] ?? '' ) ); ?></p>
							<h3 class="bi-narrative-3d__title" data-bi-slide-title><?php echo esc_html( (string) ( $panel['title'] ?? '' ) ); ?></h3>
							<p class="bi-narrative-3d__body"><?php echo esc_html( (string) ( $panel['body'] ?? '' ) ); ?></p>
							<?php if ( ! empty( $panel['chip'] ) ) : ?>
								<span class="bi-narrative-3d__chip"><?php echo esc_html( (string) $panel['chip'] ); ?></span>
							<?php endif; ?>
						</div>
						<figure class="bi-narrative-3d__media" data-bi-narrative-media>
							<?php if ( $url ) : ?>
								<img
									src="<?php echo esc_url( $url ); ?>"
									alt="<?php echo esc_attr( $alt ); ?>"
									loading="lazy"
									decoding="async"
									width="900"
									height="600"
								/>
							<?php endif; ?>
							<span class="bi-narrative-3d__glow" aria-hidden="true"></span>
						</figure>
					</div>
				</article>
			<?php endforeach; ?>
		</div>

		<div class="bi-narrative-3d__cta ngi-wrap">
			<p class="bi-narrative-3d__cta-copy"><?php esc_html_e( 'Ready when you are — find a tutor or join as one.', 'beyondinfinity' ); ?></p>
			<div class="bi-narrative-3d__cta-actions">
				<a class="ngi-btn ngi-btn-primary" href="<?php echo esc_url( $find ); ?>"><?php esc_html_e( 'Find My Tutor', 'beyondinfinity' ); ?></a>
				<a class="ngi-btn ngi-btn-secondary" href="<?php echo esc_url( $become ); ?>"><?php esc_html_e( 'Become a Tutor', 'beyondinfinity' ); ?></a>
			</div>
			<p class="bi-narrative-3d__fine"><?php esc_html_e( '*First-lesson guarantee applies to eligible packages — see Guarantee for details.', 'beyondinfinity' ); ?></p>
		</div>
	</section>
	<?php
}

add_action( 'wp_enqueue_scripts', 'bi_enqueue_narrative_3d_assets', 18 );
/**
 * Assets for narrative 3D scroll.
 */
function bi_enqueue_narrative_3d_assets() {
	if ( is_admin() || ! function_exists( 'bi_is_kinetic_home' ) ) {
		return;
	}
	if ( ! bi_is_kinetic_home() && ! is_front_page() ) {
		return;
	}
	wp_enqueue_style(
		'bi-narrative-3d',
		BI_URI . '/assets/css/bi-narrative-3d.css',
		[ 'bi-style', 'bi-components' ],
		BI_VERSION
	);
	wp_enqueue_script(
		'bi-narrative-3d',
		BI_URI . '/assets/js/bi-narrative-3d.js',
		[],
		BI_VERSION,
		true
	);
}
