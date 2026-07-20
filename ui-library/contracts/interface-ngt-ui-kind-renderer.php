<?php
/**
 * Kind renderer contract for catalog components.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! interface_exists( 'NGT_UI_Kind_Renderer_Interface' ) ) {
	/**
	 * Renders one catalog kind branch.
	 */
	interface NGT_UI_Kind_Renderer_Interface {

		/**
		 * Kind slug this renderer handles.
		 */
		public function kind(): string;

		/**
		 * Output inner HTML for the component shell.
		 */
		public function render( NGT_UI_Catalog_Render_Context $context ): void;
	}
}
