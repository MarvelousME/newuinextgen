<?php
/**
 * Elementor Globals ↔ BeyondInfinity design tokens.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Token bridge.
 */
class BI_EL_Tokens {

	/**
	 * Enqueue CSS that aliases Elementor kit vars to --ngt-* tokens.
	 */
	public static function enqueue() {
		$deps = [];
		if ( wp_style_is( 'ng-ui-tokens', 'registered' ) || wp_style_is( 'ng-ui-tokens', 'enqueued' ) ) {
			$deps[] = 'ng-ui-tokens';
		}
		if ( wp_style_is( 'bi-tokens-unified', 'registered' ) || wp_style_is( 'bi-tokens-unified', 'enqueued' ) ) {
			$deps[] = 'bi-tokens-unified';
		}

		$handle = 'bi-el-ds';
		if ( ! wp_style_is( $handle, 'registered' ) ) {
			wp_register_style(
				$handle,
				BI_URI . '/assets/css/elementor-design-system.css',
				$deps,
				BI_EL_DS_VERSION
			);
		}
		if ( ! wp_style_is( 'bi-el-ds-presets', 'registered' ) ) {
			wp_register_style(
				'bi-el-ds-presets',
				BI_URI . '/assets/css/elementor-design-system-presets.css',
				[ $handle ],
				BI_EL_DS_VERSION
			);
		}
		wp_enqueue_style( $handle );
		wp_enqueue_style( 'bi-el-ds-presets' );

		wp_register_script(
			'bi-el-ds-webgl',
			BI_URI . '/assets/js/elementor-design-system-webgl.js',
			[],
			BI_EL_DS_VERSION,
			true
		);

		$deps = [ 'jquery' ];
		if ( wp_script_is( 'ngt3d-runtime', 'registered' ) || wp_script_is( 'ngt3d-runtime', 'enqueued' ) ) {
			$deps[] = 'ngt3d-runtime';
		}
		wp_register_script(
			'bi-el-ds-motion',
			BI_URI . '/assets/js/elementor-design-system-motion.js',
			$deps,
			BI_EL_DS_VERSION,
			true
		);
		wp_enqueue_script( 'bi-el-ds-motion' );
		wp_enqueue_script( 'bi-el-ds-webgl' );
	}

	/**
	 * Editor panel styles.
	 */
	public static function enqueue_editor() {
		self::enqueue();
		wp_enqueue_style(
			'bi-el-ds-editor',
			BI_URI . '/assets/css/elementor-design-system-editor.css',
			[ 'bi-el-ds' ],
			BI_EL_DS_VERSION
		);
	}

	/**
	 * Map of Elementor kit CSS variables → canonical NextGen tokens.
	 *
	 * @return array<string, string>
	 */
	public static function kit_aliases() {
		return [
			'--e-global-color-primary'   => 'var(--ngt-primary)',
			'--e-global-color-secondary' => 'var(--ngt-secondary)',
			'--e-global-color-text'      => 'var(--ngt-text)',
			'--e-global-color-accent'    => 'var(--ngt-secondary)',
			'--e-global-typography-primary-font-family' => 'var(--ngt-font-sans, inherit)',
		];
	}
}
