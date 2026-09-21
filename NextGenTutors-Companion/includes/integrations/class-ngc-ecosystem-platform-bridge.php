<?php
/**
 * Ecosystem-platform bridge — tenant context and API health without Odoo leakage to theme.
 *
 * Talks only to ecosystem-platform HTTP API (ECOSYSTEM_PLATFORM_API_URL).
 * Must never call Odoo / XML-RPC directly; business data stays behind platform-api.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bridge Companion domain events to ecosystem-platform control plane.
 */
class NGC_Ecosystem_Platform_Bridge {

	/**
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest' ] );
		add_action( 'ngc_domain_event', [ __CLASS__, 'forward_domain_event' ], 20, 1 );
	}

	/**
	 * @return string
	 */
	public static function api_url() {
		if ( defined( 'ECOSYSTEM_PLATFORM_API_URL' ) ) {
			return (string) ECOSYSTEM_PLATFORM_API_URL;
		}
		$env = getenv( 'ECOSYSTEM_PLATFORM_API_URL' );
		return is_string( $env ) && $env !== '' ? $env : 'http://localhost:8790';
	}

	/**
	 * @return void
	 */
	public static function register_rest() {
		register_rest_route(
			'ecosystem/v1',
			'/platform-health',
			[
				'methods'             => 'GET',
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'callback'            => static function () {
					$url  = rtrim( self::api_url(), '/' ) . '/health';
					$res  = wp_remote_get( $url, [ 'timeout' => 8 ] );
					if ( is_wp_error( $res ) ) {
						return new WP_REST_Response(
							[ 'status' => 'UNHEALTHY', 'error' => $res->get_error_message() ],
							503
						);
					}
					$body = json_decode( wp_remote_retrieve_body( $res ), true );
					return new WP_REST_Response( is_array( $body ) ? $body : [ 'status' => 'UNKNOWN' ], 200 );
				},
			]
		);
	}

	/**
	 * @param array<string, mixed> $event
	 * @return void
	 */
	public static function forward_domain_event( $event ) {
		if ( ! is_array( $event ) ) {
			return;
		}
		// Ingress stub — platform event bus will consume via signed webhook in production.
		do_action( 'ngc_ecosystem_event_enqueued', $event );
	}
}
