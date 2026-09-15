<?php
/**
 * Premium visual style presets for NextGen Elementor widgets.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Style presets (CSS via data-bi-el-preset).
 */
class BI_EL_Presets {

	/**
	 * @return array<string, string>
	 */
	public static function choices() {
		return [
			'glass'            => __( 'Glass', 'beyondinfinity' ),
			'aurora'           => __( 'Aurora', 'beyondinfinity' ),
			'orbital'          => __( 'Orbital', 'beyondinfinity' ),
			'neural'           => __( 'Neural', 'beyondinfinity' ),
			'spatial'          => __( 'Spatial', 'beyondinfinity' ),
			'holographic'      => __( 'Holographic', 'beyondinfinity' ),
			'editorial'        => __( 'Editorial', 'beyondinfinity' ),
			'dark-technology'  => __( 'Dark Technology', 'beyondinfinity' ),
			'soft-education'   => __( 'Soft Education', 'beyondinfinity' ),
			'premium-minimal'  => __( 'Premium Minimal', 'beyondinfinity' ),
		];
	}

	/**
	 * Sanitize preset slug.
	 *
	 * @param string $preset Raw.
	 * @return string
	 */
	public static function sanitize( $preset ) {
		$key = sanitize_key( (string) $preset );
		return array_key_exists( $key, self::choices() ) ? $key : 'glass';
	}
}
