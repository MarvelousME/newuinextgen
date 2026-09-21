<?php
/**
 * Elementor primary-page ownership — seed kinetic HTML into Elementor documents
 * so pages open cleanly in the Elementor editor (no Safe Mode from theme shells).
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether primary pages should prefer Elementor ownership.
 *
 * @return bool
 */
function bi_elementor_pages_enabled() {
	return (bool) apply_filters( 'bi_elementor_pages_enabled', true );
}

/**
 * Generate a short Elementor-style element id.
 *
 * @return string
 */
function bi_elementor_new_id() {
	try {
		return substr( bin2hex( random_bytes( 4 ) ), 0, 7 );
	} catch ( Exception $e ) {
		return substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 7 );
	}
}

/**
 * Whether a page-body capture for Elementor seeding is in progress.
 *
 * @return bool
 */
function bi_elementor_is_seeding_capture() {
	return ! empty( $GLOBALS['bi_elementor_seeding_capture'] );
}

/**
 * Shortcodes captured during seed (full strings with attributes).
 *
 * @return string[]
 */
function bi_elementor_seed_captured_shortcodes() {
	if ( empty( $GLOBALS['bi_elementor_seed_shortcodes'] ) || ! is_array( $GLOBALS['bi_elementor_seed_shortcodes'] ) ) {
		return [];
	}
	return array_values( array_unique( $GLOBALS['bi_elementor_seed_shortcodes'] ) );
}

/**
 * Capture canonical page body HTML for Elementor seeding.
 * Defers ngc_/ngt_ shortcodes into placeholders and records full shortcode strings.
 *
 * @param string $slug Registry slug.
 * @return string
 */
