<?php
/**
 * Border Beam — animated border highlight (Magic UI MIT reimplementation).
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Border_Beam' ) ) {
	/**
	 * CSS offset-path border beam around nested content.
	 */
	class NGT_UI_Border_Beam extends NGT_UI_Component_Base {

		public function get_name(): string {
			return 'border-beam';
		}

		public function get_label(): string {
			return __( 'Border Beam', 'ngt-ui' );
		}

		public function get_category(): string {
			return 'effects';
		}

		/**
		 * @return array<string, mixed>
		 */
		public function get_settings_schema(): array {
			return array(
				'content'         => array( 'type' => 'html', 'default' => '' ),
				'size'            => array( 'type' => 'integer', 'default' => 50 ),
				'duration'        => array( 'type' => 'number', 'default' => 6 ),
				'delay'           => array( 'type' => 'number', 'default' => 0 ),
				'color_from'      => array( 'type' => 'color', 'default' => '#ffaa40' ),
				'color_to'        => array( 'type' => 'color', 'default' => '#9c40ff' ),
				'reverse'         => array( 'type' => 'boolean', 'default' => false ),
				'border_width'    => array( 'type' => 'integer', 'default' => 1 ),
				'class'           => array( 'type' => 'string', 'default' => '' ),
			);
		}

		/**
		 * @return array<int, string>
		 */
		public function get_style_dependencies(): array {
			return array( 'ngt-ui-border-beam' );
		}

		/**
		 * @return array<int, string>
		 */
		public function get_script_dependencies(): array {
			return array();
		}

		/**
		 * @param array<string, mixed> $settings Settings.
		 * @param array<string, mixed> $context  Context.
		 */
		public function render( array $settings, array $context = array() ): string {
			$id      = $this->instance_id( 'ngt-border-beam' );
			$content = (string) ( $settings['content'] ?? '' );
			if ( ! empty( $context['content'] ) ) {
				$content = (string) $context['content'];
			}
			$class = trim( 'ngt-ui-border-beam ' . ( string ) ( $settings['class'] ?? '' ) );
			if ( ! empty( $settings['reverse'] ) ) {
				$class .= ' is-reverse';
			}

			$style = sprintf(
				'--ngt-bb-size:%dpx;--ngt-bb-duration:%ss;--ngt-bb-delay:%ss;--ngt-bb-from:%s;--ngt-bb-to:%s;--ngt-bb-width:%dpx;',
				(int) $settings['size'],
				esc_attr( (string) $settings['duration'] ),
				esc_attr( (string) $settings['delay'] ),
				esc_attr( (string) $settings['color_from'] ),
				esc_attr( (string) $settings['color_to'] ),
				(int) $settings['border_width']
			);

			ob_start();
			?>
			<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $class ); ?>" data-ngt-ui="border-beam" style="<?php echo esc_attr( $style ); ?>">
				<div class="ngt-ui-border-beam__frame" aria-hidden="true">
					<span class="ngt-ui-border-beam__ray"></span>
				</div>
				<div class="ngt-ui-border-beam__body">
					<?php echo wp_kses_post( $content ); ?>
				</div>
			</div>
			<?php
			return (string) ob_get_clean();
		}
	}
}
