<?php
/** @package NGT_UI */
declare(strict_types=1);
if ( ! class_exists( 'NGT_UI_Kind_List' ) ) {
	class NGT_UI_Kind_List implements NGT_UI_Kind_Renderer_Interface {
		public function kind(): string { return 'list'; }
		public function render( NGT_UI_Catalog_Render_Context $context ): void {
			echo '<div class="ngt-ui-list ngt-ui-list--' . esc_attr( $context->name ) . '">';
			if ( 'bento-grid' === $context->name ) {
				foreach ( array_slice( $context->items, 0, 6 ) as $i => $item ) {
					echo '<div class="ngt-ui-bento__cell" style="--i:' . esc_attr( (string) $i ) . '"><strong>' . esc_html( $item ) . '</strong></div>';
				}
			} elseif ( 'avatar-circles' === $context->name ) {
				foreach ( array_slice( $context->items, 0, 5 ) as $i => $item ) {
					$initial = strtoupper( substr( $item, 0, 1 ) );
					echo '<span class="ngt-ui-avatar" style="--i:' . esc_attr( (string) $i ) . '" title="' . esc_attr( $item ) . '">' . esc_html( $initial ) . '</span>';
				}
			} elseif ( 'file-tree' === $context->name ) {
				echo '<ul class="ngt-ui-file-tree"><li>ui-library/<ul>';
				foreach ( $context->items as $item ) {
					echo '<li>' . esc_html( $item ) . '</li>';
				}
				echo '</ul></li></ul>';
			} elseif ( 'dock' === $context->name ) {
				echo '<nav class="ngt-ui-dock" data-ngt-dock>';
				foreach ( array_slice( $context->items, 0, 7 ) as $item ) {
					echo '<button type="button" class="ngt-ui-dock__item" aria-label="' . esc_attr( $item ) . '"><span>' . esc_html( substr( $item, 0, 1 ) ) . '</span></button>';
				}
				echo '</nav>';
			} else {
				echo '<ul class="ngt-ui-animated-list" data-ngt-list>';
				foreach ( $context->items as $i => $item ) {
					echo '<li style="--i:' . esc_attr( (string) $i ) . '">' . esc_html( $item ) . '</li>';
				}
				echo '</ul>';
			}
			if ( $context->content ) {
				echo '<div class="ngt-ui-list__extra">' . wp_kses_post( $context->content ) . '</div>';
			}
			echo '</div>';
		}
	}
}
