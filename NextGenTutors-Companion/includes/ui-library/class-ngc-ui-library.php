<?php
/**
 * NextGen UI Library bootstrap.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers shortcodes, REST, and theme integration hooks.
 */
class NGC_UI_Library {

	/**
	 * Boot UI library.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_shortcodes' ], 25 );
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest' ] );
		add_filter( 'ngc_ui_render_component', [ __CLASS__, 'render_component' ], 10, 2 );
		add_action( 'admin_init', [ __CLASS__, 'maybe_seed_cms_copy' ] );
	}

	/**
	 * One-time seed of research-normalized homepage CMS defaults.
	 */
	public static function maybe_seed_cms_copy() {
		if ( get_option( 'ngc_ui_cms_research_seeded' ) || ! class_exists( 'NGC_Section_CMS' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		NGC_Section_CMS::seed_research_copy( false );
	}

	/**
	 * [ng_ui_component slug="hero" page_key="home"].
	 */
	public static function register_shortcodes() {
		add_shortcode( 'ng_ui_component', [ __CLASS__, 'shortcode_component' ] );
		add_shortcode( 'ngc_ui_component', [ __CLASS__, 'shortcode_component' ] );
	}

	/**
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function shortcode_component( $atts ) {
		$atts = shortcode_atts(
			[
				'slug'      => '',
				'component' => '',
				'page_key'  => '',
				'limit'     => 6,
			],
			is_array( $atts ) ? $atts : [],
			'ng_ui_component'
		);

		$slug        = sanitize_key( $atts['slug'] );
		$magic_slug  = sanitize_key( $atts['component'] );
		if ( ! $slug && $magic_slug ) {
			$slug = $magic_slug;
		}
		if ( ! $slug ) {
			return '';
		}

		$context = [
			'slug'       => $slug,
			'page_key'   => sanitize_key( $atts['page_key'] ),
			'limit'      => (int) $atts['limit'],
			'atts'       => $atts,
		];
		if ( $magic_slug ) {
			$context['magic_slug'] = $magic_slug;
		}

		return (string) apply_filters( 'ngc_ui_render_component', '', $context );
	}

	/**
	 * Default renderer — theme should hook ng_ui_render_component at priority 20+.
	 *
	 * @param string               $html    Existing HTML.
	 * @param array<string, mixed> $context Context.
	 * @return string
	 */
	public static function render_component( $html, $context ) {
		if ( '' !== $html || empty( $context['slug'] ) ) {
			return $html;
		}

		$def = NGC_UI_Component_Registry::get( $context['slug'] );
		if ( ! $def ) {
			return '<!-- ng-ui: unknown component -->';
		}

		$provider_key = $def['provider'] ?? '';
		$data         = $provider_key
			? NGC_UI_Provider_Registry::component_data( $provider_key, $context['slug'], $context )
			: [];

		ob_start();
		echo '<div class="ng-ui ng-ui--' . esc_attr( $context['slug'] ) . '" data-ng-ui="' . esc_attr( $context['slug'] ) . '">';
		foreach ( $data as $row ) {
			if ( ! empty( $row['empty'] ) ) {
				echo '<p class="ng-ui-empty">' . esc_html( $row['message'] ?? '' ) . '</p>';
				continue;
			}
			echo '<div class="ng-ui-item">';
			echo esc_html( $row['title'] ?? $row['name'] ?? '' );
			echo '</div>';
		}
		echo '</div>';
		return (string) ob_get_clean();
	}

	/**
	 * REST verification endpoint.
	 */
	public static function register_rest() {
		register_rest_route(
			'ngc/v1',
			'/ui-library/verify',
			[
				'methods'             => 'GET',
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'callback'            => static function () {
					return rest_ensure_response(
						[
							'providers'   => NGC_UI_Provider_Registry::verification_report(),
							'components'  => array_keys( NGC_UI_Component_Registry::definitions() ),
							'scan'        => NGC_UI_Import_Scanner::scan(),
						]
					);
				},
			]
		);
	}
}
