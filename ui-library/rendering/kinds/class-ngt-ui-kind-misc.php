<?php
/** @package NGT_UI */
declare(strict_types=1);
if ( ! class_exists( 'NGT_UI_Kind_Misc' ) ) {
	class NGT_UI_Kind_Misc implements NGT_UI_Kind_Renderer_Interface {
		public function kind(): string { return 'misc'; }
		public function render( NGT_UI_Catalog_Render_Context $context ): void {
			echo '<div class="ngt-ui-comp__body">' . ( $context->content ? wp_kses_post( $context->content ) : esc_html( $context->text ) ) . '</div>';
		}
	}
}
