<?php
/**
 * Base Elementor widget for NG UI Library components.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared render logic for data-backed UI components.
 */
abstract class NG_UI_Elementor_Widget_Base extends \Elementor\Widget_Base {

	/**
	 * Component registry slug.
	 *
	 * @return string
	 */
	abstract protected function component_slug();

	/**
	 * @return string[]
	 */
	public function get_categories() {
		return [ 'nextgen-tutors' ];
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
	}

	/**
	 * Register shared + component controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', [ 'label' => __( 'Data', 'beyondinfinity' ) ] );
		$this->register_component_controls();
		$this->end_controls_section();
	}

	/**
	 * Override in child widgets for extra controls.
	 */
	protected function register_component_controls() {
		$this->add_control(
			'page_key',
			[
				'label'   => __( 'CMS page key', 'beyondinfinity' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'home',
			]
		);
		$this->add_control(
			'limit',
			[
				'label'   => __( 'Item limit', 'beyondinfinity' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 6,
				'min'     => 1,
				'max'     => 24,
			]
		);
	}

	/**
	 * Render via companion shortcode → theme partial.
	 */
	protected function render() {
		$s = $this->get_settings_for_display();
		$atts = [
			'slug'     => $this->component_slug(),
			'page_key' => $s['page_key'] ?? '',
			'limit'    => (int) ( $s['limit'] ?? 6 ),
		];
		if ( ! empty( $s['subject'] ) ) {
			$atts['subject'] = $s['subject'];
		}
		$parts = [];
		foreach ( $atts as $key => $val ) {
			if ( '' === (string) $val ) {
				continue;
			}
			$parts[] = sprintf( '%s="%s"', $key, esc_attr( (string) $val ) );
		}
		echo do_shortcode( '[ng_ui_component ' . implode( ' ', $parts ) . ']' );
	}
}

/**
 * Hero — CMS-driven headline block.
 */
class NG_UI_Elementor_Hero_Widget extends NG_UI_Elementor_Widget_Base {

	/**
	 * @return string
	 */
	public function get_name() {
		return 'ng_ui_hero';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'NG Hero', 'beyondinfinity' );
	}

	/**
	 * @return string
	 */
	protected function component_slug() {
		return 'hero';
	}

	/**
	 * Hero defaults to home CMS sections.
	 */
	protected function register_component_controls() {
		$this->add_control(
			'page_key',
			[
				'label'   => __( 'CMS page key', 'beyondinfinity' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'home',
			]
		);
	}
}

/**
 * Tutor card grid — tutors CPT / marketplace provider.
 */
class NG_UI_Elementor_Tutor_Card_Widget extends NG_UI_Elementor_Widget_Base {

	/**
	 * @return string
	 */
	public function get_name() {
		return 'ng_ui_tutor_card';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'NG Tutor Cards', 'beyondinfinity' );
	}

	/**
	 * @return string
	 */
	protected function component_slug() {
		return 'tutor-card';
	}

	/**
	 * Tutor-specific filters.
	 */
	protected function register_component_controls() {
		parent::register_component_controls();
		$this->add_control(
			'subject',
			[
				'label'       => __( 'Subject filter (slug)', 'beyondinfinity' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'mathematics',
			]
		);
	}
}

/**
 * Pricing cards — WooCommerce or CMS tiers.
 */
class NG_UI_Elementor_Pricing_Card_Widget extends NG_UI_Elementor_Widget_Base {

	/**
	 * @return string
	 */
	public function get_name() {
		return 'ng_ui_pricing_card';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'NG Pricing Cards', 'beyondinfinity' );
	}

	/**
	 * @return string
	 */
	protected function component_slug() {
		return 'pricing-card';
	}

	/**
	 * Pricing defaults to pricing page CMS.
	 */
	protected function register_component_controls() {
		$this->add_control(
			'page_key',
			[
				'label'   => __( 'CMS page key', 'beyondinfinity' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'pricing',
			]
		);
		$this->add_control(
			'limit',
			[
				'label'   => __( 'Tier limit', 'beyondinfinity' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 3,
				'min'     => 1,
				'max'     => 6,
			]
		);
	}
}
