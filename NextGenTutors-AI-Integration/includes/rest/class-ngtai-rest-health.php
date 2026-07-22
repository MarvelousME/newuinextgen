<?php
/**
 * Public health endpoint.
 *
 * @package NextGenTutorsAIIntegration
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
final class NGTAI_Rest_Health {
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register' ] );
	}
	public static function register() {
		register_rest_route(
			'ngtai/v1',
			'/health',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => static function () {
					return rest_ensure_response( NGTAI_Health::public_health() );
				},
				'permission_callback' => '__return_true',
			]
		);
	}
}
