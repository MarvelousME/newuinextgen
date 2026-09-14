<?php
/**
 * Asset Loader — conditional wp_enqueue based on current page's rules.
 *
 * Loading strategy:
 *  1. Resolve current page (server-side, no extra DB round-trip).
 *  2. Query enabled rules for that page.
 *  3. If no rules → enqueue NOTHING related to the dynamic 3D engine.
 *  4. If rules exist → determine dependencies → enqueue only what's needed.
 *  5. Pass sanitized config via wp_localize_script().
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Asset_Loader {

	/** Script/style handle for the runtime JS. */
	const RUNTIME_HANDLE = 'ngt3d-runtime';

	/**
	 * Register action hooks.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue' ], 20 );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin' ], 20 );
	}

	/**
	 * Frontend enqueue — runs on wp_enqueue_scripts.
	 */
	public static function enqueue(): void {
		if ( ! NGT3D_Page_Resolver::can_load_engine() ) {
			return;
		}

		$page  = NGT3D_Page_Resolver::resolve();
		$rules = NGT3D_Rule_Repository::get_for_page( $page['id'], $page['slug'] );

		if ( empty( $rules ) ) {
			// No rules for this page — do NOT load any 3D engine overhead.
			return;
		}

		$deps_info = NGT3D_Dependency_Resolver::resolve( $rules );

		// ── External Libraries ─────────────────────────────────────────────────

		// GSAP — reuse the handle already registered by the theme if available,
		// otherwise register from CDN.
		if ( $deps_info['needs_gsap'] && ! wp_script_is( 'bi-ngt-gsap', 'registered' ) && ! wp_script_is( 'bi-ngt-gsap', 'enqueued' ) ) {
			wp_register_script(
				'bi-ngt-gsap',
				'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js',
				[],
				'3.12.5',
				true
			);
		}

		if ( $deps_info['needs_gsap'] ) {
			wp_enqueue_script( 'bi-ngt-gsap' );
		}

		// ScrollTrigger.
		if ( $deps_info['needs_scrolltrigger'] && ! wp_script_is( 'bi-ngt-scrolltrigger', 'registered' ) && ! wp_script_is( 'bi-ngt-scrolltrigger', 'enqueued' ) ) {
			wp_register_script(
				'bi-ngt-scrolltrigger',
				'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js',
				[ 'bi-ngt-gsap' ],
				'3.12.5',
				true
			);
		}

		if ( $deps_info['needs_scrolltrigger'] ) {
			wp_enqueue_script( 'bi-ngt-scrolltrigger' );
		}

		// Three.js — local copy required; CDN only when file exists.
		if ( $deps_info['needs_three'] ) {
			$three_path = NGT3D_PLUGIN_DIR . 'assets/vendor/three.min.js';
			if ( file_exists( $three_path ) ) {
				wp_enqueue_script(
					'ngt3d-three',
					NGT3D_PLUGIN_URL . 'assets/vendor/three.min.js',
					[],
					'0.160.0',
					true
				);
			}
		}

		// Atropos.
		if ( $deps_info['needs_atropos'] ) {
			$atropos_path = NGT3D_PLUGIN_DIR . 'assets/vendor/atropos.min.js';
			if ( file_exists( $atropos_path ) ) {
				wp_enqueue_script(
					'ngt3d-atropos',
					NGT3D_PLUGIN_URL . 'assets/vendor/atropos.min.js',
					[],
					'1.0.0',
					true
				);
				wp_enqueue_style(
					'ngt3d-atropos',
					NGT3D_PLUGIN_URL . 'assets/vendor/atropos.min.css',
					[],
					'1.0.0'
				);
			}
		}

		// ── Plugin Engine Scripts ──────────────────────────────────────────────

		$runtime_deps = [ 'bi-ngt-scrolltrigger' ];

		if ( $deps_info['needs_three'] ) {
			$runtime_deps[] = 'ngt3d-three';
		}
		if ( $deps_info['needs_atropos'] ) {
			$runtime_deps[] = 'ngt3d-atropos';
		}

		// Compatibility shim (legacy bi-3d attributes → registry bridge).
		wp_enqueue_script(
			'ngt3d-compat',
			NGT3D_PLUGIN_URL . 'assets/js/ngt-3d-compat.js',
			[ 'bi-ngt-scrolltrigger' ],
			NGT3D_VERSION,
			true
		);
		$runtime_deps[] = 'ngt3d-compat';

		// WebGL engine (Three.js effects) — only when needed.
		if ( $deps_info['needs_webgl'] && file_exists( NGT3D_PLUGIN_DIR . 'assets/js/ngt-3d-webgl-engine.js' ) ) {
			wp_enqueue_script(
				'ngt3d-webgl',
				NGT3D_PLUGIN_URL . 'assets/js/ngt-3d-webgl-engine.js',
				[ 'ngt3d-three' ],
				NGT3D_VERSION,
				true
			);
			$runtime_deps[] = 'ngt3d-webgl';
		}

		// Main runtime — depends on everything above.
		wp_enqueue_script(
			self::RUNTIME_HANDLE,
			NGT3D_PLUGIN_URL . 'assets/js/ngt-3d-runtime.js',
			$runtime_deps,
			NGT3D_VERSION,
			true
		);

		// Engine CSS.
		wp_enqueue_style(
			'ngt3d-engine',
			NGT3D_PLUGIN_URL . 'assets/css/ngt-3d-engine.css',
			[],
			NGT3D_VERSION
		);

		// Showcase preset CSS — only for presets referenced by active rules.
		$preset_assets = [];
		foreach ( $rules as $rule ) {
			$names = array_filter( array_map( 'trim', explode( ',', (string) ( $rule['animation_names'] ?? '' ) ) ) );
			foreach ( $names as $name ) {
				$def = NGT3D_Animation_Registry::get( sanitize_key( $name ) );
				if ( ! empty( $def['preset_asset'] ) ) {
					$preset_assets[ sanitize_key( (string) $def['preset_asset'] ) ] = true;
				}
			}
		}
		foreach ( array_keys( $preset_assets ) as $asset_id ) {
			$css_rel = 'assets/css/presets/ngt-3d-preset-' . $asset_id . '.css';
			if ( file_exists( NGT3D_PLUGIN_DIR . $css_rel ) ) {
				wp_enqueue_style(
					'ngt3d-preset-' . $asset_id,
					NGT3D_PLUGIN_URL . $css_rel,
					[ 'ngt3d-engine' ],
					NGT3D_VERSION
				);
			}
		}

		// ── Localize Config ────────────────────────────────────────────────────

		$config = NGT3D_Runtime_Config::build( $rules, $page );

		/**
		 * Fires just before the runtime configuration is passed to the browser.
		 *
		 * @param array $config The configuration array.
		 * @param array $rules  Enabled rules for this page.
		 */
		do_action( 'ngt_3d_before_runtime_config', $config, $rules );

		wp_localize_script( self::RUNTIME_HANDLE, 'NGT3D', $config );

		do_action( 'ngt_3d_after_runtime_config', $config, $rules );
	}

	/**
	 * Admin enqueue — load admin UI assets only on the NGT3D admin pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_admin( string $hook ): void {
		// Only load on our own admin pages.
		if ( false === strpos( $hook, 'ngt-3d-scroll' ) ) {
			return;
		}

		wp_enqueue_style(
			'ngt3d-admin',
			NGT3D_PLUGIN_URL . 'assets/css/ngt-3d-admin.css',
			[ 'wp-components' ],
			NGT3D_VERSION
		);

		wp_enqueue_script(
			'ngt3d-admin',
			NGT3D_PLUGIN_URL . 'assets/js/ngt-3d-admin.js',
			[ 'jquery', 'wp-util' ],
			NGT3D_VERSION,
			true
		);

		wp_localize_script( 'ngt3d-admin', 'NGT3D_Admin', [
			'restUrl'    => esc_url_raw( rest_url( 'ngt3d/v1' ) ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'adminNonce' => wp_create_nonce( 'ngt3d_admin' ),
			'pluginUrl'  => NGT3D_PLUGIN_URL,
			'registry'   => NGT3D_Animation_Registry::all(),
			'i18n'       => [
				'confirmDelete'  => __( 'Delete selected rules? This cannot be undone.', 'ngt-3d-scroll' ),
				'importing'      => __( 'Importing…', 'ngt-3d-scroll' ),
				'importSuccess'  => __( 'Import complete.', 'ngt-3d-scroll' ),
				'importError'    => __( 'Import failed: ', 'ngt-3d-scroll' ),
				'targetNotFound' => __( 'TARGET_NOT_FOUND', 'ngt-3d-scroll' ),
				'targetFound'    => __( 'TARGET_FOUND', 'ngt-3d-scroll' ),
			],
		] );

		// Select2 for page/animation selector UIs.
		wp_enqueue_script( 'ngt3d-select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', [ 'jquery' ], '4.0.13', true );
		wp_enqueue_style( 'ngt3d-select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', [], '4.0.13' );
	}
}
