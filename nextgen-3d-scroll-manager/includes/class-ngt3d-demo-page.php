<?php
/**
 * 3D Scroll Test demo page â€” ensure WP page + NGT3D rules + shortcode markup.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Demo_Page {

	public const SLUG = '3d-scroll-test';
	public const SEED_FLAG = 'ngt_3d_demo_page_seed_v1';

	public static function boot(): void {
		add_shortcode( 'ngt_3d_scroll_test', [ self::class, 'shortcode' ] );
		add_action( 'init', [ self::class, 'maybe_seed' ], 30 );
		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_demo_assets' ], 25 );
	}

	public static function maybe_seed(): void {
		if ( get_option( self::SEED_FLAG ) === '2026-09-12-motion-text' ) {
			return;
		}
		if ( ! class_exists( 'NGT3D_Rule_Repository' ) || ! class_exists( 'NGT3D_Schema' ) ) {
			return;
		}

		$page_id = self::ensure_page();
		if ( $page_id > 0 ) {
			self::seed_rules( $page_id );
			update_option( self::SEED_FLAG, '2026-09-12-motion-text', false );
		}
	}

	/**
	 * Create/publish the demo page and assign the theme template.
	 */
	public static function ensure_page(): int {
		$existing = get_page_by_path( self::SLUG );
		if ( $existing instanceof WP_Post ) {
			$page_id = (int) $existing->ID;
			update_post_meta( $page_id, '_wp_page_template', 'page-3d-scroll-test.php' );
			return $page_id;
		}

		$page_id = wp_insert_post(
			[
				'post_title'   => '3D Scroll Test',
				'post_name'    => self::SLUG,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- wp:shortcode -->[ngt_3d_scroll_test]<!-- /wp:shortcode -->',
			],
			true
		);

		if ( is_wp_error( $page_id ) || ! $page_id ) {
			return 0;
		}

		update_post_meta( (int) $page_id, '_wp_page_template', 'page-3d-scroll-test.php' );
		return (int) $page_id;
	}

	/**
	 * Seed one rule per demo section for this page only.
	 */
	public static function seed_rules( int $page_id ): void {
		global $wpdb;
		$table = NGT3D_Schema::table();

		// Disable previous demo-page rules (idempotent re-seed safe).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET enabled = 0 WHERE page_slug = %s OR page_id = %d",
				self::SLUG,
				$page_id
			)
		);

		$map = self::rule_map();
		$order = 10;
		foreach ( $map as $row ) {
			NGT3D_Rule_Repository::create(
				[
					'page_id'           => $page_id,
					'page_slug'         => self::SLUG,
					'target_selector'   => $row['selector'],
					'target_type'       => 'selector',
					'animation_names'   => $row['animation'],
					'style_classes'     => 'ngt-3d-demo-target',
					'animation_options' => $row['options'],
					'sort_order'        => $order,
					'enabled'           => 1,
					'desktop_mode'      => 'full',
					'tablet_mode'       => $row['tablet'] ?? 'reduced',
					'mobile_mode'       => $row['mobile'] ?? 'reduced',
					'created_by'        => 0,
					'updated_by'        => 0,
				]
			);
			$order += 10;
		}

		NGT3D_Rule_Repository::flush_all_caches();
	}

	/**
	 * @return array<int, array{selector:string,animation:string,options:array,tablet?:string,mobile?:string}>
	 */
	public static function rule_map(): array {
		return [
			[ 'selector' => '#ngt3d-zoom', 'animation' => 'zoom', 'options' => [ 'scrub' => 1.1, 'scaleFrom' => 0.78, 'scaleTo' => 1, 'perspective' => 1200 ], 'mobile' => 'reduced' ],
			[ 'selector' => '#ngt3d-doublescroll', 'animation' => 'doublescroll', 'options' => [ 'scrub' => 1.2, 'pin' => true, 'distance' => 320 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-4kvideo', 'animation' => '4kvideo', 'options' => [ 'scrub' => 1, 'pin' => true, 'scaleFrom' => 1.15, 'scaleTo' => 1 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-wiper', 'animation' => 'wiper', 'options' => [ 'scrub' => 0.9, 'direction' => 'left' ], 'mobile' => 'reduced' ],
			[ 'selector' => '#ngt3d-dark-veles', 'animation' => 'dark-veles', 'options' => [ 'scrub' => 0.9, 'stagger' => 0.1, 'scaleFrom' => 0.94, 'y' => 36 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-scroll-mask', 'animation' => 'scroll-mask', 'options' => [ 'scrub' => 1.1, 'stagger' => 0.12 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-onscroll', 'animation' => 'onscroll', 'options' => [ 'stagger' => 0.1, 'y' => 48, 'scaleFrom' => 0.94 ], 'mobile' => 'reduced' ],
			[ 'selector' => '#ngt3d-swag-card', 'animation' => 'swag-card', 'options' => [ 'scrub' => 1, 'pin' => true, 'perspective' => 1400, 'rise' => 110, 'scaleTo' => 0.9, 'rotateX' => -12 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-transforms', 'animation' => 'transforms', 'options' => [ 'scrub' => 1, 'perspective' => 1600, 'rotateY' => 48, 'rotateX' => -6, 'zFrom' => -180, 'scaleFrom' => 0.86 ], 'mobile' => 'reduced' ],
			[ 'selector' => '#ngt3d-depth-scroll', 'animation' => 'depth-scroll', 'options' => [ 'scrub' => 1, 'y' => -90 ], 'mobile' => 'reduced' ],
			[ 'selector' => '#ngt3d-perspective-reveal', 'animation' => 'perspective-reveal', 'options' => [ 'scrub' => 0.8, 'rotateX' => 8, 'perspective' => 1200 ], 'mobile' => 'reduced' ],
			[ 'selector' => '#ngt3d-scale-depth', 'animation' => 'scale-depth', 'options' => [ 'scrub' => 1, 'scaleFrom' => 0.88, 'scaleTo' => 1 ], 'mobile' => 'full' ],
			[ 'selector' => '#ngt3d-rotate-x', 'animation' => 'rotate-x-scroll', 'options' => [ 'scrub' => 1, 'rotateX' => 18 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-rotate-y', 'animation' => 'rotate-y-scroll', 'options' => [ 'scrub' => 1, 'rotateY' => 18 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-translate-z', 'animation' => 'translate-z', 'options' => [ 'scrub' => 1, 'z' => 100, 'perspective' => 1000 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-stagger-depth', 'animation' => 'stagger-depth', 'options' => [ 'stagger' => 0.12, 'rotateX' => 10, 'y' => 28 ], 'mobile' => 'full' ],
			[ 'selector' => '#ngt3d-stack-3d', 'animation' => 'stack-3d', 'options' => [ 'scrub' => 1.4, 'pin' => true, 'perspective' => 1000 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-cards-fan', 'animation' => 'cards-fan', 'options' => [ 'scrub' => 1.2, 'stagger' => 0.08 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-pin-section', 'animation' => 'pin-section', 'options' => [ 'scrub' => 1, 'pin' => true, 'end' => '+=180%' ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-horizontal', 'animation' => 'horizontal-scroll', 'options' => [ 'scrub' => 1, 'pin' => true ], 'mobile' => 'disabled', 'tablet' => 'disabled' ],
			[ 'selector' => '#ngt3d-image-depth', 'animation' => 'image-depth', 'options' => [ 'scrub' => 1, 'rate' => 0.35 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-text-depth', 'animation' => 'text-depth', 'options' => [ 'scrub' => 1, 'z' => 48 ], 'mobile' => 'reduced' ],
			[ 'selector' => '#ngt3d-section-cinematic', 'animation' => 'section-cinematic', 'options' => [ 'scrub' => 1, 'scaleFrom' => 1.06, 'scaleTo' => 1 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-tilt', 'animation' => 'tilt-3d', 'options' => [ 'max' => 12 ], 'mobile' => 'disabled', 'tablet' => 'disabled' ],
			// Text + entrance effects on existing lab markup.
			[ 'selector' => '.ngt-3d-demo__intro h1', 'animation' => 'gsapify-text-fade-up-words', 'options' => [ 'duration' => 0.9, 'stagger' => 0.04 ], 'mobile' => 'reduced' ],
			[ 'selector' => '.ngt-3d-demo__intro .ngt-3d-demo__lede', 'animation' => 'fade-up', 'options' => [ 'duration' => 0.7, 'distance' => 24 ], 'mobile' => 'full' ],
			[ 'selector' => '#ngt3d-zoom h2', 'animation' => 'gsapify-text-word-build', 'options' => [ 'duration' => 0.7, 'stagger' => 0.045 ], 'mobile' => 'reduced' ],
			[ 'selector' => '#ngt3d-onscroll h2', 'animation' => 'gsapify-text-staggered-letters', 'options' => [ 'duration' => 0.55, 'stagger' => 0.028 ], 'mobile' => 'reduced' ],
			[ 'selector' => '#ngt3d-onscroll .ngt-3d-demo__grid', 'animation' => 'gsapify-card-slide-in-stagger', 'options' => [ 'stagger' => 0.1, 'direction' => 'up' ], 'mobile' => 'reduced' ],
			[ 'selector' => '#ngt3d-swag-card h2', 'animation' => 'gsapify-text-line-by-line', 'options' => [ 'duration' => 0.7, 'stagger' => 0.05 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-transforms h2', 'animation' => 'gsapify-text-slide-left', 'options' => [ 'duration' => 0.7, 'stagger' => 0.04 ], 'mobile' => 'reduced' ],
			[ 'selector' => '#ngt3d-stagger-depth h2', 'animation' => 'gsapify-kinetic-split-lines', 'options' => [ 'duration' => 0.75, 'stagger' => 0.04 ], 'mobile' => 'full' ],
			[ 'selector' => '#ngt3d-stagger-depth .ngt-3d-demo__grid--trust', 'animation' => 'stagger-children', 'options' => [ 'stagger' => 0.1, 'childSelector' => '.ngt-3d-demo__card' ], 'mobile' => 'full' ],
			[ 'selector' => '#ngt3d-text-depth .ngi-heading', 'animation' => 'gsapify-text-blur-in', 'options' => [ 'duration' => 0.8, 'stagger' => 0.03 ], 'mobile' => 'reduced' ],
			[ 'selector' => '#ngt3d-scale-depth .ngt-3d-demo__cta', 'animation' => 'magnetic-button', 'options' => [ 'strength' => 0.35, 'radius' => 120 ], 'mobile' => 'disabled', 'tablet' => 'disabled' ],
			[ 'selector' => '#ngt3d-pin-section .ngt-3d-demo__cta', 'animation' => 'gsapify-magnetic-button', 'options' => [ 'strength' => 0.3, 'radius' => 100 ], 'mobile' => 'disabled', 'tablet' => 'disabled' ],
			[ 'selector' => '#ngt3d-dark-veles .ngt-3d-dv__reveal', 'animation' => 'gsapify-text-fade-up-words', 'options' => [ 'duration' => 0.75, 'stagger' => 0.04 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-scroll-mask .ngt-3d-mask__text', 'animation' => 'gsapify-text-staggered-letters', 'options' => [ 'duration' => 0.5, 'stagger' => 0.03 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-cards-fan .ngt-3d-demo__grid--cards', 'animation' => 'gsapify-card-slide-in-stagger', 'options' => [ 'stagger' => 0.08 ], 'mobile' => 'disabled' ],
			[ 'selector' => '#ngt3d-image-depth h2', 'animation' => 'fade-up', 'options' => [ 'duration' => 0.7, 'distance' => 30 ], 'mobile' => 'reduced' ],
			[ 'selector' => '#ngt3d-section-cinematic h2', 'animation' => 'gsapify-curtain-reveal', 'options' => [ 'duration' => 0.9, 'distance' => 48 ], 'mobile' => 'disabled' ],
		];
	}

	public static function enqueue_demo_assets(): void {
		if ( ! is_page( self::SLUG ) ) {
			return;
		}

		// Ensure engine CSS is available even if dependency order races.
		if ( ! wp_style_is( 'ngt3d-engine', 'enqueued' ) && ! wp_style_is( 'ngt3d-engine', 'registered' ) ) {
			wp_register_style(
				'ngt3d-engine',
				NGT3D_PLUGIN_URL . 'assets/css/ngt-3d-engine.css',
				[],
				NGT3D_VERSION
			);
		}

		$deps = ( wp_style_is( 'ngt3d-engine', 'registered' ) || wp_style_is( 'ngt3d-engine', 'enqueued' ) )
			? [ 'ngt3d-engine' ]
			: [];

		wp_enqueue_style(
			'ngt3d-demo-page',
			NGT3D_PLUGIN_URL . 'assets/css/ngt-3d-demo-page.css',
			$deps,
			(string) filemtime( NGT3D_PLUGIN_DIR . 'assets/css/ngt-3d-demo-page.css' )
		);

		// Keep theme chrome from painting the lab dark-on-dark.
		wp_add_inline_style(
			'ngt3d-demo-page',
			'body.page-template-page-3d-scroll-test{background:#eef3f8!important;color:#132033!important}'
			. 'body.page-template-page-3d-scroll-test .site-content,body.page-template-page-3d-scroll-test #content{background:transparent!important}'
		);
	}

	public static function shortcode(): string {
		ob_start();
		self::render();
		return (string) ob_get_clean();
	}

	/**
	 * Theme photography first; picsum seeds as reliable fallbacks (no broken hotlinks).
	 *
	 * @return array{theme:array<string,string>,web:array<string,string>,pick:callable}
	 */
	public static function media_pack(): array {
		$base = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/images/';
		$theme = [
			'hero'      => $base . 'hero-bg.jpg',
			'video'     => $base . 'home-video.jpg',
			'about'     => $base . 'about-feature.jpg',
			'become'    => $base . 'become-tutor.jpg',
			'pricing'   => $base . 'pricing-bg.jpg',
			'cta'       => $base . 'cta-bg.jpg',
			'guarantee' => $base . 'guarantee-bg.jpg',
		];

		$web = [
			'classroom' => 'https://picsum.photos/seed/ngt-class/1200/750',
			'students'  => 'https://picsum.photos/seed/ngt-study/1200/750',
			'library'   => 'https://picsum.photos/seed/ngt-library/1200/750',
			'mentor'    => 'https://picsum.photos/seed/ngt-mentor/1200/750',
			'desk'      => 'https://picsum.photos/seed/ngt-desk/1200/750',
			'tutor'     => 'https://picsum.photos/seed/ngt-tutor/900/900',
		];

		$pick = static function ( string $key ) use ( $theme, $web ): string {
			return $theme[ $key ] ?? $web[ $key ] ?? $web['classroom'];
		};

		return [
			'theme' => $theme,
			'web'   => $web,
			'pick'  => $pick,
		];
	}

	/**
	 * Real-world tutoring use cases for the lab.
	 *
	 * @return array{trust:array<int,array{title:string,body:string}>,journey:array<int,array{title:string,body:string}>,chips:array<int,string>}
	 */
	public static function front_blend_copy(): array {
		return [
			'trust'   => [
				[
					'title' => __( 'Thandi books Grade 10 Maths help', 'ngt-3d-scroll' ),
					'body'  => __( 'Parent in Gauteng picks CAPS Maths, online evenings, and gets three vetted matches in under 48 hours.', 'ngt-3d-scroll' ),
				],
				[
					'title' => __( 'Siphoâ€™s first lesson guarantee', 'ngt-3d-scroll' ),
					'body'  => __( 'If the chemistry fit is wrong, rematch or refund â€” proof sits on the tutor card before payment.', 'ngt-3d-scroll' ),
				],
				[
					'title' => __( 'Parent dashboard after week 3', 'ngt-3d-scroll' ),
					'body'  => __( 'Session notes, homework completion at 76%, and the next booking slot appear in one place.', 'ngt-3d-scroll' ),
				],
			],
			'journey' => [
				[ 'title' => __( 'Free assessment', 'ngt-3d-scroll' ), 'body' => __( 'Map gaps against CAPS / IEB outcomes.', 'ngt-3d-scroll' ) ],
				[ 'title' => __( 'Tutor match', 'ngt-3d-scroll' ), 'body' => __( 'Filter by subject, grade, province, format.', 'ngt-3d-scroll' ) ],
				[ 'title' => __( 'Learning plan', 'ngt-3d-scroll' ), 'body' => __( 'Agree weekly goals parents can audit.', 'ngt-3d-scroll' ) ],
				[ 'title' => __( 'Live lessons', 'ngt-3d-scroll' ), 'body' => __( 'Book, attend, review notes, rebook.', 'ngt-3d-scroll' ) ],
			],
			'chips'   => [
				__( 'CAPS aligned', 'ngt-3d-scroll' ),
				__( 'IEB ready', 'ngt-3d-scroll' ),
				__( 'Cambridge paths', 'ngt-3d-scroll' ),
				__( 'POPIA aware', 'ngt-3d-scroll' ),
				__( 'First-lesson guarantee', 'ngt-3d-scroll' ),
				__( 'ID-checked tutors', 'ngt-3d-scroll' ),
			],
		];
	}

	public static function render(): void {
		$media = self::media_pack();
		$pick  = $media['pick'];
		$blend = self::front_blend_copy();
		$img   = [
			'hero'      => $pick( 'hero' ),
			'video'     => $pick( 'video' ),
			'about'     => $pick( 'about' ),
			'become'    => $pick( 'become' ),
			'pricing'   => $pick( 'pricing' ),
			'cta'       => $pick( 'cta' ),
			'guarantee' => $pick( 'guarantee' ),
			'classroom' => $media['web']['classroom'],
			'students'  => $media['web']['students'],
			'library'   => $media['web']['library'],
			'mentor'    => $media['web']['mentor'],
			'desk'      => $media['web']['desk'],
			'tutor'     => $media['web']['tutor'],
		];

		$find_url = home_url( '/find-a-tutor/' );
		$home_url = home_url( '/' );

		$film_manual = implode(
			"\n",
			[
				'Lerato Dlamini|' . __( 'Grade 11 Physical Sciences Â· Johannesburg', 'ngt-3d-scroll' ) . '|' . $img['tutor'] . '|' . $find_url,
				'Mathematics|' . __( 'Parent use case: CAPS exam sprint', 'ngt-3d-scroll' ) . '|' . $img['desk'] . '|' . add_query_arg( 'subject', 'mathematics', $find_url ),
				'English HL|' . __( 'Use case: essay structure in 6 weeks', 'ngt-3d-scroll' ) . '|' . $img['library'] . '|' . add_query_arg( 'subject', 'english', $find_url ),
				'Weekly reports|' . __( 'Use case: parent sees progress every Friday', 'ngt-3d-scroll' ) . '|' . $img['mentor'] . '|' . $find_url,
				'Become a tutor|' . __( 'Use case: vetted educator applies in 20 minutes', 'ngt-3d-scroll' ) . '|' . $img['become'] . '|' . home_url( '/become-a-tutor/' ),
				'Family booking|' . __( 'Use case: book assessment after school hours', 'ngt-3d-scroll' ) . '|' . $img['students'] . '|' . $home_url,
			]
		);
		?>
		<div class="ngt-3d-demo">
			<header class="ngt-3d-demo__intro ngt-container">
				<div class="ngt-3d-demo__intro-main">
					<p class="ngt-3d-demo__eyebrow"><?php esc_html_e( 'Product motion lab', 'ngt-3d-scroll' ); ?></p>
					<h1><?php esc_html_e( 'Scroll patterns for real tutoring journeys', 'ngt-3d-scroll' ); ?></h1>
					<p class="ngt-3d-demo__lede"><?php esc_html_e( 'Each block maps a NextGen use case â€” parent booking, subject choice, tutor proof, weekly progress â€” so motion stays tied to decisions families actually make.', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__metrics" aria-label="<?php esc_attr_e( 'Example outcomes', 'ngt-3d-scroll' ); ?>">
						<div class="ngt-3d-demo__metric"><strong>48h</strong><span><?php esc_html_e( 'typical match window', 'ngt-3d-scroll' ); ?></span></div>
						<div class="ngt-3d-demo__metric"><strong>R320+</strong><span><?php esc_html_e( 'hourly packages shown live', 'ngt-3d-scroll' ); ?></span></div>
						<div class="ngt-3d-demo__metric"><strong>76%</strong><span><?php esc_html_e( 'sample homework completion', 'ngt-3d-scroll' ); ?></span></div>
					</div>
				</div>
				<aside class="ngt-3d-demo__usecases-panel">
					<h2><?php esc_html_e( 'Use cases on this page', 'ngt-3d-scroll' ); ?></h2>
					<ol>
						<li><?php esc_html_e( 'Parent lands after searching for a Maths tutor and needs confidence fast.', 'ngt-3d-scroll' ); ?></li>
						<li><?php esc_html_e( 'Learner picks a subject track mapped to CAPS / IEB / Cambridge.', 'ngt-3d-scroll' ); ?></li>
						<li><?php esc_html_e( 'Family compares vetted tutors, then books a free assessment.', 'ngt-3d-scroll' ); ?></li>
						<li><?php esc_html_e( 'Educator applies to teach; dashboard and guarantee close the loop.', 'ngt-3d-scroll' ); ?></li>
					</ol>
				</aside>
				<ul class="ngt-3d-demo__toc">
					<li><a href="#ngt3d-showcase-subjects"><?php esc_html_e( 'Subjects search', 'ngt-3d-scroll' ); ?></a></li>
					<li><a href="#ngt3d-showcase-filmstrip"><?php esc_html_e( 'Tutor filmstrip', 'ngt-3d-scroll' ); ?></a></li>
					<?php foreach ( self::rule_map() as $row ) : ?>
						<li><a href="<?php echo esc_url( '#' . ltrim( $row['selector'], '#' ) ); ?>"><?php echo esc_html( $row['animation'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</header>

			<section id="ngt3d-showcase-subjects" class="ngt-3d-demo__section ngt-3d-demo__section--showcase" data-ngt-showcase="subjects-widget">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">subjects-widget</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: search â€œPhysical Sciencesâ€ before opening Find a Tutor', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__section-head">
						<p class="ngt-3d-demo__eyebrow"><?php esc_html_e( 'Live catalog', 'ngt-3d-scroll' ); ?></p>
						<h2><?php esc_html_e( 'Subject explorer parents actually use', 'ngt-3d-scroll' ); ?></h2>
						<p><?php esc_html_e( 'Same searchable grid as production â€” filter the catalog, then jump into matching tutors.', 'ngt-3d-scroll' ); ?></p>
					</div>
					<div class="ngt-3d-demo__plugin-stage">
						<?php
						if ( shortcode_exists( 'nextgen_subjects' ) ) {
							echo do_shortcode( '[nextgen_subjects title="Browse subjects for your learner" subtitle="CAPS, IEB and Cambridge tracks with clear next steps." columns="4" show_search="yes" source="auto"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							echo '<p class="ngt-3d-demo__note">' . esc_html__( 'Activate the NextGen Subjects Widget plugin to render this showcase.', 'ngt-3d-scroll' ) . '</p>';
						}
						?>
					</div>
				</div>
			</section>

			<section id="ngt3d-showcase-filmstrip" class="ngt-3d-demo__section ngt-3d-demo__section--showcase" data-ngt-showcase="3d-filmstrip">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">3d-filmstrip</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: swipe tutor cards like a marketplace shortlist', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__section-head">
						<p class="ngt-3d-demo__eyebrow"><?php esc_html_e( 'Perspective deck', 'ngt-3d-scroll' ); ?></p>
						<h2><?php esc_html_e( 'Shortlist tutors and pathways in 3D', 'ngt-3d-scroll' ); ?></h2>
						<p><?php esc_html_e( 'Named educators, subject sprints, and booking destinations â€” drag, swipe, or use arrows.', 'ngt-3d-scroll' ); ?></p>
					</div>
					<div class="ngt-3d-demo__plugin-stage ngt-3d-demo__plugin-stage--film">
						<?php
						if ( class_exists( 'NGTFS_Renderer' ) && class_exists( 'NGTFS_Data' ) ) {
							$manual_cards = NGTFS_Data::get_cards(
								[
									'source' => 'manual',
									'limit'  => 6,
									'manual' => $film_manual,
								]
							);
							echo NGTFS_Renderer::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$manual_cards,
								[
									'title'    => __( 'Real journeys on cards', 'ngt-3d-scroll' ),
									'subtitle' => __( 'From Grade 11 Sciences to Friday progress reports â€” each card is a decision point.', 'ngt-3d-scroll' ),
									'source'   => 'manual',
									'autoplay' => true,
									'loop'     => true,
									'class'    => 'ngt-3d-demo-filmstrip',
								]
							);

							$tutor_cards = NGTFS_Data::get_cards(
								[
									'source' => 'tutors',
									'limit'  => 8,
								]
							);
							if ( $tutor_cards ) {
								echo NGTFS_Renderer::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									$tutor_cards,
									[
										'title'    => __( 'Marketplace tutors', 'ngt-3d-scroll' ),
										'subtitle' => __( 'Live profiles when Companion / theme tutor data is available.', 'ngt-3d-scroll' ),
										'source'   => 'tutors',
										'autoplay' => false,
										'loop'     => true,
										'class'    => 'ngt-3d-demo-filmstrip-tutors',
									]
								);
							} else {
								echo do_shortcode( '[ngt_filmstrip source="subjects" title="Subjects shortlist" subtitle="Catalog cards when tutor photos are not seeded yet." limit="8" autoplay="yes"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						} elseif ( shortcode_exists( 'ngt_filmstrip' ) ) {
							echo do_shortcode( '[ngt_filmstrip source="subjects" limit="8" autoplay="yes"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							echo '<p class="ngt-3d-demo__note">' . esc_html__( 'Activate the NextGen 3D Filmstrip plugin to render this showcase.', 'ngt-3d-scroll' ) . '</p>';
						}
						?>
					</div>
				</div>
			</section>

			<section id="ngt3d-zoom" class="ngt-3d-demo__section" data-ngt-3d-target="zoom">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">zoom</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: first viewport after Google â€œmath tutor near meâ€', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-zoom__target" data-ngt-zoom>
						<img src="<?php echo esc_url( $img['hero'] ); ?>" alt="<?php esc_attr_e( 'Parent-facing homepage hero', 'ngt-3d-scroll' ); ?>" width="800" height="500" loading="eager" />
					</div>
					<h2><?php esc_html_e( 'Hero zooms into the brand promise', 'ngt-3d-scroll' ); ?></h2>
					<p class="ngt-3d-demo__note"><?php esc_html_e( 'Motion draws attention to the assessment CTA without hiding copy or contrast.', 'ngt-3d-scroll' ); ?></p>
				</div>
			</section>

			<section id="ngt3d-doublescroll" class="ngt-3d-demo__section ngt-3d-demo__section--tall" data-ngt-3d-target="doublescroll">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">doublescroll</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: two audiences â€” parents discovering vs tutors advancing', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-ds__cols" data-ngt-cols>
						<div class="ngt-3d-ds__left" data-ngt-track="left">
							<h2 class="ngt-3d-ds__title" data-ngt-title><?php esc_html_e( 'For families', 'ngt-3d-scroll' ); ?></h2>
							<p><?php esc_html_e( 'Find a Tutor', 'ngt-3d-scroll' ); ?></p>
							<p><?php esc_html_e( 'Transparent pricing', 'ngt-3d-scroll' ); ?></p>
							<p><?php esc_html_e( 'First-lesson guarantee', 'ngt-3d-scroll' ); ?></p>
							<p><?php esc_html_e( 'Child safety guide', 'ngt-3d-scroll' ); ?></p>
							<p><?php esc_html_e( 'Subject explorer', 'ngt-3d-scroll' ); ?></p>
							<p><?php esc_html_e( 'Parent dashboard', 'ngt-3d-scroll' ); ?></p>
						</div>
						<div class="ngt-3d-ds__right" data-ngt-track="right">
							<h2 class="ngt-3d-ds__title" data-ngt-title><?php esc_html_e( 'For educators', 'ngt-3d-scroll' ); ?></h2>
							<p><?php esc_html_e( 'Become a Tutor', 'ngt-3d-scroll' ); ?></p>
							<p><?php esc_html_e( 'ID + background vetting', 'ngt-3d-scroll' ); ?></p>
							<p><?php esc_html_e( 'Tutor dashboard', 'ngt-3d-scroll' ); ?></p>
							<p><?php esc_html_e( 'Lesson notes & payouts', 'ngt-3d-scroll' ); ?></p>
							<p><?php esc_html_e( 'Onboarding checklist', 'ngt-3d-scroll' ); ?></p>
							<p><?php esc_html_e( 'Support desk', 'ngt-3d-scroll' ); ?></p>
						</div>
					</div>
				</div>
			</section>

			<section id="ngt3d-4kvideo" class="ngt-3d-demo__section ngt-3d-demo__section--media" data-ngt-3d-target="4kvideo">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">4kvideo</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: watch how the first online lesson feels', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-4k__media" data-ngt-media>
						<img src="<?php echo esc_url( $img['video'] ); ?>" alt="<?php esc_attr_e( 'Story video still from the homepage', 'ngt-3d-scroll' ); ?>" width="960" height="540" loading="lazy" />
					</div>
					<h2><?php esc_html_e( 'Story media locks focus while you scroll', 'ngt-3d-scroll' ); ?></h2>
					<p class="ngt-3d-demo__note"><?php esc_html_e( 'Parents get a clear picture of the session format before booking.', 'ngt-3d-scroll' ); ?></p>
				</div>
			</section>

			<section id="ngt3d-wiper" class="ngt-3d-demo__section" data-ngt-3d-target="wiper">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">wiper</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: reveal â€œafter tutoringâ€ confidence vs the worried baseline', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-wiper__panel" data-ngt-wipe>
						<img src="<?php echo esc_url( $img['about'] ); ?>" alt="<?php esc_attr_e( 'Learning progress imagery', 'ngt-3d-scroll' ); ?>" width="800" height="420" loading="lazy" />
					</div>
					<h2><?php esc_html_e( 'Progress reveal for report-card anxiety', 'ngt-3d-scroll' ); ?></h2>
				</div>
			</section>

			<section id="ngt3d-dark-veles" class="ngt-3d-demo__section" data-ngt-3d-target="dark-veles">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">dark-veles</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: prove trust before asking for payment details', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-dv__section" data-ngt-pace>
						<h2 class="ngt-3d-dv__reveal" data-ngt-reveal><?php esc_html_e( 'Verified tutors. Visible dashboards. Rematch guarantee.', 'ngt-3d-scroll' ); ?></h2>
						<p class="ngt-3d-dv__reveal" data-ngt-reveal><?php esc_html_e( 'Scroll pacing stacks the reasons a cautious parent keeps reading.', 'ngt-3d-scroll' ); ?></p>
						<img class="ngt-3d-dv__media" data-ngt-scale src="<?php echo esc_url( $img['guarantee'] ); ?>" alt="<?php esc_attr_e( 'Guarantee visual', 'ngt-3d-scroll' ); ?>" width="720" height="400" loading="lazy" />
					</div>
				</div>
			</section>

			<section id="ngt3d-scroll-mask" class="ngt-3d-demo__section" data-ngt-3d-target="scroll-mask">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">scroll-mask</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: brand line that should stay readable on light surfaces', 'ngt-3d-scroll' ); ?></p>
					<h2 class="ngt-3d-mask__text" data-ngt-mask><?php esc_html_e( 'NextGen Tutors', 'ngt-3d-scroll' ); ?></h2>
					<h3 class="ngt-3d-mask__text" data-ngt-mask><?php esc_html_e( 'progress you can audit', 'ngt-3d-scroll' ); ?></h3>
					<p class="ngt-mask-line" data-ngt-mask><?php esc_html_e( 'Typography reveals with scroll â€” ink stays high-contrast on the lab surface.', 'ngt-3d-scroll' ); ?></p>
				</div>
			</section>

			<section id="ngt3d-onscroll" class="ngt-3d-demo__section" data-ngt-3d-target="onscroll">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">onscroll</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: subject tiles enter as the learner explores options', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__grid">
						<figure class="ngt-3d-onscroll__item" data-ngt-enter><img src="<?php echo esc_url( $img['classroom'] ); ?>" alt="<?php esc_attr_e( 'Classroom learning', 'ngt-3d-scroll' ); ?>" loading="lazy" /><figcaption><?php esc_html_e( 'Sciences lab prep', 'ngt-3d-scroll' ); ?></figcaption></figure>
						<figure class="ngt-3d-onscroll__item" data-ngt-enter><img src="<?php echo esc_url( $img['students'] ); ?>" alt="<?php esc_attr_e( 'Students studying', 'ngt-3d-scroll' ); ?>" loading="lazy" /><figcaption><?php esc_html_e( 'Peer study habits', 'ngt-3d-scroll' ); ?></figcaption></figure>
						<figure class="ngt-3d-onscroll__item" data-ngt-enter><img src="<?php echo esc_url( $img['library'] ); ?>" alt="<?php esc_attr_e( 'Library study', 'ngt-3d-scroll' ); ?>" loading="lazy" /><figcaption><?php esc_html_e( 'Language & literature', 'ngt-3d-scroll' ); ?></figcaption></figure>
						<figure class="ngt-3d-onscroll__item" data-ngt-enter><img src="<?php echo esc_url( $img['mentor'] ); ?>" alt="<?php esc_attr_e( 'Mentor session', 'ngt-3d-scroll' ); ?>" loading="lazy" /><figcaption><?php esc_html_e( '1:1 mentoring', 'ngt-3d-scroll' ); ?></figcaption></figure>
					</div>
					<h2><?php esc_html_e( 'Subject moments arrive as you scroll', 'ngt-3d-scroll' ); ?></h2>
				</div>
			</section>

			<section id="ngt3d-swag-card" class="ngt-3d-demo__section ngt-3d-demo__section--tall ngt-3d-swag" data-ngt-3d-target="swag-card">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">swag-card</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: peel through onboarding steps while the stage stays sticky', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__section-head">
						<p class="ngt-3d-demo__eyebrow"><?php esc_html_e( 'Sticky card stack', 'ngt-3d-scroll' ); ?></p>
						<h2><?php esc_html_e( 'Journey cards stack, then peel', 'ngt-3d-scroll' ); ?></h2>
						<p><?php esc_html_e( 'Recreated from the Swag sticky-card motion — each step rises into place so parents can follow assessment to first lesson without losing context.', 'ngt-3d-scroll' ); ?></p>
					</div>
					<div class="ngt-3d-swag__stage" data-ngt-swag-stage>
						<?php
						$swag_cards = [
							[ $blend['journey'][0]['title'] ?? __( 'Free assessment', 'ngt-3d-scroll' ), $blend['journey'][0]['body'] ?? __( 'Baseline gaps captured in one session.', 'ngt-3d-scroll' ), $img['desk'] ],
							[ $blend['journey'][1]['title'] ?? __( 'Matched tutor', 'ngt-3d-scroll' ), $blend['journey'][1]['body'] ?? __( 'Vetted educator confirmed within 48 hours.', 'ngt-3d-scroll' ), $img['tutor'] ],
							[ $blend['journey'][2]['title'] ?? __( 'Weekly plan', 'ngt-3d-scroll' ), $blend['journey'][2]['body'] ?? __( 'Goals parents can audit every Friday.', 'ngt-3d-scroll' ), $img['classroom'] ],
							[ $blend['journey'][3]['title'] ?? __( 'First lesson', 'ngt-3d-scroll' ), $blend['journey'][3]['body'] ?? __( 'Love it — or rematch under the guarantee.', 'ngt-3d-scroll' ), $img['mentor'] ],
						];
						foreach ( $swag_cards as $i => $card ) :
							?>
							<article class="ngt-3d-swag__card" data-ngt-swag-card>
								<img src="<?php echo esc_url( $card[2] ); ?>" alt="" width="420" height="220" loading="<?php echo 0 === $i ? 'eager' : 'lazy'; ?>" />
								<div class="ngt-3d-swag__card-body">
									<span class="ngt-3d-demo__step"><?php echo (int) ( $i + 1 ); ?></span>
									<h3><?php echo esc_html( $card[0] ); ?></h3>
									<p><?php echo esc_html( $card[1] ); ?></p>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
					<p class="ngt-3d-demo__note"><?php esc_html_e( 'Desktop pin + scrub; reduced on tablet; static when prefers-reduced-motion.', 'ngt-3d-scroll' ); ?></p>
				</div>
			</section>

			<section id="ngt3d-transforms" class="ngt-3d-demo__section ngt-3d-transforms" data-ngt-3d-target="transforms">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">transforms</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: 3D gallery for proof moments before booking', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__section-head">
						<p class="ngt-3d-demo__eyebrow"><?php esc_html_e( 'Perspective transforms', 'ngt-3d-scroll' ); ?></p>
						<h2><?php esc_html_e( 'Proof tiles swing into flat readability', 'ngt-3d-scroll' ); ?></h2>
						<p><?php esc_html_e( 'Framer scroll-transform language — rotateY/X, translateZ, scale — so classroom, tutor, and guarantee imagery feel spatial then settle for reading.', 'ngt-3d-scroll' ); ?></p>
					</div>
					<div class="ngt-3d-transforms__stage" data-ngt-transforms-stage>
						<figure class="ngt-3d-transforms__item" data-ngt-transforms-item>
							<img src="<?php echo esc_url( $img['classroom'] ); ?>" alt="<?php esc_attr_e( 'Classroom session', 'ngt-3d-scroll' ); ?>" width="480" height="200" loading="lazy" />
							<figcaption><?php esc_html_e( 'Live session clarity', 'ngt-3d-scroll' ); ?></figcaption>
						</figure>
						<figure class="ngt-3d-transforms__item" data-ngt-transforms-item>
							<img src="<?php echo esc_url( $img['students'] ); ?>" alt="<?php esc_attr_e( 'Students collaborating', 'ngt-3d-scroll' ); ?>" width="480" height="200" loading="lazy" />
							<figcaption><?php esc_html_e( 'Peer study habits', 'ngt-3d-scroll' ); ?></figcaption>
						</figure>
						<figure class="ngt-3d-transforms__item" data-ngt-transforms-item>
							<img src="<?php echo esc_url( $img['library'] ); ?>" alt="<?php esc_attr_e( 'Library study', 'ngt-3d-scroll' ); ?>" width="480" height="200" loading="lazy" />
							<figcaption><?php esc_html_e( 'Subject depth', 'ngt-3d-scroll' ); ?></figcaption>
						</figure>
						<figure class="ngt-3d-transforms__item" data-ngt-transforms-item>
							<img src="<?php echo esc_url( $img['guarantee'] ); ?>" alt="<?php esc_attr_e( 'Guarantee visual', 'ngt-3d-scroll' ); ?>" width="480" height="200" loading="lazy" />
							<figcaption><?php esc_html_e( 'Risk-reversal promise', 'ngt-3d-scroll' ); ?></figcaption>
						</figure>
					</div>
					<p class="ngt-3d-demo__note"><?php esc_html_e( 'Original transforms.framer.website changed; motion follows Academy scroll-transform patterns with tutoring media.', 'ngt-3d-scroll' ); ?></p>
				</div>
			</section>

			<section id="ngt3d-depth-scroll" class="ngt-3d-demo__section ngt-3d-demo__section--depth" style="--ngt-demo-depth-bg:url('<?php echo esc_url( $img['pricing'] ); ?>')">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">depth-scroll</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: explain pricing without burying the rates', 'ngt-3d-scroll' ); ?></p>
					<h2><?php esc_html_e( 'Packages stay readable over soft depth', 'ngt-3d-scroll' ); ?></h2>
					<p><?php esc_html_e( 'Parallax atmosphere, light wash â€” copy and rates remain ink-on-wash, never dark-on-dark.', 'ngt-3d-scroll' ); ?></p>
				</div>
			</section>

			<section id="ngt3d-perspective-reveal" class="ngt-3d-demo__section">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">perspective-reveal</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: show the path from assessment to improvement', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__card">
						<p class="ngt-3d-demo__eyebrow"><?php esc_html_e( 'Learner journey', 'ngt-3d-scroll' ); ?></p>
						<h2><?php esc_html_e( 'From free assessment to measurable weekly gains', 'ngt-3d-scroll' ); ?></h2>
						<p><?php esc_html_e( 'Panel tilts into place as the section centers â€” same story parents hear on the homepage.', 'ngt-3d-scroll' ); ?></p>
					</div>
				</div>
			</section>

			<section id="ngt3d-scale-depth" class="ngt-3d-demo__section">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">scale-depth</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: primary conversion â€” book free assessment', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__card ngt-3d-demo__card--lg ngt-3d-demo__card--media" style="background-image:linear-gradient(180deg,rgba(19,32,51,.42),rgba(19,32,51,.72)),url('<?php echo esc_url( $img['cta'] ); ?>')">
						<h2><?php esc_html_e( 'Book a free assessment this week', 'ngt-3d-scroll' ); ?></h2>
						<p><a class="ngt-3d-demo__cta" href="<?php echo esc_url( $find_url ); ?>"><?php esc_html_e( 'Start matching', 'ngt-3d-scroll' ); ?></a></p>
					</div>
				</div>
			</section>

			<section id="ngt3d-rotate-x" class="ngt-3d-demo__section">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">rotate-x-scroll</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: micro-delight on a pricing or plan card', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__card">
						<h2><?php esc_html_e( 'Flat monthly packages tilt into view', 'ngt-3d-scroll' ); ?></h2>
						<p><?php esc_html_e( 'Keep plan names and hourly figures fully visible while the card rotates on X.', 'ngt-3d-scroll' ); ?></p>
					</div>
				</div>
			</section>

			<section id="ngt3d-rotate-y" class="ngt-3d-demo__section">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">rotate-y-scroll</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: flip attention between learner and parent views', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__card">
						<h2><?php esc_html_e( 'Student plan â†” parent report', 'ngt-3d-scroll' ); ?></h2>
						<p><?php esc_html_e( 'Y-axis motion suggests two linked dashboards without hiding either label.', 'ngt-3d-scroll' ); ?></p>
					</div>
				</div>
			</section>

			<section id="ngt3d-translate-z" class="ngt-3d-demo__section">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">translate-z</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: pull a key guarantee line toward the reader', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__card">
						<h2><?php esc_html_e( 'Love the first lesson â€” or rematch', 'ngt-3d-scroll' ); ?></h2>
						<p><?php esc_html_e( 'Z-depth emphasizes the risk-reversal promise without reducing contrast.', 'ngt-3d-scroll' ); ?></p>
					</div>
				</div>
			</section>

			<section id="ngt3d-stagger-depth" class="ngt-3d-demo__section">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">stagger-depth</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: calm a worried parent with staged proof', 'ngt-3d-scroll' ); ?></p>
					<p class="ngt-3d-demo__eyebrow"><?php esc_html_e( 'Trusted learning ecosystem', 'ngt-3d-scroll' ); ?></p>
					<h2><?php esc_html_e( 'Stories parents recognise from their own week', 'ngt-3d-scroll' ); ?></h2>
					<div class="ngt-3d-demo__grid ngt-3d-demo__grid--cards ngt-3d-demo__grid--trust">
						<?php foreach ( $blend['trust'] as $card ) : ?>
							<article class="ngt-3d-demo__card">
								<h3><?php echo esc_html( $card['title'] ); ?></h3>
								<p><?php echo esc_html( $card['body'] ); ?></p>
							</article>
						<?php endforeach; ?>
						<article class="ngt-3d-demo__card ngt-3d-demo__card--chips">
							<?php foreach ( $blend['chips'] as $chip ) : ?>
								<span><?php echo esc_html( $chip ); ?></span>
							<?php endforeach; ?>
						</article>
					</div>
				</div>
			</section>

			<section id="ngt3d-stack-3d" class="ngt-3d-demo__section ngt-3d-demo__section--tall">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">stack-3d</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: onboarding checklist stacked in depth', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__stack" data-bi-stack-3d>
						<?php foreach ( $blend['journey'] as $i => $step ) : ?>
							<article class="ngt-3d-demo__card">
								<span class="ngt-3d-demo__step"><?php echo (int) ( $i + 1 ); ?></span>
								<h3><?php echo esc_html( $step['title'] ); ?></h3>
								<p><?php echo esc_html( $step['body'] ); ?></p>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>

			<section id="ngt3d-cards-fan" class="ngt-3d-demo__section">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">cards-fan</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: pick a learning lane before booking', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__grid ngt-3d-demo__grid--cards">
						<article class="ngt-3d-demo__card"><img src="<?php echo esc_url( $img['desk'] ); ?>" alt="" loading="lazy" /><h3><?php esc_html_e( 'Mathematics', 'ngt-3d-scroll' ); ?></h3><p><?php esc_html_e( 'Exam sprints & homework loops', 'ngt-3d-scroll' ); ?></p></article>
						<article class="ngt-3d-demo__card"><img src="<?php echo esc_url( $img['classroom'] ); ?>" alt="" loading="lazy" /><h3><?php esc_html_e( 'Sciences', 'ngt-3d-scroll' ); ?></h3><p><?php esc_html_e( 'Labs, formulas, paper practice', 'ngt-3d-scroll' ); ?></p></article>
						<article class="ngt-3d-demo__card"><img src="<?php echo esc_url( $img['library'] ); ?>" alt="" loading="lazy" /><h3><?php esc_html_e( 'Languages', 'ngt-3d-scroll' ); ?></h3><p><?php esc_html_e( 'Reading, writing, orals', 'ngt-3d-scroll' ); ?></p></article>
						<article class="ngt-3d-demo__card"><img src="<?php echo esc_url( $img['become'] ); ?>" alt="" loading="lazy" /><h3><?php esc_html_e( 'Teach with us', 'ngt-3d-scroll' ); ?></h3><p><?php esc_html_e( 'Apply, get vetted, go live', 'ngt-3d-scroll' ); ?></p></article>
					</div>
				</div>
			</section>

			<section id="ngt3d-pin-section" class="ngt-3d-demo__section ngt-3d-demo__section--pin">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">pin-section</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: keep â€œGet helpâ€ visible while options scroll by', 'ngt-3d-scroll' ); ?></p>
					<h2><?php esc_html_e( 'Pinned help while you compare subjects', 'ngt-3d-scroll' ); ?></h2>
					<p><?php esc_html_e( 'The CTA stays in view so a parent never loses the next step.', 'ngt-3d-scroll' ); ?></p>
					<p><a class="ngt-3d-demo__cta" href="<?php echo esc_url( $find_url ); ?>"><?php esc_html_e( 'Get subject help', 'ngt-3d-scroll' ); ?></a></p>
				</div>
			</section>

			<section id="ngt3d-horizontal" class="ngt-3d-demo__section ngt-3d-demo__section--horizontal">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">horizontal-scroll</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: week-by-week timeline after the first booking', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__htrack">
						<?php
						$panels = [
							[ __( 'Week 0 Â· Assessment', 'ngt-3d-scroll' ), $img['desk'], __( 'Baseline gaps captured', 'ngt-3d-scroll' ) ],
							[ __( 'Week 1 Â· Match', 'ngt-3d-scroll' ), $img['tutor'], __( 'Vetted tutor confirmed', 'ngt-3d-scroll' ) ],
							[ __( 'Week 2 Â· Plan', 'ngt-3d-scroll' ), $img['classroom'], __( 'Goals parents can audit', 'ngt-3d-scroll' ) ],
							[ __( 'Week 3 Â· Lessons', 'ngt-3d-scroll' ), $img['students'], __( 'Notes + homework loop', 'ngt-3d-scroll' ) ],
							[ __( 'Week 4 Â· Report', 'ngt-3d-scroll' ), $img['mentor'], __( 'Progress shared Friday', 'ngt-3d-scroll' ) ],
						];
						foreach ( $panels as $panel ) :
							?>
							<article class="ngt-3d-demo__card">
								<img src="<?php echo esc_url( $panel[1] ); ?>" alt="" loading="lazy" />
								<h3><?php echo esc_html( $panel[0] ); ?></h3>
								<p><?php echo esc_html( $panel[2] ); ?></p>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>

			<section id="ngt3d-image-depth" class="ngt-3d-demo__section">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">image-depth</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: recruit tutors without burying the apply CTA', 'ngt-3d-scroll' ); ?></p>
					<img class="bi-theme-image" src="<?php echo esc_url( $img['become'] ); ?>" alt="<?php esc_attr_e( 'Become a tutor imagery', 'ngt-3d-scroll' ); ?>" width="800" height="420" loading="lazy" />
					<h2><?php esc_html_e( 'Educators see themselves in the frame', 'ngt-3d-scroll' ); ?></h2>
					<p><a class="ngt-3d-demo__cta" href="<?php echo esc_url( home_url( '/become-a-tutor/' ) ); ?>"><?php esc_html_e( 'Apply to teach', 'ngt-3d-scroll' ); ?></a></p>
				</div>
			</section>

			<section id="ngt3d-text-depth" class="ngt-3d-demo__section">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">text-depth</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: subject change updates the plan in front of the parent', 'ngt-3d-scroll' ); ?></p>
					<h2 class="ngi-heading"><?php esc_html_e( 'Pick Physical Sciences â€” the plan adapts in place', 'ngt-3d-scroll' ); ?></h2>
				</div>
			</section>

			<section id="ngt3d-section-cinematic" class="ngt-3d-demo__section">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">section-cinematic</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: cinematic beat before the booking modal', 'ngt-3d-scroll' ); ?></p>
					<h2><?php esc_html_e( 'See how a NextGen session actually runs', 'ngt-3d-scroll' ); ?></h2>
					<p class="ngt-3d-demo__note"><?php esc_html_e( 'Transition softens the cut into video or modal without lowering text contrast.', 'ngt-3d-scroll' ); ?></p>
				</div>
			</section>

			<section id="ngt3d-tilt" class="ngt-3d-demo__section">
				<div class="ngt-container">
					<span class="ngt-3d-demo__label">tilt-3d</span>
					<p class="ngt-3d-demo__usecase"><?php esc_html_e( 'Use case: hover proof cards before committing', 'ngt-3d-scroll' ); ?></p>
					<div class="ngt-3d-demo__grid ngt-3d-demo__grid--cards ngt-3d-demo__grid--trust">
						<?php foreach ( $blend['trust'] as $card ) : ?>
							<article class="ngt-3d-demo__card bi-tilt-3d" data-bi-tilt>
								<h3><?php echo esc_html( $card['title'] ); ?></h3>
								<p><?php echo esc_html( $card['body'] ); ?></p>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>

			<footer class="ngt-3d-demo__outro ngt-container">
				<p><?php esc_html_e( 'Tune any block in WP Admin â†’ 3D Scrolling. Rules stream through window.NGT3D â€” keep intensity subtle so use-case copy stays readable.', 'ngt-3d-scroll' ); ?></p>
				<p><a href="<?php echo esc_url( $home_url ); ?>"><?php esc_html_e( 'Live front page', 'ngt-3d-scroll' ); ?></a>
					Â· <a href="<?php echo esc_url( home_url( '/home-3d/' ) ); ?>"><?php esc_html_e( 'Home 3D preview', 'ngt-3d-scroll' ); ?></a>
					Â· <a href="<?php echo esc_url( admin_url( 'admin.php?page=ngt-3d-scroll' ) ); ?>"><?php esc_html_e( 'Edit 3D rules', 'ngt-3d-scroll' ); ?></a></p>
			</footer>
		</div>
		<?php
	}
}

