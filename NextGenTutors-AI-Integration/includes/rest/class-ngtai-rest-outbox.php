<?php
/**
 * Outbox operations endpoint.
 *
 * @package NextGenTutorsAIIntegration
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
final class NGTAI_Rest_Outbox extends NGTAI_Rest {
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register' ] );
	}
	public static function register() {
		register_rest_route(
			'ngtai/v1',
			'/events/outbox-status',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => static function () {
					return rest_ensure_response(
						[
							'counts'        => NGTAI_Delivery_Repository::counts(),
							'last_delivery' => get_option( 'ngtai_last_delivery', null ),
							'last_callback' => get_option( 'ngtai_last_callback', null ),
						]
					);
				},
				'permission_callback' => [ __CLASS__, 'admin_guard' ],
			]
		);
	}
}
