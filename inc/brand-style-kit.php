<?php
/**
 * BeyondInfinity Brand Style Kit — token API, admin preview, builder sync.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'bi_brand_style_kit_admin_menu' );
add_action( 'admin_post_bi_sync_elementor_kit', 'bi_brand_style_kit_sync_elementor' );
add_action( 'after_setup_theme', 'bi_brand_style_kit_editor_support', 20 );
add_action( 'customize_save_after', 'bi_brand_style_kit_auto_sync_elementor', 30 );
add_action( 'update_option_bi_theme_options', 'bi_brand_style_kit_auto_sync_elementor', 10, 0 );

/**
 * Block editor + theme.json support.
 */
function bi_brand_style_kit_editor_support() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style(
		[
			'assets/css/tokens/base.css',
			'assets/css/tokens/unified.css',
			'assets/css/tokens/brand-semantic.css',
			'assets/css/integrations/gutenberg.css',
		]
	);
}

/**
 * Canonical semantic token map for PHP consumers (Elementor sync, docs).
 *
 * @return array<string, string>
 */
function bi_brand_semantic_tokens() {
	$skin = function_exists( 'bi_get_active_color_tokens' ) ? bi_get_active_color_tokens() : [];
	return array_merge(
		[
			'--ngt-color-brand-primary'        => $skin['--ngt-primary'] ?? '#07172f',
			'--ngt-color-brand-primary-hover'  => $skin['--ngt-primary-dark'] ?? '#031126',
			'--ngt-color-brand-secondary'      => $skin['--ngt-secondary'] ?? '#28c7f7',
			'--ngt-color-brand-accent'         => $skin['--ngt-accent'] ?? '#ffb703',
			'--ngt-color-text-primary'         => $skin['--ngt-text'] ?? '#10213f',
			'--ngt-color-text-secondary'       => $skin['--ngt-text-2'] ?? '#687386',
			'--ngt-color-text-muted'           => $skin['--ngt-text-3'] ?? '#94a3b8',
			'--ngt-color-bg-page'              => $skin['--ngt-bg'] ?? '#f5f8ff',
			'--ngt-color-bg-elevated'          => '#ffffff',
			'--ngt-color-border'               => '#e6edf7',
			'--ngt-color-success'              => '#10b981',
			'--ngt-color-warning'              => '#f59e0b',
			'--ngt-color-danger'               => '#ef4444',
			'--ngt-color-info'                 => '#0369a1',
			'--ngt-font-family-display'        => '"Sora", system-ui, sans-serif',
			'--ngt-font-family-body'           => '"Inter", system-ui, sans-serif',
			'--ngt-container-default'          => '1280px',
			'--ngt-container-narrow'             => '720px',
			'--ngt-button-radius'              => '10px',
			'--ngt-card-radius'                => '16px',
		],
		$skin
	);
}

/**
 * Admin menu under Appearance.
 */
function bi_brand_style_kit_admin_menu() {
	add_theme_page(
		__( 'Brand Style Kit', 'beyondinfinity' ),
		__( 'Brand Style Kit', 'beyondinfinity' ),
		'manage_options',
		'bi-brand-style-kit',
		'bi_brand_style_kit_render_admin'
	);
}

/**
 * Auto-sync Elementor kit when customizer or theme options change (silent, no redirect).
 */
function bi_brand_style_kit_auto_sync_elementor() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	bi_brand_style_kit_push_elementor_globals();
}

/**
 * Relative luminance for WCAG contrast (sRGB hex).
 *
 * @param string $hex #rrggbb
 * @return float 0–1
 */
