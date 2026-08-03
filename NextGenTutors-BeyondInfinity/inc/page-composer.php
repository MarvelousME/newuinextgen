<?php
/**
 * Blended page layout — unifies UI library, section partials, and theme defaults.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Current page slug for layout decisions.
 */
function bi_page_slug() {
	static $slug = null;
	if ( null !== $slug ) {
		return $slug;
	}
	if ( is_front_page() ) {
		$slug = 'home';
		return $slug;
	}
	$post_id = function_exists( 'bi_get_current_page_id' ) ? bi_get_current_page_id() : get_queried_object_id();
	$slug    = $post_id ? (string) get_post_field( 'post_name', $post_id ) : '';
	return $slug;
}

/**
 * Page metadata type from registry (public, dashboard, auth, …).
 */
function bi_page_type( $slug = '' ) {
	$slug = $slug ?: bi_page_slug();
	$reg  = function_exists( 'bi_pages_registry' ) ? bi_pages_registry() : [];
	return (string) ( $reg[ $slug ]['type'] ?? 'public' );
}

/**
 * Whether page should get marketing chrome (trust rail, footer CTA).
 */
function bi_page_uses_marketing_chrome( $slug = '' ) {
	$type = bi_page_type( $slug );
	return in_array( $type, [ 'public', 'trust', 'legal' ], true );
}

/**
 * Open blended page shell.
 */
function bi_page_open( $slug = '' ) {
	$slug = $slug ?: bi_page_slug();
	printf(
		'<div class="ng-page bi-blended-layout" data-page-slug="%s" data-page-type="%s">',
		esc_attr( $slug ),
		esc_attr( bi_page_type( $slug ) )
	);
	echo '<div class="ng-page__canvas" aria-hidden="true"></div>';
	echo '<div class="bi-theme-content framer-frame ng-page__body">';
}

/**
 * Close blended page shell + optional footer band.
 */
function bi_page_close( $slug = '' ) {
	echo '</div>';
	if ( bi_page_uses_marketing_chrome( $slug ) ) {
		bi_page_render_footer_band( $slug );
	}
	echo '</div>';
}

/**
 * Modern section wrapper — blends ng-ui tokens with ngt-section rhythm.
 *
 * @param array<string, mixed> $args       { id, tone, width, reveal, tilt }.
 * @param callable|null        $callback   Inner HTML.
 */
function bi_page_section( $args, $callback = null ) {
	$id     = sanitize_key( $args['id'] ?? 'section' );
	$tone   = sanitize_key( $args['tone'] ?? 'default' );
	$width  = sanitize_key( $args['width'] ?? 'default' );
	$reveal = ! isset( $args['reveal'] ) || ! empty( $args['reveal'] );
	$tilt   = ! empty( $args['tilt'] );

	$classes = [
		'ng-page-section',
		'ngt-section',
		'ng-page-section--' . $tone,
	];
	if ( 'narrow' === $width ) {
		$classes[] = 'ng-page-section--narrow';
	}
	if ( 'alt' === $tone || 'muted' === $tone ) {
		$classes[] = 'ngt-section--alt';
	}
	if ( $reveal ) {
		$classes[] = 'ng-reveal';
	}

	printf(
		'<section class="%s" id="%s"%s>',
		esc_attr( implode( ' ', $classes ) ),
		esc_attr( $id ),
		$tilt ? ' data-ng-tilt-section' : ''
	);
	echo '<div class="ng-container ng-page-section__inner">';
	if ( is_callable( $callback ) ) {
		call_user_func( $callback );
	}
	echo '</div></section>';
}

/**
 * Section heading block used across defaults.
 */
function bi_page_heading( $title, $subtitle = '', $eyebrow = '' ) {
	echo '<header class="ng-page-heading ng-reveal">';
	if ( $eyebrow ) {
		echo '<p class="ng-page-heading__eyebrow">' . esc_html( $eyebrow ) . '</p>';
	}
	echo '<h2 class="ng-page-heading__title" data-bi-slide-title>' . esc_html( $title ) . '</h2>';
	if ( $subtitle ) {
		echo '<p class="ng-page-heading__subtitle">' . esc_html( $subtitle ) . '</p>';
	}
	echo '</header>';
}

/**
 * Render UI-library hero when CMS data exists; else modern theme hero.
 *
 * @param string $title    Fallback title.
 * @param string $subtitle Fallback subtitle.
 * @param array  $args     Extra: class, cta_label, cta_url, show_stats, show_trust.
 */
