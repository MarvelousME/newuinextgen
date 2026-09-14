<?php
/**
 * Home 3D Preview — WP page seed + curated NGT3D homepage rule map.
 *
 * UX policy: primary / secondary / micro / breathing room.
 * Does not alter the live front-page rules.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Home_3d_Page {

	public const SLUG      = 'home-3d';
	public const SEED_FLAG = 'ngt_3d_home_3d_seed_v1';

	public static function boot(): void {
		add_action( 'init', [ self::class, 'maybe_seed' ], 31 );
		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_banner_css' ], 26 );
		add_filter( 'body_class', [ self::class, 'body_class' ] );
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( array $classes ): array {
		if ( is_page( self::SLUG ) ) {
			$classes[] = 'ngt-home-3d-preview';
			$classes[] = 'bi-kinetic-home';
		}
		return $classes;
	}

	public static function enqueue_banner_css(): void {
		if ( ! is_page( self::SLUG ) ) {
			return;
		}
		$css = '
.ngt-home-3d-preview__banner{position:sticky;top:0;z-index:60;background:linear-gradient(90deg,#092746,#1a5594);color:#e8f3ff;border-bottom:1px solid rgba(40,199,247,.35)}
.ngt-home-3d-preview__banner .ngt-container,.ngt-home-3d-preview__banner>div{display:flex;flex-wrap:wrap;gap:.75rem 1.25rem;align-items:center;justify-content:center;padding:.65rem 1rem;font-size:.875rem}
.ngt-home-3d-preview__banner a{color:#28c7f7;font-weight:600;text-decoration:none}
.ngt-home-3d-preview__banner a:hover,.ngt-home-3d-preview__banner a:focus-visible{text-decoration:underline;outline:2px solid #28c7f7;outline-offset:2px}
.ngt-home-3d-preview .ngi-wrap--filmstrip{width:min(1180px,100%);margin:0 auto;padding:0}
.ngt-home-3d-preview .ngi-home-3d-filmstrip{padding:0;overflow:visible}
.ngt-home-3d-preview .ngi-home-3d-filmstrip .ngtfs{border-radius:0;margin:0}
@media (prefers-reduced-motion:reduce){.ngt-home-3d-preview__banner{position:static}}
';
		wp_register_style( 'ngt3d-home-3d-banner', false, [], NGT3D_VERSION );
		wp_enqueue_style( 'ngt3d-home-3d-banner' );
		wp_add_inline_style( 'ngt3d-home-3d-banner', $css );
	}

	public static function maybe_seed(): void {
		if ( get_option( self::SEED_FLAG ) === '2026-09-12-motion-text' ) {
			return;
		}
		if ( ! class_exists( 'NGT3D_Rule_Repository' ) || ! class_exists( 'NGT3D_Schema' ) ) {
			return;
		}
		$page_id = self::ensure_page();
		if ( $page_id > 0 ) {
			self::seed_rules( $page_id );
			update_option( self::SEED_FLAG, '2026-09-12-motion-text', false );
		}
	}

	public static function ensure_page(): int {
		$existing = get_page_by_path( self::SLUG );
		if ( $existing instanceof WP_Post ) {
			$page_id = (int) $existing->ID;
			update_post_meta( $page_id, '_wp_page_template', 'page-home-3d.php' );
			return $page_id;
		}

		$page_id = wp_insert_post(
			[
				'post_title'   => 'Home 3D Preview',
				'post_name'    => self::SLUG,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			],
			true
		);

		if ( is_wp_error( $page_id ) || ! $page_id ) {
			return 0;
		}

		update_post_meta( (int) $page_id, '_wp_page_template', 'page-home-3d.php' );
		return (int) $page_id;
	}

	/**
	 * Curated map: primary / secondary / micro — not every effect.
	 *
	 * @return array<int, array{selector:string,animation:string,options:array,role:string,tablet?:string,mobile?:string}>
	 */
	public static function curated_map(): array {
		return [
			[
				'selector'  => '#hero',
				'animation' => 'zoom',
				'role'      => 'primary',
				'options'   => [ 'scrub' => 1.05, 'scaleFrom' => 0.88, 'scaleTo' => 1, 'opacityFrom' => 0.92, 'opacityTo' => 1, 'perspective' => 1200, 'intensity' => 'subtle' ],
				'mobile'    => 'reduced',
			],
			[
				'selector'  => '#trust',
				'animation' => 'stagger-depth',
				'role'      => 'micro',
				'options'   => [ 'stagger' => 0.08, 'rotateX' => 6, 'y' => 18 ],
				'mobile'    => 'full',
			],
			[
				'selector'  => '#subjects',
				'animation' => 'scale-depth',
				'role'      => 'secondary',
				'options'   => [ 'scrub' => 0.85, 'scaleFrom' => 0.97, 'scaleTo' => 1, 'intensity' => 'subtle' ],
				'mobile'    => 'reduced',
			],
			[
				'selector'  => '#journey',
				'animation' => 'perspective-reveal',
				'role'      => 'micro',
				'options'   => [ 'scrub' => 0.75, 'rotateX' => 5, 'perspective' => 1200, 'opacityFrom' => 0.82 ],
				'mobile'    => 'reduced',
			],
			[
				'selector'  => '#tutoring-story',
				'animation' => 'doublescroll',
				'role'      => 'secondary',
				'options'   => [ 'scrub' => 1.15, 'pin' => true, 'distance' => 220 ],
				'mobile'    => 'disabled',
				'tablet'    => 'reduced',
			],
			[
				'selector'  => '#platform-highlights',
				'animation' => 'dark-veles',
				'role'      => 'secondary',
				'options'   => [ 'scrub' => 0.85, 'stagger' => 0.1, 'scaleFrom' => 0.96, 'y' => 28, 'pin' => false ],
				'mobile'    => 'disabled',
			],
			[
				'selector'  => '#video-story',
				'animation' => '4kvideo',
				'role'      => 'primary',
				'options'   => [ 'scrub' => 1, 'pin' => true, 'scaleFrom' => 1.08, 'scaleTo' => 1 ],
				'mobile'    => 'disabled',
				'tablet'    => 'reduced',
			],
			[
				'selector'  => '#image-hover',
				'animation' => 'wiper',
				'role'      => 'secondary',
				'options'   => [ 'scrub' => 0.85, 'direction' => 'left' ],
				'mobile'    => 'reduced',
			],
			[
				'selector'  => '#cursor-reveal',
				'animation' => 'scroll-mask',
				'role'      => 'secondary',
				'options'   => [ 'scrub' => 1, 'stagger' => 0.1 ],
				'mobile'    => 'disabled',
			],
			[
				'selector'  => '#tutors',
				'animation' => 'perspective-reveal',
				'role'      => 'micro',
				'options'   => [ 'scrub' => 0.7, 'rotateX' => 4, 'perspective' => 1200, 'opacityFrom' => 0.9 ],
				'mobile'    => 'full',
			],
			[
				'selector'  => '#pricing',
				'animation' => 'scale-depth',
				'role'      => 'micro',
				'options'   => [ 'scrub' => 0.9, 'scaleFrom' => 0.94, 'scaleTo' => 1 ],
				'mobile'    => 'full',
			],
			[
				'selector'  => '#reviews',
				'animation' => 'horizontal-scroll',
				'role'      => 'secondary',
				'options'   => [ 'scrub' => 1, 'pin' => true ],
				'mobile'    => 'disabled',
				'tablet'    => 'disabled',
			],
			[
				'selector'  => '#cta',
				'animation' => 'parallax-slow,depth-scroll',
				'role'      => 'micro',
				'options'   => [ 'scrub' => 1.2, 'y' => -48, 'rate' => 0.22 ],
				'mobile'    => 'disabled',
			],
			// Text / entrance / interaction on child targets (won't fight section 3D).
			[
				'selector'  => '#hero .ngi-title',
				'animation' => 'gsapify-text-fade-up-words',
				'role'      => 'text',
				'options'   => [ 'duration' => 0.85, 'stagger' => 0.045, 'ease' => 'power3.out', 'start' => 'top 80%' ],
				'mobile'    => 'reduced',
			],
			[
				'selector'  => '#hero .ngi-lead',
				'animation' => 'fade-up',
				'role'      => 'text',
				'options'   => [ 'duration' => 0.7, 'distance' => 28, 'delay' => 0.12 ],
				'mobile'    => 'full',
			],
			[
				'selector'  => '#hero .ngi-btn-primary',
				'animation' => 'gsapify-magnetic-button',
				'role'      => 'interactive',
				'options'   => [ 'strength' => 0.32, 'radius' => 110 ],
				'mobile'    => 'disabled',
				'tablet'    => 'disabled',
			],
			[
				'selector'  => '#trust .ngi-heading',
				'animation' => 'gsapify-text-line-by-line',
				'role'      => 'text',
				'options'   => [ 'duration' => 0.7, 'stagger' => 0.05 ],
				'mobile'    => 'reduced',
			],
			[
				'selector'  => '#trust .ngi-card-grid',
				'animation' => 'gsapify-card-slide-in-stagger',
				'role'      => 'micro',
				'options'   => [ 'stagger' => 0.1, 'direction' => 'up', 'distance' => 36 ],
				'mobile'    => 'full',
			],
			[
				'selector'  => '#subjects .ngi-heading',
				'animation' => 'text-words-rise',
				'role'      => 'text',
				'options'   => [ 'duration' => 0.65, 'stagger' => 0.04 ],
				'mobile'    => 'reduced',
			],
			[
				'selector'  => '#journey .ngi-heading',
				'animation' => 'gsapify-text-word-build',
				'role'      => 'text',
				'options'   => [ 'duration' => 0.7, 'stagger' => 0.05 ],
				'mobile'    => 'reduced',
			],
			[
				'selector'  => '#journey .ngi-steps',
				'animation' => 'stagger-children',
				'role'      => 'micro',
				'options'   => [ 'stagger' => 0.09, 'direction' => 'up', 'childSelector' => '.ngi-step' ],
				'mobile'    => 'full',
			],
			[
				'selector'  => '#platform-highlights .ngi-heading',
				'animation' => 'gsapify-kinetic-split-lines',
				'role'      => 'text',
				'options'   => [ 'duration' => 0.75, 'stagger' => 0.04 ],
				'mobile'    => 'reduced',
			],
			[
				'selector'  => '#tutors .ngi-heading',
				'animation' => 'gsapify-text-slide-left',
				'role'      => 'text',
				'options'   => [ 'duration' => 0.7, 'stagger' => 0.04 ],
				'mobile'    => 'reduced',
			],
			[
				'selector'  => '#pricing .ngi-heading',
				'animation' => 'gsapify-text-blur-in',
				'role'      => 'text',
				'options'   => [ 'duration' => 0.8, 'stagger' => 0.03 ],
				'mobile'    => 'disabled',
			],
			[
				'selector'  => '#video-story #ngi-story-heading, #video-story h2',
				'animation' => 'gsapify-text-staggered-letters',
				'role'      => 'text',
				'options'   => [ 'duration' => 0.55, 'stagger' => 0.025 ],
				'mobile'    => 'disabled',
			],
			[
				'selector'  => '#cta h2',
				'animation' => 'gsapify-text-fade-up-words',
				'role'      => 'text',
				'options'   => [ 'duration' => 0.8, 'stagger' => 0.04 ],
				'mobile'    => 'reduced',
			],
			[
				'selector'  => '#cta .ngi-btn-primary',
				'animation' => 'magnetic-button',
				'role'      => 'interactive',
				'options'   => [ 'strength' => 0.35, 'radius' => 120 ],
				'mobile'    => 'disabled',
				'tablet'    => 'disabled',
			],
			[
				'selector'  => '#cta .ngi-cta-panel',
				'animation' => 'fade-up',
				'role'      => 'micro',
				'options'   => [ 'duration' => 0.75, 'distance' => 32 ],
				'mobile'    => 'full',
			],
		];
	}

	public static function seed_rules( int $page_id ): void {
		global $wpdb;
		$table = NGT3D_Schema::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET enabled = 0 WHERE page_slug = %s OR page_id = %d",
				self::SLUG,
				$page_id
			)
		);

		$order = 10;
		foreach ( self::curated_map() as $row ) {
			NGT3D_Rule_Repository::create(
				[
					'page_id'           => $page_id,
					'page_slug'         => self::SLUG,
					'target_selector'   => $row['selector'],
					'target_type'       => 'selector',
					'animation_names'   => $row['animation'],
					'style_classes'     => 'ngt-3d-home-target ngt-3d-role-' . sanitize_key( $row['role'] ),
					'animation_options' => $row['options'],
					'sort_order'        => $order,
					'enabled'           => 1,
					'desktop_mode'      => 'full',
					'tablet_mode'       => $row['tablet'] ?? 'reduced',
					'mobile_mode'       => $row['mobile'] ?? 'reduced',
					'created_by'        => 0,
					'updated_by'        => 0,
				]
			);
			$order += 10;
		}

		NGT3D_Rule_Repository::flush_all_caches();
	}
}
