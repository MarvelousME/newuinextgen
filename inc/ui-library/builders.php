<?php
/**
 * Elementor + WPBakery bridges for UI Library shortcodes.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'elementor/elements/categories_registered', 'ng_ui_register_elementor_category' );
/**
 * Dedicated Elementor panel category.
 *
 * @param \Elementor\Elements_Manager $elements_manager Manager.
 */
function ng_ui_register_elementor_category( $elements_manager ) {
	$elements_manager->add_category(
		'nextgen-tutors',
		[
			'title' => __( 'NextGen Tutors', 'beyondinfinity' ),
			'icon'  => 'fa fa-plug',
		]
	);
}

add_action( 'vc_before_init', 'ng_ui_register_wpbakery' );
/**
 * WPBakery element → [ng_ui_component].
 */
function ng_ui_register_wpbakery() {
	if ( ! function_exists( 'vc_map' ) || ! class_exists( 'NGC_UI_Component_Registry' ) ) {
		return;
	}

	$options = [];
	foreach ( NGC_UI_Component_Registry::definitions() as $slug => $def ) {
		$options[ $def['label'] ?? $slug ] = $slug;
	}

	vc_map(
		[
			'name'        => __( 'NG UI Component', 'beyondinfinity' ),
			'base'        => 'ng_ui_component',
			'category'    => __( 'NextGen Tutors', 'beyondinfinity' ),
			'description' => __( 'Renders a data-backed UI library component.', 'beyondinfinity' ),
			'params'      => [
				[
					'type'       => 'dropdown',
					'heading'    => __( 'Component', 'beyondinfinity' ),
					'param_name' => 'slug',
					'value'      => $options,
				],
				[
					'type'       => 'textfield',
					'heading'    => __( 'Page key (CMS)', 'beyondinfinity' ),
					'param_name' => 'page_key',
				],
				[
					'type'       => 'textfield',
					'heading'    => __( 'Limit', 'beyondinfinity' ),
					'param_name' => 'limit',
					'value'      => '6',
				],
			],
		]
	);
}

add_action( 'elementor/widgets/register', 'ng_ui_register_elementor_widgets' );
/**
 * Register generic + dedicated Elementor widgets.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager Manager.
 */
function ng_ui_register_elementor_widgets( $widgets_manager ) {
	if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
		return;
	}

	require_once BI_DIR . '/inc/ui-library/elementor-widget.php';
	require_once BI_DIR . '/inc/ui-library/elementor-widget-base.php';

	$widgets_manager->register( new NG_UI_Elementor_Widget() );
	$widgets_manager->register( new NG_UI_Elementor_Hero_Widget() );
	$widgets_manager->register( new NG_UI_Elementor_Tutor_Card_Widget() );
	$widgets_manager->register( new NG_UI_Elementor_Pricing_Card_Widget() );
}
