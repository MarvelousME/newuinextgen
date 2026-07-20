<?php
/** @package NGT_UI */
declare(strict_types=1);
if ( ! class_exists( 'NGT_UI_Kind_Map' ) ) {
	class NGT_UI_Kind_Map implements NGT_UI_Kind_Renderer_Interface {
		public function kind(): string { return 'map'; }
		public function render( NGT_UI_Catalog_Render_Context $context ): void {
			echo '<div class="ngt-ui-map" aria-label="' . esc_attr( $context->text ?: 'Dotted map' ) . '">';
			for ( $r = 0; $r < 8; $r++ ) {
				for ( $c = 0; $c < 16; $c++ ) {
					$on = ( ( $r + $c ) % 3 ) !== 0;
					echo '<span class="' . ( $on ? 'is-on' : '' ) . '"></span>';
				}
			}
			echo '</div>';
		}
	}
}
