<?php
/**
 * Content grouping — dedupe archetypes, hub navigation, related pages.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Marketing content hubs (navigation + layout groups).
 *
 * @return array<string, array<string, mixed>>
 */
function bi_content_hub_catalog() {
	return [
		'intro'        => [
			'label' => __( 'Overview', 'beyondinfinity' ),
			'icon'  => 'compass',
		],
		'proof'        => [
			'label' => __( 'Proof', 'beyondinfinity' ),
			'icon'  => 'chart',
		],
		'values'       => [
			'label' => __( 'Values', 'beyondinfinity' ),
			'icon'  => 'heart',
		],
		'timeline'     => [
			'label' => __( 'Story', 'beyondinfinity' ),
			'icon'  => 'clock',
		],
		'pricing'      => [
			'label' => __( 'Plans', 'beyondinfinity' ),
			'icon'  => 'tag',
		],
		'tools'        => [
			'label' => __( 'Calculator', 'beyondinfinity' ),
			'icon'  => 'calculator',
		],
		'process'      => [
			'label' => __( 'How it works', 'beyondinfinity' ),
			'icon'  => 'route',
		],
		'directory'    => [
			'label' => __( 'Browse', 'beyondinfinity' ),
			'icon'  => 'search',
		],
		'marketplace'  => [
			'label' => __( 'Tutors', 'beyondinfinity' ),
			'icon'  => 'users',
		],
		'verification' => [
			'label' => __( 'Verification', 'beyondinfinity' ),
			'icon'  => 'shield',
		],
		'faq'          => [
			'label' => __( 'FAQ', 'beyondinfinity' ),
			'icon'  => 'help',
		],
		'contact'      => [
			'label' => __( 'Contact', 'beyondinfinity' ),
			'icon'  => 'mail',
		],
		'content'      => [
			'label' => __( 'Details', 'beyondinfinity' ),
			'icon'  => 'file',
		],
		'action'       => [
			'label' => __( 'Next step', 'beyondinfinity' ),
			'icon'  => 'arrow',
		],
		'forms'        => [
			'label' => __( 'Forms', 'beyondinfinity' ),
			'icon'  => 'form',
		],
	];
}

/**
 * Per-page content profile: strip duplicates + related pages.
 *
 * @param string $slug Page slug.
 * @return array<string, mixed>
 */
function bi_page_content_profile( $slug ) {
	$profiles = [
		'about'           => [
			'hub_group'         => 'trust',
			'strip_hubs'        => [],
			'strip_faq_phrases' => [],
			'related'           => [ 'find-a-tutor', 'guarantee', 'tutor-vetting' ],
		],
		'pricing'         => [
			'hub_group'         => 'discover',
			'strip_hubs'        => [ 'guarantee-promo' ],
			'strip_faq_phrases' => [ 'first lesson really guaranteed', 'NextGen100 guarantee' ],
			'related'           => [ 'guarantee', 'find-a-tutor', 'contact' ],
			'teasers'           => [ 'guarantee' ],
		],
		'guarantee'       => [
			'hub_group'         => 'discover',
			'strip_hubs'        => [],
			'strip_faq_phrases' => [],
			'related'           => [ 'pricing', 'find-a-tutor', 'support' ],
		],
		'find-a-tutor'    => [
			'hub_group'         => 'discover',
			'strip_hubs'        => [],
			'strip_faq_phrases' => [],
			'related'           => [ 'pricing', 'guarantee', 'contact' ],
		],
		'become-a-tutor'  => [
			'hub_group'         => 'tutors',
			'strip_hubs'        => [],
			'strip_faq_phrases' => [],
			'related'           => [ 'tutor-vetting', 'pricing', 'contact' ],
		],
		'contact'         => [
			'hub_group'         => 'help',
			'strip_hubs'        => [],
			'strip_faq_phrases' => [ 'PayFast', 'first lesson' ],
			'related'           => [ 'find-a-tutor', 'support', 'pricing' ],
		],
		'support'         => [
			'hub_group'         => 'help',
			'strip_hubs'        => [],
			'strip_faq_phrases' => [ 'satisfaction guarantee', 'first session doesn' ],
			'related'           => [ 'guarantee', 'contact', 'pricing' ],
		],
		'safety-guide'    => [
			'hub_group'         => 'trust',
			'strip_hubs'        => [ 'verification' ],
			'strip_faq_phrases' => [ 'verify tutor identities' ],
			'related'           => [ 'tutor-vetting', 'child-safety', 'contact' ],
			'teasers'           => [ 'verification' ],
		],
		'tutor-vetting'   => [
			'hub_group'         => 'trust',
			'strip_hubs'        => [],
			'strip_faq_phrases' => [],
			'related'           => [ 'safety-guide', 'find-a-tutor', 'become-a-tutor' ],
		],
		'blog'            => [
			'hub_group'         => 'discover',
			'strip_hubs'        => [],
			'related'           => [ 'find-a-tutor', 'about' ],
		],
		'privacy-policy'  => [
			'hub_group'         => 'legal',
			'strip_hubs'        => [ 'action' ],
			'related'           => [ 'terms', 'child-safety', 'contact' ],
		],
		'terms'           => [
			'hub_group'         => 'legal',
			'strip_hubs'        => [ 'action' ],
			'related'           => [ 'privacy-policy', 'guarantee', 'contact' ],
		],
	];

	return apply_filters(
		'bi_page_content_profile',
		$profiles[ $slug ] ?? [
			'hub_group'         => 'content',
			'strip_hubs'        => [],
			'strip_faq_phrases' => [],
			'related'           => [ 'find-a-tutor', 'contact' ],
		],
		$slug
	);
}

