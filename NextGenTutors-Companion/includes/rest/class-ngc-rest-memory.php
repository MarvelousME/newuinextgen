<?php
/**
 * REST API for Bridge memory (admin / agent).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ngc/v1/memory/* routes.
 */
class NGC_Rest_Memory {

	/**
	 * Register routes.
	 */
	public static function register() {
		$admin = [ __CLASS__, 'can_admin' ];
		$use   = [ __CLASS__, 'can_use' ];

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/memory/health',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'health' ],
				'permission_callback' => $admin,
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/memory/settings',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'settings_get' ],
				'permission_callback' => $admin,
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/memory/settings',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'settings_save' ],
				'permission_callback' => $admin,
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/memory/retrieve',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'retrieve' ],
				'permission_callback' => $use,
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/memory/write',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'write' ],
				'permission_callback' => $use,
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/memory/forget',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'forget' ],
				'permission_callback' => $admin,
			]
		);
	}

	/**
	 * @return bool
	 */
	public static function can_admin() {
		return current_user_can( 'manage_options' ) || current_user_can( 'ngc_manage_platform' );
	}

	/**
	 * @return bool
	 */
	public static function can_use() {
		return self::can_admin() || current_user_can( 'bia_ai_use' );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function health() {
		return rest_ensure_response( NGC_Memory_Service::health() );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function settings_get() {
		$cfg = NGC_Memory_Settings::get();
		// Never expose secret refs' plaintext; only presence.
		$cfg['gateway_bearer_present'] = '' !== (string) ( $cfg['gateway_bearer_ref'] ?? '' );
		$cfg['admin_user_key_present'] = '' !== (string) ( $cfg['admin_user_key_ref'] ?? '' );
		unset( $cfg['gateway_bearer'], $cfg['admin_user_key'] );
		$cfg['proxy_enabled'] = false;
		return rest_ensure_response( $cfg );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function settings_save( $request ) {
		$body = (array) $request->get_json_params();
		$patch = [];
		$bools = [
			'enabled',
			'retrieve_enabled',
			'write_enabled',
			'skills_enabled',
			'wiki_enabled',
			'codegraph_enabled',
			'allow_long_term_minors',
			'sqlite_ha_acknowledged',
		];
		foreach ( $bools as $k ) {
			if ( array_key_exists( $k, $body ) ) {
				$patch[ $k ] = (bool) $body[ $k ];
			}
		}
		if ( isset( $body['mode'] ) ) {
			$patch['mode'] = sanitize_text_field( (string) $body['mode'] );
		}
		if ( isset( $body['core_base_url'] ) ) {
			$patch['core_base_url'] = esc_url_raw( (string) $body['core_base_url'] );
		}
		if ( isset( $body['knowledge_base_url'] ) ) {
			$patch['knowledge_base_url'] = esc_url_raw( (string) $body['knowledge_base_url'] );
		}
		if ( isset( $body['service_id_strategy'] ) ) {
			$patch['service_id_strategy'] = sanitize_key( (string) $body['service_id_strategy'] );
		}
		if ( isset( $body['timeout_ms'] ) ) {
			$patch['timeout_ms'] = max( 500, (int) $body['timeout_ms'] );
		}
		if ( isset( $body['max_retrieve_items'] ) ) {
			$patch['max_retrieve_items'] = max( 1, (int) $body['max_retrieve_items'] );
		}
		if ( isset( $body['max_retrieve_chars'] ) ) {
			$patch['max_retrieve_chars'] = max( 256, (int) $body['max_retrieve_chars'] );
		}

		// Hard forbid enabling proxy as gateway.
		$patch['proxy_enabled'] = false;

		// Skills/Wiki/CodeGraph only via explicit flags (still default off).
		if ( ! empty( $body['gateway_bearer'] ) && class_exists( 'NGC_Secret_Vault' ) ) {
			$ref = NGC_Secret_Vault::store( (string) $body['gateway_bearer'], 'memory_gateway_bearer' );
			if ( ! is_wp_error( $ref ) ) {
				$patch['gateway_bearer_ref'] = $ref;
			}
		}
		if ( ! empty( $body['admin_user_key'] ) && class_exists( 'NGC_Secret_Vault' ) ) {
			$ref = NGC_Secret_Vault::store( (string) $body['admin_user_key'], 'memory_admin_user_key' );
			if ( ! is_wp_error( $ref ) ) {
				$patch['admin_user_key_ref'] = $ref;
			}
		}

		$cfg = NGC_Memory_Settings::update( $patch );
		NGC_Memory_Service::reset_provider();
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'memory_settings_updated', 'memory', 0, [ 'enabled' => ! empty( $cfg['enabled'] ), 'mode' => $cfg['mode'] ] );
		}
		return self::settings_get();
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function retrieve( $request ) {
		$body = (array) $request->get_json_params();
		$body['bridge_user_id'] = (string) ( $body['bridge_user_id'] ?? get_current_user_id() );
		return rest_ensure_response( NGC_Memory_Service::retrieve_safe( $body ) );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function write( $request ) {
		$body = (array) $request->get_json_params();
		$body['bridge_user_id'] = (string) ( $body['bridge_user_id'] ?? get_current_user_id() );
		if ( current_user_can( 'manage_options' ) ) {
			$body['admin_override'] = ! empty( $body['admin_override'] );
		} else {
			unset( $body['admin_override'] );
		}
		return rest_ensure_response( NGC_Memory_Service::write_safe( $body ) );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function forget( $request ) {
		$body   = (array) $request->get_json_params();
		$result = NGC_Memory_Service::provider()->forget( $body );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		return rest_ensure_response( $result );
	}
}
