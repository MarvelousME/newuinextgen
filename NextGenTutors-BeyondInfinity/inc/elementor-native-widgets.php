<?php
/**
 * Elementor widgets for Elementor-native pages.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NextGen shortcode widget with spacing / typography style controls.
 */
class BI_Elementor_Shortcode_Widget extends \Elementor\Widget_Base {

	/**
	 * @return string
	 */
	public function get_name() {
		return 'bi_ngc_shortcode';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'NextGen Shortcode', 'beyondinfinity' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-shortcode';
	}

	/**
	 * @return string[]
	 */
	public function get_categories() {
		return [ 'nextgen-tutors' ];
	}

	/**
	 * @return string[]
	 */
	public function get_keywords() {
		return [ 'nextgen', 'shortcode', 'ngc', 'form', 'dashboard' ];
	}

	/**
	 * Register controls.
	 */
	protected function register_controls() {
		$presets = [
			''                           => __( 'Custom…', 'beyondinfinity' ),
			'[ngc_tutor_marketplace]'    => __( 'Tutor marketplace', 'beyondinfinity' ),
			'[ngc_find_tutor_form]'      => __( 'Find a tutor form', 'beyondinfinity' ),
			'[ngc_become_tutor_form]'    => __( 'Become a tutor form', 'beyondinfinity' ),
			'[ngc_contact_support_form]' => __( 'Contact / support form', 'beyondinfinity' ),
			'[ngc_login_form]'           => __( 'Login form', 'beyondinfinity' ),
			'[ngc_parent_dashboard]'     => __( 'Parent dashboard', 'beyondinfinity' ),
			'[ngc_student_dashboard]'    => __( 'Student dashboard', 'beyondinfinity' ),
			'[ngc_tutor_dashboard]'      => __( 'Tutor dashboard', 'beyondinfinity' ),
			'[ngc_admin_dashboard]'      => __( 'Admin dashboard', 'beyondinfinity' ),
			'[ngc_parent_checkout]'      => __( 'Parent checkout', 'beyondinfinity' ),
		];

		$this->start_controls_section( 'content', [ 'label' => __( 'Shortcode', 'beyondinfinity' ) ] );
		$this->add_control(
			'preset',
			[
				'label'   => __( 'Preset', 'beyondinfinity' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $presets,
				'default' => '',
			]
		);
		$this->add_control(
			'shortcode',
			[
				'label'       => __( 'Shortcode', 'beyondinfinity' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'placeholder' => '[ngc_login_form]',
				'condition'   => [ 'preset' => '' ],
			]
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'style_box',
			[
				'label' => __( 'Container', 'beyondinfinity' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'padding',
			[
				'label'      => __( 'Padding', 'beyondinfinity' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bi-el-shortcode' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'margin',
			[
				'label'      => __( 'Margin', 'beyondinfinity' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bi-el-shortcode' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'bg',
			[
				'label'     => __( 'Background', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bi-el-shortcode' => 'background-color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .bi-el-shortcode',
			]
		);
		$this->add_control(
			'text_color',
			[
				'label'     => __( 'Text color', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bi-el-shortcode' => 'color: {{VALUE}};',
				],
			]
		);
		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$s = $this->get_settings_for_display();
		$shortcode = ! empty( $s['preset'] ) ? $s['preset'] : ( $s['shortcode'] ?? '' );
		$shortcode = trim( (string) $shortcode );
		if ( '' === $shortcode ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="bi-el-shortcode"><em>' . esc_html__( 'Select a NextGen shortcode preset.', 'beyondinfinity' ) . '</em></div>';
			}
			return;
		}
		echo '<div class="bi-el-shortcode">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode HTML is escaped by handlers.
		echo do_shortcode( $shortcode );
		echo '</div>';
	}
}

/**
 * Theme page body widget — wraps PHP defaults so Elementor can place/style them.
 */
class BI_Elementor_Theme_Body_Widget extends \Elementor\Widget_Base {

	/**
	 * @return string
	 */
	public function get_name() {
		return 'bi_theme_page_body';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'Theme Page Body', 'beyondinfinity' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-site-title';
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
		$options = [ '' => __( 'Current page', 'beyondinfinity' ) ];
		if ( function_exists( 'bi_pages_registry' ) ) {
			foreach ( array_keys( bi_pages_registry() ) as $slug ) {
				$options[ $slug ] = $slug;
			}
		}

		$this->start_controls_section( 'content', [ 'label' => __( 'Content', 'beyondinfinity' ) ] );
		$this->add_control(
			'slug',
			[
				'label'   => __( 'Page slug', 'beyondinfinity' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $options,
				'default' => '',
			]
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'style_box',
			[
				'label' => __( 'Container', 'beyondinfinity' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'padding',
			[
				'label'      => __( 'Padding', 'beyondinfinity' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bi-el-theme-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'bg',
			[
				'label'     => __( 'Background', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bi-el-theme-body' => 'background-color: {{VALUE}};',
				],
			]
		);
		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$s    = $this->get_settings_for_display();
		$slug = sanitize_title( (string) ( $s['slug'] ?? '' ) );
		$sc   = $slug ? '[bi_theme_page_body slug="' . esc_attr( $slug ) . '"]' : '[bi_theme_page_body]';
		echo '<div class="bi-el-theme-body">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo do_shortcode( $sc );
		echo '</div>';
	}
}
