<?php
/** @package NGT_UI */
declare(strict_types=1);
if ( ! class_exists( 'NGT_UI_Kind_Interactive' ) ) {
	class NGT_UI_Kind_Interactive implements NGT_UI_Kind_Renderer_Interface {
		public function kind(): string { return 'interactive'; }

		/**
		 * Slugs that may request heavy vendor scripts (GSAP / Three) when enabled.
		 *
		 * @return array<int, string>
		 */
		public static function heavy_vendor_slugs(): array {
			return array( 'globe', 'particles' );
		}

		public function render( NGT_UI_Catalog_Render_Context $context ): void {
			$needs_heavy = in_array( $context->name, self::heavy_vendor_slugs(), true );
			echo '<div class="ngt-ui-interactive ngt-ui-interactive--' . esc_attr( $context->name ) . '" data-ngt-interactive="' . esc_attr( $context->name ) . '"' . ( $needs_heavy ? ' data-ngt-needs-vendor="1"' : '' ) . '>';
			switch ( $context->name ) {
				case 'particles':
				case 'globe':
					echo '<canvas class="ngt-ui-canvas" width="640" height="360" data-ngt-canvas="' . esc_attr( $context->name ) . '"' . ( $needs_heavy ? ' data-ngt-needs-three="1"' : '' ) . '></canvas>';
					break;
				case 'orbiting-circles':
					echo '<div class="ngt-ui-orbit"><div class="ngt-ui-orbit__core">' . esc_html( $context->text ?: 'NGT' ) . '</div>';
					foreach ( array_slice( $context->items, 0, 6 ) as $i => $item ) {
						echo '<span class="ngt-ui-orbit__item" style="--i:' . esc_attr( (string) $i ) . '">' . esc_html( substr( $item, 0, 2 ) ) . '</span>';
					}
					echo '</div>';
					break;
				case 'animated-beam':
					echo '<div class="ngt-ui-beam" data-ngt-beam><span class="ngt-ui-beam__node" data-a></span>';
					echo '<svg class="ngt-ui-beam__svg" viewBox="0 0 200 80" preserveAspectRatio="none"><path d="M10 40 C 70 0, 130 80, 190 40" /></svg>';
					echo '<span class="ngt-ui-beam__node" data-b></span></div>';
					break;
				case 'icon-cloud':
					echo '<div class="ngt-ui-icon-cloud" data-ngt-cloud>';
					foreach ( array_slice( $context->items, 0, 10 ) as $i => $item ) {
						echo '<span style="--i:' . esc_attr( (string) $i ) . '">' . esc_html( substr( $item, 0, 1 ) ) . '</span>';
					}
					echo '</div>';
					break;
				case 'confetti':
					echo '<button type="button" class="ngt-ui-btn" data-ngt-confetti>' . esc_html( $context->settings['label'] ?: 'Celebrate' ) . '</button>';
					echo '<canvas class="ngt-ui-confetti-layer" data-ngt-confetti-canvas width="400" height="240"></canvas>';
					break;
				case 'cool-mode':
					echo '<button type="button" class="ngt-ui-btn" data-ngt-cool>' . esc_html( $context->settings['label'] ?: 'Cool Mode' ) . '</button>';
					break;
				case 'lens':
					echo '<div class="ngt-ui-lens" data-ngt-lens><div class="ngt-ui-lens__target">' . ( $context->content ? wp_kses_post( $context->content ) : esc_html( $context->text ?: 'Hover to magnify' ) ) . '</div>';
					echo '<div class="ngt-ui-lens__glass" aria-hidden="true"></div></div>';
					break;
				case 'pointer':
				case 'smooth-cursor':
					echo '<div class="ngt-ui-cursor-demo" data-ngt-cursor="' . esc_attr( $context->name ) . '">Move pointer here<span class="ngt-ui-cursor-dot" aria-hidden="true"></span></div>';
					break;
				default:
					echo $context->content ? wp_kses_post( $context->content ) : esc_html( $context->text ?: $context->label );
			}
			echo '</div>';
		}
	}
}