function bi_brand_style_kit_relative_luminance( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( strlen( $hex ) === 3 ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( strlen( $hex ) !== 6 ) {
		return 0;
	}
	$channels = [];
	for ( $i = 0; $i < 3; $i++ ) {
		$c = hexdec( substr( $hex, $i * 2, 2 ) ) / 255;
		$channels[] = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
	}
	return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
}

/**
 * Contrast ratio between two colours.
 *
 * @param string $fg Foreground hex.
 * @param string $bg Background hex.
 * @return float
 */
function bi_brand_style_kit_contrast_ratio( $fg, $bg ) {
	$l1 = bi_brand_style_kit_relative_luminance( $fg );
	$l2 = bi_brand_style_kit_relative_luminance( $bg );
	$lighter = max( $l1, $l2 );
	$darker  = min( $l1, $l2 );
	return ( $lighter + 0.05 ) / ( $darker + 0.05 );
}

/**
 * Key brand pairs for admin contrast audit.
 *
 * @return array<int, array{label:string,fg:string,bg:string,ratio:float,aa:bool,aaa:bool}>
 */
function bi_brand_style_kit_contrast_audit() {
	$tokens = bi_brand_semantic_tokens();
	$pairs  = [
		[ 'Text on page', $tokens['--ngt-color-text-primary'] ?? '#10213f', $tokens['--ngt-color-bg-page'] ?? '#f5f8ff' ],
		[ 'Muted on page', $tokens['--ngt-color-text-muted'] ?? '#94a3b8', $tokens['--ngt-color-bg-page'] ?? '#f5f8ff' ],
		[ 'Primary on white', $tokens['--ngt-color-brand-primary'] ?? '#07172f', '#ffffff' ],
		[ 'Secondary on white', $tokens['--ngt-color-brand-secondary'] ?? '#28c7f7', '#ffffff' ],
	];
	$out = [];
	foreach ( $pairs as $pair ) {
		$ratio = bi_brand_style_kit_contrast_ratio( $pair[1], $pair[2] );
		$out[] = [
			'label' => $pair[0],
			'fg'    => $pair[1],
			'bg'    => $pair[2],
			'ratio' => round( $ratio, 2 ),
			'aa'    => $ratio >= 4.5,
			'aaa'   => $ratio >= 7.0,
		];
	}
	return $out;
}

/**
 * Sync Elementor Site Settings kit from theme tokens.
 */
function bi_brand_style_kit_sync_elementor() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Access denied.', 'beyondinfinity' ) );
	}
	check_admin_referer( 'bi_sync_elementor_kit' );

	$result = bi_brand_style_kit_push_elementor_globals();
	wp_safe_redirect(
		add_query_arg(
			[
				'page'           => 'bi-brand-style-kit',
				'elementor_sync' => $result['ok'] ? '1' : '0',
				'elementor_msg'  => rawurlencode( $result['message'] ),
			],
			admin_url( 'themes.php' )
		)
	);
	exit;
}

/**
 * Push globals into active Elementor kit.
 *
 * @return array{ok:bool,message:string}
 */
function bi_brand_style_kit_push_elementor_globals() {
	if ( ! did_action( 'elementor/loaded' ) && ! class_exists( '\Elementor\Plugin' ) ) {
		return [
			'ok'      => false,
			'message' => __( 'Elementor is not active.', 'beyondinfinity' ),
		];
	}

	$kit_id = (int) get_option( 'elementor_active_kit', 0 );
	if ( $kit_id <= 0 ) {
		return [
			'ok'      => false,
			'message' => __( 'No active Elementor kit found.', 'beyondinfinity' ),
		];
	}

	$tokens = bi_brand_semantic_tokens();
	$meta   = get_post_meta( $kit_id, '_elementor_page_settings', true );
	$meta   = is_array( $meta ) ? $meta : [];

	$meta['system_colors'] = [
		[
			'_id'   => 'primary',
			'title' => 'Brand Primary',
			'color' => $tokens['--ngt-color-brand-primary'] ?? '#07172f',
		],
		[
			'_id'   => 'secondary',
			'title' => 'Brand Secondary',
			'color' => $tokens['--ngt-color-brand-secondary'] ?? '#28c7f7',
		],
		[
			'_id'   => 'text',
			'title' => 'Text',
			'color' => $tokens['--ngt-color-text-primary'] ?? '#10213f',
		],
		[
			'_id'   => 'accent',
			'title' => 'Accent',
			'color' => $tokens['--ngt-color-brand-accent'] ?? '#ffb703',
		],
	];

	$meta['system_typography'] = [
		[
			'_id'                    => 'primary',
			'title'                  => 'Display',
			'typography_typography'  => 'custom',
			'typography_font_family' => 'Sora',
			'typography_font_weight' => '700',
		],
		[
			'_id'                    => 'secondary',
			'title'                  => 'Body',
			'typography_typography'  => 'custom',
			'typography_font_family' => 'Inter',
			'typography_font_weight' => '400',
		],
	];

	update_post_meta( $kit_id, '_elementor_page_settings', $meta );

	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	return [
		'ok'      => true,
		'message' => sprintf(
			/* translators: %d: kit post ID */
			__( 'Elementor kit #%d updated from BeyondInfinity tokens.', 'beyondinfinity' ),
			$kit_id
		),
	];
}