/**
 * Detect hub type from a section HTML chunk.
 *
 * @param string $html Section markup.
 * @param string $slug Page slug.
 */
function bi_detect_section_hub( $html, $slug ) {
	if ( str_contains( $html, 'class="pagehead' ) || str_contains( $html, 'class="hero"' ) ) {
		return 'intro';
	}
	if ( str_contains( $html, 'stat-grid' ) || str_contains( $html, 'pagehead__stats' ) ) {
		return 'proof';
	}
	if ( str_contains( $html, 'price-grid' ) ) {
		return 'pricing';
	}
	if ( str_contains( $html, 'class="calc"' ) ) {
		return 'tools';
	}
	if ( str_contains( $html, 'class="guarantee"' ) && ! str_contains( $html, 'class="calc"' ) ) {
		return 'guarantee-promo';
	}
	if ( str_contains( $html, 'class="steps"' ) || str_contains( $html, 'class="vsteps"' ) ) {
		return 'process';
	}
	if ( str_contains( $html, 'class="timeline"' ) || str_contains( $html, 'tl-item' ) ) {
		return 'timeline';
	}
	if ( str_contains( $html, 'values-grid' ) ) {
		return 'values';
	}
	if ( str_contains( $html, 'cmp-table' ) || ( str_contains( $html, 'Verification Badge' ) && str_contains( $html, '<table' ) ) ) {
		return 'verification';
	}
	if ( str_contains( $html, 'class="directory"' ) || str_contains( $html, 'dir-grid' ) ) {
		return 'directory';
	}
	if ( str_contains( $html, 'class="faq"' ) ) {
		return 'faq';
	}
	if ( str_contains( $html, 'contact-grid' ) ) {
		return 'contact';
	}
	if ( str_contains( $html, 'cta-band' ) ) {
		return 'action';
	}
	if ( str_contains( $html, 'contact-card' ) && str_contains( $html, 'Emergency' ) ) {
		return 'action';
	}
	return 'content';
}

/**
 * Human label for hub jump nav from section HTML.
 *
 * @param string $hub  Hub key.
 * @param string $html Section HTML.
 */
