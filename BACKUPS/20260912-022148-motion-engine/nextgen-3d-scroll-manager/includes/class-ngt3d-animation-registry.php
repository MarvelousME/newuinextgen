<?php
/**
 * Animation Registry — PHP-side whitelist of all permitted animations.
 *
 * SECURITY: Only animation names listed here are permitted to run.
 * An animation name from the database that is not in this registry
 * will be silently ignored on the frontend (no JS execution).
 *
 * Each entry defines:
 *   label         – Human-readable name shown in the admin.
 *   description   – Short description for the Animation Library screen.
 *   engine        – 'gsap' | 'native' | 'three' | 'atropos'
 *   cost          – 'low' | 'medium' | 'high'  (performance classification)
 *   desktop_mode  – Default mode: 'full' | 'reduced' | 'disabled'
 *   tablet_mode   – Default mode.
 *   mobile_mode   – Default mode.
 *   reduced_motion – 'disabled' (no motion) | 'static' (immediate render)
 *   deps          – JS dependency identifiers (ngt3d-gsap, ngt3d-scrolltrigger,
 *                   ngt3d-three, ngt3d-atropos)
 *   recommended   – Suggested target selectors / element types.
 *   options       – Available configuration parameters with defaults.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Animation_Registry {

	/**
	 * @var array<string, array<string, mixed>>|null
	 */
	private static ?array $registry = null;

	/**
	 * Return the full registry, filtered through ngt_3d_animation_registry.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function all(): array {
		if ( null !== self::$registry ) {
			return self::$registry;
		}

		$built = self::build();

		/**
		 * Filters the full animation registry.
		 *
		 * Themes and plugins can add custom animations here.
		 * Any animation added here MUST have a matching implementation
		 * in ngt-3d-registry.js (or ngt-3d-gsap-engine.js etc.).
		 *
		 * @param array<string, array<string, mixed>> $built The default registry.
		 */
		self::$registry = (array) apply_filters( 'ngt_3d_animation_registry', $built );

		return self::$registry;
	}

	/**
	 * Check if an animation name is registered.
	 *
	 * @param string $name Animation name.
	 * @return bool
	 */
	public static function exists( string $name ): bool {
		return array_key_exists( $name, self::all() );
	}

	/**
	 * Return a single animation's definition, or null.
	 *
	 * @param string $name Animation name.
	 * @return array<string, mixed>|null
	 */
	public static function get( string $name ): ?array {
		$all = self::all();
		return $all[ $name ] ?? null;
	}

	/**
	 * Return only the names (keys) of the registry.
	 *
	 * @return string[]
	 */
	public static function names(): array {
		return array_keys( self::all() );
	}

	/**
	 * Given an array of names, return only those that are registered.
	 * Unrecognised names are logged to NGT3D_Diagnostics and dropped.
	 *
	 * @param string[] $names Submitted animation names.
	 * @return string[]
	 */
	public static function filter_valid( array $names ): array {
		$all   = self::all();
		$valid = [];

		foreach ( $names as $name ) {
			$clean = sanitize_key( $name );
			if ( isset( $all[ $clean ] ) ) {
				$valid[] = $clean;
			} else {
				do_action( 'ngt_3d_unknown_animation', $clean );
			}
		}

		return $valid;
	}

	/**
	 * Determine which JS dependency handles are required for a set of animation names.
	 *
	 * @param string[] $names Valid animation names.
	 * @return string[]       Script handle suffixes (e.g. 'ngt3d-gsap').
	 */
	public static function deps_for( array $names ): array {
		$all  = self::all();
		$deps = [];

		foreach ( $names as $name ) {
			if ( isset( $all[ $name ]['deps'] ) ) {
				foreach ( $all[ $name ]['deps'] as $dep ) {
					$deps[ $dep ] = true;
				}
			}
		}

		return array_keys( $deps );
	}

	/**
	 * Build the default registry definition.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function build(): array {
		$gsap_st_deps = [ 'ngt3d-gsap', 'ngt3d-scrolltrigger' ];

		return [

			// ── Depth & Parallax ───────────────────────────────────────────────
			'depth-scroll' => [
				'label'         => __( 'Depth Scroll', 'ngt-3d-scroll' ),
				'description'   => __( 'Slow background translateY linked to scroll progress.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'reduced',
				'mobile_mode'   => 'reduced',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ 'section', '#cta', '.bi-parallax-cta' ],
				'options'       => [ 'scrub' => 1, 'y' => -80 ],
			],

			'depth-hero' => [
				'label'         => __( 'Hero Depth Parallax', 'ngt-3d-scroll' ),
				'description'   => __( 'Multi-layer depth parallax for the page hero. Background moves slower than foreground to create depth.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'reduced',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '#hero', '.ngi-hero', '.bi-cinematic-hero' ],
				'options'       => [ 'scrub' => 1.2, 'y' => -120, 'perspective' => 1200 ],
			],

			'parallax-slow' => [
				'label'         => __( 'Parallax — Slow', 'ngt-3d-scroll' ),
				'description'   => __( 'Gentle 0.2× rate parallax background shift.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'reduced',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '[data-parallax-rate]', '.bi-parallax-cta__bg' ],
				'options'       => [ 'scrub' => 1, 'rate' => 0.2 ],
			],

			'parallax-medium' => [
				'label'         => __( 'Parallax — Medium', 'ngt-3d-scroll' ),
				'description'   => __( '0.4× rate parallax for feature images.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'disabled',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '.bi-theme-image', 'img.parallax' ],
				'options'       => [ 'scrub' => 1, 'rate' => 0.4 ],
			],

			'parallax-fast' => [
				'label'         => __( 'Parallax — Fast', 'ngt-3d-scroll' ),
				'description'   => __( '0.6× rate parallax for foreground elements.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'medium',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'disabled',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '.foreground-card', '.ngi-card' ],
				'options'       => [ 'scrub' => 1, 'rate' => 0.6 ],
			],

			// ── Perspective & 3D Transforms ────────────────────────────────────
			'perspective-reveal' => [
				'label'         => __( 'Perspective Reveal', 'ngt-3d-scroll' ),
				'description'   => __( 'Section enters with a 3D perspective tilt and fades into flat view as it reaches centre.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'full',
				'mobile_mode'   => 'reduced',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ 'section', '.ngi-section', '.ngi-card' ],
				'options'       => [ 'scrub' => 0.8, 'rotateX' => 6, 'perspective' => 1200, 'opacityFrom' => 0.7, 'opacityTo' => 1 ],
			],

			'perspective-exit' => [
				'label'         => __( 'Perspective Exit', 'ngt-3d-scroll' ),
				'description'   => __( 'Section shrinks and rotates back as the user scrolls past it.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'reduced',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '#hero', '.ngi-hero' ],
				'options'       => [ 'scrub' => 1.2, 'scaleFrom' => 1, 'scaleTo' => 0.92, 'rotateX' => 4, 'opacityTo' => 0.6 ],
			],

			'rotate-x-scroll' => [
				'label'         => __( 'Rotate X (Scroll)', 'ngt-3d-scroll' ),
				'description'   => __( 'Scroll-linked rotation around the X axis.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'reduced',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '.ngi-card', 'article' ],
				'options'       => [ 'scrub' => 1, 'rotateX' => 15 ],
			],

			'rotate-y-scroll' => [
				'label'         => __( 'Rotate Y (Scroll)', 'ngt-3d-scroll' ),
				'description'   => __( 'Scroll-linked rotation around the Y axis.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'disabled',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '.ngi-card', 'article' ],
				'options'       => [ 'scrub' => 1, 'rotateY' => 15 ],
			],

			'scale-depth' => [
				'label'         => __( 'Scale Depth', 'ngt-3d-scroll' ),
				'description'   => __( 'Element scales as it enters/exits the viewport, creating a depth illusion.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'full',
				'mobile_mode'   => 'full',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '.ngi-card', '.ngi-section', 'img' ],
				'options'       => [ 'scrub' => 1, 'scaleFrom' => 0.92, 'scaleTo' => 1 ],
			],

			'translate-z' => [
				'label'         => __( 'Translate Z (Scroll)', 'ngt-3d-scroll' ),
				'description'   => __( 'Element moves toward or away from viewer linked to scroll progress.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'reduced',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '.ngi-card', '.ngi-hero__headline' ],
				'options'       => [ 'scrub' => 1, 'z' => 80, 'perspective' => 1000 ],
			],

			// ── Card / Section Stacking ────────────────────────────────────────
			'stack-3d' => [
				'label'         => __( 'Stack 3D', 'ngt-3d-scroll' ),
				'description'   => __( 'Cards stack and unstack with perspective as the user scrolls through the section.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'medium',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'reduced',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '#how-it-works', '[data-bi-stack-3d]', '.bi-stack-3d' ],
				'options'       => [ 'scrub' => 1.5, 'pin' => true, 'perspective' => 1000 ],
			],

			'cards-fan' => [
				'label'         => __( 'Cards Fan', 'ngt-3d-scroll' ),
				'description'   => __( 'Cards fan out in 3D space and then collapse as the section is scrolled.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'medium',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'reduced',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '#pathways', '.ngi-section--pathways' ],
				'options'       => [ 'scrub' => 1.2, 'stagger' => 0.08 ],
			],

			'stagger-depth' => [
				'label'         => __( 'Stagger Depth Entrance', 'ngt-3d-scroll' ),
				'description'   => __( 'Child elements enter sequentially with a 3D depth offset.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'full',
				'mobile_mode'   => 'full',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '.ngi-section--trust', '.ngi-grid' ],
				'options'       => [ 'scrub' => false, 'stagger' => 0.1, 'rotateX' => 10, 'y' => 30 ],
			],

			// ── Pinning & Scrolling ────────────────────────────────────────────
			'pin-section' => [
				'label'         => __( 'Pin Section', 'ngt-3d-scroll' ),
				'description'   => __( 'Pins the section in place while scroll-linked content moves inside it.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'medium',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'disabled',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '#how-it-works', '#pathways' ],
				'options'       => [ 'scrub' => 1, 'pin' => true, 'pinSpacing' => true, 'end' => '+=300%' ],
			],

			'horizontal-scroll' => [
				'label'         => __( 'Horizontal Scroll', 'ngt-3d-scroll' ),
				'description'   => __( 'Content scrolls horizontally while the page scrolls vertically.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'medium',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'disabled',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '#testimonials', '.ngi-section--testimonials' ],
				'options'       => [ 'scrub' => 1, 'pin' => true ],
			],

			// ── Image Effects ──────────────────────────────────────────────────
			'image-depth' => [
				'label'         => __( 'Image Depth', 'ngt-3d-scroll' ),
				'description'   => __( 'Image moves in parallax relative to its containing section.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'reduced',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ 'img', '.bi-theme-image', '.bi-theme-image-wrap' ],
				'options'       => [ 'scrub' => 1, 'rate' => 0.3 ],
			],

			'image-reveal-3d' => [
				'label'         => __( 'Image Reveal 3D', 'ngt-3d-scroll' ),
				'description'   => __( 'Clip-path wipe with a 3D perspective tilt on the image entering the viewport.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'medium',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'full',
				'mobile_mode'   => 'reduced',
				'reduced_motion'=> 'static',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ 'img', '.bi-theme-image-wrap' ],
				'options'       => [ 'scrub' => false, 'duration' => 1.2, 'rotateX' => 8 ],
			],

			// ── Text Effects ───────────────────────────────────────────────────
			'text-depth' => [
				'label'         => __( 'Text Depth', 'ngt-3d-scroll' ),
				'description'   => __( 'Headline or text moves forward in Z space as the user scrolls.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'full',
				'mobile_mode'   => 'reduced',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ 'h1', 'h2', '.ngi-heading', '.ngi-hero__headline' ],
				'options'       => [ 'scrub' => 1, 'z' => 40 ],
			],

			'text-perspective' => [
				'label'         => __( 'Text Perspective Entrance', 'ngt-3d-scroll' ),
				'description'   => __( 'Text enters with a subtle rotateX tilt and fades in.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'full',
				'mobile_mode'   => 'full',
				'reduced_motion'=> 'static',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ 'h2', '.ngi-heading', '.ngi-section-head' ],
				'options'       => [ 'scrub' => false, 'rotateX' => 12, 'y' => 20, 'duration' => 0.9 ],
			],

			// ── Cinematic ─────────────────────────────────────────────────────
			'section-cinematic' => [
				'label'         => __( 'Section Cinematic Transition', 'ngt-3d-scroll' ),
				'description'   => __( 'Dramatic scale + depth shift between sections, creating a cinematic cut feel.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'medium',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'reduced',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ 'section', '.ngi-section' ],
				'options'       => [ 'scrub' => 1, 'scaleFrom' => 1.05, 'scaleTo' => 1, 'opacityFrom' => 0.5 ],
			],

			// ── Carousel Enhancement ───────────────────────────────────────────
			'carousel-depth' => [
				'label'         => __( 'Carousel Depth', 'ngt-3d-scroll' ),
				'description'   => __( 'Enhances the existing tutors carousel with subtle scroll-linked depth and glow.', 'ngt-3d-scroll' ),
				'engine'        => 'gsap',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'full',
				'mobile_mode'   => 'full',
				'reduced_motion'=> 'disabled',
				'deps'          => $gsap_st_deps,
				'recommended'   => [ '#tutors', '.ngi-section--tutors-3d', '.bi-carousel-3d-shell' ],
				'options'       => [ 'scrub' => 0.6, 'y' => -20 ],
			],

			// ── Mouse-driven ───────────────────────────────────────────────────
			'mouse-parallax' => [
				'label'         => __( 'Mouse Parallax', 'ngt-3d-scroll' ),
				'description'   => __( 'Elements shift subtly based on mouse position within a section.', 'ngt-3d-scroll' ),
				'engine'        => 'native',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'disabled',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => [],
				'recommended'   => [ '#hero', '.ngi-hero' ],
				'options'       => [ 'depth' => 20 ],
			],

			'tilt-3d' => [
				'label'         => __( 'Tilt 3D (Mouse)', 'ngt-3d-scroll' ),
				'description'   => __( 'Element tilts toward the mouse cursor. Wraps the existing bi-tilt-3d system.', 'ngt-3d-scroll' ),
				'engine'        => 'native',
				'cost'          => 'low',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'disabled',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => [],
				'recommended'   => [ '[data-bi-tilt]', '.ngi-card', '.bi-tilt-3d' ],
				'options'       => [ 'max' => 10 ],
			],

			// ── Atropos ───────────────────────────────────────────────────────
			'atropos-depth' => [
				'label'         => __( 'Atropos 3D Depth', 'ngt-3d-scroll' ),
				'description'   => __( 'Multi-layer 3D hover depth card using the Atropos library.', 'ngt-3d-scroll' ),
				'engine'        => 'atropos',
				'cost'          => 'medium',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'disabled',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'disabled',
				'deps'          => [ 'ngt3d-atropos' ],
				'recommended'   => [ '.ngi-card', '.tutor-card3d' ],
				'options'       => [ 'activeOffset' => 40, 'shadowScale' => 1.05 ],
			],

			// ── WebGL ─────────────────────────────────────────────────────────
			'webgl-distortion' => [
				'label'         => __( 'WebGL Distortion', 'ngt-3d-scroll' ),
				'description'   => __( 'WebGL-powered image distortion effect (fluid / wave distortion on hover).', 'ngt-3d-scroll' ),
				'engine'        => 'three',
				'cost'          => 'high',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'disabled',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'static',
				'deps'          => [ 'ngt3d-three', 'ngt3d-webgl' ],
				'recommended'   => [ 'img', '.bi-theme-image-wrap' ],
				'options'       => [ 'intensity' => 0.3, 'speed' => 0.5 ],
			],

			'webgl-image-reveal' => [
				'label'         => __( 'WebGL Image Reveal', 'ngt-3d-scroll' ),
				'description'   => __( 'WebGL-powered cinematic image reveal using a custom displacement map.', 'ngt-3d-scroll' ),
				'engine'        => 'three',
				'cost'          => 'high',
				'desktop_mode'  => 'full',
				'tablet_mode'   => 'disabled',
				'mobile_mode'   => 'disabled',
				'reduced_motion'=> 'static',
				'deps'          => [ 'ngt3d-three', 'ngt3d-webgl' ],
				'recommended'   => [ 'img', '.bi-theme-image-wrap' ],
				'options'       => [ 'duration' => 1.5, 'displacement' => 'default' ],
			],

			// ── Framer showcase presets (evidence-based) ───────────────────────
			'doublescroll' => [
				'label'            => __( 'Double Scroll', 'ngt-3d-scroll' ),
				'description'      => __( 'Opposite-direction dual vertical tracks in a sticky scene (inspired by Double Scroll Effect).', 'ngt-3d-scroll' ),
				'engine'           => 'gsap',
				'cost'             => 'medium',
				'status'           => 'READY',
				'reference'        => 'https://doublescroll.framer.website/',
				'reference_status' => 'live',
				'desktop_mode'     => 'full',
				'tablet_mode'      => 'reduced',
				'mobile_mode'      => 'disabled',
				'reduced_motion'   => 'disabled',
				'deps'             => $gsap_st_deps,
				'preset_asset'     => 'doublescroll',
				'recommended'      => [ '#tutoring-story', '#how-it-works', '[data-ngt-3d-target="doublescroll"]' ],
				'options'          => [
					'scrub' => 1.2, 'pin' => true, 'primaryDistance' => 280, 'secondaryDistance' => -280,
					'speedRatio' => 1, 'perspective' => 1200, 'intensity' => 'balanced',
				],
			],

			'4kvideo' => [
				'label'            => __( '4K Video Emphasis', 'ngt-3d-scroll' ),
				'description'      => __( 'Pinned media scale emphasis for cinematic video bands. Does not scrub video.currentTime.', 'ngt-3d-scroll' ),
				'engine'           => 'gsap',
				'cost'             => 'medium',
				'status'           => 'READY',
				'reference'        => 'https://4kvideo.framer.website/',
				'reference_status' => 'live',
				'desktop_mode'     => 'full',
				'tablet_mode'      => 'reduced',
				'mobile_mode'      => 'disabled',
				'reduced_motion'   => 'disabled',
				'deps'             => $gsap_st_deps,
				'preset_asset'     => '4kvideo',
				'recommended'      => [ '#video-story', '.bi-cinematic-band', 'video' ],
				'options'          => [
					'scrub' => 1, 'pin' => true, 'scaleFrom' => 0.86, 'scaleTo' => 1,
					'borderRadiusFrom' => 28, 'borderRadiusTo' => 12, 'overlayOpacity' => 0.15,
				],
			],

			'wiper' => [
				'label'            => __( 'Wiper Reveal', 'ngt-3d-scroll' ),
				'description'      => __( 'Clip-path wipe reveal driven by scroll progress (Wiper Effect).', 'ngt-3d-scroll' ),
				'engine'           => 'gsap',
				'cost'             => 'low',
				'status'           => 'READY',
				'reference'        => 'https://wiper.framer.website/',
				'reference_status' => 'live',
				'desktop_mode'     => 'full',
				'tablet_mode'      => 'full',
				'mobile_mode'      => 'reduced',
				'reduced_motion'   => 'static',
				'deps'             => $gsap_st_deps,
				'preset_asset'     => 'wiper',
				'recommended'      => [ '#image-hover', '.ngi-hover-lab', '[data-ngt-3d-target="wiper"]' ],
				'options'          => [
					'scrub' => 1, 'direction' => 'left', 'startCoverage' => 100, 'endCoverage' => 0, 'feather' => 0,
				],
			],

			'dark-veles' => [
				'label'            => __( 'Dark Veles Motion Language', 'ngt-3d-scroll' ),
				'description'      => __( 'Portfolio-style sticky pacing, staggered reveals, and image scale — motion grammar only, not a site clone.', 'ngt-3d-scroll' ),
				'engine'           => 'gsap',
				'cost'             => 'medium',
				'status'           => 'READY',
				'reference'        => 'https://dark-veles.framer.website/',
				'reference_status' => 'live',
				'desktop_mode'     => 'full',
				'tablet_mode'      => 'reduced',
				'mobile_mode'      => 'disabled',
				'reduced_motion'   => 'disabled',
				'deps'             => $gsap_st_deps,
				'preset_asset'     => 'dark-veles',
				'recommended'      => [ '#platform-highlights', '#trust', '.ngi-aura' ],
				'options'          => [
					'scrub' => 0.9, 'stagger' => 0.12, 'scaleFrom' => 0.94, 'scaleTo' => 1, 'y' => 40, 'pin' => false,
				],
			],

			'scroll-mask' => [
				'label'            => __( 'Scroll Mask', 'ngt-3d-scroll' ),
				'description'      => __( 'Masked/clipped typographic or media reveals scrubbed by scroll (Scroll Mask).', 'ngt-3d-scroll' ),
				'engine'           => 'gsap',
				'cost'             => 'medium',
				'status'           => 'READY',
				'reference'        => 'https://scroll-mask.framer.website/',
				'reference_status' => 'live',
				'desktop_mode'     => 'full',
				'tablet_mode'      => 'reduced',
				'mobile_mode'      => 'disabled',
				'reduced_motion'   => 'static',
				'deps'             => $gsap_st_deps,
				'preset_asset'     => 'scroll-mask',
				'recommended'      => [ '#pathways', '.ngi-section--pathways', 'h2' ],
				'options'          => [
					'scrub' => 1.1, 'pin' => true, 'maskStart' => 0, 'maskEnd' => 100, 'maskScale' => 1.15, 'origin' => 'center',
				],
			],

			'swag-card' => [
				'label'            => __( 'Swag Card Stack', 'ngt-3d-scroll' ),
				'description'      => __( 'Sticky stacked cards that peel and settle on scroll — recreated from the Swag card motion (learnframer demo). Ideal for tutoring journey steps or product moments.', 'ngt-3d-scroll' ),
				'engine'           => 'gsap',
				'cost'             => 'medium',
				'status'           => 'READY',
				'reference'        => 'https://swag-card.learnframer.site/',
				'reference_status' => 'live',
				'desktop_mode'     => 'full',
				'tablet_mode'      => 'reduced',
				'mobile_mode'      => 'disabled',
				'reduced_motion'   => 'static',
				'deps'             => $gsap_st_deps,
				'preset_asset'     => 'swag-card',
				'recommended'      => [ '#ngt3d-swag-card', '.ngt-3d-swag', '.ngi-journey' ],
				'options'          => [
					'scrub' => 1, 'pin' => true, 'perspective' => 1400, 'rise' => 120, 'scaleTo' => 0.88, 'rotateX' => -14,
				],
			],

			'onscroll' => [
				'label'            => __( 'OnScroll Media', 'ngt-3d-scroll' ),
				'description'      => __( 'Staggered image/media scroll entrances inspired by the OnScroll illustrations showcase.', 'ngt-3d-scroll' ),
				'engine'           => 'gsap',
				'cost'             => 'low',
				'status'           => 'READY',
				'reference'        => 'https://onscroll.framer.website/',
				'reference_status' => 'live',
				'desktop_mode'     => 'full',
				'tablet_mode'      => 'full',
				'mobile_mode'      => 'reduced',
				'reduced_motion'   => 'static',
				'deps'             => $gsap_st_deps,
				'preset_asset'     => 'onscroll',
				'recommended'      => [ '#subjects', '.ngi-grid', 'img' ],
				'options'          => [
					'scrub' => false, 'stagger' => 0.08, 'y' => 48, 'scaleFrom' => 0.92, 'scaleTo' => 1, 'duration' => 0.9,
				],
			],

			'transforms' => [
				'label'            => __( '3D Transforms Gallery', 'ngt-3d-scroll' ),
				'description'      => __( 'Perspective media gallery with scrubbed rotateY/X, translateZ and scale — Framer scroll-transform language. Original transforms.framer.website changed; motion grounded in Academy + Framer University 3D gallery patterns.', 'ngt-3d-scroll' ),
				'engine'           => 'gsap',
				'cost'             => 'medium',
				'status'           => 'READY',
				'reference'        => 'https://www.framer.com/academy/lessons/framer-animations-scroll-transform',
				'reference_status' => 'documented',
				'desktop_mode'     => 'full',
				'tablet_mode'      => 'reduced',
				'mobile_mode'      => 'reduced',
				'reduced_motion'   => 'static',
				'deps'             => $gsap_st_deps,
				'preset_asset'     => 'transforms',
				'recommended'      => [ '#ngt3d-transforms', '.ngt-3d-transforms', '.ngi-media-grid' ],
				'options'          => [
					'scrub' => 1, 'perspective' => 1600, 'rotateY' => 55, 'rotateX' => -8, 'zFrom' => -220, 'zTo' => 40, 'scaleFrom' => 0.82,
				],
			],

			'zoom' => [
				'label'            => __( 'Zoom Entrance', 'ngt-3d-scroll' ),
				'description'      => __( 'Scale-based zoom entrance/exit suitable for hero, media, or cards (Zoom Entrance).', 'ngt-3d-scroll' ),
				'engine'           => 'gsap',
				'cost'             => 'low',
				'status'           => 'READY',
				'reference'        => 'https://zoom.framer.website/',
				'reference_status' => 'live',
				'desktop_mode'     => 'full',
				'tablet_mode'      => 'full',
				'mobile_mode'      => 'reduced',
				'reduced_motion'   => 'static',
				'deps'             => $gsap_st_deps,
				'preset_asset'     => 'zoom',
				'recommended'      => [ '#hero', '.ngi-hero', '.ngi-card' ],
				'options'          => [
					'scrub' => 1.1, 'scaleFrom' => 0.82, 'scaleTo' => 1, 'opacityFrom' => 0.85, 'opacityTo' => 1,
					'perspective' => 1200, 'originX' => '50%', 'originY' => '50%', 'z' => 40,
				],
			],
		];
	}
}