/**
 * Render Brand Style Kit preview (uses production component classes).
 */
function bi_brand_style_kit_render_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Access denied.', 'beyondinfinity' ) );
	}

	wp_enqueue_style( 'bi-tokens-base', BI_URI . '/assets/css/tokens/base.css', [], BI_VERSION );
	wp_enqueue_style( 'bi-skin-beyond-infinity', BI_URI . '/assets/css/skins/beyond-infinity.css', [ 'bi-tokens-base' ], BI_VERSION );
	wp_enqueue_style( 'bi-tokens-unified', BI_URI . '/assets/css/tokens/unified.css', [ 'bi-skin-beyond-infinity' ], BI_VERSION );
	wp_enqueue_style( 'bi-tokens-brand', BI_URI . '/assets/css/tokens/brand-semantic.css', [ 'bi-tokens-unified' ], BI_VERSION );
	wp_enqueue_style( 'bi-style', get_stylesheet_uri(), [ 'bi-tokens-brand' ], BI_VERSION );
	wp_enqueue_style( 'bi-components', BI_URI . '/assets/css/components.css', [ 'bi-style' ], BI_VERSION );
	wp_enqueue_style( 'bi-accessibility', BI_URI . '/assets/css/accessibility.css', [ 'bi-components' ], BI_VERSION );

	if ( ! empty( $_GET['elementor_sync'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$type = ! empty( $_GET['elementor_sync'] ) && '1' === $_GET['elementor_sync'] ? 'success' : 'error'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$msg  = isset( $_GET['elementor_msg'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['elementor_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $type ),
			esc_html( $msg )
		);
	}

	$tokens = bi_brand_semantic_tokens();
	?>
	<div class="wrap bi-brand-kit">
		<h1><?php esc_html_e( 'BeyondInfinity Brand Style Kit', 'beyondinfinity' ); ?></h1>
		<p><?php esc_html_e( 'Live preview of production tokens and components. BeyondInfinity is the single source of truth; Elementor, Gutenberg, and WPBakery map to these semantic tokens.', 'beyondinfinity' ); ?></p>

		<p>
			<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=bi_sync_elementor_kit' ), 'bi_sync_elementor_kit' ) ); ?>">
				<?php esc_html_e( 'Sync Elementor Global Colors & Fonts', 'beyondinfinity' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>"><?php esc_html_e( 'Customizer', 'beyondinfinity' ); ?></a>
		</p>

		<style>
			.bi-brand-kit__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin: 16px 0 32px; }
			.bi-brand-kit__swatch { border-radius: 12px; overflow: hidden; border: 1px solid #ccd6e4; }
			.bi-brand-kit__swatch span { display: block; height: 56px; }
			.bi-brand-kit__swatch code { display: block; padding: 8px; font-size: 11px; background: #fff; }
			.bi-brand-kit__section { background: var(--ngt-color-bg-elevated, #fff); border: 1px solid var(--ngt-color-border, #e6edf7); border-radius: 16px; padding: 24px; margin-bottom: 24px; }
			html[data-bi-skin="beyond-infinity"] .bi-brand-kit { --ngt-primary: #07172f; }
		</style>

		<div class="bi-brand-kit__section">
			<h2><?php esc_html_e( 'Brand colours', 'beyondinfinity' ); ?></h2>
			<div class="bi-brand-kit__grid">
				<?php
				$swatches = [
					'Primary'   => $tokens['--ngt-color-brand-primary'] ?? '',
					'Secondary' => $tokens['--ngt-color-brand-secondary'] ?? '',
					'Accent'    => $tokens['--ngt-color-brand-accent'] ?? '',
					'Text'      => $tokens['--ngt-color-text-primary'] ?? '',
					'Muted'     => $tokens['--ngt-color-text-muted'] ?? '',
					'Page BG'   => $tokens['--ngt-color-bg-page'] ?? '',
				];
				foreach ( $swatches as $label => $hex ) :
					?>
					<div class="bi-brand-kit__swatch">
						<span style="background:<?php echo esc_attr( $hex ); ?>"></span>
						<code><?php echo esc_html( $label . ' ' . $hex ); ?></code>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="bi-brand-kit__section">
			<h2><?php esc_html_e( 'Contrast audit (WCAG 2.2)', 'beyondinfinity' ); ?></h2>
			<table class="widefat striped" style="max-width:720px">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Pair', 'beyondinfinity' ); ?></th>
						<th><?php esc_html_e( 'Ratio', 'beyondinfinity' ); ?></th>
						<th><?php esc_html_e( 'AA (4.5:1)', 'beyondinfinity' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( bi_brand_style_kit_contrast_audit() as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['label'] ); ?></td>
							<td><?php echo esc_html( (string) $row['ratio'] ); ?>:1</td>
							<td><?php echo $row['aa'] ? '✓' : '✗'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description"><?php esc_html_e( 'Secondary-on-white may fail AA for small text — use secondary as accent, not body copy colour.', 'beyondinfinity' ); ?></p>
		</div>

		<div class="bi-brand-kit__section">
			<h2><?php esc_html_e( 'Typography', 'beyondinfinity' ); ?></h2>
			<h1 style="font-family:var(--ngt-font-family-display);margin:0 0 8px"><?php esc_html_e( 'Display heading — Sora', 'beyondinfinity' ); ?></h1>
			<p style="font-family:var(--ngt-font-family-body);max-width:60ch;color:var(--ngt-color-text-secondary)">
				<?php esc_html_e( 'Body copy uses Inter with comfortable measure. Hierarchy comes from size and weight, not decorative font switching.', 'beyondinfinity' ); ?>
			</p>
		</div>

		<div class="bi-brand-kit__section">
			<h2><?php esc_html_e( 'Buttons', 'beyondinfinity' ); ?></h2>
			<p>
				<button type="button" class="ngt-btn ngt-btn--primary"><?php esc_html_e( 'Primary', 'beyondinfinity' ); ?></button>
				<button type="button" class="ngt-btn ngt-btn--secondary"><?php esc_html_e( 'Secondary', 'beyondinfinity' ); ?></button>
				<button type="button" class="ngt-btn ngt-btn--outline"><?php esc_html_e( 'Outline', 'beyondinfinity' ); ?></button>
				<button type="button" class="ngt-btn ngt-btn--primary" disabled><?php esc_html_e( 'Disabled', 'beyondinfinity' ); ?></button>
			</p>
		</div>

		<div class="bi-brand-kit__section">
			<h2><?php esc_html_e( 'Cards & forms', 'beyondinfinity' ); ?></h2>
			<div class="ngt-card" style="max-width:420px;margin-bottom:16px;padding:20px">
				<h3 style="margin-top:0"><?php esc_html_e( 'Feature card', 'beyondinfinity' ); ?></h3>
				<p style="color:var(--ngt-color-text-secondary);margin-bottom:0"><?php esc_html_e( 'Cards use shared surface, radius, border and elevation tokens.', 'beyondinfinity' ); ?></p>
			</div>
			<div class="ngt-form-group" style="max-width:420px">
				<label for="bi-kit-demo-input"><?php esc_html_e( 'Email', 'beyondinfinity' ); ?></label>
				<input type="email" id="bi-kit-demo-input" class="ngt-form-control" placeholder="parent@example.com" />
			</div>
		</div>

		<div class="bi-brand-kit__section">
			<h2><?php esc_html_e( 'Token contract (PHP export)', 'beyondinfinity' ); ?></h2>
			<pre style="overflow:auto;font-size:11px;background:#0b1524;color:#e6edf7;padding:16px;border-radius:12px"><?php echo esc_html( wp_json_encode( $tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
		</div>
	</div>
	<?php
}
