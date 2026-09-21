<?php
/**
 * REST API — endpoints for the Page Inspector and admin AJAX bridge.
 *
 * Namespace: ngt3d/v1
 *
 * All routes require manage_options capability.
 *
 * @package NGT_3D_Scroll
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NGT3D_Rest_Api {

	const NAMESPACE = 'ngt3d/v1';

	public static function boot(): void {
		add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
	}

	public static function register_routes(): void {
		$cap_check = [ self::class, 'check_permission' ];

		// Rules CRUD.
		register_rest_route( self::NAMESPACE, '/rules', [
			[ 'methods' => 'GET',  'callback' => [ self::class, 'get_rules' ],    'permission_callback' => $cap_check ],
			[ 'methods' => 'POST', 'callback' => [ self::class, 'create_rule' ],  'permission_callback' => $cap_check ],
		] );

		register_rest_route( self::NAMESPACE, '/rules/(?P<id>\d+)', [
			[ 'methods' => 'GET',    'callback' => [ self::class, 'get_rule' ],     'permission_callback' => $cap_check ],
			[ 'methods' => 'PUT',    'callback' => [ self::class, 'update_rule' ],  'permission_callback' => $cap_check ],
			[ 'methods' => 'DELETE', 'callback' => [ self::class, 'delete_rule' ],  'permission_callback' => $cap_check ],
		] );

		// Bulk actions.
		register_rest_route( self::NAMESPACE, '/rules/bulk', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'bulk_action' ],
			'permission_callback' => $cap_check,
		] );

		// Duplicate.
		register_rest_route( self::NAMESPACE, '/rules/(?P<id>\d+)/duplicate', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'duplicate_rule' ],
			'permission_callback' => $cap_check,
		] );

		// Export.
		register_rest_route( self::NAMESPACE, '/export', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'export_rules' ],
			'permission_callback' => $cap_check,
		] );

		// Import.
		register_rest_route( self::NAMESPACE, '/import', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'import_rules' ],
			'permission_callback' => $cap_check,
		] );

		// Animation registry.
		register_rest_route( self::NAMESPACE, '/registry', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'get_registry' ],
			'permission_callback' => $cap_check,
		] );

		// Page inspector.
		register_rest_route( self::NAMESPACE, '/inspect', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'inspect_page' ],
			'permission_callback' => $cap_check,
		] );

		// Diagnostics.
		register_rest_route( self::NAMESPACE, '/diagnostics', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'get_diagnostics' ],
			'permission_callback' => $cap_check,
		] );

		// Settings.
		register_rest_route( self::NAMESPACE, '/settings', [
			[ 'methods' => 'GET',  'callback' => [ self::class, 'get_settings' ],  'permission_callback' => $cap_check ],
			[ 'methods' => 'POST', 'callback' => [ self::class, 'save_settings' ], 'permission_callback' => $cap_check ],
		] );

		// Pages list (for the page selector dropdown).
		register_rest_route( self::NAMESPACE, '/pages', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'get_pages' ],
			'permission_callback' => $cap_check,
		] );

		// Selector validator.
		register_rest_route( self::NAMESPACE, '/validate-selector', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'validate_selector' ],
			'permission_callback' => $cap_check,
		] );
	}

	// ── Permission ──────────────────────────────────────────────────────────────

	public static function check_permission( WP_REST_Request $request ): bool|WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'ngt3d_forbidden',
				__( '3D Scroll Manager: insufficient permissions.', 'ngt-3d-scroll' ),
				[ 'status' => 403 ]
			);
		}
		return true;
	}

	// ── Rules Endpoints ─────────────────────────────────────────────────────────

	public static function get_rules( WP_REST_Request $req ): WP_REST_Response {
		$args = [
			'page'     => absint( $req->get_param( 'page' ) ?: 1 ),
			'per_page' => absint( $req->get_param( 'per_page' ) ?: 20 ),
			'orderby'  => sanitize_key( $req->get_param( 'orderby' ) ?: 'sort_order' ),
			'order'    => sanitize_key( $req->get_param( 'order' ) ?: 'ASC' ),
			'search'   => sanitize_text_field( $req->get_param( 'search' ) ?: '' ),
		];

		$result = NGT3D_Rule_Repository::get_all( $args );

		return new WP_REST_Response( [
			'rules' => $result['rules'],
			'total' => $result['total'],
		], 200 );
	}

	public static function get_rule( WP_REST_Request $req ): WP_REST_Response|WP_Error {
		$rule = NGT3D_Rule_Repository::get( (int) $req['id'] );
		if ( null === $rule ) {
			return new WP_Error( 'ngt3d_not_found', __( 'Rule not found.', 'ngt-3d-scroll' ), [ 'status' => 404 ] );
		}
		return new WP_REST_Response( $rule, 200 );
	}

	public static function create_rule( WP_REST_Request $req ): WP_REST_Response|WP_Error {
		$body   = $req->get_json_params() ?: [];
		$result = NGT3D_Validator::sanitize_rule( $body );

		if ( ! $result['valid'] ) {
			return new WP_Error( 'ngt3d_invalid', __( 'Validation failed.', 'ngt-3d-scroll' ), [
				'status' => 422,
				'errors' => $result['errors'],
			] );
		}

		$id = NGT3D_Rule_Repository::create( $result['data'] );
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		return new WP_REST_Response( NGT3D_Rule_Repository::get( $id ), 201 );
	}

	public static function update_rule( WP_REST_Request $req ): WP_REST_Response|WP_Error {
		$id   = (int) $req['id'];
		$rule = NGT3D_Rule_Repository::get( $id );
		if ( null === $rule ) {
			return new WP_Error( 'ngt3d_not_found', __( 'Rule not found.', 'ngt-3d-scroll' ), [ 'status' => 404 ] );
		}

		$body   = $req->get_json_params() ?: [];
		$merged = array_merge( $rule, $body );
		$result = NGT3D_Validator::sanitize_rule( $merged );

		if ( ! $result['valid'] ) {
			return new WP_Error( 'ngt3d_invalid', __( 'Validation failed.', 'ngt-3d-scroll' ), [
				'status' => 422,
				'errors' => $result['errors'],
			] );
		}

		$saved = NGT3D_Rule_Repository::update( $id, $result['data'] );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return new WP_REST_Response( NGT3D_Rule_Repository::get( $id ), 200 );
	}

	public static function delete_rule( WP_REST_Request $req ): WP_REST_Response|WP_Error {
		$deleted = NGT3D_Rule_Repository::delete( [ (int) $req['id'] ] );
		return new WP_REST_Response( [ 'deleted' => $deleted ], 200 );
	}

	public static function bulk_action( WP_REST_Request $req ): WP_REST_Response|WP_Error {
		$body   = $req->get_json_params() ?: [];
		$action = sanitize_key( $body['action'] ?? '' );
		$ids    = array_map( 'absint', (array) ( $body['ids'] ?? [] ) );

		if ( empty( $ids ) ) {
			return new WP_Error( 'ngt3d_empty', __( 'No IDs provided.', 'ngt-3d-scroll' ), [ 'status' => 400 ] );
		}

		switch ( $action ) {
			case 'enable':
				$count = NGT3D_Rule_Repository::set_enabled( $ids, true );
				break;
			case 'disable':
				$count = NGT3D_Rule_Repository::set_enabled( $ids, false );
				break;
			case 'delete':
				$count = NGT3D_Rule_Repository::delete( $ids );
				break;
			default:
				return new WP_Error( 'ngt3d_bad_action', __( 'Unknown bulk action.', 'ngt-3d-scroll' ), [ 'status' => 400 ] );
		}

		return new WP_REST_Response( [ 'affected' => $count ], 200 );
	}

	public static function duplicate_rule( WP_REST_Request $req ): WP_REST_Response|WP_Error {
		$id = NGT3D_Rule_Repository::duplicate( (int) $req['id'] );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		return new WP_REST_Response( NGT3D_Rule_Repository::get( $id ), 201 );
	}

	// ── Export / Import ─────────────────────────────────────────────────────────

	public static function export_rules( WP_REST_Request $req ): WP_REST_Response {
		$page_id   = absint( $req->get_param( 'page_id' ) ?: 0 ) ?: null;
		$page_slug = sanitize_key( $req->get_param( 'page_slug' ) ?: '' ) ?: null;

		return new WP_REST_Response( NGT3D_Rule_Repository::export( $page_id, $page_slug ), 200 );
	}

	public static function import_rules( WP_REST_Request $req ): WP_REST_Response|WP_Error {
		$body = $req->get_body();
		if ( empty( $body ) ) {
			$params = $req->get_json_params() ?: [];
			$body   = wp_json_encode( $params );
		}

		$result = NGT3D_Validator::validate_import_json( (string) $body );

		if ( empty( $result['rules'] ) ) {
			return new WP_Error( 'ngt3d_invalid', __( 'No valid rules found.', 'ngt-3d-scroll' ), [
				'status' => 422,
				'errors' => $result['errors'],
			] );
		}

		$preview = (bool) ( $req->get_param( 'preview' ) ?? false );

		if ( $preview ) {
			return new WP_REST_Response( [
				'preview' => true,
				'rules'   => $result['rules'],
				'errors'  => $result['errors'],
			], 200 );
		}

		$created = 0;
		foreach ( $result['rules'] as $rule_data ) {
			$id = NGT3D_Rule_Repository::create( $rule_data );
			if ( ! is_wp_error( $id ) ) {
				$created++;
			}
		}

		return new WP_REST_Response( [
			'created' => $created,
			'errors'  => $result['errors'],
		], 201 );
	}

	// ── Other Endpoints ─────────────────────────────────────────────────────────

	public static function get_registry(): WP_REST_Response {
		return new WP_REST_Response( NGT3D_Animation_Registry::all(), 200 );
	}

	public static function inspect_page( WP_REST_Request $req ): WP_REST_Response|WP_Error {
		$page_id = absint( $req->get_param( 'page_id' ) ?: 0 );
		if ( ! $page_id ) {
			return new WP_Error( 'ngt3d_missing', __( 'page_id is required.', 'ngt-3d-scroll' ), [ 'status' => 400 ] );
		}

		$page_url = get_permalink( $page_id );
		if ( ! $page_url ) {
			return new WP_Error( 'ngt3d_not_found', __( 'Page not found.', 'ngt-3d-scroll' ), [ 'status' => 404 ] );
		}

		// Fetch rendered HTML.
		$response = wp_remote_get( $page_url, [
			'timeout'   => 15,
			'sslverify' => false,
			'headers'   => [ 'Cookie' => '' ],
		] );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ngt3d_fetch_failed', $response->get_error_message(), [ 'status' => 500 ] );
		}

		$html = wp_remote_retrieve_body( $response );

		return new WP_REST_Response( [
			'page_id'    => $page_id,
			'page_url'   => $page_url,
			'targets'    => self::extract_targets( $html ),
		], 200 );
	}

	public static function get_diagnostics(): WP_REST_Response {
		return new WP_REST_Response( NGT3D_Diagnostics::collect(), 200 );
	}

	public static function get_settings(): WP_REST_Response {
		return new WP_REST_Response( NGT3D_Runtime_Config::get_settings(), 200 );
	}

	public static function save_settings( WP_REST_Request $req ): WP_REST_Response {
		$body    = $req->get_json_params() ?: [];
		$saved   = NGT3D_Runtime_Config::save_settings( $body );
		return new WP_REST_Response( [ 'saved' => $saved ], 200 );
	}

	public static function get_pages(): WP_REST_Response {
		$pages = get_pages( [
			'post_status' => [ 'publish', 'draft' ],
			'number'      => 200,
		] );

		$out = [
			[ 'id' => 0, 'slug' => 'all',        'title' => __( '— All Pages —', 'ngt-3d-scroll' ) ],
			[ 'id' => 0, 'slug' => 'front-page', 'title' => __( 'Front Page (static)', 'ngt-3d-scroll' ) ],
		];

		foreach ( $pages as $page ) {
			$out[] = [
				'id'    => $page->ID,
				'slug'  => $page->post_name,
				'title' => $page->post_title . ' — ID ' . $page->ID,
			];
		}

		return new WP_REST_Response( $out, 200 );
	}

	public static function validate_selector( WP_REST_Request $req ): WP_REST_Response {
		$body     = $req->get_json_params() ?: [];
		$selector = sanitize_text_field( $body['selector'] ?? '' );
		$result   = NGT3D_Validator::validate_selector( $selector );
		return new WP_REST_Response( $result, 200 );
	}

	// ── Helpers ─────────────────────────────────────────────────────────────────

	/**
	 * Extract likely animation targets from rendered HTML.
	 *
	 * Looks for IDs, data-bi-* attributes, and key class patterns.
	 *
	 * @param string $html Rendered page HTML.
	 * @return array
	 */
	private static function extract_targets( string $html ): array {
		if ( ! class_exists( 'DOMDocument' ) || empty( $html ) ) {
			return [];
		}

		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
		libxml_clear_errors();

		$targets = [];

		$tags = [ 'section', 'div', 'article', 'header', 'main' ];

		foreach ( $tags as $tag ) {
			$elements = $dom->getElementsByTagName( $tag );
			foreach ( $elements as $el ) {
				$id      = $el->getAttribute( 'id' );
				$classes = $el->getAttribute( 'class' );
				$data_st = $el->getAttribute( 'data-bi-stack-3d' );
				$data_tl = $el->getAttribute( 'data-bi-tilt' );
				$data_pr = $el->getAttribute( 'data-parallax-rate' );
				$data_mo = $el->getAttribute( 'data-bi-motion' );

				// Only surface nodes that look like animation targets.
				if ( ! $id && ! $data_st && ! $data_tl && ! $data_pr && ! $data_mo ) {
					// Filter by class patterns.
					if ( ! preg_match( '/\b(ngi-section|ngi-hero|bi-carousel|bi-stack|ngi-card|ngt-section|bi-parallax)\b/', $classes ) ) {
						continue;
					}
				}

				$selector      = $id ? '#' . $id : ( $classes ? '.' . str_replace( ' ', '.', trim( $classes ) ) : $tag );
				$recommendation = self::recommend_animation( $id, $classes, [
					'data-bi-stack-3d'  => $data_st,
					'data-bi-tilt'      => $data_tl,
					'data-parallax-rate'=> $data_pr,
					'data-bi-motion'    => $data_mo,
				] );

				$targets[] = [
					'tag'        => $tag,
					'id'         => $id,
					'classes'    => $classes,
					'selector'   => $selector,
					'recommend'  => $recommendation,
				];

				if ( count( $targets ) >= 50 ) {
					break 2;
				}
			}
		}

		return $targets;
	}

	/**
	 * Heuristically recommend an animation based on element attributes.
	 *
	 * @param string $id      Element ID.
	 * @param string $classes Element classes.
	 * @param array  $data    Data attributes.
	 * @return string  Comma-separated animation suggestions.
	 */
	private static function recommend_animation( string $id, string $classes, array $data ): string {
		$map = [
			'hero'              => 'depth-hero,perspective-exit',
			'tutors'            => 'carousel-depth',
			'how-it-works'      => 'stack-3d',
			'subjects'          => 'perspective-reveal,scale-depth',
			'testimonials'      => 'horizontal-scroll,perspective-reveal',
			'pathways'          => 'cards-fan',
			'trust'             => 'perspective-reveal,stagger-depth',
			'cta'               => 'parallax-slow,depth-scroll',
			'pricing'           => 'perspective-reveal',
			'results'           => 'stagger-depth',
		];

		if ( isset( $map[ $id ] ) ) {
			return $map[ $id ];
		}

		if ( isset( $data['data-bi-stack-3d'] ) ) {
			return 'stack-3d';
		}
		if ( isset( $data['data-bi-tilt'] ) ) {
			return 'tilt-3d';
		}
		if ( isset( $data['data-parallax-rate'] ) ) {
			return 'parallax-slow';
		}

		if ( str_contains( $classes, 'ngi-hero' ) || str_contains( $classes, 'bi-cinematic' ) ) {
			return 'depth-hero,perspective-exit';
		}
		if ( str_contains( $classes, 'bi-carousel' ) ) {
			return 'carousel-depth';
		}
		if ( str_contains( $classes, 'bi-parallax' ) ) {
			return 'parallax-slow';
		}
		if ( str_contains( $classes, 'ngi-section' ) ) {
			return 'perspective-reveal';
		}

		return 'perspective-reveal';
	}
}
