<?php
/**
 * Elementor widget adapter — canonical NGT_UI_Renderer only.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Elementor' ) ) {
	/**
	 * Boots Elementor category + widget when Elementor is active.
	 */
	class NGT_UI_Elementor {

		public static function init(): void {
			add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
			add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
		}

		/**
		 * @param \Elementor\Elements_Manager $elements_manager Manager.
		 */
		public static function register_category( $elements_manager ): void {
			if ( ! is_object( $elements_manager ) || ! method_exists( $elements_manager, 'add_category' ) ) {
				return;
			}
			$elements_manager->add_category(
				'ngt-ui',
				array(
					'title' => __( 'NextGen UI Library', 'ngt-ui' ),
					'icon'  => 'fa fa-plug',
				)
			);
		}

		/**
		 * @param \Elementor\Widgets_Manager $widgets_manager Manager.
		 */
		public static function register_widgets( $widgets_manager ): void {
			if ( ! class_exists( '\Elementor\Widget_Base' ) || ! is_object( $widgets_manager ) ) {
				return;
			}
			$dir = defined( 'NGT_UI_LIBRARY_DIR' ) ? NGT_UI_LIBRARY_DIR : dirname( __DIR__, 2 );
			require_once trailingslashit( $dir ) . 'integrations/elementor/class-ngt-ui-elementor-widget.php';
			if ( class_exists( 'NGT_UI_Elementor_Widget' ) && method_exists( $widgets_manager, 'register' ) ) {
				$widgets_manager->register( new NGT_UI_Elementor_Widget() );
			}
		}
	}
}