function bi_elementor_capture_page_body( $slug ) {
	$slug = sanitize_key( $slug );
	$path = trailingslashit( BI_DIR ) . 'template-parts/pages/' . $slug . '.php';
	if ( ! file_exists( $path ) ) {
		$prod = trailingslashit( BI_DIR ) . 'inc/defaults-production/' . $slug . '.php';
		if ( ! file_exists( $prod ) ) {
			return '';
		}
		$path = $prod;
	}

	$GLOBALS['bi_elementor_seeding_capture'] = true;
	$GLOBALS['bi_elementor_seed_shortcodes'] = [];
	add_filter( 'pre_do_shortcode_tag', 'bi_elementor_seed_pre_shortcode_tag', 10, 4 );

	ob_start();
	include $path;
	$html = (string) ob_get_clean();

	remove_filter( 'pre_do_shortcode_tag', 'bi_elementor_seed_pre_shortcode_tag', 10 );
	$GLOBALS['bi_elementor_seeding_capture'] = false;

	$html = preg_replace( '/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html );
	$html = preg_replace( '/<script\b[^>]*\/>/i', '', $html );
	$html = preg_replace( '/<!-- bi-elementor-seed:shortcode-deferred -->/', '', $html );

	$wrapped  = '<div class="bi-elementor-kinetic ng-page bi-kinetic-surface" data-page-slug="' . esc_attr( $slug ) . '">';
	$wrapped .= '<div class="bi-theme-content framer-frame ng-page__body bi-elementor-kinetic__body">';
	$wrapped .= $html;
	$wrapped .= '</div></div>';

	return $wrapped;
}

/**
 * Capture full shortcode string (with attrs) and skip expansion during seed.
 *
 * @param false|string $return Short-circuit return.
 * @param string       $tag    Tag.
 * @param array        $attr   Attributes.
 * @param array        $m      Regex match (full shortcode at [0]).
 * @return false|string
 */
function bi_elementor_seed_pre_shortcode_tag( $return, $tag, $attr, $m ) {
	if ( ! bi_elementor_is_seeding_capture() ) {
		return $return;
	}
	if ( ! is_string( $tag ) || ( 0 !== strpos( $tag, 'ngc_' ) && 0 !== strpos( $tag, 'ngt_' ) ) ) {
		return $return;
	}
	$full = '';
	if ( is_array( $m ) && ! empty( $m[0] ) ) {
		$full = (string) $m[0];
	} else {
		$full = '[' . $tag . ']';
	}
	if ( empty( $GLOBALS['bi_elementor_seed_shortcodes'] ) || ! is_array( $GLOBALS['bi_elementor_seed_shortcodes'] ) ) {
		$GLOBALS['bi_elementor_seed_shortcodes'] = [];
	}
	$GLOBALS['bi_elementor_seed_shortcodes'][] = $full;
	return '<!-- bi-elementor-seed:shortcode-deferred -->';
}

/**
 * Build a full-width Elementor section containing an HTML widget.
 *
 * @param string $html Inner HTML.
 * @return array<int, array<string, mixed>>
 */
function bi_elementor_html_document( $html ) {
	return [
		[
			'id'       => bi_elementor_new_id(),
			'elType'   => 'section',
			'isInner'  => false,
			'settings' => [
				'layout'           => 'full_width',
				'gap'              => 'no',
				'content_width'    => [
					'unit' => 'px',
					'size' => '',
				],
				'padding'          => [
					'unit'     => 'px',
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'isLinked' => true,
				],
				'css_classes'      => 'bi-elementor-kinetic-section',
			],
			'elements' => [
				[
					'id'       => bi_elementor_new_id(),
					'elType'   => 'column',
					'isInner'  => false,
					'settings' => [
						'_column_size'     => 100,
						'_inline_size'     => null,
						'content_position' => 'top',
					],
					'elements' => [
						[
							'id'         => bi_elementor_new_id(),
							'elType'     => 'widget',
							'widgetType' => 'html',
							'isInner'    => false,
							'settings'   => [
								'html' => $html,
							],
							'elements'   => [],
						],
					],
				],
			],
		],
	];
}

/**
 * Build a shortcode widget section (Companion forms / dashboards).
 *
 * @param string $shortcode Shortcode tag without brackets, or full shortcode.
 * @return array<string, mixed>
 */
function bi_elementor_shortcode_section( $shortcode ) {
	$shortcode = trim( (string) $shortcode );
	if ( $shortcode && '[' !== $shortcode[0] ) {
		$shortcode = '[' . $shortcode . ']';
	}

	return [
		'id'       => bi_elementor_new_id(),
		'elType'   => 'section',
		'isInner'  => false,
		'settings' => [
			'layout'      => 'boxed',
			'gap'         => 'default',
			'css_classes' => 'bi-elementor-shortcode-section',
		],
		'elements' => [
			[
				'id'       => bi_elementor_new_id(),
				'elType'   => 'column',
				'isInner'  => false,
				'settings' => [
					'_column_size' => 100,
				],
				'elements' => [
					[
						'id'         => bi_elementor_new_id(),
						'elType'     => 'widget',
						'widgetType' => 'shortcode',
						'isInner'    => false,
						'settings'   => [
							'shortcode' => $shortcode,
						],
						'elements'   => [],
					],
				],
			],
		],
	];
}

/**
 * Slugs to convert to Elementor (marketing + auth; dashboards optional).
 *
 * @return string[]
 */
function bi_elementor_seed_slugs() {
	$registry = function_exists( 'bi_pages_registry' ) ? bi_pages_registry() : [];
	$exclude  = apply_filters(
		'bi_elementor_seed_exclude_slugs',
		[ 'home' ] // Keep PHP kinetic home canonical — do not snapshot into Elementor.
	);
	$slugs    = [];
	foreach ( $registry as $slug => $meta ) {
		$type = $meta['type'] ?? 'public';
		if ( in_array( $slug, (array) $exclude, true ) ) {
			continue;
		}
		if ( in_array( $type, [ 'public', 'trust', 'legal', 'auth', 'utility' ], true ) ) {
			$slugs[] = $slug;
		}
	}
	return apply_filters( 'bi_elementor_seed_slugs', $slugs );
}

/**
 * Resolve WP page for a registry slug (home = page_on_front).
 *
 * @param string $slug Registry slug.
 * @return WP_Post|null
 */
function bi_elementor_find_page( $slug ) {
	$slug = sanitize_key( $slug );
	if ( 'home' === $slug ) {
		$front = (int) get_option( 'page_on_front' );
		if ( $front ) {
			$page = get_post( $front );
			return ( $page instanceof WP_Post ) ? $page : null;
		}
	}
	if ( function_exists( 'bi_find_page_by_slug' ) ) {
		$page = bi_find_page_by_slug( $slug );
		if ( $page instanceof WP_Post ) {
			return $page;
		}
	}
	$page = get_page_by_path( $slug );
	return ( $page instanceof WP_Post ) ? $page : null;
}

/**
 * Seed one page as an Elementor document with kinetic HTML (+ optional shortcodes).
 *
 * @param string $slug  Registry slug.
 * @param bool   $force Overwrite existing Elementor data.
 * @return array<string, mixed>
 */
function bi_elementor_seed_page( $slug, $force = false ) {
	if ( ! bi_elementor_pages_enabled() ) {
		return [ 'ok' => false, 'error' => 'disabled' ];
	}
	if ( ! bi_elementor_active() ) {
		return [ 'ok' => false, 'error' => 'elementor_inactive' ];
	}

	$slug = sanitize_key( $slug );
	if ( 'home' === $slug ) {
		return [
			'ok'      => false,
			'error'   => 'home_excluded',
			'slug'    => 'home',
			'message' => 'Home stays on kinetic PHP (front-page.php); use bi_elementor_restore_kinetic_home().',
		];
	}

	$page = bi_elementor_find_page( $slug );
	if ( ! $page ) {
		return [ 'ok' => false, 'error' => 'page_missing', 'slug' => $slug ];
	}

	if ( ! $force && bi_elementor_has_content( $page->ID ) ) {
		return [
			'ok'      => true,
			'skipped' => true,
			'page_id' => (int) $page->ID,
			'slug'    => $slug,
			'reason'  => 'already_has_elementor_data',
		];
	}

	$html = bi_elementor_capture_page_body( $slug );
	if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
		return [ 'ok' => false, 'error' => 'empty_body', 'slug' => $slug, 'page_id' => (int) $page->ID ];
	}

	$document = bi_elementor_html_document( $html );

	// Prefer full shortcodes captured during render (preserves attributes).
	$captured = bi_elementor_seed_captured_shortcodes();
	$seen     = [];
	foreach ( $captured as $sc ) {
		$key = strtolower( trim( $sc ) );
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$document[]   = bi_elementor_shortcode_section( $sc );
	}

	// Fallback: registry tags not already captured.
	$registry = function_exists( 'bi_pages_registry' ) ? bi_pages_registry() : [];
	$tags     = $registry[ $slug ]['shortcodes'] ?? [];
	foreach ( (array) $tags as $tag ) {
		$tag = sanitize_key( (string) $tag );
		if ( ! $tag || ! shortcode_exists( $tag ) ) {
			continue;
		}
		$candidate = '[' . $tag . ']';
		$key       = strtolower( $candidate );
		$already   = false;
		foreach ( array_keys( $seen ) as $existing ) {
			if ( false !== strpos( $existing, '[' . $tag ) ) {
				$already = true;
				break;
			}
		}
		if ( $already ) {
			continue;
		}
		$seen[ $key ] = true;
		$document[]   = bi_elementor_shortcode_section( $candidate );
	}

	$json = wp_json_encode( $document );
	if ( ! $json ) {
		return [ 'ok' => false, 'error' => 'json_encode_failed', 'page_id' => (int) $page->ID ];
	}

	update_post_meta( $page->ID, '_elementor_edit_mode', 'builder' );
	update_post_meta( $page->ID, '_elementor_data', wp_slash( $json ) );
	update_post_meta( $page->ID, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
	update_post_meta( $page->ID, '_elementor_template_type', 'wp-page' );
	update_post_meta( $page->ID, '_elementor_page_settings', [] );

	// Keep theme header/footer; Elementor owns the body — best editor UX with Hello Elementor child.
	update_post_meta( $page->ID, '_wp_page_template', 'elementor_header_footer' );

	// Clear theme force flags that block Elementor.
	$meta = get_post_meta( $page->ID, 'bi_options', true );
	if ( ! is_array( $meta ) ) {
		$meta = [];
	}
	$meta['force_theme_default'] = 0;
	update_post_meta( $page->ID, 'bi_options', $meta );
	delete_post_meta( $page->ID, '_bi_prototype_body' );

	/**
	 * Allow Companion / kits to react after seed.
	 *
	 * @param int    $page_id Page ID.
	 * @param string $slug    Registry slug.
	 */
	do_action( 'bi_elementor_page_seeded', (int) $page->ID, $slug );

	return [
		'ok'      => true,
		'page_id' => (int) $page->ID,
		'slug'    => $slug,
		'widgets' => count( $document ),
	];
}

/**
 * Seed all primary Elementor pages.
 *
 * @param bool $force Overwrite existing Elementor data.
 * @return array<string, array<string, mixed>>
 */
function bi_elementor_seed_all_pages( $force = false ) {
	$out = [];
	foreach ( bi_elementor_seed_slugs() as $slug ) {
		$out[ $slug ] = bi_elementor_seed_page( $slug, $force );
	}

	if ( bi_elementor_active() && class_exists( '\Elementor\Plugin' ) ) {
		$plugin = \Elementor\Plugin::instance();
		if ( isset( $plugin->files_manager ) && method_exists( $plugin->files_manager, 'clear_cache' ) ) {
			$plugin->files_manager->clear_cache();
		}
	}

	update_option( 'bi_elementor_pages_seeded_at', gmdate( 'c' ), false );
	update_option( 'bi_elementor_pages_seed_report', $out, false );

	return $out;
}

/**
 * Preserve Elementor documents — Companion must not wipe them.
 */
add_filter( 'ngc_preserve_elementor_pages', '__return_true' );

/**
 * Expand Elementor editor dequeue list (prevent Safe Mode from theme JS conflicts).
 * Dequeue only — do not deregister (breaks preview/other consumers).
 */
add_action( 'elementor/editor/before_enqueue_scripts', 'bi_elementor_editor_compat_extra', 20 );
function bi_elementor_editor_compat_extra() {
	$handles = [
		'bi-kinetic-home',
		'bi-kinetic-page',
		'bi-kinetic-bridge',
		'bi-cinematic-video',
		'bi-page-composer',
		'bi-main',
		'bi-3d',
		'nbi-infinity',
		'bi-loader',
		'bi-ngt-floating',
		'bi-ngt-chat',
		'bi-nav-menu',
		'bi-tutors-carousel',
		'bi-focus-trap',
		'bi-nextgen-beyond-infinity-ui',
	];
	foreach ( $handles as $handle ) {
		wp_dequeue_script( $handle );
	}
}

/**
 * Enqueue kinetic tokens inside Elementor preview so the canvas matches the site.
 */
add_action( 'elementor/preview/enqueue_styles', 'bi_elementor_preview_kinetic_styles', 5 );
function bi_elementor_preview_kinetic_styles() {
	wp_enqueue_style( 'bi-style', get_stylesheet_uri(), [], BI_VERSION );
	wp_enqueue_style( 'bi-kinetic-tokens', BI_URI . '/assets/css/kinetic-tokens.css', [ 'bi-style' ], BI_VERSION );
	wp_enqueue_style( 'bi-kinetic-home', BI_URI . '/assets/css/kinetic-home.css', [ 'bi-kinetic-tokens' ], BI_VERSION );
	wp_enqueue_style( 'bi-kinetic-image-hover', BI_URI . '/assets/css/kinetic-image-hover.css', [ 'bi-kinetic-home' ], BI_VERSION );
	wp_enqueue_style( 'bi-kinetic-bridge', BI_URI . '/assets/css/kinetic-bridge.css', [ 'bi-kinetic-tokens' ], BI_VERSION );
	wp_enqueue_style( 'bi-elementor-kinetic', BI_URI . '/assets/css/elementor-kinetic.css', [ 'bi-kinetic-bridge' ], BI_VERSION );
	wp_enqueue_style( 'bi-page-composer', BI_URI . '/assets/css/page-composer.css', [ 'bi-style' ], BI_VERSION );
}

/**
 * Restore kinetic PHP home if it was force-seeded into Elementor.
 *
 * @return array<string, mixed>
 */
function bi_elementor_restore_kinetic_home() {
	$page = bi_elementor_find_page( 'home' );
	if ( ! $page ) {
		return [ 'ok' => false, 'error' => 'page_missing' ];
	}
	delete_post_meta( $page->ID, '_elementor_edit_mode' );
	delete_post_meta( $page->ID, '_elementor_data' );
	delete_post_meta( $page->ID, '_elementor_version' );
	delete_post_meta( $page->ID, '_elementor_template_type' );
	delete_post_meta( $page->ID, '_elementor_page_settings' );
	delete_post_meta( $page->ID, '_wp_page_template' ); // Use front-page.php hierarchy.
	$meta = get_post_meta( $page->ID, 'bi_options', true );
	if ( ! is_array( $meta ) ) {
		$meta = [];
	}
	$meta['force_theme_default'] = 0;
	update_post_meta( $page->ID, 'bi_options', $meta );
	return [ 'ok' => true, 'page_id' => (int) $page->ID, 'restored' => 'kinetic_php_home' ];
}

/**
 * WP-CLI: wp eval 'bi_elementor_seed_all_pages(true);'
 * Also expose a simple admin-post for privileged rebuild.
 */
add_action( 'admin_post_bi_seed_elementor_pages', 'bi_admin_seed_elementor_pages' );
function bi_admin_seed_elementor_pages() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden', 'beyondinfinity' ), 403 );
	}
	check_admin_referer( 'bi_seed_elementor_pages' );
	$force  = ! empty( $_GET['force'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$report = bi_elementor_seed_all_pages( (bool) $force );
	wp_safe_redirect(
		add_query_arg(
			[
				'bi_elementor_seeded' => 1,
				'count'               => count( $report ),
			],
			admin_url( 'edit.php?post_type=page' )
		)
	);
	exit;
}
