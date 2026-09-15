<?php
/**
 * Elementor template library source — NextGen Tutors pages.
 *
 * Templates are library items only. Existing pages are never overwritten.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template definitions + Elementor source.
 */
class BI_EL_Templates {

	/**
	 * Register library source when Elementor is ready.
	 */
	public static function register_source() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! class_exists( '\Elementor\TemplateLibrary\Source_Base' ) ) {
			return;
		}
		require_once BI_EL_DS_DIR . '/class-bi-el-template-source.php';
		$manager = \Elementor\Plugin::$instance->templates_manager ?? null;
		if ( $manager && method_exists( $manager, 'register_source' ) ) {
			$manager->register_source( 'BI_EL_Template_Source' );
		}
	}

	/**
	 * Page templates.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function catalog() {
		return [
			'homepage'       => [ 'title' => __( 'Homepage', 'beyondinfinity' ), 'widgets' => [ 'hero', 'stats', 'subject-grid', 'tutor-filmstrip', 'testimonials', 'pricing', 'faq', 'booking-cta' ] ],
			'find-a-tutor'  => [ 'title' => __( 'Find a Tutor', 'beyondinfinity' ), 'widgets' => [ 'hero', 'find-tutor', 'tutor-grid' ] ],
			'tutor-profile'  => [ 'title' => __( 'Tutor Profile', 'beyondinfinity' ), 'widgets' => [ 'hero', 'tutor-card', 'booking-cta' ] ],
			'subjects'       => [ 'title' => __( 'Subjects', 'beyondinfinity' ), 'widgets' => [ 'hero', 'subject-grid' ] ],
			'pricing'        => [ 'title' => __( 'Pricing', 'beyondinfinity' ), 'widgets' => [ 'hero', 'pricing', 'faq', 'booking-cta' ] ],
			'become-a-tutor' => [ 'title' => __( 'Become a Tutor', 'beyondinfinity' ), 'widgets' => [ 'hero', 'stats', 'faq', 'booking-cta' ] ],
			'about'          => [ 'title' => __( 'About', 'beyondinfinity' ), 'widgets' => [ 'hero', 'stats', 'testimonials', 'sticky-story' ] ],
			'safety'         => [ 'title' => __( 'Safety', 'beyondinfinity' ), 'widgets' => [ 'hero', 'faq' ] ],
			'guarantee'      => [ 'title' => __( 'Guarantee', 'beyondinfinity' ), 'widgets' => [ 'hero', 'faq', 'booking-cta' ] ],
			'contact'        => [ 'title' => __( 'Contact', 'beyondinfinity' ), 'widgets' => [ 'hero', 'booking-cta' ] ],
		];
	}

	/**
	 * Build Elementor document content for a template id.
	 *
	 * @param string $id Template id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function document( $id ) {
		$cat = self::catalog();
		if ( empty( $cat[ $id ]['widgets'] ) ) {
			return [];
		}
		$sections = [];
		foreach ( $cat[ $id ]['widgets'] as $wid ) {
			$sections[] = self::widget_container( $wid );
		}
		return $sections;
	}

	/**
	 * One container wrapping a widget.
	 *
	 * @param string $definition_id Inventory id.
	 * @return array<string, mixed>
	 */
	private static function widget_container( $definition_id ) {
		$el_id    = self::eid( $definition_id );
		$widget_id = self::eid( $definition_id . '-w' );
		$type     = 'bi_el_' . str_replace( '-', '_', $definition_id );
		$settings = [
			'style_preset'       => 'glass',
			'ngt_motion_enable'  => 'yes',
			'ngt_motion_preset'  => 'perspective-reveal',
			'ngt_scroll_trigger' => 'yes',
			'ngt_reduced_motion' => 'yes',
			'ngt_disable_mobile' => 'yes',
		];
		if ( 'hero' === $definition_id ) {
			$settings['show_search'] = 'yes';
			$settings['heading_tag'] = 'h1';
			$settings['ngt_motion_preset'] = 'depth-hero';
		}
		if ( 'gsap-text-reveal' === $definition_id ) {
			$settings['ngt_motion_preset'] = 'text-perspective';
		}
		if ( in_array( $definition_id, [ '3d-scene', 'particle-background', 'orb-background', 'webgl-background' ], true ) ) {
			$settings['webgl_quality'] = 'medium';
			$settings['ngt_motion_preset'] = 'webgl-distortion';
		}
		if ( '3d-tilt-card' === $definition_id ) {
			$settings['ngt_mouse'] = 'yes';
			$settings['ngt_motion_preset'] = 'tilt-3d';
		}
		if ( 'scroll-mask' === $definition_id ) {
			$settings['ngt_motion_preset'] = 'scroll-mask';
		}
		if ( 'zoom-reveal' === $definition_id ) {
			$settings['ngt_motion_preset'] = 'zoom';
		}
		if ( 'horizontal-scroll' === $definition_id ) {
			$settings['ngt_motion_preset'] = 'horizontal-scroll';
		}
		if ( 'sticky-story' === $definition_id ) {
			$settings['ngt_motion_preset'] = 'pin-section';
		}

		return [
			'id'       => $el_id,
			'elType'   => 'container',
			'isInner'  => false,
			'settings' => [
				'flex_direction' => 'column',
				'content_width'  => 'boxed',
			],
			'elements' => [
				[
					'id'         => $widget_id,
					'elType'     => 'widget',
					'widgetType' => $type,
					'settings'   => $settings,
					'elements'   => [],
				],
			],
		];
	}

	/**
	 * Stable 7-char element id.
	 *
	 * @param string $seed Seed.
	 * @return string
	 */
	private static function eid( $seed ) {
		return substr( md5( 'bi-el-' . $seed ), 0, 7 );
	}
}
