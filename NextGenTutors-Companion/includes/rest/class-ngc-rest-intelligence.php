<?php
/**
 * REST API — operational intelligence platform.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Intelligence REST routes consumed by Mission Control.
 */
class NGC_Rest_Intelligence {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/dashboard',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'dashboard' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/events',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'events' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				'args'                => [
					'page'        => [ 'type' => 'integer', 'default' => 1 ],
					'per_page'    => [ 'type' => 'integer', 'default' => 25 ],
					'domain'      => [ 'type' => 'string' ],
					'plugin_slug' => [ 'type' => 'string' ],
					'module'      => [ 'type' => 'string' ],
					'feature'     => [ 'type' => 'string' ],
					'severity'    => [ 'type' => 'string' ],
					'event_key'   => [ 'type' => 'string' ],
					'search'      => [ 'type' => 'string' ],
					'since_id'    => [ 'type' => 'integer' ],
				],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/notifications',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'notifications' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				'args'                => [
					'status' => [ 'type' => 'string', 'default' => 'open' ],
					'limit'  => [ 'type' => 'integer', 'default' => 30 ],
				],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/notifications/(?P<id>\d+)/ack',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'ack_notification' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/stream',
			[
				'methods'             => 'GET',
				'callback'            => [ NGC_Intelligence_Stream::class, 'rest_stream' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				'args'                => [
					'since'   => [ 'type' => 'integer', 'default' => 0 ],
					'timeout' => [ 'type' => 'integer', 'default' => 30 ],
				],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/registry',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'registry' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/drill/(?P<level>[a-z]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'drill' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/config',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ __CLASS__, 'get_config' ],
					'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				],
				[
					'methods'             => 'POST',
					'callback'            => [ __CLASS__, 'save_config' ],
					'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/emit',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'emit' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/insights',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'insights' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/insights/ask',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'ask' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/health',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'health' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/events/export',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'export_events' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/audit',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'audit_log' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/visualizations',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'visualizations' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/intelligence/layout',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ __CLASS__, 'get_layout' ],
					'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				],
				[
					'methods'             => 'POST',
					'callback'            => [ __CLASS__, 'save_layout' ],
					'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
				],
			]
		);

		add_filter( 'rest_pre_serve_request', [ __CLASS__, 'serve_sse' ], 0, 4 );
		add_filter( 'rest_pre_serve_request', [ __CLASS__, 'serve_csv' ], 0, 4 );
	}

	/**
	 * Serve SSE stream without JSON wrapping.
	 *
	 * @param bool             $served  Served.
	 * @param WP_REST_Response $result  Result.
	 * @param WP_REST_Request  $request Request.
	 * @param WP_REST_Server   $server  Server.
	 * @return bool
	 */
	public static function serve_sse( $served, $result, $request, $server ) {
		unset( $server, $result );
		$route = method_exists( $request, 'get_route' ) ? $request->get_route() : '';
		if ( '/ngc/v1/intelligence/stream' !== $route ) {
			return $served;
		}
		NGC_Intelligence_Stream::rest_stream( $request );
		return true;
	}

	/**
	 * Serve CSV export without JSON wrapping.
	 *
	 * @param bool             $served  Served.
	 * @param WP_REST_Response $result  Result.
	 * @param WP_REST_Request  $request Request.
	 * @param WP_REST_Server   $server  Server.
	 * @return bool
	 */
	public static function serve_csv( $served, $result, $request, $server ) {
		unset( $server );
		$route = method_exists( $request, 'get_route' ) ? $request->get_route() : '';
		if ( '/ngc/v1/intelligence/events/export' !== $route ) {
			return $served;
		}
		$data = $result->get_data();
		if ( ! is_string( $data ) ) {
			return $served;
		}
		$server->send_headers( $result );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV download.
		echo $data;
		return true;
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function dashboard( $request ) {
		unset( $request );
		return new WP_REST_Response(
			NGC_Intelligence_Kpi_Engine::executive_dashboard(),
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function events( $request ) {
		return new WP_REST_Response(
			NGC_Intelligence_Event_Bus::query(
				[
					'page'        => (int) $request->get_param( 'page' ),
					'per_page'    => (int) $request->get_param( 'per_page' ),
					'domain'      => $request->get_param( 'domain' ),
					'plugin_slug' => $request->get_param( 'plugin_slug' ),
					'module'        => $request->get_param( 'module' ),
					'feature'       => $request->get_param( 'feature' ),
					'severity'    => $request->get_param( 'severity' ),
					'event_key'   => $request->get_param( 'event_key' ),
					'search'      => $request->get_param( 'search' ),
					'since_id'    => (int) $request->get_param( 'since_id' ),
				]
			),
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function visualizations( $request ) {
		unset( $request );
		return new WP_REST_Response( NGC_Intelligence_Visualizations::all(), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_layout( $request ) {
		unset( $request );
		return new WP_REST_Response(
			[ 'widgets' => NGC_Intelligence_Layout::get() ],
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function save_layout( $request ) {
		$body = $request->get_json_params();
		$list = is_array( $body ) && isset( $body['widgets'] ) ? $body['widgets'] : $body;
		if ( ! is_array( $list ) ) {
			$list = [];
		}
		return new WP_REST_Response(
			[ 'widgets' => NGC_Intelligence_Layout::save( $list ) ],
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function export_events( $request ) {
		$csv = NGC_Intelligence_Event_Bus::export_csv(
			[
				'domain'      => $request->get_param( 'domain' ),
				'plugin_slug' => $request->get_param( 'plugin_slug' ),
				'severity'    => $request->get_param( 'severity' ),
				'event_key'   => $request->get_param( 'event_key' ),
				'search'      => $request->get_param( 'search' ),
				'per_page'    => (int) $request->get_param( 'limit' ),
			]
		);
		$response = new WP_REST_Response( $csv, 200 );
		$response->header( 'Content-Type', 'text/csv; charset=utf-8' );
		$response->header( 'Content-Disposition', 'attachment; filename="ngc-intelligence-events.csv"' );
		return $response;
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function health( $request ) {
		unset( $request );
		return new WP_REST_Response( NGC_Intelligence_Health::matrix(), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function audit_log( $request ) {
		$limit = min( 100, max( 5, (int) $request->get_param( 'limit' ) ) );
		return new WP_REST_Response(
			[ 'rows' => NGC_Intelligence_Audit::recent( $limit ) ],
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function notifications( $request ) {
		return new WP_REST_Response(
			NGC_Intelligence_Notifications::list(
				[
					'status' => (string) $request->get_param( 'status' ),
					'limit'  => (int) $request->get_param( 'limit' ),
				]
			),
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function ack_notification( $request ) {
		$id = (int) $request->get_param( 'id' );
		if ( $id <= 0 ) {
			return new WP_Error( 'ngc_intel_bad_id', 'Invalid notification id', [ 'status' => 400 ] );
		}
		$ok = NGC_Intelligence_Notifications::acknowledge( $id );
		return new WP_REST_Response( [ 'ok' => $ok, 'id' => $id ], $ok ? 200 : 404 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function registry( $request ) {
		unset( $request );
		return new WP_REST_Response( NGC_Intelligence_Registry::all(), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function drill( $request ) {
		$level = sanitize_key( (string) $request->get_param( 'level' ) );
		$ctx   = [
			'domain'      => sanitize_key( (string) $request->get_param( 'domain' ) ),
			'plugin_slug' => sanitize_key( (string) $request->get_param( 'plugin_slug' ) ),
			'event_key'   => sanitize_key( (string) $request->get_param( 'event_key' ) ),
			'severity'    => sanitize_key( (string) $request->get_param( 'severity' ) ),
		];
		return new WP_REST_Response(
			NGC_Intelligence_Kpi_Engine::drill_down( $level, $ctx ),
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_config( $request ) {
		unset( $request );
		return new WP_REST_Response( NGC_Intelligence_Config::get_for_api(), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function save_config( $request ) {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			$body = [];
		}
		NGC_Intelligence_Config::save( $body );
		return new WP_REST_Response( NGC_Intelligence_Config::get_for_api(), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function emit( $request ) {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			return new WP_Error( 'ngc_intel_bad_body', 'JSON body required', [ 'status' => 400 ] );
		}
		$body['force'] = true;
		$id = NGC_Intelligence::emit( $body );
		return new WP_REST_Response( [ 'id' => $id ], $id > 0 ? 201 : 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function insights( $request ) {
		unset( $request );
		return new WP_REST_Response( NGC_Intelligence_Ai::executive_brief(), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function ask( $request ) {
		$body = $request->get_json_params();
		$q    = is_array( $body ) ? (string) ( $body['question'] ?? '' ) : '';
		return new WP_REST_Response(
			NGC_Intelligence_Ai::answer( $q ),
			200
		);
	}
}
