<?php
/**
 * Motion catalogue — source-tagged presets merged into the animation registry.
 *
 * Existing 3D / showcase IDs are annotated; new motion primitives + GSAPify-mapped
 * presets are appended. Does not remove or rename legacy IDs (zero regression).
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Motion_Catalogue {

	/**
	 * Boot filters.
	 */
	public static function boot(): void {
		add_filter( 'ngt_3d_animation_registry', [ self::class, 'merge_registry' ], 20 );
	}

	/**
	 * @param array<string, array<string, mixed>> $registry Registry.
	 * @return array<string, array<string, mixed>>
	 */
	public static function merge_registry( array $registry ): array {
		foreach ( $registry as $id => $def ) {
			if ( empty( $def['source'] ) ) {
				$registry[ $id ]['source'] = self::infer_source( (string) $id, $def );
			}
			if ( empty( $def['supports_effect_stack'] ) ) {
				$registry[ $id ]['supports_effect_stack'] = true;
			}
		}

		foreach ( self::motion_presets() as $id => $def ) {
			if ( isset( $registry[ $id ] ) ) {
				continue; // Never overwrite legacy / showcase implementations.
			}
			$registry[ $id ] = $def;
		}

		return $registry;
	}

	/**
	 * @param string               $id  Animation id.
	 * @param array<string, mixed> $def Definition.
	 */
	private static function infer_source( string $id, array $def ): string {
		$showcase = [ 'doublescroll', '4kvideo', 'wiper', 'dark-veles', 'scroll-mask', 'onscroll', 'zoom', 'swag-card', 'transforms' ];
		if ( in_array( $id, $showcase, true ) ) {
			return 'SYSTEM-ENHANCEMENT';
		}
		if ( ! empty( $def['preset_asset'] ) ) {
			return 'SYSTEM-ENHANCEMENT';
		}
		return 'SYSTEM-ENHANCEMENT';
	}

	/**
	 * New motion presets (JS implementations in ngt-3d-motion-primitives.js).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function motion_presets(): array {
		$gsap = [ 'ngt3d-gsap', 'ngt3d-scrolltrigger' ];
		$base = static function ( string $label, string $source, string $category, string $cost = 'low' ) use ( $gsap ): array {
			return [
				'label'                  => $label,
				'description'            => $label,
				'engine'                 => 'gsap',
				'cost'                   => $cost,
				'status'                 => 'READY',
				'source'                 => $source,
				'desktop_mode'           => 'full',
				'tablet_mode'            => 'reduced',
				'mobile_mode'            => 'reduced',
				'reduced_motion'         => 'static',
				'deps'                   => $gsap,
				'recommended'            => [],
				'options'                => [ 'duration' => 0.85, 'ease' => 'power3.out' ],
				'category'               => $category,
				'supports_effect_stack'  => true,
				'supportsReducedMotion'  => true,
			];
		};

		$out = [];

		$official = [
			'fade-in' => 'Fade In',
			'fade-up' => 'Fade Up',
			'fade-down' => 'Fade Down',
			'fade-left' => 'Fade Left',
			'fade-right' => 'Fade Right',
			'slide-up' => 'Slide Up',
			'slide-down' => 'Slide Down',
			'slide-left' => 'Slide Left',
			'slide-right' => 'Slide Right',
			'scale-in' => 'Scale In',
			'zoom-in' => 'Zoom In',
			'rotate-in' => 'Rotate In',
			'flip-in-x' => 'Flip In X',
			'flip-in-y' => 'Flip In Y',
			'stagger-children' => 'Stagger Children',
		];
		foreach ( $official as $id => $label ) {
			$out[ $id ] = $base( $label, 'GSAP-OFFICIAL', 'entrance' );
		}

		$system = [
			'blur-in' => [ 'Blur In', 'entrance', 'medium' ],
			'blur-up' => [ 'Blur + Slide Up', 'entrance', 'medium' ],
			'depth-entrance' => [ 'Depth Entrance', '3d', 'medium' ],
			'elastic-pop' => [ 'Elastic Pop', 'entrance', 'medium' ],
			'magnetic-button' => [ 'Magnetic Button', 'button', 'medium' ],
			'text-chars-rise' => [ 'Character Rise', 'text', 'medium' ],
			'text-words-rise' => [ 'Word Rise', 'text', 'medium' ],
			'text-blur-chars' => [ 'Blur Characters', 'text', 'high' ],
		];
		foreach ( $system as $id => $row ) {
			$out[ $id ] = $base( $row[0], 'SYSTEM-ENHANCEMENT', $row[1], $row[2] );
		}

		$gsapify = [
			'gsapify-fade-up-on-scroll' => 'Fade Up on Scroll',
			'gsapify-card-hover-lift' => 'Card Hover Lift',
			'gsapify-card-slide-in-stagger' => 'Card Slide-In Stagger',
			'gsapify-clip-path-image-reveal' => 'Clip-Path Image Reveal',
			'gsapify-horizontal-scroll-section' => 'Horizontal Scroll Section',
			'gsapify-image-parallax-zoom' => 'Image Parallax Zoom',
			'gsapify-image-tilt-on-hover' => 'Image Tilt on Hover',
			'gsapify-ken-burns' => 'Ken Burns Slideshow',
			'gsapify-kinetic-split-lines' => 'Kinetic Split Lines',
			'gsapify-layered-zoom-scroll' => 'Layered Zoom Scroll',
			'gsapify-magnetic-button' => 'Magnetic Button',
			'gsapify-stacked-card-fan' => 'Stacked Card Fan',
			'gsapify-stagger-letter-reveal' => 'Stagger Letter Reveal',
			'gsapify-staggered-grid-reveal' => 'Staggered Grid Reveal',
			'gsapify-text-scramble' => 'Text Scramble',
			'gsapify-typewriter' => 'Typewriter',
			'gsapify-tilt-parallax-card' => 'Tilt Parallax Card',
			'gsapify-vertical-card-stack' => 'Vertical Card Stack',
			'gsapify-wobble-card-enter' => 'Wobble Card Enter',
			'gsapify-word-by-word-slide' => 'Word-by-Word Slide',
			'gsapify-curtain-reveal' => 'Curtain Reveal',
			'gsapify-grayscale-to-color' => 'Grayscale to Color',
			'gsapify-underline-slide' => 'Underline Slide',
			'gsapify-count-up' => 'Count-Up Numbers',
			'gsapify-scroll-scrubbed-progress' => 'Scroll-Scrubbed Progress',
			'gsapify-text-fade-up-words' => 'Fade Up Words',
			'gsapify-text-line-by-line' => 'Line-by-Line Reveal',
			'gsapify-text-blur-in' => 'Blur In',
			'gsapify-text-slide-left' => 'Slide From Left',
			'gsapify-text-slide-right' => 'Slide From Right',
			'gsapify-text-staggered-letters' => 'Staggered Letters',
			'gsapify-text-word-build' => 'Word-by-Word Build',
			'gsapify-text-scramble-decode' => 'Scramble Decode',
			'gsapify-text-typewriter-effect' => 'Typewriter Effect',
		];
		foreach ( $gsapify as $id => $label ) {
			$row = $base( $label, 'GSAPIFY-VERIFIED', str_starts_with( $id, 'gsapify-text' ) ? 'text' : 'gsapify', 'medium' );
			$row['reference'] = str_starts_with( $id, 'gsapify-text' )
				? 'https://gsapify.com/gsap-text-animations/#text-animation-collection'
				: 'https://gsapify.com/gsap-animations/';
			$row['reference_name'] = $label;
			$out[ $id ] = $row;
		}

		return $out;
	}

	/**
	 * Catalogue counts for reports / diagnostics.
	 *
	 * @return array<string, int>
	 */
	public static function counts(): array {
		$all = NGT3D_Animation_Registry::all();
		$counts = [
			'total' => count( $all ),
			'GSAPIFY-VERIFIED' => 0,
			'GSAP-OFFICIAL' => 0,
			'SYSTEM-ENHANCEMENT' => 0,
			'CUSTOM' => 0,
			'UNVERIFIED' => 0,
		];
		foreach ( $all as $def ) {
			$src = (string) ( $def['source'] ?? 'UNVERIFIED' );
			if ( ! isset( $counts[ $src ] ) ) {
				$counts[ $src ] = 0;
			}
			++$counts[ $src ];
		}
		return $counts;
	}
}
