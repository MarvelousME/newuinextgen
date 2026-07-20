<?php
/**
 * Pattern kind renderer.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Kind_Pattern' ) ) {
	class NGT_UI_Kind_Pattern implements NGT_UI_Kind_Renderer_Interface {

		public function kind(): string {
			return 'pattern';
		}

		public function render( NGT_UI_Catalog_Render_Context $context ): void {
			echo '<div class="ngt-ui-pattern ngt-ui-pattern--' . esc_attr( $context->name ) . '" aria-hidden="true">';
			if ( 'meteors' === $context->name ) {
				for ( $i = 0; $i < 12; $i++ ) {
					echo '<span class="ngt-ui-meteor" style="--delay:' . esc_attr( (string) ( $i * 0.35 ) ) . 's;--x:' . esc_attr( (string) ( ( $i * 17 ) % 100 ) ) . '%"></span>';
				}
			} elseif ( 'ripple' === $context->name ) {
				echo '<span></span><span></span><span></span>';
			} elseif ( 'flickering-grid' === $context->name || 'interactive-grid-pattern' === $context->name ) {
				echo '<div class="ngt-ui-grid-cells" data-ngt-grid></div>';
			}
			echo '</div>';
			if ( $context->content || $context->text ) {
				echo '<div class="ngt-ui-pattern__content">' . ( $context->content ? wp_kses_post( $context->content ) : esc_html( $context->text ) ) . '</div>';
			}
		}
	}
}
