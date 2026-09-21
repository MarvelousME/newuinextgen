<?php
/**
 * NGI page system — shared .ngi-* section builders for site-wide kinetic pages.
 *
 * @package TutorFabulous
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Force theme PHP (.ngi-*) over Elementor/WPBakery stored content on front-end.
 *
 * @return bool
 */
function bi_ngi_force_theme_defaults() {
	if ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) {
		return false;
	}
	return (bool) apply_filters( 'bi_ngi_force_theme_defaults', true );
}

/**
 * Whether a post is a theme-managed kinetic page (registry or front page).
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function bi_ngi_is_managed_page( $post_id = 0 ) {
	if ( function_exists( 'is_front_page' ) && is_front_page() ) {
		return true;
	}
	$post_id = $post_id ?: ( function_exists( 'bi_get_current_page_id' ) ? bi_get_current_page_id() : get_queried_object_id() );
	if ( ! $post_id ) {
		return true;
	}
	$slug = (string) get_post_field( 'post_name', $post_id );
	$reg  = function_exists( 'bi_pages_registry' ) ? bi_pages_registry() : [];
	return isset( $reg[ $slug ] );
}

/**
 * Open .ngi-home page root.
 *
 * @param string               $slug  Page slug.
 * @param array<string,mixed>  $args  Extra classes.
 */
function bi_ngi_page_open( $slug, $args = [] ) {
	$slug    = sanitize_key( $slug );
	$extra   = isset( $args['class'] ) ? ' ' . sanitize_html_class( (string) $args['class'] ) : '';
	$role    = isset( $args['role'] ) ? sanitize_html_class( (string) $args['role'] ) : '';
	$classes = trim( 'ngi-home ngi-page ngi-page--' . $slug . $extra . ( $role ? ' ngi-page--' . $role : '' ) );
	printf(
		'<div class="%s" id="ngi-page-%s" data-page-slug="%s">',
		esc_attr( $classes ),
		esc_attr( $slug ),
		esc_attr( $slug )
	);
}

/**
 * Close .ngi-home page root.
 */
function bi_ngi_page_close() {
	echo '</div>';
}

/**
 * Kinetic hero matching homepage language.
 *
 * @param array<string,mixed> $args Hero args.
 */
