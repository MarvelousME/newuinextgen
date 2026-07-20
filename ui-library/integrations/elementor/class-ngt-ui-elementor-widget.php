<?php
/**
 * Elementor widget: NGT UI Component.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Elementor_Widget' ) && class_exists( '\Elementor\Widget_Base' ) ) {
	/**
	 * Single Elementor widget selecting any registered component.
	 */
	class NGT_UI_Elementor_Widget extends \Elementor\Widget_Base {

		public function get_name(): string {
			return 'ngt_ui_component';
		}

		public function get_title(): string {
			return __( 'NGT UI Component', 'ngt-ui' );
		}

		public function get_icon(): string {
			return 'eicon-shortcode';
		}

		/**
		 * @return array<int, string>
		 */
		public function get_categories(): array {
			return array( 'ngt-ui', 'general' );
		}

		/**
		 * @return array<int, string>
		 */
		public function get_keywords(): array {
			return array( 'ngt', 'magic', 'ui', 'nextgen' );
		}

		protected function register_controls(): void {
			$options = array();
			if ( class_exists( 'NGT_UI_Registry' ) ) {
				foreach ( NGT_UI_Registry::all() as $slug => $component ) {
					$options[ $slug ] = $component->get_label() . ' (' . $slug . ')';
				}
			}
			if ( ! $options ) {
				$options['magic-card'] = 'Magic Card';
			}

			$this->start_controls_section(
				'section_content',
				array( 'label' => __( 'Content', 'ngt-ui' ) )
			);

			$this->add_control(
				'component',
				array(
					'label'   => __( 'Component', 'ngt-ui' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'magic-card',
					'options' => $options,
				)
			);

			$this->add_control(
				'title',
				array(
					'label'   => __( 'Title / text', 'ngt-ui' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => '',
				)
			);

			$this->add_control(
				'label',
				array(
					'label'   => __( 'Button label', 'ngt-ui' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => '',
				)
			);

			$this->add_control(
				'items',
				array(
					'label'       => __( 'Items', 'ngt-ui' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'description' => __( 'Pipe-separated list', 'ngt-ui' ),
					'default'     => '',
				)
			);

			$this->add_control(
				'content',
				array(
					'label'   => __( 'Content', 'ngt-ui' ),
					'type'    => \Elementor\Controls_Manager::WYSIWYG,
					'default' => '',
				)
			);

			$this->end_controls_section();
		}

		protected function render(): void {
			$settings = $this->get_settings_for_display();
			$name     = sanitize_key( (string) ( $settings['component'] ?? '' ) );
			if ( '' === $name || ! class_exists( 'NGT_UI_Renderer' ) ) {
				return;
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes internally.
			echo NGT_UI_Renderer::render(
				$name,
				array(
					'title'   => (string) ( $settings['title'] ?? '' ),
					'text'    => (string) ( $settings['title'] ?? '' ),
					'label'   => (string) ( $settings['label'] ?? '' ),
					'items'   => (string) ( $settings['items'] ?? '' ),
					'content' => (string) ( $settings['content'] ?? '' ),
				),
				array( 'editor' => 'elementor' )
			);
		}
	}
}
