<?php
/**
 * Text kind renderer.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Kind_Text' ) ) {
	class NGT_UI_Kind_Text implements NGT_UI_Kind_Renderer_Interface {

		public function kind(): string {
			return 'text';
		}

		public function render( NGT_UI_Catalog_Render_Context $context ): void {
			$display = $context->content ? $context->content : esc_html( $context->text ?: implode( ' · ', $context->items ) );
			echo '<div class="ngt-ui-text ngt-ui-text--' . esc_attr( $context->name ) . '" data-items="' . esc_attr( wp_json_encode( $context->items ) ) . '" data-from="' . esc_attr( (string) $context->settings['from'] ) . '" data-to="' . esc_attr( (string) $context->settings['to'] ) . '" data-value="' . esc_attr( (string) $context->settings['value'] ) . '">';
			if ( in_array( $context->name, array( 'number-ticker' ), true ) ) {
				echo '<span class="ngt-ui-text__value" data-ngt-ticker>' . esc_html( (string) $context->settings['from'] ) . '</span>';
			} elseif ( in_array( $context->name, array( 'typing-animation', 'word-rotate', 'morphing-text', 'hyper-text', 'text-3d-flip', 'text-animate', 'kinetic-text' ), true ) ) {
				echo '<span class="ngt-ui-text__value" data-ngt-words>' . esc_html( $context->items[0] ) . '</span>';
			} elseif ( 'spinning-text' === $context->name ) {
				$chars = preg_split( '//u', $context->text ?: 'SPINNING TEXT · ', -1, PREG_SPLIT_NO_EMPTY ) ?: array();
				echo '<span class="ngt-ui-spin-ring" aria-hidden="true">';
				$total = max( 1, count( $chars ) );
				foreach ( $chars as $i => $ch ) {
					$deg = ( 360 / $total ) * $i;
					echo '<span style="--i:' . esc_attr( (string) $i ) . ';--deg:' . esc_attr( (string) $deg ) . 'deg">' . esc_html( $ch ) . '</span>';
				}
				echo '</span>';
			} elseif ( 'video-text' === $context->name ) {
				echo '<span class="ngt-ui-video-text__fill">' . ( $context->content ? wp_kses_post( $context->content ) : esc_html( $context->text ) ) . '</span>';
			} elseif ( 'highlighter' === $context->name ) {
				echo '<mark class="ngt-ui-highlighter">' . ( $context->content ? wp_kses_post( $context->content ) : esc_html( $context->text ) ) . '</mark>';
			} else {
				echo '<span class="ngt-ui-text__value">' . ( is_string( $display ) && $context->content ? wp_kses_post( $context->content ) : $display ) . '</span>';
			}
			echo '</div>';
		}
	}
}
