<?php
/**
 * BeyondInfinity Visual Builder host adapter.
 *
 * Theme stays a renderer — Companion owns the editor and document store.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register host with Companion when interface is available.
 */
function bi_builder_register_host() {
	if ( ! interface_exists( 'NGC_Builder_Host' ) ) {
		return;
	}

	if ( ! class_exists( 'BI_Builder_Host_Adapter', false ) ) {
		/**
		 * Implements NGC_Builder_Host when Companion is active.
		 */
		class BI_Builder_Host_Adapter implements NGC_Builder_Host {

			/**
			 * @return string
			 */
			public function contract_version(): string {
				return '1.0.0';
			}

			/**
			 * @return array<string, array{label: string, description?: string}>
			 */
			public function slots(): array {
				return [
					'main' => [
						'label'       => __( 'Main content', 'beyondinfinity' ),
						'description' => __( 'Primary page body region.', 'beyondinfinity' ),
					],
				];
			}

			/**
			 * @return array<string, array<string, mixed>>
			 */
			public function sections(): array {
				$keys = class_exists( 'NGC_Section_CMS' ) ? NGC_Section_CMS::section_keys() : [
					'hero',
					'trust_bar',
					'how_it_works',
					'learning_modes',
					'subject_explorer',
					'featured_tutors',
					'success_stories',
					'trust_safety',
					'pricing',
					'faq',
					'cta',
				];

				$out = [];
				foreach ( $keys as $key ) {
					$id         = 'home.' . $key;
					$out[ $id ] = [
						'id'           => $id,
						'label'        => ucwords( str_replace( '_', ' ', $key ) ),
						'pageKeys'     => [ 'home' ],
						'sectionKey'   => $key,
						'propSchema'   => [
							'enabled' => [ 'type' => 'boolean', 'default' => true ],
						],
						'defaultProps' => [
							'sectionKey' => $key,
							'enabled'    => true,
						],
					];
				}

				/**
				 * Filter BeyondInfinity builder section catalog.
				 *
				 * @param array $out Sections.
				 */
				return apply_filters( 'bi_builder_sections', $out );
			}

			/**
			 * @return string
			 */
			public function tokens_css_path(): string {
				$path = BI_DIR . '/assets/css/tokens/unified.css';
				return file_exists( $path ) ? $path : '';
			}

			/**
			 * @param string               $section_id Section id (home.hero).
			 * @param array<string, mixed> $props      Props.
			 * @return string
			 */
			public function render_section( string $section_id, array $props = [] ): string {
				$section_key = $props['sectionKey'] ?? '';
				if ( ! $section_key && 0 === strpos( $section_id, 'home.' ) ) {
					$section_key = substr( $section_id, 5 );
				}
				$section_key = sanitize_key( (string) $section_key );
				$content     = is_array( $props['content'] ?? null ) ? $props['content'] : [];

				if ( empty( $content ) && class_exists( 'NGC_Section_CMS' ) && $section_key ) {
					$content = NGC_Section_CMS::get_section( 'home', $section_key );
				}

				/**
				 * Allow theme templates to render a builder section.
				 *
				 * @param string|null $html        Pre-rendered HTML.
				 * @param string      $section_id  Registry id.
				 * @param string      $section_key CMS key.
				 * @param array       $props       Props.
				 * @param array       $content     CMS content.
				 */
				$html = apply_filters( 'bi_builder_render_section', null, $section_id, $section_key, $props, $content );
				if ( is_string( $html ) ) {
					return $html;
				}

				$title = esc_html( (string) ( $content['title'] ?? ucwords( str_replace( '_', ' ', $section_key ) ) ) );
				$text  = isset( $content['text'] ) ? wp_kses_post( (string) $content['text'] ) : '';
				$sub   = isset( $content['subtitle'] ) ? esc_html( (string) $content['subtitle'] ) : '';

				ob_start();
				?>
				<div class="bi-builder-section bi-builder-section--<?php echo esc_attr( $section_key ); ?>" data-bi-section="<?php echo esc_attr( $section_key ); ?>">
					<?php if ( $title ) : ?>
						<h2 class="bi-builder-section__title"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
					<?php endif; ?>
					<?php if ( $sub ) : ?>
						<p class="bi-builder-section__subtitle"><?php echo $sub; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					<?php endif; ?>
					<?php if ( $text ) : ?>
						<div class="bi-builder-section__body"><?php echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<?php endif; ?>
				</div>
				<?php
				return (string) ob_get_clean();
			}
		}
	}

	add_filter(
		'ngc_builder_host',
		static function ( $host ) {
			if ( $host instanceof NGC_Builder_Host ) {
				return $host;
			}
			return new BI_Builder_Host_Adapter();
		},
		10
	);
}
add_action( 'plugins_loaded', 'bi_builder_register_host', 20 );
add_action( 'after_setup_theme', 'bi_builder_register_host', 5 );