function bi_hub_nav_label( $hub, $html ) {
	$catalog = bi_content_hub_catalog();
	if ( ! empty( $catalog[ $hub ]['label'] ) ) {
		$label = $catalog[ $hub ]['label'];
	} else {
		$label = ucfirst( str_replace( '-', ' ', $hub ) );
	}

	if ( preg_match( '/<h2[^>]*class="[^"]*shead__title[^"]*"[^>]*>(.*?)<\/h2>/is', $html, $m ) ) {
		$title = wp_strip_all_tags( $m[1] );
		if ( $title ) {
			return $title;
		}
	}
	if ( preg_match( '/<h1[^>]*class="[^"]*pagehead__title[^"]*"[^>]*>(.*?)<\/h1>/is', $html, $m ) ) {
		$title = wp_strip_all_tags( $m[1] );
		if ( $title ) {
			return $title;
		}
	}

	return $label;
}

/**
 * Remove FAQ items whose question contains a phrase.
 *
 * @param string $html    HTML.
 * @param string $phrase  Needle.
 */
function bi_remove_faq_item_containing( $html, $phrase ) {
	if ( ! $phrase ) {
		return $html;
	}

	$parts = preg_split( '/(?=<div class="faq-item")/i', (string) $html ) ?: [];
	if ( count( $parts ) <= 1 ) {
		return $html;
	}

	$out = array_shift( $parts );
	foreach ( $parts as $part ) {
		if ( str_contains( wp_strip_all_tags( $part ), $phrase ) ) {
			continue;
		}
		$out .= $part;
	}

	return $out;
}

/**
 * Render compact teaser linking to canonical page (no duplicate body).
 *
 * @param string $type Teaser type.
 */
function bi_render_content_hub_teaser( $type ) {
	if ( 'guarantee' === $type ) {
		$url = function_exists( 'ngt_get_page_url' ) ? ngt_get_page_url( 'guarantee' ) : home_url( '/guarantee/' );
		?>
		<section class="section bi-hub-teaser bi-hub-teaser--guarantee" data-hub="guarantee-promo">
			<div class="wrap">
				<div class="bi-hub-teaser__card">
					<p class="bi-hub-teaser__eyebrow"><?php esc_html_e( 'NextGen100 Guarantee', 'beyondinfinity' ); ?></p>
					<h2 class="bi-hub-teaser__title"><?php esc_html_e( 'Love the lesson or it\'s free', 'beyondinfinity' ); ?></h2>
					<p class="bi-hub-teaser__text"><?php esc_html_e( 'Full guarantee terms, claim process, and eligibility live on our dedicated guarantee page — not repeated here.', 'beyondinfinity' ); ?></p>
					<a class="btn btn--primary" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Read guarantee details', 'beyondinfinity' ); ?></a>
				</div>
			</div>
		</section>
		<?php
		return;
	}

	if ( 'verification' === $type ) {
		$url = function_exists( 'ngt_get_page_url' ) ? ngt_get_page_url( 'tutor-vetting' ) : home_url( '/tutor-vetting/' );
		?>
		<section class="section bi-hub-teaser bi-hub-teaser--verification" data-hub="verification">
			<div class="wrap">
				<div class="bi-hub-teaser__card bi-hub-teaser__card--inline">
					<div>
						<p class="bi-hub-teaser__eyebrow"><?php esc_html_e( 'Canonical verification', 'beyondinfinity' ); ?></p>
						<h2 class="bi-hub-teaser__title"><?php esc_html_e( 'See the full 5-step vetting pipeline', 'beyondinfinity' ); ?></h2>
						<p class="bi-hub-teaser__text"><?php esc_html_e( 'Badge definitions and acceptance metrics are maintained once on Tutor Vetting to avoid duplicate tables.', 'beyondinfinity' ); ?></p>
					</div>
					<a class="btn btn--outline" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Tutor Vetting', 'beyondinfinity' ); ?></a>
				</div>
			</div>
		</section>
		<?php
	}
}

/**
 * Related pages rail — grouped links, no duplicate CTAs.
 *
 * @param string $slug Current page slug.
 */
