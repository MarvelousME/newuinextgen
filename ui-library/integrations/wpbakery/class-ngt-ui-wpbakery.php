<?php
/**
 * WPBakery (Visual Composer) adapter — canonical renderer.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_WPBakery' ) ) {
	/**
	 * Registers vc_map element when WPBakery is present.
	 */
	class NGT_UI_WPBakery {

		public static function init(): void {
			add_action( 'vc_before_init', array( __CLASS__, 'map' ) );
			add_shortcode( 'ngt_ui_vc', array( __CLASS__, 'render' ) );
		}

		/**
		 * Map element.
		 */
		public static function map(): void {
			if ( ! function_exists( 'vc_map' ) || ! class_exists( 'NGT_UI_Registry' ) ) {
				return;
			}

			$values = array();
			foreach ( NGT_UI_Registry::all() as $slug => $component ) {
				$values[ $component->get_label() . ' (' . $slug . ')' ] = $slug;
			}
			if ( ! $values ) {
				$values['Magic Card'] = 'magic-card';
			}

			vc_map(
				array(
					'name'        => __( 'NGT UI Component', 'ngt-ui' ),
					'base'        => 'ngt_ui_vc',
					'icon'        => 'icon-wpb-application-icon-large',
					'category'    => __( 'NextGen UI', 'ngt-ui' ),
					'description' => __( 'Renders a UI Library component', 'ngt-ui' ),
					'params'      => array(
						array(
							'type'        => 'dropdown',
							'heading'     => __( 'Component', 'ngt-ui' ),
							'param_name'  => 'component',
							'value'       => $values,
							'admin_label' => true,
						),
						array(
							'type'       => 'textfield',
							'heading'    => __( 'Title / text', 'ngt-ui' ),
							'param_name' => 'title',
						),
						array(
							'type'       => 'textfield',
							'heading'    => __( 'Button label', 'ngt-ui' ),
							'param_name' => 'label',
						),
						array(
							'type'        => 'textfield',
							'heading'     => __( 'Items (pipe-separated)', 'ngt-ui' ),
							'param_name'  => 'items',
						),
						array(
							'type'       => 'textarea_html',
							'heading'    => __( 'Content', 'ngt-ui' ),
							'param_name' => 'content',
						),
					),
				)
			);
		}

		/**
		 * @param array<string, mixed>|string $atts    Attributes.
		 * @param string|null                 $content Content.
		 */
		public static function render( $atts = array(), $content = null ): string {
			$atts = shortcode_atts(
				array(
					'component' => 'magic-card',
					'title'     => '',
					'label'     => '',
					'items'     => '',
				),
				is_array( $atts ) ? $atts : array(),
				'ngt_ui_vc'
			);

			if ( ! class_exists( 'NGT_UI_Renderer' ) ) {
				return '';
			}

			return NGT_UI_Renderer::render(
				sanitize_key( (string) $atts['component'] ),
				array(
					'title'   => (string) $atts['title'],
					'text'    => (string) $atts['title'],
					'label'   => (string) $atts['label'],
					'items'   => (string) $atts['items'],
					'content' => $content ? (string) $content : '',
				),
				array( 'editor' => 'wpbakery' )
			);
		}
	}
}
