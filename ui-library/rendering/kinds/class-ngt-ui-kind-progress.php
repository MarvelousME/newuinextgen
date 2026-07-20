<?php
/** @package NGT_UI */
declare(strict_types=1);
if ( ! class_exists( 'NGT_UI_Kind_Progress' ) ) {
	class NGT_UI_Kind_Progress implements NGT_UI_Kind_Renderer_Interface {
		public function kind(): string { return 'progress'; }
		public function render( NGT_UI_Catalog_Render_Context $context ): void {
			$value = (float) ( $context->settings['value'] ?? 0 );
			$value = max( 0, min( 100, $value ) );
			if ( 'animated-circular-progress-bar' === $context->name ) {
				$r      = 54;
				$c      = 2 * M_PI * $r;
				$offset = $c * ( 1 - ( $value / 100 ) );
				echo '<svg class="ngt-ui-ring" viewBox="0 0 120 120" role="img" aria-label="' . esc_attr( (string) $value ) . '%">';
				echo '<circle class="ngt-ui-ring__track" cx="60" cy="60" r="' . esc_attr( (string) $r ) . '"></circle>';
				echo '<circle class="ngt-ui-ring__value" cx="60" cy="60" r="' . esc_attr( (string) $r ) . '" style="stroke-dasharray:' . esc_attr( (string) $c ) . ';stroke-dashoffset:' . esc_attr( (string) $offset ) . '" data-ngt-progress="' . esc_attr( (string) $value ) . '"></circle>';
				echo '<text x="60" y="66" text-anchor="middle" class="ngt-ui-ring__label">' . esc_html( (string) (int) $value ) . '%</text>';
				echo '</svg>';
			} else {
				echo '<div class="ngt-ui-progress" role="progressbar" aria-valuenow="' . esc_attr( (string) $value ) . '" aria-valuemin="0" aria-valuemax="100">';
				echo '<div class="ngt-ui-progress__bar" style="width:' . esc_attr( (string) $value ) . '%" data-ngt-scroll-progress></div>';
				echo '</div>';
			}
		}
	}
}
