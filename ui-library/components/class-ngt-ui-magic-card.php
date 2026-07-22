<?php
/**
 * Magic Card — spotlight / orb card (Magic UI MIT reimplementation).
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Magic_Card' ) ) {
	/**
	 * Spotlight card without React/Motion.
	 */
	class NGT_UI_Magic_Card extends NGT_UI_Component_Base {

		public function get_name(): string {
			return 'magic-card';
		}

		public function get_label(): string {
			return __( 'Magic Card', 'ngt-ui' );
		}

		public function get_category(): string {
			return 'effects';
		}

		/**
		 * @return array<string, mixed>
		 */
		public function get_settings_schema(): array {
			return array(
				'title'            => array( 'type' => 'string', 'default' => '' ),
				'content'          => array( 'type' => 'html', 'default' => '' ),
				'mode'             => array( 'type' => 'enum', 'enum' => array( 'gradient', 'orb' ), 'default' => 'gradient' ),
				'gradient_size'    => array( 'type' => 'integer', 'default' => 200 ),
				'gradient_from'    => array( 'type' => 'color', 'default' => '#059669' ),
				'gradient_to'      => array( 'type' => 'color', 'default' => '#FF9F0A' ),
				'gradient_color'   => array( 'type' => 'color', 'default' => '#262626' ),
				'gradient_opacity' => array( 'type' => 'number', 'default' => 0.8 ),
				'class'            => array( 'type' => 'string', 'default' => '' ),
			);
		}

		/**
		 * @return array<int, string>
		 */
		public function get_style_dependencies(): array {
			return array( 'ngt-ui-magic-card' );
		}

		/**
		 * @return array<int, string>
		 */
		public function get_script_dependencies(): array {
			return array( 'ngt-ui-magic-card' );
		}

		/**
		 * @param array<string, mixed> $settings Settings.
		 * @param array<string, mixed> $context  Context.
		 */
		public function render( array $settings, array $context = array() ): string {
			$id    = $this->instance_id( 'ngt-magic-card' );
			$title = (string) ( $settings['title'] ?? '' );
			$body  = (string) ( $settings['content'] ?? '' );
			$mode  = (string) ( $settings['mode'] ?? 'gradient' );
			$class = trim( 'ngt-ui-magic-card ' . (string) ( $settings['class'] ?? '' ) );

			$style = sprintf(
				'--ngt-mc-size:%dpx;--ngt-mc-from:%s;--ngt-mc-to:%s;--ngt-mc-spot:%s;--ngt-mc-opacity:%s;',
				(int) $settings['gradient_size'],
				esc_attr( (string) $settings['gradient_from'] ),
				esc_attr( (string) $settings['gradient_to'] ),
				esc_attr( (string) $settings['gradient_color'] ),
				esc_attr( (string) $settings['gradient_opacity'] )
			);

			ob_start();
			?>
			<div
				id="<?php echo esc_attr( $id ); ?>"
				class="<?php echo esc_attr( $class ); ?>"
				data-ngt-ui="magic-card"
				data-mode="<?php echo esc_attr( $mode ); ?>"
				style="<?php echo esc_attr( $style ); ?>"
			>
				<div class="ngt-ui-magic-card__border" aria-hidden="true"></div>
				<div class="ngt-ui-magic-card__surface" aria-hidden="true"></div>
				<div class="ngt-ui-magic-card__spot" aria-hidden="true"></div>
				<div class="ngt-ui-magic-card__body">
					<?php if ( $title ) : ?>
						<h3 class="ngt-ui-magic-card__title"><?php echo esc_html( $title ); ?></h3>
					<?php endif; ?>
					<?php if ( $body ) : ?>
						<div class="ngt-ui-magic-card__content"><?php echo wp_kses_post( $body ); ?></div>
					<?php endif; ?>
					<?php
					if ( ! empty( $context['content'] ) ) {
						echo wp_kses_post( (string) $context['content'] );
					}
					?>
				</div>
			</div>
			<?php
			return (string) ob_get_clean();
		}
	}
}
