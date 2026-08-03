<?php
/**
 * REST API for Visual Builder (`ngc/v1/builder/*`).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builder routes.
 */
class NGC_Rest_Builder {

	/**
	 * Register routes.
	 */
	public static function register() {
		$ns   = NGC_Rest::NAMESPACE;
		$auth = [ __CLASS__, 'can_edit' ];

		register_rest_route(
			$ns,
			'/builder/documents',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ __CLASS__, 'list_documents' ],
					'permission_callback' => $auth,
				],
				[
					'methods'             => 'POST',
					'callback'            => [ __CLASS__, 'create_document' ],
					'permission_callback' => $auth,
				],
			]
		);

		register_rest_route(
			$ns,
			'/builder/documents/(?P<id>[a-z0-9_\-]+)',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ __CLASS__, 'get_document' ],
					'permission_callback' => $auth,
				],
				[
					'methods'             => 'PUT',
					'callback'            => [ __CLASS__, 'put_document' ],
					'permission_callback' => $auth,
				],
			]
		);

		register_rest_route(
			$ns,
			'/builder/documents/(?P<id>[a-z0-9_\-]+)/publish',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'publish_document' ],
				'permission_callback' => $auth,
			]
		);

		register_rest_route(
			$ns,
			'/builder/documents/(?P<id>[a-z0-9_\-]+)/revisions',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'list_revisions' ],
				'permission_callback' => $auth,
			]
		);

		register_rest_route(
			$ns,
			'/builder/revisions/(?P<id>\d+)/restore',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'restore_revision' ],
				'permission_callback' => $auth,
			]
		);

		register_rest_route(
			$ns,
			'/builder/tokens',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ __CLASS__, 'get_tokens' ],
					'permission_callback' => $auth,
				],
				[
					'methods'             => 'PUT',
					'callback'            => [ __CLASS__, 'put_tokens' ],
					'permission_callback' => $auth,
				],
			]
		);

		register_rest_route(
			$ns,
			'/builder/catalog/sections',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'catalog_sections' ],
				'permission_callback' => $auth,
			]
		);

		register_rest_route(
			$ns,
			'/builder/catalog/components',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'catalog_components' ],
				'permission_callback' => $auth,
			]
		);

		register_rest_route(
			$ns,
			'/builder/catalog/node-types',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'catalog_node_types' ],
				'permission_callback' => $auth,
			]
		);

		register_rest_route(
			$ns,
			'/builder/catalog/interactions',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'catalog_interactions' ],
				'permission_callback' => $auth,
			]
		);

		register_rest_route(
			$ns,
			'/builder/catalog/dynamics',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'catalog_dynamics' ],
				'permission_callback' => $auth,
			]
		);

		register_rest_route(
			$ns,
			'/builder/assets',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'list_assets' ],
				'permission_callback' => $auth,
			]
		);

		register_rest_route(
			$ns,
			'/builder/compile/(?P<id>[a-z0-9_\-]+)',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'compile' ],
				'permission_callback' => $auth,
			]
		);

		register_rest_route(
			$ns,
			'/builder/migrate',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'migrate' ],
				'permission_callback' => $auth,
			]
		);

		register_rest_route(
			$ns,
			'/builder/chrome',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'create_chrome' ],
				'permission_callback' => $auth,
			]
		);

		register_rest_route(
			$ns,
			'/builder/host',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'host_status' ],
				'permission_callback' => $auth,
			]
		);
	}

	/**
	 * @return bool
	 */
	public static function can_edit() {
		return NGC_Visual_Builder::can_edit();
	}

	/**
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public static function list_documents( $req ) {
		$rows = NGC_Builder_Repository::list_documents(
			[
				'kind'   => (string) $req->get_param( 'kind' ),
				'status' => (string) $req->get_param( 'status' ),
			]
		);
		return rest_ensure_response( [ 'items' => $rows ] );
	}

	/**
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_document( $req ) {
		$body = $req->get_json_params();
		if ( ! is_array( $body ) ) {
			$body = [];
		}
		if ( ! empty( $body['kind'] ) && in_array( $body['kind'], [ 'header', 'footer', 'popup', 'mega_menu', 'template', 'reusable' ], true ) ) {
			return rest_ensure_response( NGC_Builder_Interactions::create_chrome( $body['kind'], $body['title'] ?? '' ) );
		}
		$id  = sanitize_key( (string) ( $body['id'] ?? ( 'doc_' . wp_generate_password( 8, false, false ) ) ) );
		$doc = isset( $body['document'] ) && is_array( $body['document'] )
			? $body['document']
			: NGC_Builder_Document::blank( $id, $body['title'] ?? 'Untitled' );
		$saved = NGC_Builder_Repository::save( $doc, [ 'title' => $body['title'] ?? ( $doc['meta']['title'] ?? $id ) ] );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		return rest_ensure_response( $saved );
	}

	/**
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_document( $req ) {
		$row = NGC_Builder_Repository::get_by_key( (string) $req['id'] );
		if ( ! $row ) {
			return new WP_Error( 'ngc_builder_missing', __( 'Document not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
		}
		return rest_ensure_response( $row );
	}

	/**
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function put_document( $req ) {
		$body = $req->get_json_params();
		if ( ! is_array( $body ) || empty( $body['document'] ) || ! is_array( $body['document'] ) ) {
			return new WP_Error( 'ngc_builder_body', __( 'document object required.', 'nextgencompanion' ), [ 'status' => 400 ] );
		}
		$doc = $body['document'];
		$doc['id'] = sanitize_key( (string) $req['id'] );
		$host = NGC_Visual_Builder::host();
		if ( ! $host ) {
			return new WP_Error( 'ngc_builder_readonly', __( 'Theme host missing — editor is read-only.', 'nextgencompanion' ), [ 'status' => 423 ] );
		}
		$saved = NGC_Builder_Repository::save(
			$doc,
			[
				'title'     => $body['title'] ?? ( $doc['meta']['title'] ?? $doc['id'] ),
				'status'    => $body['status'] ?? 'draft',
				'wp_post_id'=> $body['wp_post_id'] ?? ( $doc['wpPostId'] ?? 0 ),
			]
		);
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		return rest_ensure_response( $saved );
	}

	/**
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function publish_document( $req ) {
		$saved = NGC_Builder_Repository::publish( (string) $req['id'] );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		return rest_ensure_response( $saved );
	}

	/**
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function list_revisions( $req ) {
		$row = NGC_Builder_Repository::get_by_key( (string) $req['id'] );
		if ( ! $row ) {
			return new WP_Error( 'ngc_builder_missing', __( 'Document not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
		}
		return rest_ensure_response( [ 'items' => NGC_Builder_Repository::list_revisions( (int) $row['id'] ) ] );
	}

	/**
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function restore_revision( $req ) {
		$saved = NGC_Builder_Repository::restore_revision( (int) $req['id'] );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		return rest_ensure_response( $saved );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function get_tokens() {
		return rest_ensure_response(
			[
				'tokens'  => NGC_Builder_Tokens::all(),
				'overlay' => NGC_Builder_Tokens::overlay(),
				'css'     => NGC_Builder_Tokens::to_css(),
			]
		);
	}

	/**
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public static function put_tokens( $req ) {
		$body = $req->get_json_params();
		$overlay = is_array( $body['overlay'] ?? null ) ? $body['overlay'] : ( is_array( $body ) ? $body : [] );
		unset( $overlay['tokens'], $overlay['css'] );
		$merged = NGC_Builder_Tokens::save_overlay( $overlay );
		return rest_ensure_response(
			[
				'tokens'  => $merged,
				'overlay' => NGC_Builder_Tokens::overlay(),
				'css'     => NGC_Builder_Tokens::to_css( $merged ),
			]
		);
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function catalog_sections() {
		$host = NGC_Visual_Builder::host();
		$sections = $host ? array_values( $host->sections() ) : [];
		return rest_ensure_response( [ 'items' => $sections, 'host' => NGC_Visual_Builder::host_status() ] );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function catalog_components() {
		$items = [];
		$path  = trailingslashit( dirname( NGC_PLUGIN_DIR ) ) . 'ui-library/catalog/components.json';
		if ( is_readable( $path ) ) {
			$data = json_decode( (string) file_get_contents( $path ), true );
			if ( is_array( $data ) ) {
				$items = isset( $data['components'] ) ? $data['components'] : $data;
			}
		}
		return rest_ensure_response( [ 'items' => $items ] );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function catalog_node_types() {
		return rest_ensure_response(
			[
				'types'    => NGC_Builder_Registry::node_types(),
				'controls' => NGC_Builder_Registry::controls(),
			]
		);
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function catalog_interactions() {
		return rest_ensure_response( NGC_Builder_Interactions::catalog() );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function catalog_dynamics() {
		return rest_ensure_response( NGC_Builder_Dynamics::catalog() );
	}

	/**
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public static function list_assets( $req ) {
		$search = sanitize_text_field( (string) $req->get_param( 'search' ) );
		$q      = [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 40,
			'post_mime_type' => [ 'image', 'video', 'application/json' ],
		];
		if ( $search ) {
			$q['s'] = $search;
		}
		$query = new WP_Query( $q );
		$items = [];
		foreach ( $query->posts as $post ) {
			$items[] = [
				'id'       => $post->ID,
				'title'    => get_the_title( $post ),
				'url'      => wp_get_attachment_url( $post->ID ),
				'mime'     => get_post_mime_type( $post ),
				'thumb'    => wp_get_attachment_image_url( $post->ID, 'thumbnail' ),
			];
		}
		return rest_ensure_response( [ 'items' => $items ] );
	}

	/**
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function compile( $req ) {
		$body = $req->get_json_params();
		if ( is_array( $body ) && ! empty( $body['document'] ) && is_array( $body['document'] ) ) {
			return rest_ensure_response( NGC_Builder_Compiler::compile( $body['document'] ) );
		}
		$row = NGC_Builder_Repository::get_by_key( (string) $req['id'] );
		if ( ! $row ) {
			return new WP_Error( 'ngc_builder_missing', __( 'Document not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
		}
		return rest_ensure_response( NGC_Builder_Compiler::compile( $row['document'] ) );
	}

	/**
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public static function migrate( $req ) {
		$force = (bool) $req->get_param( 'force' );
		return rest_ensure_response( NGC_Builder_Migrator::migrate( $force ) );
	}

	/**
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_chrome( $req ) {
		$body = $req->get_json_params();
		$kind = sanitize_key( (string) ( $body['kind'] ?? '' ) );
		return rest_ensure_response( NGC_Builder_Interactions::create_chrome( $kind, $body['title'] ?? '' ) );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function host_status() {
		return rest_ensure_response( NGC_Visual_Builder::host_status() );
	}
}
