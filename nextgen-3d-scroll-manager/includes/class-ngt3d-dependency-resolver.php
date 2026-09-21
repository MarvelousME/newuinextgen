<?php
/**
 * Dependency Resolver — maps animation names to required JS/CSS handles.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Dependency_Resolver {

	/**
	 * Given a list of rule rows, return the complete set of asset handles
	 * that need to be enqueued.
	 *
	 * @param array $rules Rule rows from NGT3D_Rule_Repository::get_for_page().
	 * @return array{
	 *   needs_gsap: bool,
	 *   needs_scrolltrigger: bool,
	 *   needs_three: bool,
	 *   needs_webgl: bool,
	 *   needs_atropos: bool,
	 *   needs_lenis: bool,
	 *   needs_hscroll: bool,
	 *   needs_hscroll_gltf: bool,
	 *   has_rules: bool
	 * }
	 */
	public static function resolve( array $rules ): array {
		$deps = [
			'has_rules'           => false,
			'needs_gsap'          => false,
			'needs_scrolltrigger' => false,
			'needs_three'         => false,
			'needs_webgl'         => false,
			'needs_atropos'       => false,
			'needs_lenis'         => false,
			'needs_hscroll'       => false,
			'needs_hscroll_gltf'  => false,
		];

		if ( empty( $rules ) ) {
			return $deps;
		}

		$deps['has_rules'] = true;
		$settings          = NGT3D_Runtime_Config::get_settings();

		foreach ( $rules as $rule ) {
			$names   = array_filter( array_map( 'trim', explode( ',', (string) ( $rule['animation_names'] ?? '' ) ) ) );
			$options = is_array( $rule['animation_options'] ?? null ) ? $rule['animation_options'] : [];

			foreach ( $names as $name ) {
				$def = NGT3D_Animation_Registry::get( $name );
				if ( null === $def ) {
					continue;
				}

				$engine = $def['engine'] ?? 'gsap';

				match ( $engine ) {
					'gsap'       => (function () use ( &$deps ) {
						$deps['needs_gsap']          = true;
						$deps['needs_scrolltrigger'] = true;
					})(),
					'three'      => (function () use ( &$deps ) {
						$deps['needs_gsap']          = true;
						$deps['needs_scrolltrigger'] = true;
						$deps['needs_three']         = true;
						$deps['needs_webgl']         = true;
					})(),
					'atropos'    => (function () use ( &$deps ) {
						$deps['needs_atropos'] = true;
					})(),
					'3d-hscroll' => (function () use ( &$deps, $options, $settings ) {
						$deps['needs_gsap']          = true;
						$deps['needs_scrolltrigger'] = true;
						$deps['needs_hscroll']       = true;

						// Three.js/WebGL is only pulled in when the rule actually
						// asks for a model (geometry other than 'none', or a glTF URL).
						$geometry = (string) ( $options['modelGeometry'] ?? 'icosahedron' );
						$has_url  = ! empty( $options['modelUrl'] );
						if ( 'none' !== $geometry || $has_url ) {
							$deps['needs_three'] = true;
							$deps['needs_webgl'] = true;
						}
						if ( $has_url ) {
							$deps['needs_hscroll_gltf'] = true;
						}

						// Lenis is opt-in globally via Settings → Lenis smooth scroll.
						if ( ! empty( $settings['lenis_enabled'] ) ) {
							$deps['needs_lenis'] = true;
						}
					})(),
					'native'     => null, // no extra deps
					default      => null,
				};
			}
		}

		return $deps;
	}
}
