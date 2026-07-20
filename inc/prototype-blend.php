<?php
/**
 * Prototype body blend — map prototypes/ PHP into live pages with kinetic styling.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slug => prototype filename map.
 *
 * @return array<string, string>
 */
function bi_prototype_page_map() {
	return apply_filters(
		'bi_prototype_page_map',
		[
			'home'              => 'index-body.php',
			'find-a-tutor'      => 'find-a-tutor-body.php',
			'become-a-tutor'    => 'become-a-tutor-body.php',
			'about'             => 'about-body.php',
			'contact'           => 'contact-body.php',
			'support'           => 'support-body.php',
			'blog'              => 'blog-body.php',
			'guarantee'         => 'guarantee-body.php',
			'pricing'           => 'pricing-body.php',
			'tutor-vetting'     => 'tutor-vetting-body.php',
			'safety-guide'      => 'safety-guide-body.php',
			'privacy-policy'    => 'privacy-body.php',
			'terms'             => 'terms-body.php',
			'onboarding'        => 'onboarding-body.php',
			'wordpress-setup'   => 'setup-body.php',
			'student-dashboard' => 'dashboard-body.php',
			'parent-dashboard'  => 'dashboard-body.php',
			'tutor-dashboard'   => 'tutor-dashboard-body.php',
			'admin-dashboard'   => 'admin-dashboard-body.php',
		]
	);
}

/**
 * Resolve prototype file for a page slug.
 *
 * @param string $slug Page slug.
 * @return string Basename or empty.
 */
function bi_get_prototype_file_for_slug( $slug ) {
	$map = bi_prototype_page_map();
	if ( ! empty( $map[ $slug ] ) ) {
		return $map[ $slug ];
	}

	foreach ( bi_prototype_page_slug_aliases() as $alias => $target ) {
		if ( $target === $slug && ! empty( $map[ $alias ] ) ) {
			return $map[ $alias ];
		}
	}

	return '';
}

/**
 * Whether prototype bodies should blend into theme pages (default on).
 */
function bi_use_prototype_blend() {
	if ( function_exists( 'bi_use_prototype_bodies' ) && bi_use_prototype_bodies() ) {
		return false;
	}
	if ( function_exists( 'bi_load_theme_options' ) ) {
		bi_load_theme_options();
	}
	if ( function_exists( 'bi_storage_isset' ) && ! bi_storage_isset( 'options', 'prototype_blend', 'val' ) ) {
		return (bool) apply_filters( 'bi_use_prototype_blend', true );
	}
	return (bool) apply_filters(
		'bi_use_prototype_blend',
		function_exists( 'bi_theme_option_is_on' ) ? bi_theme_option_is_on( 'prototype_blend' ) : true
	);
}

/**
 * Whether the current request should render a blended prototype body.
 *
 * @param string $slug Page slug.
 */
function bi_should_render_prototype_blend( $slug = '' ) {
	$slug = $slug ?: ( function_exists( 'bi_page_slug' ) ? bi_page_slug() : '' );
	if ( ! $slug || ! bi_use_prototype_blend() ) {
		return false;
	}
	if ( 'home' === $slug && function_exists( 'bi_use_kinetic_home' ) && bi_use_kinetic_home() ) {
		return false;
	}
	$file = bi_get_prototype_file_for_slug( $slug );
	if ( ! $file ) {
		return false;
	}
	return file_exists( bi_prototypes_dir() . '/' . basename( $file ) );
}

/**
 * Whether NGT prototype assets should load on this request.
 */
function bi_prototype_blend_active() {
	if ( is_admin() || ( function_exists( 'bi_is_builder_edit_mode' ) && bi_is_builder_edit_mode() ) ) {
		return false;
	}
	if ( ! bi_use_prototype_blend() ) {
		return false;
	}
	if ( is_page() ) {
		return bi_should_render_prototype_blend();
	}
	return false;
}

/**
 * Render prototype HTML + companion shortcode appendages.
 *
 * @param string $slug           Page slug.
 * @param string $prototype_file Prototype basename.
 */
function bi_render_blended_prototype( $slug, $prototype_file = '' ) {
	$prototype_file = $prototype_file ?: bi_get_prototype_file_for_slug( $slug );
	if ( ! $prototype_file ) {
		if ( function_exists( 'bi_render_production_default' ) ) {
			bi_render_production_default( $slug );
		}
		return;
	}

	$classes = [ 'bi-prototype-blend', 'bi-kinetic-prototype' ];
	if ( 'find-a-tutor' === $slug && shortcode_exists( 'ngc_tutor_marketplace' ) ) {
		$classes[] = 'bi-has-live-marketplace';
	}

	printf(
		'<div class="%s" data-prototype-slug="%s">',
		esc_attr( implode( ' ', $classes ) ),
		esc_attr( $slug )
	);

	if ( function_exists( 'bi_include_prototype_body' ) ) {
		bi_include_prototype_body( $prototype_file, [ 'slug' => $slug ] );
	}

	bi_render_blended_companion_blocks( $slug );

	echo '</div>';
}

/**
 * Append live Companion blocks after prototype marketing shells.
 *
 * @param string $slug Page slug.
 */
