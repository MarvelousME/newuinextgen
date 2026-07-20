<?php
/**
 * REST routes for homepage section CMS.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Section CMS REST API.
 */
class NGC_Rest_Section_Cms {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/sections/(?P<page>[a-z0-9_-]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'list_sections' ],
				'permission_callback' => function () {
					return NGC_Rest::public_throttled( 'section_cms_read', 90, 600 );
				},
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/sections/(?P<page>[a-z0-9_-]+)/(?P<section>[a-z0-9_-]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'get_section' ],
					'permission_callback' => function () {
						return NGC_Rest::public_throttled( 'section_cms_read', 90, 600 );
					},
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ __CLASS__, 'update_section' ],
					'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				],
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function list_sections( $request ) {
		$page = sanitize_key( $request['page'] );
		$out  = [];
		foreach ( NGC_Section_CMS::section_keys() as $key ) {
			$out[ $key ] = NGC_Section_CMS::get_section( $page, $key );
		}
		return rest_ensure_response( $out );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_section( $request ) {
		$page    = sanitize_key( $request['page'] );
		$section = sanitize_key( $request['section'] );
		if ( ! in_array( $section, NGC_Section_CMS::section_keys(), true ) ) {
			return NGC_Rest::error_response( 'ngc_section_unknown', __( 'Unknown section.', 'nextgencompanion' ), 404 );
		}
		return rest_ensure_response( NGC_Section_CMS::get_section( $page, $section ) );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function update_section( $request ) {
		$page    = sanitize_key( $request['page'] );
		$section = sanitize_key( $request['section'] );
		if ( ! in_array( $section, NGC_Section_CMS::section_keys(), true ) ) {
			return NGC_Rest::error_response( 'ngc_section_unknown', __( 'Unknown section.', 'nextgencompanion' ), 404 );
		}
		$body    = $request->get_json_params();
		$content = is_array( $body['content'] ?? null ) ? $body['content'] : [];
		$enabled = ! isset( $body['enabled'] ) || (bool) $body['enabled'];
		$id      = NGC_Section_CMS::save_section( $page, $section, $content, $enabled );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		return rest_ensure_response(
			[
				'id'      => $id,
				'section' => $section,
				'content' => NGC_Section_CMS::get_section( $page, $section ),
			]
		);
	}
}