function bi_ngi_hero( $args = [] ) {
	$badge     = (string) ( $args['badge'] ?? '' );
	$title     = (string) ( $args['title'] ?? get_the_title() );
	$accent    = (string) ( $args['accent'] ?? '' );
	$lead      = (string) ( $args['lead'] ?? '' );
	$primary_l = (string) ( $args['primary_label'] ?? '' );
	$primary_u = (string) ( $args['primary_url'] ?? '' );
	$second_l  = (string) ( $args['secondary_label'] ?? '' );
	$second_u  = (string) ( $args['secondary_url'] ?? '' );
	$show_stats = ! empty( $args['show_stats'] );
	$aria       = (string) ( $args['aria'] ?? $title );

	$video  = function_exists( 'bi_tutoring_video_url' ) ? bi_tutoring_video_url( bi_page_slug() ) : '';
	$poster = function_exists( 'bi_tutoring_video_poster_url' ) ? bi_tutoring_video_poster_url( bi_page_slug() ) : '';
	$hero_class = 'ngi-hero ngi-hero--theme ngi-hero--cinematic';
	if ( $video || $poster ) {
		$hero_class .= ' ngi-hero--has-video';
	}
	?>
	<section class="<?php echo esc_attr( $hero_class ); ?>" aria-label="<?php echo esc_attr( $aria ); ?>">
		<?php if ( $video ) : ?>
			<video class="ngi-hero-video" data-bi-cinematic muted loop playsinline preload="metadata" <?php echo $poster ? 'poster="' . esc_url( $poster ) . '"' : ''; ?> aria-hidden="true">
				<source src="<?php echo esc_url( $video ); ?>" type="video/mp4" />
			</video>
		<?php endif; ?>
		<div class="ngi-kh-mesh" aria-hidden="true"></div>
		<div class="ngi-wrap">
			<div class="ngi-hero-grid ngi-hero-grid--page">
				<div>
					<?php if ( $badge ) : ?>
						<div class="ngi-badge ngi-reveal">
							<?php echo function_exists( 'bi_kinetic_icon' ) ? bi_kinetic_icon( 'bolt' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php echo esc_html( $badge ); ?></span>
						</div>
					<?php endif; ?>
					<h1 class="ngi-title ngi-reveal">
						<?php echo esc_html( $title ); ?>
						<?php if ( $accent ) : ?>
							<span class="ngi-accent"><?php echo esc_html( $accent ); ?></span>
						<?php endif; ?>
					</h1>
					<?php if ( $lead ) : ?>
						<p class="ngi-lead ngi-reveal"><?php echo esc_html( $lead ); ?></p>
					<?php endif; ?>
					<?php if ( $primary_l || $second_l ) : ?>
						<div class="ngi-actions ngi-reveal">
							<?php if ( $primary_l && $primary_u ) : ?>
								<a class="ngi-btn ngi-btn-primary ngi-magnetic" href="<?php echo esc_url( $primary_u ); ?>"><?php echo esc_html( $primary_l ); ?></a>
							<?php endif; ?>
							<?php if ( $second_l && $second_u ) : ?>
								<a class="ngi-btn ngi-btn-secondary" href="<?php echo esc_url( $second_u ); ?>"><?php echo esc_html( $second_l ); ?></a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
					<?php if ( $show_stats && function_exists( 'bi_real_stat_cards' ) ) : ?>
						<div class="ngi-stats ngi-reveal" aria-label="<?php esc_attr_e( 'Platform statistics', 'beyondinfinity' ); ?>">
							<?php foreach ( array_slice( bi_real_stat_cards(), 0, 4 ) as $stat ) : ?>
								<div class="ngi-stat">
									<strong data-count="<?php echo esc_attr( (string) $stat['count'] ); ?>" data-suffix="<?php echo esc_attr( $stat['suffix'] ); ?>">0<?php echo esc_html( $stat['suffix'] ); ?></strong>
									<small><?php echo esc_html( $stat['label'] ); ?></small>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="ngi-visual ngi-visual--page" data-kh-ambient-visual aria-hidden="true">
					<div class="ngi-glow"></div>
					<div class="ngi-panel ngi-reveal" data-kh-motion-container>
						<div class="ngi-panel-head">
							<div>
								<h2 style="margin:0"><?php echo esc_html( $title ); ?></h2>
								<small><?php esc_html_e( 'NextGen Tutors', 'beyondinfinity' ); ?></small>
							</div>
							<div class="ngi-kpi-pill"><?php esc_html_e( 'Verified', 'beyondinfinity' ); ?></div>
						</div>
						<div class="ngi-progress-card">
							<div class="ngi-progress-title">
								<span><?php esc_html_e( 'Platform trust', 'beyondinfinity' ); ?></span>
								<span>100%</span>
							</div>
							<div class="ngi-bar" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"><span style="width:100%"></span></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="ngi-shape" aria-hidden="true"></div>
	</section>
	<?php
}

/**
 * Section open.
 *
 * @param string $id    Section id.
 * @param string $class Extra classes (e.g. ngi-alt).
 */
function bi_ngi_section_open( $id = '', $class = '' ) {
	$id_attr = $id ? ' id="' . esc_attr( $id ) . '"' : '';
	$class   = trim( 'ngi-section ' . $class );
	printf( '<section%s class="%s">', $id_attr, esc_attr( $class ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<div class="ngi-wrap">';
}

/**
 * Section close.
 */
function bi_ngi_section_close() {
	echo '</div></section>';
}

/**
 * Section heading block.
 *
 * @param string $eyebrow  Eyebrow.
 * @param string $title    Title.
 * @param string $subtitle Subtitle.
 */
function bi_ngi_section_head( $eyebrow, $title, $subtitle = '' ) {
	echo '<div class="ngi-section-head">';
	if ( $eyebrow ) {
		echo '<p class="ngi-eyebrow">' . esc_html( $eyebrow ) . '</p>';
	}
	echo '<h2 class="ngi-heading">' . esc_html( $title ) . '</h2>';
	if ( $subtitle ) {
		echo '<p class="ngi-subtitle">' . esc_html( $subtitle ) . '</p>';
	}
	echo '</div>';
}

/**
 * Card grid.
 *
 * @param array<int,array{title?:string,text?:string,icon?:string}> $cards Cards.
 */
function bi_ngi_cards( $cards ) {
	echo '<div class="ngi-card-grid">';
	foreach ( (array) $cards as $card ) {
		$title = (string) ( $card['title'] ?? '' );
		$text  = (string) ( $card['text'] ?? '' );
		echo '<article class="ngi-card ngi-reveal">';
		if ( ! empty( $card['icon'] ) && function_exists( 'bi_kinetic_icon' ) ) {
			echo bi_kinetic_icon( (string) $card['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		if ( $title ) {
			echo '<h3>' . esc_html( $title ) . '</h3>';
		}
		if ( $text ) {
			echo '<p>' . esc_html( $text ) . '</p>';
		}
		echo '</article>';
	}
	echo '</div>';
}

/**
 * Pricing-style cards.
 *
 * @param array<int,array{name:string,price:string,featured?:bool,items?:string[],cta_label?:string,cta_url?:string}> $plans Plans.
 */
function bi_ngi_pricing( $plans ) {
	echo '<div class="ngi-pricing">';
	foreach ( (array) $plans as $plan ) {
		$feat = ! empty( $plan['featured'] ) ? ' is-featured' : '';
		echo '<article class="ngi-card ngi-price' . esc_attr( $feat ) . ' ngi-reveal">';
		echo '<h3>' . esc_html( (string) ( $plan['name'] ?? '' ) ) . '</h3>';
		echo '<strong>' . esc_html( (string) ( $plan['price'] ?? '' ) ) . '</strong>';
		if ( ! empty( $plan['items'] ) && is_array( $plan['items'] ) ) {
			echo '<ul>';
			foreach ( $plan['items'] as $item ) {
				echo '<li>' . esc_html( (string) $item ) . '</li>';
			}
			echo '</ul>';
		}
		if ( ! empty( $plan['cta_label'] ) && ! empty( $plan['cta_url'] ) ) {
			echo '<a class="ngi-btn ngi-btn-primary" href="' . esc_url( (string) $plan['cta_url'] ) . '">' . esc_html( (string) $plan['cta_label'] ) . '</a>';
		}
		echo '</article>';
	}
	echo '</div>';
}

/**
 * FAQ accordion.
 *
 * @param array<int,array{q:string,a:string}> $faqs FAQs.
 */
function bi_ngi_faq( $faqs ) {
	echo '<div class="ngi-faq">';
	foreach ( (array) $faqs as $i => $faq ) {
		$q = (string) ( $faq['q'] ?? '' );
		$a = (string) ( $faq['a'] ?? '' );
		echo '<div class="ngi-faq-item">';
		echo '<button type="button" class="ngi-faq-q" aria-expanded="false" data-ngi-faq>' . esc_html( $q ) . '<span aria-hidden="true">+</span></button>';
		echo '<div class="ngi-faq-a"><p>' . esc_html( $a ) . '</p></div>';
		echo '</div>';
	}
	echo '</div>';
}

/**
 * CTA band.
 *
 * @param string $title Title.
 * @param string $lead  Lead.
 * @param string $btn_label Primary label.
 * @param string $btn_url Primary URL.
 * @param string $btn2_label Secondary label.
 * @param string $btn2_url Secondary URL.
 */
function bi_ngi_cta( $title, $lead, $btn_label, $btn_url, $btn2_label = '', $btn2_url = '' ) {
	bi_ngi_section_open( 'cta', 'bi-parallax-cta' );
	echo '<div class="ngi-cta">';
	echo '<div>';
	echo '<h2>' . esc_html( $title ) . '</h2>';
	if ( $lead ) {
		echo '<p>' . esc_html( $lead ) . '</p>';
	}
	echo '</div>';
	echo '<div class="ngi-cta-panel">';
	echo '<div class="ngi-actions">';
	echo '<a class="ngi-btn ngi-btn-primary ngi-magnetic" href="' . esc_url( $btn_url ) . '">' . esc_html( $btn_label ) . '</a>';
	if ( $btn2_label && $btn2_url ) {
		echo '<a class="ngi-btn ngi-btn-secondary" href="' . esc_url( $btn2_url ) . '">' . esc_html( $btn2_label ) . '</a>';
	}
	echo '</div></div></div>';
	bi_ngi_section_close();
}

/**
 * Shortcode inside an ngi section.
 *
 * @param string $id        Section id.
 * @param string $eyebrow   Eyebrow.
 * @param string $title     Title.
 * @param string $subtitle  Subtitle.
 * @param string $shortcode Shortcode markup.
 * @param string $class     Extra section class.
 */
function bi_ngi_shortcode_section( $id, $eyebrow, $title, $subtitle, $shortcode, $class = 'ngi-alt' ) {
	bi_ngi_section_open( $id, $class );
	bi_ngi_section_head( $eyebrow, $title, $subtitle );
	echo '<div class="ngi-kinetic-box" style="padding:clamp(1.25rem,3vw,2rem)">';
	echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';
	bi_ngi_section_close();
}

/**
 * Dashboard shell wrapping Companion shortcodes in kinetic chrome.
 *
 * @param string               $slug      Dashboard slug.
 * @param string               $title     Title.
 * @param string               $lead      Lead.
 * @param string               $shortcode Shortcode.
 * @param array<string,mixed>  $args      Extra.
 */
function bi_ngi_dashboard_page( $slug, $title, $lead, $shortcode, $args = [] ) {
	bi_ngi_page_open( $slug, [ 'class' => 'ngi-page--dashboard', 'role' => 'dashboard' ] );
	bi_ngi_hero(
		[
			'badge'          => (string) ( $args['badge'] ?? __( 'Mission control', 'beyondinfinity' ) ),
			'title'          => $title,
			'lead'           => $lead,
			'primary_label'  => (string) ( $args['primary_label'] ?? __( 'Find a Tutor', 'beyondinfinity' ) ),
			'primary_url'    => (string) ( $args['primary_url'] ?? home_url( '/find-a-tutor/' ) ),
			'secondary_label'=> (string) ( $args['secondary_label'] ?? __( 'Support', 'beyondinfinity' ) ),
			'secondary_url'  => (string) ( $args['secondary_url'] ?? home_url( '/contact/' ) ),
			'aria'           => $title,
		]
	);
	bi_ngi_section_open( 'dashboard-main', 'ngi-alt ngi-section--dashboard' );
	bi_ngi_section_head(
		__( 'Live workspace', 'beyondinfinity' ),
		__( 'Your dashboard', 'beyondinfinity' ),
		__( 'Bookings, learners, and progress — powered by Companion.', 'beyondinfinity' )
	);
	echo '<div class="ngi-kinetic-box ngi-dashboard-panel">';
	if ( shortcode_exists( preg_replace( '/[^a-z0-9_]/', '', strtok( trim( $shortcode, '[]' ), ' ' ) ) ) || false !== strpos( $shortcode, '[' ) ) {
		echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo '<p class="ngi-subtitle">' . esc_html__( 'Activate NextGen Companion to load this dashboard.', 'beyondinfinity' ) . '</p>';
	}
	echo '</div>';
	bi_ngi_section_close();
	bi_ngi_page_close();
}

add_filter( 'bi_use_prototype_blend', static function ( $on ) {
	if ( function_exists( 'bi_ngi_force_theme_defaults' ) && bi_ngi_force_theme_defaults() ) {
		return false;
	}
	return $on;
}, 5 );
