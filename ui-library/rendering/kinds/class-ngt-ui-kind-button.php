<?php
/**
 * Button kind renderer.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Kind_Button' ) ) {
	class NGT_UI_Kind_Button implements NGT_UI_Kind_Renderer_Interface {

		public function kind(): string {
			return 'button';
		}

		public function render( NGT_UI_Catalog_Render_Context $context ): void {
			$label = (string) ( $context->settings['label'] ?? 'Get started' );
			$href  = (string) ( $context->settings['href'] ?? '#' );
			echo '<a class="ngt-ui-btn ngt-ui-btn--' . esc_attr( $context->name ) . '" href="' . esc_url( $href ) . '">';
			echo '<span class="ngt-ui-btn__label">' . esc_html( $label ) . '</span>';
			echo '<span class="ngt-ui-btn__fx" aria-hidden="true"></span>';
			echo '</a>';
		}
	}
}
