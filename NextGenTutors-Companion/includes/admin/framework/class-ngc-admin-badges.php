<?php
/**
 * Menu notification badges — real-time counts via heartbeat.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes badge counts for the admin shell.
 */
final class NGC_Admin_Badges {

	/**
	 * Init.
	 */
	public static function init() {
		add_filter( 'heartbeat_received', [ __CLASS__, 'heartbeat' ], 10, 2 );
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * @param array<string, mixed> $response Response.
	 * @param array<string, mixed> $data     Data.
	 * @return array<string, mixed>
	 */
	public static function heartbeat( $response, $data ) {
		if ( empty( $data['ngt_admin_badges'] ) ) {
			return $response;
		}
		$response['ngt_admin_badges'] = self::all();
		return $response;
	}

	/**
	 * REST.
	 */
	public static function register_routes() {
		register_rest_route(
			'ngc/v1',
			'/admin/badges',
			[
				'methods'             => 'GET',
				'callback'            => static function () {
					return new WP_REST_Response( self::all(), 200 );
				},
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
	}

	/**
	 * @return array<string, int>
	 */
	public static function all() {
		$keys = [ 'tutor_applications', 'errors', 'ai_approvals', 'workflow_retries', 'safeguarding', 'fraud' ];
		$out  = [];
		foreach ( $keys as $key ) {
			$out[ $key ] = NGC_Admin_Registry::badge_count( $key );
		}
		return $out;
	}
}
