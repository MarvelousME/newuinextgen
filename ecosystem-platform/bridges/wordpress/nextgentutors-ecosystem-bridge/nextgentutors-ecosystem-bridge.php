<?php
/**
 * Plugin Name: NextGen Ecosystem Platform Bridge
 * Description: WordPress boundary — proxies tenant context to ecosystem-platform API (not Odoo directly).
 * Version: 0.1.0
 * Author: NextGen Tutors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NGT_ECOSYSTEM_BRIDGE_VERSION', '0.1.0' );
define( 'NGT_ECOSYSTEM_API_URL', getenv( 'ECOSYSTEM_PLATFORM_API_URL' ) ?: 'http://ecosystem-api:8790' );

/**
 * Register REST routes for ecosystem platform health (read-only).
 */
add_action( 'rest_api_init', static function () {
	register_rest_route(
		'ecosystem/v1',
		'/health',
		[
			'methods'             => 'GET',
			'permission_callback' => static function () {
				return current_user_can( 'manage_options' );
			},
			'callback'            => static function () {
				$url = rtrim( NGT_ECOSYSTEM_API_URL, '/' ) . '/health';
				$res = wp_remote_get( $url, [ 'timeout' => 10 ] );
				if ( is_wp_error( $res ) ) {
					return new WP_REST_Response( [ 'status' => 'UNHEALTHY', 'error' => $res->get_error_message() ], 503 );
				}
				$body = json_decode( wp_remote_retrieve_body( $res ), true );
				return new WP_REST_Response( $body ?: [ 'status' => 'UNKNOWN' ], 200 );
			},
		]
	);
} );

/**
 * Companion integration hook — emit domain events to platform when bridge active.
 */
add_action( 'ngc_domain_event', static function ( $event ) {
	if ( ! is_array( $event ) ) {
		return;
	}
	// Future: POST to ecosystem event ingress with tenant + correlation headers.
}, 10, 1 );