function bi_render_blended_companion_blocks( $slug ) {
	if ( function_exists( 'bi_render_prototype_live_dashboard' ) && bi_prototype_dashboard_type( $slug ) ) {
		bi_render_prototype_live_dashboard( $slug );
	}

	if ( 'find-a-tutor' === $slug && shortcode_exists( 'ngc_tutor_marketplace' ) ) {
		echo '<section class="section bi-prototype-marketplace" id="live-tutor-marketplace"><div class="wrap">';
		echo do_shortcode( '[ngc_tutor_marketplace per_page="12"]' );
		echo '</div></section>';
	}

	if ( 'find-a-tutor' === $slug && shortcode_exists( 'ngc_tutor_marketplace' ) ) {
		return;
	}

	if ( function_exists( 'bi_render_registry_shortcodes' ) ) {
		$registry = function_exists( 'bi_pages_registry' ) ? bi_pages_registry() : [];
		if ( ! empty( $registry[ $slug ]['shortcodes'] ) ) {
			bi_render_registry_shortcodes( $slug );
		}
	}
}

/**
 * Sync prototype meta + shortcode blocks into WP page content.
 *
 * @param int    $page_id Page ID.
 * @param string $slug    Page slug.
 */
function bi_sync_page_prototype_content( $page_id, $slug ) {
	$page_id = (int) $page_id;
	$slug    = sanitize_key( $slug );
	if ( $page_id <= 0 || ! $slug ) {
		return;
	}

	$file = bi_get_prototype_file_for_slug( $slug );
	if ( $file ) {
		update_post_meta( $page_id, '_bi_prototype_body', sanitize_file_name( $file ) );
	} else {
		delete_post_meta( $page_id, '_bi_prototype_body' );
	}

	$registry   = function_exists( 'bi_pages_registry' ) ? bi_pages_registry() : [];
	$shortcodes = $registry[ $slug ]['shortcodes'] ?? [];
	$blocks     = [];
	foreach ( (array) $shortcodes as $tag ) {
		if ( shortcode_exists( $tag ) ) {
			$blocks[] = '[' . $tag . ']';
		}
	}

	$marker  = '<!-- bi-prototype:' . $slug . ' -->';
	$existing = (string) get_post_field( 'post_content', $page_id );

	if ( $file ) {
		$content = $marker . "\n\n";
		if ( $blocks ) {
			$content .= implode( "\n\n", $blocks );
		}
		if ( '' === trim( wp_strip_all_tags( $existing ) ) || str_contains( $existing, 'bi-prototype:' ) ) {
			wp_update_post(
				[
					'ID'           => $page_id,
					'post_content' => $content,
				]
			);
		}
		return;
	}

	if ( $blocks && ( '' === trim( wp_strip_all_tags( $existing ) ) || str_contains( $existing, 'bi-prototype:' ) ) ) {
		wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => $marker . "\n\n" . implode( "\n\n", $blocks ),
			]
		);
	}
}

/**
 * Body classes for prototype blend pages.
 *
 * @param array<string, mixed> $classes Body classes.
 * @return array<string, mixed>
 */
function bi_prototype_blend_body_class( $classes ) {
	if ( ! bi_prototype_blend_active() ) {
		return $classes;
	}
	$classes[] = 'bi-prototype-blend-active';
	$slug = function_exists( 'bi_page_slug' ) ? bi_page_slug() : '';
	if ( $slug && function_exists( 'bi_page_content_profile' ) ) {
		$profile = bi_page_content_profile( $slug );
		if ( ! empty( $profile['hub_group'] ) ) {
			$classes[] = 'bi-hub-group-' . sanitize_html_class( $profile['hub_group'] );
		}
	}
	if ( 'find-a-tutor' === $slug && shortcode_exists( 'ngc_tutor_marketplace' ) ) {
		$classes[] = 'bi-has-live-marketplace';
	}
	return $classes;
}
add_filter( 'body_class', 'bi_prototype_blend_body_class', 14 );

/**
 * Enqueue prototype blend styling on top of NGT + kinetic tokens.
 */
function bi_prototype_blend_assets() {
	if ( ! bi_prototype_blend_active() ) {
		return;
	}

	wp_enqueue_style(
		'bi-kinetic-tokens',
		BI_URI . '/assets/css/kinetic-tokens.css',
		[ 'bi-style' ],
		BI_VERSION
	);

	$deps = [ 'bi-kinetic-tokens' ];
	if ( wp_style_is( 'bi-ngt-content', 'registered' ) || wp_style_is( 'bi-ngt-content', 'enqueued' ) ) {
		$deps[] = 'bi-ngt-content';
	}
	if ( wp_style_is( 'bi-kinetic-bridge', 'registered' ) || wp_style_is( 'bi-kinetic-bridge', 'enqueued' ) ) {
		$deps[] = 'bi-kinetic-bridge';
	}

	wp_enqueue_style(
		'bi-prototype-blend',
		BI_URI . '/assets/css/prototype-blend.css',
		$deps,
		BI_VERSION
	);

	if ( ! wp_script_is( 'bi-kinetic-page', 'enqueued' ) ) {
		$js_deps = wp_script_is( 'bi-ngt-wp-bridge', 'registered' ) ? [ 'bi-ngt-wp-bridge' ] : [];
		wp_enqueue_script(
			'bi-kinetic-page',
			BI_URI . '/assets/js/kinetic-page.js',
			$js_deps,
			BI_VERSION,
			true
		);
	}

	wp_enqueue_style(
		'bi-content-hubs',
		BI_URI . '/assets/css/content-hubs.css',
		[ 'bi-prototype-blend' ],
		BI_VERSION
	);

	wp_enqueue_script(
		'bi-content-hubs',
		BI_URI . '/assets/js/content-hubs.js',
		[],
		BI_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'bi_prototype_blend_assets', 40 );
