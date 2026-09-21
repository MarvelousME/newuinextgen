<?php
/**
 * Compile Elementor NextGen Motion controls into NGT3D rules.
 *
 * Does not start a second GSAP engine.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Motion compiler.
 */
class BI_EL_Motion {

	const WIDGET_PREFIX = 'bi_el_';

	/**
	 * NGT3D animation IDs exposed as presets.
	 *
	 * @return array<string, string>
	 */
	public static function preset_choices() {
		return [
			''                   => __( 'None (use transform fields)', 'beyondinfinity' ),
			'depth-hero'         => __( 'Depth Hero', 'beyondinfinity' ),
			'perspective-reveal' => __( 'Perspective Reveal', 'beyondinfinity' ),
			'text-perspective'   => __( 'Text Perspective', 'beyondinfinity' ),
			'text-depth'         => __( 'Text Depth', 'beyondinfinity' ),
			'tilt-3d'            => __( 'Tilt 3D', 'beyondinfinity' ),
			'scroll-mask'        => __( 'Scroll Mask', 'beyondinfinity' ),
			'zoom'               => __( 'Zoom', 'beyondinfinity' ),
			'horizontal-scroll'  => __( 'Horizontal Scroll', 'beyondinfinity' ),
			'pin-section'        => __( 'Pin / Sticky Story', 'beyondinfinity' ),
			'mouse-parallax'     => __( 'Mouse Parallax', 'beyondinfinity' ),
			'stagger-depth'      => __( 'Stagger Depth', 'beyondinfinity' ),
			'stack-3d'           => __( 'Stack 3D', 'beyondinfinity' ),
			'translate-z'        => __( 'Translate Z', 'beyondinfinity' ),
			'rotate-x-scroll'    => __( 'Rotate X Scroll', 'beyondinfinity' ),
			'webgl-distortion'   => __( 'WebGL Distortion', 'beyondinfinity' ),
		];
	}

