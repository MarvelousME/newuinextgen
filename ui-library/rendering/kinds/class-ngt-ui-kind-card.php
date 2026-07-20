<?php
/** @package NGT_UI */
declare(strict_types=1);
if ( ! class_exists( 'NGT_UI_Kind_Card' ) ) {
	class NGT_UI_Kind_Card implements NGT_UI_Kind_Renderer_Interface {
		public function kind(): string { return 'card'; }
		public function render( NGT_UI_Catalog_Render_Context $context ): void {
			$title = $context->text ?: $context->label;
			echo '<div class="ngt-ui-card ngt-ui-card--' . esc_attr( $context->name ) . '">';
			echo '<div class="ngt-ui-card__fx" aria-hidden="true"></div>';
			echo '<div class="ngt-ui-card__body">';
			echo '<h3 class="ngt-ui-card__title">' . esc_html( $title ) . '</h3>';
			if ( $context->content ) {
				echo '<div class="ngt-ui-card__content">' . wp_kses_post( $context->content ) . '</div>';
			} elseif ( in_array( $context->name, array( 'tweet-card', 'client-tweet-card' ), true ) ) {
				echo '<p class="ngt-ui-card__content">Shipping Magic UI effects natively in WordPress — no React iframe required.</p>';
				echo '<div class="ngt-ui-tweet-meta"><strong>@nextgentutors</strong> · just now</div>';
			}
			echo '</div></div>';
		}
	}
}
