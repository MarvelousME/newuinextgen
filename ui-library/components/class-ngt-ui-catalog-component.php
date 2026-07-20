<?php
/**
 * Catalog-driven Magic UI component (PHP + CSS + light JS).
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Catalog_Component' ) ) {
	/**
	 * One registry entry backed by catalog metadata.
	 */
	class NGT_UI_Catalog_Component extends NGT_UI_Component_Base {

		/** @var string */
		private $name;

		/** @var array<string, mixed> */
		private $meta;

		/**
		 * @param string               $name Component slug.
		 * @param array<string, mixed> $meta Catalog row.
		 */
		public function __construct( string $name, array $meta ) {
			$this->name = $name;
			$this->meta = $meta;
		}

		public function get_name(): string {
			return $this->name;
		}

		public function get_label(): string {
			return (string) ( $this->meta['label'] ?? $this->name );
		}

		public function get_category(): string {
			return (string) ( $this->meta['category'] ?? 'effects' );
		}

		/**
		 * @return array<string, mixed>
		 */
		public function get_settings_schema(): array {
			$kind = (string) ( $this->meta['kind'] ?? 'misc' );
			$base = array(
				'text'     => array( 'type' => 'string', 'default' => 'NextGen Tutors' ),
				'content'  => array( 'type' => 'html', 'default' => '' ),
				'items'    => array( 'type' => 'string', 'default' => '' ),
				'class'    => array( 'type' => 'string', 'default' => '' ),
				'href'     => array( 'type' => 'string', 'default' => '#' ),
				'label'    => array( 'type' => 'string', 'default' => 'Get started' ),
				'src'      => array( 'type' => 'string', 'default' => '' ),
				'value'    => array( 'type' => 'number', 'default' => 75 ),
				'from'     => array( 'type' => 'number', 'default' => 0 ),
				'to'       => array( 'type' => 'number', 'default' => 100 ),
				'duration' => array( 'type' => 'number', 'default' => 2 ),
				'color'    => array( 'type' => 'color', 'default' => class_exists( 'NGT_UI_Tokens' ) ? NGT_UI_Tokens::schema_accent_default() : '#c4a35a' ),
				'color_to' => array( 'type' => 'color', 'default' => class_exists( 'NGT_UI_Tokens' ) ? NGT_UI_Tokens::schema_accent_2_default() : '#3b2f6e' ),
			);

			if ( 'button' === $kind ) {
				$base['label']['default'] = $this->get_label();
			}
			if ( 'progress' === $kind ) {
				$base['value']['default'] = 72;
			}
			if ( 'text' === $kind ) {
				$base['text']['default'] = $this->get_label();
			}

			return $base;
		}

		/**
		 * @return array<int, string>
		 */
		public function get_style_dependencies(): array {
			$kind        = sanitize_key( (string) ( $this->meta['kind'] ?? 'misc' ) );
			$deps        = array( 'ngt-ui-catalog' );
			$kind_handle = 'ngt-ui-kind-' . $kind;
			if ( in_array( $kind, array( 'button', 'text', 'pattern', 'card', 'device', 'progress', 'list', 'media', 'map', 'interactive', 'misc' ), true ) ) {
				$deps[] = $kind_handle;
			}
			return $deps;
		}

		/**
		 * @return array<int, string>
		 */
		public function get_script_dependencies(): array {
			if ( empty( $this->meta['needs_js'] ) ) {
				return array();
			}
			$kind = sanitize_key( (string) ( $this->meta['kind'] ?? 'misc' ) );
			$deps = array( 'ngt-ui-vendor-loader', 'ngt-ui-catalog-core' );
			if ( 'interactive' === $kind ) {
				$deps[] = 'ngt-ui-catalog-interactive';
			}
			return $deps;
		}

		/**
		 * @param array<string, mixed> $settings Settings.
		 * @param array<string, mixed> $context  Context.
		 */
		public function render( array $settings, array $context = array() ): string {
			NGT_UI_Kind_Registry::boot();

			$kind    = (string) ( $this->meta['kind'] ?? 'misc' );
			$id      = $this->instance_id( 'ngt-' . $this->name );
			$class   = trim( 'ngt-ui-comp ngt-ui-' . $this->name . ' ' . (string) ( $settings['class'] ?? '' ) );
			$text    = (string) ( $settings['text'] ?? '' );
			$content = (string) ( $settings['content'] ?? '' );
			if ( ! $content && ! empty( $context['content'] ) ) {
				$content = (string) $context['content'];
			}
			$items = NGT_UI_Kind_Parser::parse_items( (string) ( $settings['items'] ?? '' ), $text );

			$accent   = class_exists( 'NGT_UI_Tokens' ) ? NGT_UI_Tokens::accent( (string) ( $settings['color'] ?? '' ) ) : (string) ( $settings['color'] ?? '#c4a35a' );
			$accent_2 = class_exists( 'NGT_UI_Tokens' ) ? NGT_UI_Tokens::accent_secondary( (string) ( $settings['color_to'] ?? '' ) ) : (string) ( $settings['color_to'] ?? '#3b2f6e' );

			$render_context = new NGT_UI_Catalog_Render_Context(
				$this->name,
				$this->get_label(),
				$settings,
				$text,
				$content,
				$items
			);

			ob_start();
			echo '<div id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" data-ngt-ui="' . esc_attr( $this->name ) . '" data-ngt-kind="' . esc_attr( $kind ) . '"';
			echo ' style="--ngt-accent:' . esc_attr( $accent ) . ';--ngt-accent-2:' . esc_attr( $accent_2 ) . ';--ngt-duration:' . esc_attr( (string) $settings['duration'] ) . 's;">';

			$renderer = NGT_UI_Kind_Registry::get( $kind );
			if ( $renderer ) {
				$renderer->render( $render_context );
			}

			echo '</div>';
			return (string) ob_get_clean();
		}
	}
}