	/**
	 * Register NextGen Motion Elementor tab + controls on a widget.
	 *
	 * @param \Elementor\Widget_Base $widget Widget.
	 */
	public static function register_controls( $widget ) {
		if ( ! class_exists( '\Elementor\Controls_Manager' ) ) {
			return;
		}

		$cm = \Elementor\Controls_Manager::class;

		if ( method_exists( $cm, 'add_tab' ) ) {
			static $tab = false;
			if ( ! $tab ) {
				$cm::add_tab( 'ngt_motion', __( 'NextGen Motion', 'beyondinfinity' ) );
				$tab = true;
			}
		}

		$widget->start_controls_section(
			'ngt_motion_section',
			[
				'label' => __( 'NextGen Motion', 'beyondinfinity' ),
				'tab'   => defined( 'Elementor\Controls_Manager::TAB_ADVANCED' ) ? \Elementor\Controls_Manager::TAB_ADVANCED : 'advanced',
			]
		);

		$widget->add_control(
			'ngt_motion_enable',
			[
				'label'        => __( 'Enable Motion', 'beyondinfinity' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			]
		);
		$widget->add_control(
			'ngt_motion_preset',
			[
				'label'     => __( 'Motion Preset', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => self::preset_choices(),
				'default'   => 'perspective-reveal',
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_scroll_trigger',
			[
				'label'        => __( 'Scroll Trigger', 'beyondinfinity' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_start',
			[
				'label'     => __( 'Start', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => 'top 80%',
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_end',
			[
				'label'     => __( 'End', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => 'bottom top',
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_scrub',
			[
				'label'     => __( 'Scrub', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 5,
				'step'      => 0.1,
				'default'   => 1,
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_perspective',
			[
				'label'     => __( 'Perspective', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 400, 'max' => 3000 ] ],
				'default'   => [ 'size' => 1200 ],
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_depth',
			[
				'label'     => __( 'Depth', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 800,
				'default'   => 80,
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_x',
			[
				'label'     => __( 'Translate X', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 0,
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_y',
			[
				'label'     => __( 'Translate Y', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 48,
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_z',
			[
				'label'     => __( 'Translate Z', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 0,
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_rotate_x',
			[
				'label'     => __( 'Rotate X', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 0,
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_rotate_y',
			[
				'label'     => __( 'Rotate Y', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 0,
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_rotate_z',
			[
				'label'     => __( 'Rotate Z', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 0,
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_scale',
			[
				'label'     => __( 'Scale', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 0.1,
				'max'       => 3,
				'step'      => 0.05,
				'default'   => 1,
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_opacity',
			[
				'label'     => __( 'Opacity', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 1,
				'step'      => 0.05,
				'default'   => 0,
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_stagger',
			[
				'label'     => __( 'Stagger', 'beyondinfinity' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 1,
				'step'      => 0.05,
				'default'   => 0.08,
				'condition' => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_mouse',
			[
				'label'        => __( 'Mouse Interaction', 'beyondinfinity' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_reduced_motion',
			[
				'label'        => __( 'Reduced Motion', 'beyondinfinity' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => __( 'Honor prefers-reduced-motion (recommended).', 'beyondinfinity' ),
				'condition'    => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_disable_tablet',
			[
				'label'        => __( 'Disable on Tablet', 'beyondinfinity' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => [ 'ngt_motion_enable' => 'yes' ],
			]
		);
		$widget->add_control(
			'ngt_disable_mobile',
			[
				'label'        => __( 'Disable on Mobile', 'beyondinfinity' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [ 'ngt_motion_enable' => 'yes' ],
			]
		);

		$widget->end_controls_section();
	}

	/**
	 * HTML attributes compiled from widget settings.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $element_id Elementor element id.
	 * @return array<string, string>
	 */
	public static function html_attrs( $settings, $element_id ) {
		if ( empty( $settings['ngt_motion_enable'] ) || 'yes' !== $settings['ngt_motion_enable'] ) {
			return [
				'data-ngt-el-motion' => '0',
			];
		}

		$preset = sanitize_key( (string) ( $settings['ngt_motion_preset'] ?? '' ) );
		$attrs  = [
			'data-ngt-el-motion'     => '1',
			'data-ngt-el-id'         => sanitize_html_class( (string) $element_id ),
			'data-ngt-motion-preset' => $preset,
			'data-ngt-scroll-trigger'=> ( ! empty( $settings['ngt_scroll_trigger'] ) && 'yes' === $settings['ngt_scroll_trigger'] ) ? '1' : '0',
			'data-ngt-start'         => sanitize_text_field( (string) ( $settings['ngt_start'] ?? 'top 80%' ) ),
			'data-ngt-end'           => sanitize_text_field( (string) ( $settings['ngt_end'] ?? 'bottom top' ) ),
			'data-ngt-scrub'         => (string) (float) ( $settings['ngt_scrub'] ?? 1 ),
			'data-ngt-perspective'   => (string) self::slider_size( $settings['ngt_perspective'] ?? [], 1200 ),
			'data-ngt-depth'         => (string) (float) ( $settings['ngt_depth'] ?? 80 ),
			'data-ngt-x'             => (string) (float) ( $settings['ngt_x'] ?? 0 ),
			'data-ngt-y'             => (string) (float) ( $settings['ngt_y'] ?? 48 ),
			'data-ngt-z'             => (string) (float) ( $settings['ngt_z'] ?? 0 ),
			'data-ngt-rotate-x'      => (string) (float) ( $settings['ngt_rotate_x'] ?? 0 ),
			'data-ngt-rotate-y'      => (string) (float) ( $settings['ngt_rotate_y'] ?? 0 ),
			'data-ngt-rotate-z'      => (string) (float) ( $settings['ngt_rotate_z'] ?? 0 ),
			'data-ngt-scale'         => (string) (float) ( $settings['ngt_scale'] ?? 1 ),
			'data-ngt-opacity'       => (string) (float) ( $settings['ngt_opacity'] ?? 0 ),
			'data-ngt-stagger'       => (string) (float) ( $settings['ngt_stagger'] ?? 0.08 ),
			'data-ngt-mouse'         => ( ! empty( $settings['ngt_mouse'] ) && 'yes' === $settings['ngt_mouse'] ) ? '1' : '0',
			'data-ngt-reduced'       => ( empty( $settings['ngt_reduced_motion'] ) || 'yes' === $settings['ngt_reduced_motion'] ) ? '1' : '0',
			'data-ngt-off-tablet'    => ( ! empty( $settings['ngt_disable_tablet'] ) && 'yes' === $settings['ngt_disable_tablet'] ) ? '1' : '0',
			'data-ngt-off-mobile'    => ( ! empty( $settings['ngt_disable_mobile'] ) && 'yes' === $settings['ngt_disable_mobile'] ) ? '1' : '0',
		];

		if ( $preset ) {
			$attrs['data-ngt-3d-target'] = $preset;
		}
		if ( ! empty( $attrs['data-ngt-mouse'] ) && '1' === $attrs['data-ngt-mouse'] ) {
			$attrs['data-bi-tilt'] = '1';
		}

		return $attrs;
	}

	/**
	 * Compile settings into an NGT3D repository-shaped rule.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $element_id Element id.
	 * @return array<string, mixed>|null
	 */
	public static function compile_rule( $settings, $element_id ) {
		if ( empty( $settings['ngt_motion_enable'] ) || 'yes' !== $settings['ngt_motion_enable'] ) {
			return null;
		}

		$preset = sanitize_key( (string) ( $settings['ngt_motion_preset'] ?? '' ) );
		if ( '' === $preset ) {
			$preset = 'translate-z';
		}

		$mouse = ( ! empty( $settings['ngt_mouse'] ) && 'yes' === $settings['ngt_mouse'] );
		$names = $preset;
		if ( $mouse && 'mouse-parallax' !== $preset && 'tilt-3d' !== $preset ) {
			$names .= ',tilt-3d';
		}

		$options = [
			'scrub'        => (float) ( $settings['ngt_scrub'] ?? 1 ),
			'start'        => sanitize_text_field( (string) ( $settings['ngt_start'] ?? 'top 80%' ) ),
			'end'          => sanitize_text_field( (string) ( $settings['ngt_end'] ?? 'bottom top' ) ),
			'perspective'  => (int) self::slider_size( $settings['ngt_perspective'] ?? [], 1200 ),
			'depth'        => (float) ( $settings['ngt_depth'] ?? 80 ),
			'x'            => (float) ( $settings['ngt_x'] ?? 0 ),
			'y'            => (float) ( $settings['ngt_y'] ?? 48 ),
			'z'            => (float) ( $settings['ngt_z'] ?? 0 ),
			'rotateX'      => (float) ( $settings['ngt_rotate_x'] ?? 0 ),
			'rotateY'      => (float) ( $settings['ngt_rotate_y'] ?? 0 ),
			'rotateZ'      => (float) ( $settings['ngt_rotate_z'] ?? 0 ),
			'scaleFrom'    => (float) ( $settings['ngt_scale'] ?? 1 ),
			'opacityFrom'  => (float) ( $settings['ngt_opacity'] ?? 0 ),
			'stagger'      => (float) ( $settings['ngt_stagger'] ?? 0.08 ),
			'source'       => 'elementor-design-system',
		];

		if ( empty( $settings['ngt_scroll_trigger'] ) || 'yes' !== $settings['ngt_scroll_trigger'] ) {
			$options['scrub'] = 0;
		}

		$tablet = ( ! empty( $settings['ngt_disable_tablet'] ) && 'yes' === $settings['ngt_disable_tablet'] ) ? 'disabled' : 'full';
		$mobile = ( ! empty( $settings['ngt_disable_mobile'] ) && 'yes' === $settings['ngt_disable_mobile'] ) ? 'disabled' : 'reduced';

		$id = abs( crc32( (string) $element_id ) );
		if ( $id > 2147483647 ) {
			$id = (int) ( $id % 1000000000 );
		}

		return [
			'id'                => -$id,
			'page_id'           => 0,
			'page_slug'         => '',
			'target_selector'   => '.elementor-element-' . sanitize_html_class( (string) $element_id ) . ' .bi-el',
			'target_type'       => 'selector',
			'animation_names'   => $names,
			'style_classes'     => 'bi-el-motion',
			'animation_options' => $options,
			'sort_order'        => 50,
			'enabled'           => true,
			'desktop_mode'      => 'full',
			'tablet_mode'       => $tablet,
			'mobile_mode'       => $mobile,
		];
	}

	/**
	 * Merge compiled Elementor rules into NGT3D page rules.
	 *
	 * @param array<int, array<string, mixed>> $rules DB rules.
	 * @param array<string, mixed>             $page  Page info.
	 * @return array<int, array<string, mixed>>
	 */
	public static function inject_page_rules( $rules, $page ) {
		$page_id = isset( $page['id'] ) ? (int) $page['id'] : 0;
		$extra   = self::rules_from_elementor_document( $page_id );
		if ( ! $extra ) {
			return is_array( $rules ) ? $rules : [];
		}
		return array_merge( is_array( $rules ) ? $rules : [], $extra );
	}

	/**
	 * Walk Elementor document JSON for bi_el_* widgets with motion enabled.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function rules_from_elementor_document( $post_id ) {
		if ( $post_id <= 0 ) {
			return [];
		}
		$data = get_post_meta( $post_id, '_elementor_data', true );
		if ( is_string( $data ) ) {
			$data = json_decode( $data, true );
		}
		if ( ! is_array( $data ) ) {
			return [];
		}
		$out = [];
		self::walk_elements( $data, $out );
		return $out;
	}

	/**
	 * Recurse Elementor tree.
	 *
	 * @param array $elements Elements.
	 * @param array $out      Collected rules.
	 */
	private static function walk_elements( $elements, &$out ) {
		foreach ( (array) $elements as $el ) {
			if ( ! is_array( $el ) ) {
				continue;
			}
			$type = $el['widgetType'] ?? '';
			if ( is_string( $type ) && 0 === strpos( $type, self::WIDGET_PREFIX ) ) {
				$settings = is_array( $el['settings'] ?? null ) ? $el['settings'] : [];
				$rule     = self::compile_rule( $settings, (string) ( $el['id'] ?? '' ) );
				if ( $rule ) {
					$out[] = $rule;
				}
			}
			if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
				self::walk_elements( $el['elements'], $out );
			}
		}
	}

	/**
	 * Page contains design-system widgets.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function page_has_widgets( $post_id ) {
		if ( $post_id <= 0 ) {
			return false;
		}
		$data = get_post_meta( $post_id, '_elementor_data', true );
		if ( ! $data ) {
			return false;
		}
		$raw = is_string( $data ) ? $data : wp_json_encode( $data );
		return false !== strpos( (string) $raw, '"widgetType":"bi_el_' );
	}

	/**
	 * Slider control size.
	 *
	 * @param mixed $value Value.
	 * @param int   $fallback Fallback.
	 * @return int
	 */
	private static function slider_size( $value, $fallback ) {
		if ( is_array( $value ) && isset( $value['size'] ) ) {
			return (int) $value['size'];
		}
		if ( is_numeric( $value ) ) {
			return (int) $value;
		}
		return (int) $fallback;
	}
}