function bi_render_related_content_rail( $slug ) {
	$profile = bi_page_content_profile( $slug );
	$related = array_filter( (array) ( $profile['related'] ?? [] ) );
	if ( count( $related ) < 2 ) {
		return;
	}

	echo '<aside class="bi-related-rail" aria-label="' . esc_attr__( 'Related pages', 'beyondinfinity' ) . '">';
	echo '<div class="wrap"><div class="bi-related-rail__inner">';
	echo '<p class="bi-related-rail__label">' . esc_html__( 'Continue exploring', 'beyondinfinity' ) . '</p>';
	echo '<div class="bi-related-rail__grid">';
	foreach ( $related as $rel_slug ) {
		if ( $rel_slug === $slug ) {
			continue;
		}
		$page = function_exists( 'bi_find_page_by_slug' ) ? bi_find_page_by_slug( $rel_slug ) : get_page_by_path( $rel_slug );
		if ( ! $page ) {
			continue;
		}
		printf(
			'<a class="bi-related-rail__link" href="%s"><span class="bi-related-rail__title">%s</span><span class="bi-related-rail__arrow" aria-hidden="true">→</span></a>',
			esc_url( get_permalink( $page ) ),
			esc_html( get_the_title( $page ) )
		);
	}
	echo '</div></div></div></aside>';
}

/**
 * Sticky hub jump navigation.
 *
 * @param array<int, array<string, string>> $nav_items Nav items.
 * @param string                            $slug      Page slug.
 */
function bi_render_hub_jump_nav( $nav_items, $slug ) {
	if ( count( $nav_items ) < 3 ) {
		return;
	}

	$profile = bi_page_content_profile( $slug );
	$group   = $profile['hub_group'] ?? 'content';

	printf(
		'<nav class="bi-hub-jump" aria-label="%s" data-hub-group="%s"><div class="wrap bi-hub-jump__track">',
		esc_attr__( 'On this page', 'beyondinfinity' ),
		esc_attr( $group )
	);
	foreach ( $nav_items as $item ) {
		printf(
			'<a class="bi-hub-jump__pill" href="#%s">%s</a>',
			esc_attr( $item['id'] ),
			esc_html( $item['label'] )
		);
	}
	echo '</div></nav>';
}

/**
 * Process prototype HTML: dedupe, hub-wrap, jump nav, related rail.
 *
 * @param string $html Raw prototype HTML.
 * @param string $slug Page slug.
 */
function bi_process_prototype_html( $html, $slug ) {
	$html    = preg_replace( '/<script\b[^>]*>[\s\S]*?<\/script>/i', '', (string) $html );
	$profile = bi_page_content_profile( $slug );

	foreach ( (array) ( $profile['strip_faq_phrases'] ?? [] ) as $phrase ) {
		$html = bi_remove_faq_item_containing( $html, $phrase );
	}

	$parts    = preg_split( '/(?=<section\s)/i', $html ) ?: [];
	$nav      = [];
	$hubs     = [];
	$hub_seen = [];

	foreach ( $parts as $chunk ) {
		$chunk = trim( $chunk );
		if ( '' === $chunk ) {
			continue;
		}

		$hub = bi_detect_section_hub( $chunk, $slug );
		if ( in_array( $hub, (array) ( $profile['strip_hubs'] ?? [] ), true ) ) {
			continue;
		}
		if ( isset( $hub_seen[ $hub ] ) && in_array( $hub, [ 'action', 'guarantee-promo' ], true ) ) {
			continue;
		}
		$hub_seen[ $hub ] = true;

		$id    = 'bi-hub-' . sanitize_key( $hub ) . '-' . count( $nav );
		$nav[] = [
			'id'    => $id,
			'hub'   => $hub,
			'label' => bi_hub_nav_label( $hub, $chunk ),
		];

		$hubs[] = sprintf(
			'<article class="bi-content-hub bi-content-hub--%1$s" id="%2$s" data-hub="%1$s">%3$s</article>',
			esc_attr( $hub ),
			esc_attr( $id ),
			$chunk
		);
	}

	ob_start();
	bi_render_hub_jump_nav( $nav, $slug );
	echo '<div class="bi-content-hubs">';
	echo implode( '', $hubs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	foreach ( (array) ( $profile['teasers'] ?? [] ) as $teaser ) {
		bi_render_content_hub_teaser( $teaser );
	}
	echo '</div>';
	bi_render_related_content_rail( $slug );
	return (string) ob_get_clean();
}
