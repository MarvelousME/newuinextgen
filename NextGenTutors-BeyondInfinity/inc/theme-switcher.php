<?php
/**
 * Theme switcher — loads token layers without overwriting component CSS.
 *
 * Architecture:
 *   1. tokens/base.css     — structural (spacing, radius, motion)
 *   2. skins/{preset}.css  — semantic color/typography tokens per skin
 *   3. dynamic scheme CSS  — palette overrides via html[data-bi-scheme]
 *   4. components/*.css    — layout rules using var() only
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'bi_enqueue_skin_layers', 5 );
add_action( 'wp_enqueue_scripts', 'bi_enqueue_unified_tokens', 60 );
add_filter( 'language_attributes', 'bi_skin_html_attributes', 20 );
add_filter( 'body_class', 'bi_skin_body_classes' );
add_shortcode( 'bi_theme_switcher', 'bi_theme_switcher_shortcode' );
add_action( 'wp_footer', 'bi_render_theme_switcher_widget', 5 );
add_action( 'wp_ajax_bi_set_skin_preview', 'bi_ajax_set_skin_preview' );
add_action( 'wp_ajax_nopriv_bi_set_skin_preview', 'bi_ajax_set_skin_preview' );

/**
 * Enqueue structural tokens + active skin (token-only CSS).
 */
function bi_enqueue_skin_layers() {
	if ( is_admin() || bi_is_builder_edit_mode() ) {
		return;
	}

	wp_enqueue_style(
		'bi-tokens-base',
		BI_URI . '/assets/css/tokens/base.css',
		[],
		BI_VERSION
	);

	$preset_id = bi_resolve_visual_preset_id();
	$preset    = bi_get_visual_preset( $preset_id );
	if ( empty( $preset['skin_file'] ) ) {
		return;
	}

	$skin_path = BI_DIR . '/assets/css/skins/' . $preset['skin_file'];
	if ( ! file_exists( $skin_path ) ) {
		return;
	}

	wp_enqueue_style(
		'bi-skin-' . $preset_id,
		BI_URI . '/assets/css/skins/' . $preset['skin_file'],
		[ 'bi-tokens-base' ],
		BI_VERSION
	);
}

/**
 * Enqueue the unified token layer last so its alias declarations win the
 * cascade over every legacy token namespace (--ng-*, NGT --navy/--lime,
 * ui-library --ngt-color-*). Skins still override --ngt-* semantics and all
 * aliases follow automatically via var() indirection.
 */
function bi_enqueue_unified_tokens() {
	if ( is_admin() || bi_is_builder_edit_mode() ) {
		return;
	}

	$file = BI_DIR . '/assets/css/tokens/unified.css';
	if ( ! file_exists( $file ) ) {
		return;
	}

	// Phase 5: inline the token layer (critical CSS). Removes a render-blocking
	// request and guarantees tokens exist at first paint (loader, dark scheme).
	wp_register_style( 'bi-tokens-unified', false, [ 'bi-tokens-base' ], BI_VERSION );
	wp_enqueue_style( 'bi-tokens-unified' );
	wp_add_inline_style( 'bi-tokens-unified', (string) file_get_contents( $file ) );
}

/**
 * Append data-bi-skin and data-bi-scheme to <html>.
 *
 * @param string $output Language attributes.
 * @return string
 */
function bi_skin_html_attributes( $output ) {
	if ( is_admin() ) {
		return $output;
	}

	$skin   = esc_attr( bi_resolve_visual_preset_id() );
	$scheme = esc_attr( bi_get_theme_option( 'color_scheme', 'default' ) );

	$output .= sprintf( ' data-bi-skin="%s" data-bi-scheme="%s"', $skin, $scheme );

	return $output;
}

/**
 * Body classes for skin/scheme (legacy hooks + debugging).
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function bi_skin_body_classes( $classes ) {
	$skin = bi_resolve_visual_preset_id();
	$classes[] = 'bi-skin-' . sanitize_html_class( $skin );
	return $classes;
}

/**
 * Whether the floating switcher should render.
 *
 * @return bool
 */
function bi_theme_switcher_enabled() {
	if ( is_admin() || bi_is_builder_edit_mode() ) {
		return false;
	}
	if ( ! (bool) bi_get_theme_option( 'theme_switcher_enabled', 0 ) ) {
		return false;
	}
	$visibility = bi_get_theme_option( 'theme_switcher_visibility', 'admins' );
	if ( 'admins' === $visibility && ! current_user_can( 'edit_theme_options' ) ) {
		return false;
	}
	return true;
}

/**
 * Enqueue switcher assets when enabled.
 */
