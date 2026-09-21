<?php
/**
 * Diagnostics — collects and reports engine status for the admin screen.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Diagnostics {

	const LOG_OPTION = 'ngt_3d_diagnostics_log';
	const MAX_LOG    = 200;

	/**
	 * Collect current system status.
	 *
	 * @return array<string, mixed>
	 */
	public static function collect(): array {
		$table_exists = NGT3D_Schema::table_exists();
		$rules        = [];
		$total        = 0;
		$active       = 0;

		if ( $table_exists ) {
			$all    = NGT3D_Rule_Repository::get_all( [ 'per_page' => 9999 ] );
			$rules  = $all['rules'];
			$total  = $all['total'];
			$active = count( array_filter( $rules, fn( $r ) => $r['enabled'] ) );
		}

		return [
			'plugin_version'      => NGT3D_VERSION,
			'schema_version'      => (int) get_option( NGT3D_Schema::OPTION_KEY, 0 ),
			'table_exists'        => $table_exists,
			'total_rules'         => $total,
			'active_rules'        => $active,
			'settings'            => NGT3D_Runtime_Config::get_settings(),
			'broken_selectors'    => self::get_broken_selectors( $rules ),
			'duplicate_page_targets' => self::get_duplicates( $rules ),
			'log'                 => self::get_log(),
			'php_version'         => PHP_VERSION,
			'wp_version'          => get_bloginfo( 'version' ),
			'multisite'           => is_multisite(),
			'bi_3d_enabled'       => function_exists( 'bi_3d_enabled' ) ? bi_3d_enabled() : null,
			'bi_motion_enabled'   => function_exists( 'bi_motion_enabled' ) ? bi_motion_enabled() : null,
			'is_kinetic_home'     => function_exists( 'bi_is_kinetic_home' ) ? bi_is_kinetic_home() : null,
			'companion_active'    => defined( 'NGC_VERSION' ),
			'vendor_assets'       => self::get_vendor_status(),
		];
	}

	/**
	 * Report whether the vendored third-party engines (Three.js, GLTFLoader,
	 * Lenis) are present. All three ship inside the plugin zip; this only
	 * catches the rare case of a partial upload or an overzealous file
	 * exclusion during deploy.
	 *
	 * @return array<string, array{present: bool, path: string}>
	 */
	private static function get_vendor_status(): array {
		$files = [
			'three'      => 'assets/vendor/three.min.js',
			'gltfloader' => 'assets/vendor/GLTFLoader.js',
			'lenis'      => 'assets/vendor/lenis.min.js',
			'lenis_css'  => 'assets/vendor/lenis.css',
		];

		$status = [];
		foreach ( $files as $key => $rel ) {
			$status[ $key ] = [
				'present' => file_exists( NGT3D_PLUGIN_DIR . $rel ),
				'path'    => $rel,
			];
		}

		return $status;
	}

	/**
	 * Log a diagnostic entry.
	 *
	 * @param string $level   'info' | 'warning' | 'error'
	 * @param string $message Message.
	 * @param array  $context Optional context.
	 */
	public static function log( string $level, string $message, array $context = [] ): void {
		$log = get_option( self::LOG_OPTION, [] );

		$log[] = [
			'time'    => gmdate( 'c' ),
			'level'   => sanitize_key( $level ),
			'message' => sanitize_text_field( $message ),
			'context' => $context,
		];

		// Trim to max entries.
		if ( count( $log ) > self::MAX_LOG ) {
			$log = array_slice( $log, -self::MAX_LOG );
		}

		update_option( self::LOG_OPTION, $log, false );
	}

	/**
	 * Clear the diagnostics log.
	 */
	public static function clear_log(): void {
		delete_option( self::LOG_OPTION );
	}

	/**
	 * @return array
	 */
	public static function get_log(): array {
		return (array) get_option( self::LOG_OPTION, [] );
	}

	/**
	 * Find rules with likely broken selectors (basic check — not a full DOM scan).
	 *
	 * @param array $rules All rules.
	 * @return array Rules with suspicious selectors.
	 */
	private static function get_broken_selectors( array $rules ): array {
		$broken = [];
		foreach ( $rules as $rule ) {
			$sel    = $rule['target_selector'] ?? '';
			$result = NGT3D_Validator::validate_selector( $sel );
			if ( 'INVALID_SELECTOR' === $result['status'] ) {
				$broken[] = [
					'id'       => $rule['id'],
					'selector' => $sel,
					'error'    => $result['error'],
				];
			}
		}
		return $broken;
	}

	/**
	 * Detect rules on the same page pointing to the same selector (potential conflicts).
	 *
	 * @param array $rules All rules.
	 * @return array Potential conflicts.
	 */
	private static function get_duplicates( array $rules ): array {
		$seen      = [];
		$conflicts = [];

		foreach ( $rules as $rule ) {
			$key = $rule['page_slug'] . '::' . $rule['target_selector'];
			if ( isset( $seen[ $key ] ) ) {
				$conflicts[] = [
					'ids'      => [ $seen[ $key ], $rule['id'] ],
					'key'      => $key,
					'selector' => $rule['target_selector'],
					'page'     => $rule['page_slug'],
				];
			} else {
				$seen[ $key ] = $rule['id'];
			}
		}

		return $conflicts;
	}
}

// Log unknown animations.
add_action( 'ngt_3d_unknown_animation', function ( string $name ): void {
	NGT3D_Diagnostics::log( 'warning', 'Unknown animation ignored: ' . $name, [ 'name' => $name ] );
} );
