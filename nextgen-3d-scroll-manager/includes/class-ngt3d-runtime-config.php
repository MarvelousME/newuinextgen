<?php
/**
 * Runtime Configuration Builder — assembles window.NGT3D for the browser.
 *
 * All data is sanitized before passing to wp_localize_script().
 * No executable JavaScript is embedded inline.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Runtime_Config {

	/**
	 * Build the sanitized configuration array to pass to the browser.
	 *
	 * @param array $rules   Rows from NGT3D_Rule_Repository::get_for_page().
	 * @param array $page    Resolved page info from NGT3D_Page_Resolver::resolve().
	 * @return array<string, mixed>  Safe JSON-serializable config.
	 */
	public static function build( array $rules, array $page ): array {
		$settings = self::get_settings();

		$config = [
			'version'        => NGT3D_VERSION,
			'enabled'        => (bool) ( $settings['engine_enabled'] ?? true ),
			'page'           => [
				'id'       => (int) $page['id'],
				'slug'     => sanitize_key( $page['slug'] ),
				'isFront'  => (bool) $page['is_front'],
			],
			'reducedMotion'  => false, // overridden at runtime by matchMedia
			'debug'          => (bool) ( $settings['debug_mode'] ?? false ),
			'lenis'          => (bool) ( $settings['lenis_enabled'] ?? false ),
			'lenisOptions'   => self::lenis_options( $settings ),
			'rules'          => empty( $settings['engine_enabled'] ) ? [] : self::serialize_rules( $rules ),
			'perspective'    => max( 400, min( 3000, (int) ( $settings['default_perspective'] ?? 1200 ) ) ),
			'fpsSafeguard'   => (bool) ( $settings['fps_safeguard'] ?? true ),
			'fpsThreshold'   => max( 10, min( 60, (int) ( $settings['fps_threshold'] ?? 30 ) ) ),
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'restUrl'        => esc_url_raw( rest_url( 'ngt3d/v1' ) ),
			'nonce'          => wp_create_nonce( 'wp_rest' ),
			'pluginUrl'      => NGT3D_PLUGIN_URL,
		];

		/**
		 * Filters the runtime configuration sent to the browser.
		 *
		 * @param array $config The assembled config.
		 * @param array $page   Page info.
		 * @param array $rules  Enabled rules.
		 */
		return (array) apply_filters( 'ngt_3d_runtime_config', $config, $page, $rules );
	}

	/**
	 * Serialize rule rows to a browser-safe format.
	 *
	 * @param array $rules DB rule rows (decoded).
	 * @return array
	 */
	private static function serialize_rules( array $rules ): array {
		$out = [];

		foreach ( $rules as $rule ) {
			// Parse animation names.
			$names = array_filter( array_map( 'trim', explode( ',', (string) $rule['animation_names'] ) ) );
			$names = array_values( $names );

			// Parse style classes.
			$classes = array_filter( explode( ' ', (string) $rule['style_classes'] ) );
			$classes = array_values( $classes );

			$out[] = [
				'id'           => (int) $rule['id'],
				'selector'     => (string) $rule['target_selector'],
				'targetType'   => (string) $rule['target_type'],
				'animations'   => $names,
				'styleClasses' => $classes,
				'options'      => is_array( $rule['animation_options'] ) ? $rule['animation_options'] : [],
				'desktopMode'  => (string) $rule['desktop_mode'],
				'tabletMode'   => (string) $rule['tablet_mode'],
				'mobileMode'   => (string) $rule['mobile_mode'],
			];
		}

		return $out;
	}

	/**
	 * Get NGT3D plugin settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$defaults = [
			'engine_enabled'      => true,
			'debug_mode'          => false,
			'lenis_enabled'       => false,
			'lenis_duration'      => 1.2,
			'lenis_easing'        => 'ease-out',
			'default_perspective' => 1200,
			'fps_safeguard'       => true,
			'fps_threshold'       => 30,
		];

		$saved = get_option( 'ngt_3d_settings', [] );

		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Save settings.
	 *
	 * @param array $raw Raw settings from admin form.
	 * @return bool
	 */
	public static function save_settings( array $raw ): bool {
		$clean = [
			'engine_enabled'      => ! empty( $raw['engine_enabled'] ),
			'debug_mode'          => ! empty( $raw['debug_mode'] ),
			'lenis_enabled'       => ! empty( $raw['lenis_enabled'] ),
			'lenis_duration'      => max( 0.1, min( 5.0, (float) ( $raw['lenis_duration'] ?? 1.2 ) ) ),
			'lenis_easing'        => in_array( $raw['lenis_easing'] ?? '', [ 'ease-out', 'ease-in-out', 'linear' ], true ) ? $raw['lenis_easing'] : 'ease-out',
			'default_perspective' => max( 400, min( 3000, absint( $raw['default_perspective'] ?? 1200 ) ) ),
			'fps_safeguard'       => ! empty( $raw['fps_safeguard'] ),
			'fps_threshold'       => max( 10, min( 60, absint( $raw['fps_threshold'] ?? 30 ) ) ),
		];

		// Also invalidate rule caches when settings change.
		NGT3D_Rule_Repository::flush_all_caches();

		return update_option( 'ngt_3d_settings', $clean, false );
	}

	/**
	 * Build Lenis options for the browser (only when enabled).
	 *
	 * @param array $settings Plugin settings.
	 * @return array
	 */
	private static function lenis_options( array $settings ): array {
		if ( empty( $settings['lenis_enabled'] ) ) {
			return [];
		}

		return [
			'duration'  => (float) ( $settings['lenis_duration'] ?? 1.2 ),
			'easing'    => sanitize_text_field( $settings['lenis_easing'] ?? 'ease' ),
			'smoothWheel' => true,
		];
	}
}
