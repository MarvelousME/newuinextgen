<?php
/** @package NGT_UI */
declare(strict_types=1);
if ( ! class_exists( 'NGT_UI_Kind_Device' ) ) {
	class NGT_UI_Kind_Device implements NGT_UI_Kind_Renderer_Interface {
		public function kind(): string { return 'device'; }
		public function render( NGT_UI_Catalog_Render_Context $context ): void {
			$src = (string) ( $context->settings['src'] ?? '' );
			echo '<div class="ngt-ui-device ngt-ui-device--' . esc_attr( $context->name ) . '">';
			echo '<div class="ngt-ui-device__chrome" aria-hidden="true"></div>';
			echo '<div class="ngt-ui-device__screen">';
			if ( $src ) {
				echo '<img src="' . esc_url( $src ) . '" alt="" loading="lazy" />';
			} elseif ( $context->content ) {
				echo wp_kses_post( $context->content );
			} else {
				echo '<div class="ngt-ui-device__placeholder">' . esc_html( $context->label ) . '</div>';
			}
			echo '</div></div>';
		}
	}
}
