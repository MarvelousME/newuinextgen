<?php
/**
 * Elementor widget: NG UI Component.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data-backed component renderer for Elementor.
 */
class NG_UI_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * @return string
	 */
	public function get_name() {
		return 'ng_ui_component';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'NG UI Component', 'beyondinfinity' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
	}

	/**
	 * @return string[]
	 */
	public function get_categories() {
		return [ 'nextgen-tutors' ];
	}

	/**
	 * Register controls.
	 */
	protected function register_controls() {
		$options = [ '' => __( 'Select…', 'beyondinfinity' ) ];
		if ( class_exists( 'NGC_UI_Component_Registry' ) ) {
			foreach ( NGC_UI_Component_Registry::definitions() as $slug => $def ) {
				$options[ $slug ] = $def['label'] ?? $slug;
			}
		}

		$this->start_controls_section( 'content', [ 'label' => __( 'Component', 'beyondinfinity' ) ] );
		$this->add_control(
			'slug',
			[
				'label'   => __( 'Component slug', 'beyondinfinity' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $options,
			]
		);
		$this->add_control(
			'page_key',
			[
				'label' => __( 'Page key', 'beyondinfinity' ),
				'type'  => \Elementor\Controls_Manager::TEXT,
			]
		);
		$this->add_control(
			'limit',
			[
				'label'   => __( 'Limit', 'beyondinfinity' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 6,
			]
		);
		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 */
	protected function render() {
		$s = $this->get_settings_for_display();
		if ( empty( $s['slug'] ) ) {
			return;
		}
		echo do_shortcode(
			sprintf(
				'[ng_ui_component slug="%s" page_key="%s" limit="%d"]',
				esc_attr( $s['slug'] ),
				esc_attr( $s['page_key'] ?? '' ),
				(int) ( $s['limit'] ?? 6 )
			)
		);
	}
}
