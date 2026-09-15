<?php
/**
 * Visual widget inventory for the NextGen Tutors Elementor category.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inventory.
 */
class BI_EL_Inventory {

	/**
	 * All design-system widgets.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function all() {
		return [
			'hero'                 => [ 'title' => __( 'NextGen Hero', 'beyondinfinity' ), 'icon' => 'eicon-banner', 'keywords' => [ 'hero', 'kinetic' ], 'data' => 'page_content' ],
			'find-tutor'           => [ 'title' => __( 'Find Tutor', 'beyondinfinity' ), 'icon' => 'eicon-search', 'keywords' => [ 'search', 'match' ], 'data' => 'marketplace' ],
			'tutor-grid'           => [ 'title' => __( 'Tutor Grid', 'beyondinfinity' ), 'icon' => 'eicon-posts-grid', 'keywords' => [ 'tutors', 'directory' ], 'data' => 'tutor' ],
			'tutor-card'           => [ 'title' => __( 'Tutor Card', 'beyondinfinity' ), 'icon' => 'eicon-person', 'keywords' => [ 'tutor', 'card' ], 'data' => 'tutor' ],
			'subject-grid'         => [ 'title' => __( 'Subject Grid', 'beyondinfinity' ), 'icon' => 'eicon-gallery-grid', 'keywords' => [ 'subjects' ], 'data' => 'subject' ],
			'subject-card'         => [ 'title' => __( 'Subject Card', 'beyondinfinity' ), 'icon' => 'eicon-folder', 'keywords' => [ 'subject' ], 'data' => 'subject' ],
			'tutor-filmstrip'      => [ 'title' => __( 'Tutor Filmstrip', 'beyondinfinity' ), 'icon' => 'eicon-slider-push', 'keywords' => [ '3d', 'filmstrip' ], 'data' => 'tutor' ],
			'testimonials'         => [ 'title' => __( 'Testimonials', 'beyondinfinity' ), 'icon' => 'eicon-testimonial', 'keywords' => [ 'reviews' ], 'data' => 'review' ],
			'pricing'              => [ 'title' => __( 'Pricing', 'beyondinfinity' ), 'icon' => 'eicon-price-table', 'keywords' => [ 'rates' ], 'data' => 'pricing' ],
			'stats'                => [ 'title' => __( 'Stats', 'beyondinfinity' ), 'icon' => 'eicon-counter', 'keywords' => [ 'kpi', 'metrics' ], 'data' => 'analytics' ],
			'faq'                  => [ 'title' => __( 'FAQ', 'beyondinfinity' ), 'icon' => 'eicon-accordion', 'keywords' => [ 'faq' ], 'data' => 'section_cms' ],
			'booking-cta'          => [ 'title' => __( 'Booking CTA', 'beyondinfinity' ), 'icon' => 'eicon-button', 'keywords' => [ 'book', 'cta' ], 'data' => 'booking' ],
			'animated-heading'     => [ 'title' => __( 'Animated Heading', 'beyondinfinity' ), 'icon' => 'eicon-heading', 'keywords' => [ 'heading', 'gsap' ] ],
			'gsap-text-reveal'     => [ 'title' => __( 'GSAP Text Reveal', 'beyondinfinity' ), 'icon' => 'eicon-animation-text', 'keywords' => [ 'text', 'reveal' ] ],
			'3d-tilt-card'         => [ 'title' => __( '3D Tilt Card', 'beyondinfinity' ), 'icon' => 'eicon-info-box', 'keywords' => [ 'tilt', '3d' ] ],
			'3d-scene'             => [ 'title' => __( '3D Scene', 'beyondinfinity' ), 'icon' => 'eicon-preview-medium', 'keywords' => [ 'three', 'scene' ], 'webgl' => true ],
			'scroll-mask'          => [ 'title' => __( 'Scroll Mask', 'beyondinfinity' ), 'icon' => 'eicon-image-mask', 'keywords' => [ 'mask' ] ],
			'zoom-reveal'          => [ 'title' => __( 'Zoom Reveal', 'beyondinfinity' ), 'icon' => 'eicon-zoom-in', 'keywords' => [ 'zoom' ] ],
			'horizontal-scroll'    => [ 'title' => __( 'Horizontal Scroll', 'beyondinfinity' ), 'icon' => 'eicon-h-align-stretch', 'keywords' => [ 'horizontal' ] ],
			'sticky-story'         => [ 'title' => __( 'Sticky Story Section', 'beyondinfinity' ), 'icon' => 'eicon-scroll', 'keywords' => [ 'pin', 'story' ] ],
			'particle-background'  => [ 'title' => __( 'Particle Background', 'beyondinfinity' ), 'icon' => 'eicon-background', 'keywords' => [ 'particles' ], 'webgl' => true ],
			'orb-background'       => [ 'title' => __( 'Orb Background', 'beyondinfinity' ), 'icon' => 'eicon-circle', 'keywords' => [ 'orb' ], 'webgl' => true ],
			'webgl-background'     => [ 'title' => __( 'Interactive WebGL Background', 'beyondinfinity' ), 'icon' => 'eicon-code', 'keywords' => [ 'webgl' ], 'webgl' => true ],
		];
	}

	/**
	 * @param string $id Widget id.
	 * @return array<string, mixed>
	 */
	public static function get( $id ) {
		$all = self::all();
		return $all[ $id ] ?? [];
	}

	/**
	 * Count.
	 */
	public static function count() {
		return count( self::all() );
	}
}
