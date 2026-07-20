<?php
/** @package NGT_UI */
declare(strict_types=1);
if ( ! class_exists( 'NGT_UI_Kind_Media' ) ) {
	class NGT_UI_Kind_Media implements NGT_UI_Kind_Renderer_Interface {
		public function kind(): string { return 'media'; }
		public function render( NGT_UI_Catalog_Render_Context $context ): void {
			$src = (string) ( $context->settings['src'] ?? '' );
			echo '<div class="ngt-ui-media ngt-ui-media--' . esc_attr( $context->name ) . '">';
			if ( 'code-comparison' === $context->name ) {
				echo '<div class="ngt-ui-code"><pre><code>// before' . "\n" . 'renderReact()</code></pre></div>';
				echo '<div class="ngt-ui-code ngt-ui-code--after"><pre><code>// after' . "\n" . 'ngt_render_ui_component()</code></pre></div>';
			} elseif ( 'terminal' === $context->name ) {
				$lines = NGT_UI_Kind_Parser::parse_items( (string) ( $context->settings['items'] ?? '' ), $context->text ?: 'npm run build|✓ built in 1.2s|Ready on :8900' );
				echo '<div class="ngt-ui-terminal" data-ngt-terminal data-lines="' . esc_attr( wp_json_encode( $lines ) ) . '">';
				echo '<div class="ngt-ui-terminal__bar"><span></span><span></span><span></span></div>';
				echo '<pre class="ngt-ui-terminal__body"><code data-ngt-term-out></code></pre>';
				echo '</div>';
			} elseif ( 'hero-video-dialog' === $context->name ) {
				echo '<button type="button" class="ngt-ui-video-dialog" data-ngt-video-dialog data-src="' . esc_attr( $src ) . '">';
				echo '<span class="ngt-ui-video-dialog__poster">' . esc_html( $context->text ?: 'Play demo' ) . '</span>';
				echo '<span class="ngt-ui-video-dialog__play" aria-hidden="true">▶</span>';
				echo '</button>';
			} elseif ( 'pixel-image' === $context->name ) {
				echo '<div class="ngt-ui-pixel" data-ngt-pixel>';
				if ( $src ) {
					echo '<img src="' . esc_url( $src ) . '" alt="" />';
				} else {
					echo '<div class="ngt-ui-pixel__fallback">' . esc_html( $context->text ?: 'Pixel reveal' ) . '</div>';
				}
				echo '</div>';
			} else {
				echo $context->content ? wp_kses_post( $context->content ) : esc_html( $context->text ?: $context->label );
			}
			echo '</div>';
		}
	}
}