function bi_render_modern_hero( $title, $subtitle = '', $args = [] ) {
	$title    = trim( (string) $title );
	$subtitle = (string) $subtitle;

	// Explicit theme titles always render — keep a real H1 for structure.
	// Only honour "hide page title" when no title was supplied by the caller.
	if ( '' === $title && function_exists( 'bi_should_show_page_title' ) && ! bi_should_show_page_title() ) {
		return;
	}

	$slug       = bi_page_slug();
	$used_ui    = false;
	$ui_context = array_merge(
		[
			'slug'        => 'hero',
			'page_key'    => $slug,
			'section_key' => 'hero',
			'title'       => $title,
			'subtitle'    => $subtitle,
		],
		$args
	);

	// Prefer the theme hero when we already have a clear page title — CMS/UI
	// heroes often render empty shells and leave the page without an H1.
	if ( '' === $title && function_exists( 'ng_ui_component' ) && class_exists( 'NGC_UI_Component_Registry' ) ) {
		ob_start();
		ng_ui_component( 'hero', $ui_context );
		$ui_html = trim( (string) ob_get_clean() );
		if ( $ui_html && false === strpos( $ui_html, 'ng-ui-fallback' ) && false !== stripos( $ui_html, '<h1' ) ) {
			echo $ui_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$used_ui = true;
		}
	}

	if ( ! $used_ui ) {
		$cta_label = (string) ( $args['cta_label'] ?? '' );
		$cta_url   = (string) ( $args['cta_url'] ?? '' );
		$extra     = trim( (string) ( $args['class'] ?? '' ) );
		$img_key   = function_exists( 'bi_hero_image_key' ) ? bi_hero_image_key( $extra ) : 'hero_bg';
		$bg_url    = function_exists( 'bi_get_theme_image_url' ) ? bi_get_theme_image_url( $img_key ) : '';
		$video_url = function_exists( 'bi_tutoring_video_url' ) ? bi_tutoring_video_url( $slug ) : '';
		$poster    = function_exists( 'bi_tutoring_video_poster_url' ) ? bi_tutoring_video_poster_url( $slug ) : '';
		if ( ! $poster && $bg_url ) {
			$poster = $bg_url;
		}

		if ( '' === $title && function_exists( 'get_the_title' ) ) {
			$title = (string) get_the_title();
		}
		if ( '' === $title ) {
			$title = __( 'NextGen Tutors', 'beyondinfinity' );
		}

		$hero_classes = trim( 'ng-page-hero bi-hero ngt-hero nbi-aurora-hero ng-page-hero--cinematic ' . $extra );
		if ( $video_url || $poster ) {
			$hero_classes .= ' ng-page-hero--has-video';
		}
		?>
		<section class="<?php echo esc_attr( $hero_classes ); ?>" aria-labelledby="ng-page-hero-title">
			<div class="ng-page-hero__mesh" aria-hidden="true"></div>
			<div class="nbi-aurora-layer" aria-hidden="true"></div>
			<?php if ( function_exists( 'bi_nbi_render_constellation' ) ) { bi_nbi_render_constellation( [ 'id' => 'nbi-page-constellation' ] ); } ?>
			<?php if ( $bg_url || $poster ) : ?>
				<div class="ng-page-hero__photo" style="background-image:url(<?php echo esc_url( $poster ? $poster : $bg_url ); ?>)" aria-hidden="true"></div>
			<?php endif; ?>
			<?php if ( $video_url ) : ?>
				<video
					class="ng-page-hero__video"
					data-bi-cinematic
					muted
					loop
					playsinline
					preload="metadata"
					<?php echo $poster ? 'poster="' . esc_url( $poster ) . '"' : ''; ?>
					aria-hidden="true"
				>
					<source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4" />
				</video>
			<?php endif; ?>
			<div class="ng-page-hero__scrim" aria-hidden="true"></div>
			<div class="ng-container ng-page-hero__inner">
				<div class="ng-page-hero__copy ng-reveal" data-bi-motion="slide-up">
					<h1 id="ng-page-hero-title" class="ng-page-hero__title" data-bi-slide-title><?php echo esc_html( $title ); ?></h1>
					<?php if ( $subtitle ) : ?>
						<p class="ng-page-hero__subtitle"><?php echo esc_html( $subtitle ); ?></p>
					<?php endif; ?>
					<?php if ( $cta_label && $cta_url ) : ?>
						<div class="ng-page-hero__actions">
							<a class="ng-btn ng-btn--primary" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
						</div>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $args['show_stats'] ) && function_exists( 'bi_real_stat_cards' ) ) : ?>
					<div class="ng-page-hero__stats ng-reveal" data-bi-motion="fade-in">
						<?php foreach ( array_slice( bi_real_stat_cards(), 0, 3 ) as $stat ) : ?>
							<div class="ng-page-hero__stat">
								<strong><?php echo esc_html( (string) $stat['count'] . (string) $stat['suffix'] ); ?></strong>
								<span><?php echo esc_html( $stat['label'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

	$show_trust = ! isset( $args['show_trust'] ) || ! empty( $args['show_trust'] );
	if ( $show_trust && bi_page_uses_marketing_chrome( $slug ) ) {
		bi_page_render_trust_rail();
	}

	if ( function_exists( 'ng_ui_component' ) && in_array( $slug, [ 'about', 'become-a-tutor', 'home', 'pricing', 'guarantee', 'tutor-vetting', 'find-a-tutor' ], true ) ) {
		ng_ui_component( 'stats-band', [ 'page_key' => $slug ] );
	}
}

/**
 * Trust chip rail under hero.
 */
function bi_page_render_trust_rail() {
	if ( function_exists( 'bi_trust_badges' ) ) {
		echo '<div class="ng-page-trust-rail ng-reveal">';
		bi_trust_badges();
		echo '</div>';
		return;
	}
	get_template_part( 'template-parts/sections/trust-strip' );
}

/**
 * Footer CTA band on marketing pages.
 */
function bi_page_render_footer_band( $slug = '' ) {
	$slug = $slug ?: bi_page_slug();
	if ( in_array( $slug, [ 'home', 'contact', 'thank-you' ], true ) ) {
		return;
	}
	?>
	<section class="ng-page-footer-band ng-reveal" aria-label="<?php esc_attr_e( 'Get started', 'beyondinfinity' ); ?>">
		<div class="ng-container ng-page-footer-band__inner">
			<div>
				<h2 class="ng-page-footer-band__title" data-bi-slide-title><?php esc_html_e( 'Ready to find the right tutor?', 'beyondinfinity' ); ?></h2>
				<p class="ng-page-footer-band__text"><?php esc_html_e( 'Vetted educators, transparent pricing, and a risk-free first lesson.', 'beyondinfinity' ); ?></p>
			</div>
			<div class="ng-page-footer-band__actions">
				<a class="ng-btn ng-btn--primary" href="<?php echo esc_url( home_url( '/find-a-tutor' ) ); ?>"><?php esc_html_e( 'Find a Tutor', 'beyondinfinity' ); ?></a>
				<a class="ng-btn ng-btn--secondary" href="<?php echo esc_url( home_url( '/guarantee' ) ); ?>"><?php esc_html_e( 'First lesson guarantee', 'beyondinfinity' ); ?></a>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Render mapped UI components for a page (between hero and body).
 *
 * @param string $slug Page slug.
 */
function bi_page_render_component_stack( $slug = '' ) {
	$slug = $slug ?: bi_page_slug();
	if ( ! function_exists( 'ng_ui_page_component_map' ) || ! function_exists( 'ng_ui_component' ) ) {
		return;
	}
	$map = ng_ui_page_component_map();
	$stack = $map[ $slug ] ?? [];
	foreach ( $stack as $component ) {
		if ( 'hero' === $component || 'stats-band' === $component ) {
			continue;
		}
		ng_ui_component( $component, [ 'page_key' => $slug, 'limit' => 6 ] );
	}
}

/**
 * Extend companion UI registry pages from theme map.
 *
 * @param array<string, array<string, mixed>> $defs Definitions.
 * @return array<string, array<string, mixed>>
 */
function bi_extend_ui_component_pages( $defs ) {
	if ( ! function_exists( 'ng_ui_page_component_map' ) ) {
		return $defs;
	}
	foreach ( ng_ui_page_component_map() as $page_slug => $components ) {
		foreach ( $components as $comp_slug ) {
			if ( empty( $defs[ $comp_slug ]['pages'] ) ) {
				continue;
			}
			if ( ! in_array( $page_slug, $defs[ $comp_slug ]['pages'], true ) ) {
				$defs[ $comp_slug ]['pages'][] = $page_slug;
			}
		}
	}
	return $defs;
}
add_filter( 'ngc_ui_component_definitions', 'bi_extend_ui_component_pages' );

/**
 * Enqueue blended layout assets.
 */
function bi_page_composer_assets() {
	if ( is_admin() || ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) ) {
		return;
	}
	wp_enqueue_style(
		'bi-page-composer',
		BI_URI . '/assets/css/page-composer.css',
		[ 'bi-style', 'ng-ui-components' ],
		BI_VERSION
	);
	wp_enqueue_style(
		'bi-cinematic-hero',
		BI_URI . '/assets/css/bi-cinematic-hero.css',
		[ 'bi-page-composer' ],
		BI_VERSION
	);
	wp_enqueue_script(
		'bi-page-composer',
		BI_URI . '/assets/js/page-composer.js',
		[],
		BI_VERSION,
		true
	);
	wp_enqueue_script(
		'bi-cinematic-video',
		BI_URI . '/assets/js/bi-cinematic-video.js',
		[],
		BI_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'bi_page_composer_assets', 35 );
