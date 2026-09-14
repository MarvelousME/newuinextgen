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
	 *   has_rules: bool
	 * }
	 */
	public static function resolve( array $rules ): array {
		$deps = [
			'has_rules'          => false,
			'needs_gsap'         => false,
			'needs_scrolltrigger'=> false,
			'needs_three'        => false,
			'needs_webgl'        => false,
			'needs_atropos'      => false,
		];

		if ( empty( $rules ) ) {
			return $deps;
		}

		$deps['has_rules'] = true;

		foreach ( $rules as $rule ) {
			$names = array_filter( array_map( 'trim', explode( ',', (string) ( $rule['animation_names'] ?? '' ) ) ) );

			foreach ( $names as $name ) {
				$def = NGT3D_Animation_Registry::get( $name );
				if ( null === $def ) {
					continue;
				}

				$engine = $def['engine'] ?? 'gsap';

				match ( $engine ) {
					'gsap'    => (function () use ( &$deps ) {
						$deps['needs_gsap']          = true;
						$deps['needs_scrolltrigger'] = true;
					})(),
					'three'   => (function () use ( &$deps ) {
						$deps['needs_gsap']          = true;
						$deps['needs_scrolltrigger'] = true;
						$deps['needs_three']         = true;
						$deps['needs_webgl']         = true;
					})(),
					'atropos' => (function () use ( &$deps ) {
						$deps['needs_atropos'] = true;
					})(),
					'native'  => null, // no extra deps
					default   => null,
				};
			}
		}

		return $deps;
	}
}
