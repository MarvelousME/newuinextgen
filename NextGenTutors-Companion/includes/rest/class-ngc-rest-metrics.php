<?php
/**
 * REST metrics export for external APM scrapers (OBS-001).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prometheus + JSON metrics endpoints.
 */
class NGC_Rest_Metrics {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/metrics',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'prometheus' ],
				'permission_callback' => [ __CLASS__, 'permission' ],
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/metrics/json',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'json' ],
				'permission_callback' => [ __CLASS__, 'permission' ],
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/metrics/push',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'push_now' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public static function permission( $request ) {
		if ( class_exists( 'NGC_Metrics' ) && NGC_Metrics::authorize_request( $request ) ) {
			return true;
		}
		return new WP_Error(
			'ngc_metrics_forbidden',
			__( 'Metrics endpoint requires admin session or valid bearer token.', 'nextgencompanion' ),
			[ 'status' => 401 ]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function prometheus( $request ) {
		unset( $request );
		$text = class_exists( 'NGC_Metrics' ) ? NGC_Metrics::prometheus_text() : "ngc_up 0\n";
		$response = new WP_REST_Response( $text, 200 );
		$response->header( 'Content-Type', 'text/plain; version=0.0.4; charset=utf-8' );
		$response->header( 'Cache-Control', 'no-store' );
		add_filter( 'rest_pre_serve_request', [ __CLASS__, 'serve_prometheus_plain' ], 0, 4 );
		return $response;
	}

	/**
	 * Serve Prometheus body as raw text (bypass JSON encoding).
	 *
	 * @param bool             $served  Already served.
	 * @param WP_REST_Response $result  Result.
	 * @param WP_REST_Request  $request Request.
	 * @param WP_REST_Server   $server  Server.
	 * @return bool
	 */
	public static function serve_prometheus_plain( $served, $result, $request, $server ) {
		$route = method_exists( $request, 'get_route' ) ? $request->get_route() : '';
		if ( '/ngc/v1/metrics' !== $route ) {
			return $served;
		}
		$data = $result->get_data();
		if ( ! is_string( $data ) ) {
			return $served;
		}
		$server->send_headers( $result );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Prometheus text exposition.
		echo $data;
		return true;
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function json( $request ) {
		unset( $request );
		$snap = class_exists( 'NGC_Metrics' ) ? NGC_Metrics::snapshot() : [ 'error' => 'unavailable' ];
		return new WP_REST_Response( $snap, 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function push_now( $request ) {
		unset( $request );
		$result = class_exists( 'NGC_Metrics' ) ? NGC_Metrics::push_to_webhook() : [ 'ok' => false ];
		return new WP_REST_Response( $result, ! empty( $result['ok'] ) ? 200 : 502 );
	}
}