function bi_enqueue_theme_switcher_assets() {
	if ( ! bi_theme_switcher_enabled() ) {
		return;
	}

	wp_enqueue_style(
		'bi-theme-switcher',
		BI_URI . '/assets/css/theme-switcher.css',
		[ 'bi-tokens-base' ],
		BI_VERSION
	);
	wp_enqueue_script(
		'bi-theme-switcher',
		BI_URI . '/assets/js/theme-switcher.js',
		[],
		BI_VERSION,
		true
	);

	$presets_out = [];
	foreach ( bi_get_visual_presets() as $id => $preset ) {
		$presets_out[ $id ] = [
			'title' => $preset['title'] ?? $id,
			'desc'  => $preset['description'] ?? '',
		];
	}

	$scheme_tokens = [];
	foreach ( (array) bi_storage_get( 'color_schemes', [] ) as $id => $scheme ) {
		$scheme_tokens[ $id ] = $scheme['colors'] ?? [];
	}

	wp_localize_script(
		'bi-theme-switcher',
		'biThemeSwitcher',
		[
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'bi_skin_preview' ),
			'active'        => bi_resolve_visual_preset_id(),
			'presets'       => $presets_out,
			'schemes'       => bi_get_list_color_schemes(),
			'scheme'        => bi_get_theme_option( 'color_scheme', 'default' ),
			'schemeTokens'  => $scheme_tokens,
			'reload'        => true,
			'i18n'     => [
				'label'   => __( 'Look & feel', 'beyondinfinity' ),
				'skin'    => __( 'Visual style', 'beyondinfinity' ),
				'scheme'  => __( 'Color palette', 'beyondinfinity' ),
				'apply'   => __( 'Apply', 'beyondinfinity' ),
				'preview' => __( 'Preview only — save in Customizer to persist.', 'beyondinfinity' ),
			],
		]
	);
}
add_action( 'wp_enqueue_scripts', 'bi_enqueue_theme_switcher_assets', 25 );

/**
 * Floating switcher widget in footer.
 */
function bi_render_theme_switcher_widget() {
	if ( ! bi_theme_switcher_enabled() ) {
		return;
	}
	?>
	<div id="bi-theme-switcher" class="bi-theme-switcher" hidden aria-label="<?php esc_attr_e( 'Theme style switcher', 'beyondinfinity' ); ?>">
		<button type="button" class="bi-theme-switcher__toggle" aria-expanded="false" aria-controls="bi-theme-switcher-panel">
			<span class="bi-theme-switcher__toggle-icon" aria-hidden="true">◐</span>
			<span class="bi-theme-switcher__toggle-label"><?php esc_html_e( 'Style', 'beyondinfinity' ); ?></span>
		</button>
		<div id="bi-theme-switcher-panel" class="bi-theme-switcher__panel" hidden>
			<p class="bi-theme-switcher__heading"><?php esc_html_e( 'Look & feel', 'beyondinfinity' ); ?></p>
			<p class="bi-theme-switcher__hint"><?php esc_html_e( 'Preview styles without overwriting component CSS.', 'beyondinfinity' ); ?></p>
			<div class="bi-theme-switcher__group" data-bi-switcher="skin"></div>
			<div class="bi-theme-switcher__group" data-bi-switcher="scheme"></div>
			<p class="bi-theme-switcher__note"><?php esc_html_e( 'Save permanently in Appearance → Customize → Brand & Colors.', 'beyondinfinity' ); ?></p>
		</div>
	</div>
	<?php
}

/**
 * Shortcode: [bi_theme_switcher layout="inline|floating"]
 *
 * @param array<string, string> $atts Shortcode attributes.
 * @return string
 */
function bi_theme_switcher_shortcode( $atts ) {
	if ( ! current_user_can( 'edit_theme_options' ) && 'public' !== bi_get_theme_option( 'theme_switcher_visibility', 'admins' ) ) {
		return '';
	}

	$atts = shortcode_atts(
		[
			'layout' => 'inline',
		],
		$atts,
		'bi_theme_switcher'
	);

	bi_enqueue_theme_switcher_assets();

	ob_start();
	?>
	<div class="bi-theme-switcher bi-theme-switcher--<?php echo esc_attr( $atts['layout'] ); ?>" data-bi-switcher-root>
		<p class="bi-theme-switcher__heading"><?php esc_html_e( 'Visual style', 'beyondinfinity' ); ?></p>
		<div class="bi-theme-switcher__group" data-bi-switcher="skin"></div>
		<div class="bi-theme-switcher__group" data-bi-switcher="scheme"></div>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * AJAX: set preview cookie for skin (session preview, not persisted to DB).
 */
function bi_ajax_set_skin_preview() {
	check_ajax_referer( 'bi_skin_preview', 'nonce' );

	$skin = isset( $_POST['skin'] ) ? sanitize_key( wp_unslash( $_POST['skin'] ) ) : '';
	if ( $skin && ! isset( bi_get_visual_presets()[ $skin ] ) ) {
		wp_send_json_error( [ 'message' => __( 'Unknown skin.', 'beyondinfinity' ) ], 400 );
	}

	if ( $skin ) {
		setcookie(
			'bi_skin_preview',
			$skin,
			[
				'expires'  => time() + DAY_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => false,
				'samesite' => 'Lax',
			]
		);
		$_COOKIE['bi_skin_preview'] = $skin; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} else {
		setcookie( 'bi_skin_preview', '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
		unset( $_COOKIE['bi_skin_preview'] );
	}

	wp_send_json_success( [ 'skin' => $skin ?: bi_get_theme_option( 'visual_preset', 'beyond-infinity' ) ] );
}
