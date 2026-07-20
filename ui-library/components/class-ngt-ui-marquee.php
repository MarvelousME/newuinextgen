<?php
/**
 * Marquee — infinite scroll strip (Magic UI MIT reimplementation).
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Marquee' ) ) {
	/**
	 * CSS marquee with pause-on-hover and reduced-motion support.
	 */
	class NGT_UI_Marquee extends NGT_UI_Component_Base {

		public function get_name(): string {
			return 'marquee';
		}

		public function get_label(): string {
			return __( 'Marquee', 'ngt-ui' );
		}

		public function get_category(): string {
			return 'motion';
		}

		/**
		 * @return array<string, mixed>
		 */
		public function get_settings_schema(): array {
			return array(
				'items'         => array( 'type' => 'string', 'default' => '' ), // pipe-separated labels
				'content'       => array( 'type' => 'html', 'default' => '' ),
				'reverse'       => array( 'type' => 'boolean', 'default' => false ),
				'pause_on_hover'=> array( 'type' => 'boolean', 'default' => true ),
				'vertical'      => array( 'type' => 'boolean', 'default' => false ),
				'repeat'        => array( 'type' => 'integer', 'default' => 4 ),
				'duration'      => array( 'type' => 'number', 'default' => 40 ),
				'gap'           => array( 'type' => 'string', 'default' => '1rem' ),
				'class'         => array( 'type' => 'string', 'default' => '' ),
			);
		}

		/**
		 * @return array<int, string>
		 */
		public function get_style_dependencies(): array {
			return array( 'ngt-ui-marquee' );
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
			$id      = $this->instance_id( 'ngt-marquee' );
			$repeat  = max( 1, min( 8, (int) $settings['repeat'] ) );
			$class   = trim( 'ngt-ui-marquee ' . (string) ( $settings['class'] ?? '' ) );
			if ( ! empty( $settings['vertical'] ) ) {
				$class .= ' is-vertical';
			}
			if ( ! empty( $settings['reverse'] ) ) {
				$class .= ' is-reverse';
			}
			if ( ! empty( $settings['pause_on_hover'] ) ) {
				$class .= ' is-pause-hover';
			}

			$items_raw = (string) ( $settings['items'] ?? '' );
			$labels    = array_filter( array_map( 'trim', explode( '|', $items_raw ) ) );
			$content   = (string) ( $settings['content'] ?? '' );
			if ( ! empty( $context['content'] ) ) {
				$content = (string) $context['content'];
			}

			$style = sprintf(
				'--ngt-mq-duration:%ss;--ngt-mq-gap:%s;',
				esc_attr( (string) $settings['duration'] ),
				esc_attr( sanitize_text_field( (string) $settings['gap'] ) )
			);

			ob_start();
			?>
			<div
				id="<?php echo esc_attr( $id ); ?>"
				class="<?php echo esc_attr( $class ); ?>"
				data-ngt-ui="marquee"
				style="<?php echo esc_attr( $style ); ?>"
				role="region"
				aria-label="<?php echo esc_attr__( 'Scrolling highlights', 'ngt-ui' ); ?>"
			>
				<?php for ( $i = 0; $i < $repeat; $i++ ) : ?>
					<div class="ngt-ui-marquee__track" <?php echo 0 === $i ? '' : 'aria-hidden="true"'; ?>>
						<?php if ( $labels ) : ?>
							<?php foreach ( $labels as $label ) : ?>
								<span class="ngt-ui-marquee__item"><?php echo esc_html( $label ); ?></span>
							<?php endforeach; ?>
						<?php elseif ( $content ) : ?>
							<div class="ngt-ui-marquee__item ngt-ui-marquee__item--html"><?php echo wp_kses_post( $content ); ?></div>
						<?php endif; ?>
					</div>
				<?php endfor; ?>
			</div>
			<?php
			return (string) ob_get_clean();
		}
	}
}
