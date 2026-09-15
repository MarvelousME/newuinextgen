<?php
/**
 * Shared Elementor widget base — native controls + NextGen Motion + tokens.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base widget.
 */
abstract class BI_EL_Widget_Base extends \Elementor\Widget_Base {

	/**
	 * Registry key (hero, tutor-grid, …).
	 *
	 * @return string
	 */
	abstract protected function definition_id();

	/**
	 * @return string
	 */
	public function get_name() {
		return 'bi_el_' . str_replace( '-', '_', $this->definition_id() );
	}

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
		$def = BI_EL_Inventory::get( $this->definition_id() );
		return $def['icon'] ?? 'eicon-star';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		$def = BI_EL_Inventory::get( $this->definition_id() );
		return $def['title'] ?? $this->definition_id();
	}

	/**
	 * @return string[]
	 */
	public function get_keywords() {
		$def = BI_EL_Inventory::get( $this->definition_id() );
		return $def['keywords'] ?? [ 'nextgen', 'tutors' ];
	}

	/**
	 * @return string[]
	 */
	public function get_style_depends() {
		return [ 'bi-el-ds', 'bi-el-ds-presets' ];
	}

	/**
	 * @return string[]
	 */
	public function get_script_depends() {
		$scripts = [ 'bi-el-ds-motion' ];
		$def     = BI_EL_Inventory::get( $this->definition_id() );
		if ( ! empty( $def['webgl'] ) ) {
			$scripts[] = 'bi-el-ds-webgl';
		}
		return $scripts;
	}

	/**
	 * Controls: Content / Style / Advanced (native) + NextGen Motion.
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
		BI_EL_Motion::register_controls( $this );
	}

	/**
	 * Content tab.
	 */
	protected function register_content_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Content', 'beyondinfinity' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);
		$this->register_widget_content_controls();
		$this->end_controls_section();
	}

	/**
	 * Per-widget content controls.
	 */
	protected function register_widget_content_controls() {
		$this->add_control(
			'heading',
			[
				'label'       => __( 'Heading', 'beyondinfinity' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => '',
			]
		);
		$this->add_control(
			'subheading',
			[
				'label'       => __( 'Subheading', 'beyondinfinity' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'default'     => '',
			]
		);
	}

	/**
	 * Style tab: presets + typography + colors (Elementor native).
	 */
	protected function register_style_controls() {
		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Style', 'beyondinfinity' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'style_preset',
			[
				'label'   => __( 'NextGen preset', 'beyondinfinity' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => BI_EL_Presets::choices(),
				'default' => 'glass',
			]
		);

		$heading_color = [
			'label'     => __( 'Heading color', 'beyondinfinity' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .bi-el__title' => 'color: {{VALUE}};',
			],
		];
		if ( class_exists( '\Elementor\Core\Kits\Documents\Tabs\Global_Colors' ) ) {
			$heading_color['global'] = [ 'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Colors::COLOR_PRIMARY ];
		}
		$this->add_control( 'heading_color', $heading_color );

		$typo = [
			'name'     => 'heading_typo',
			'selector' => '{{WRAPPER}} .bi-el__title',
		];
		if ( class_exists( '\Elementor\Core\Kits\Documents\Tabs\Global_Typography' ) ) {
			$typo['global'] = [ 'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Typography::TYPOGRAPHY_PRIMARY ];
		}
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), $typo );

		$this->add_responsive_control(
			'block_padding',
			[
				'label'      => __( 'Padding', 'beyondinfinity' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bi-el' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'radius',
			[
				'label'      => __( 'Border radius', 'beyondinfinity' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'rem' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 48 ] ],
				'selectors'  => [
					'{{WRAPPER}} .bi-el' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Frontend render.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$preset   = BI_EL_Presets::sanitize( $settings['style_preset'] ?? 'glass' );
		$mode     = 'frontend';
		if ( function_exists( 'bi_el_ds_is_editor_mode' ) && bi_el_ds_is_editor_mode() ) {
			$mode = 'editor';
		} elseif ( function_exists( 'bi_el_ds_is_preview_mode' ) && bi_el_ds_is_preview_mode() ) {
			$mode = 'preview';
		}

		$attrs = [
			'class'              => 'bi-el bi-el--' . sanitize_html_class( $this->definition_id() ) . ' bi-el-preset--' . sanitize_html_class( $preset ),
			'data-ngt-el'        => $this->definition_id(),
			'data-bi-el-preset'  => $preset,
			'data-bi-el-mode'    => $mode,
			'data-reduced-motion'=> ( empty( $settings['ngt_reduced_motion'] ) || 'yes' === $settings['ngt_reduced_motion'] ) ? 'respect' : 'ignore',
		];
		foreach ( BI_EL_Motion::html_attrs( $settings, $this->get_id() ) as $key => $val ) {
			$attrs[ $key ] = $val;
		}

		$this->add_render_attribute( '_wrapper_inner', $attrs );

		echo '<div ' . $this->get_render_attribute_string( '_wrapper_inner' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		BI_EL_Renders::render( $this->definition_id(), $settings, [
			'mode'       => $mode,
			'element_id' => $this->get_id(),
			'widget'     => $this,
		] );
		echo '</div>';
	}

	/**
	 * Editor JS template — empty so Elementor uses PHP render (AJAX).
	 */
	protected function content_template() {}
}
