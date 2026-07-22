<?php
/**
 * Signed machine callback routes.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGTAI_Rest_Callbacks extends NGTAI_Rest {
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register' ] );
	}

	public static function register() {
		foreach ( [ 'agent-result', 'health-ping', 'integration-result', 'approval-request', 'notification-status' ] as $name ) {
			register_rest_route(
				'ngtai/v1',
				'/callbacks/' . $name,
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, str_replace( '-', '_', $name ) ],
					// Guard is deliberately the first callback operation; see NGTAI_Rest::machine_guard().
					'permission_callback' => '__return_true',
				]
			);
		}
	}

	private static function begin( WP_REST_Request $request, $name ) {
		$path  = '/wp-json/ngtai/v1/callbacks/' . $name;
		$guard = self::machine_guard( $request, $path );
		if ( 'duplicate' === $guard ) {
			return new WP_REST_Response( [ 'success' => true, 'idempotent' => true ], 200 );
		}
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		update_option( 'ngtai_last_callback', current_time( 'mysql', true ), false );
		$body = json_decode( self::read_raw_body( $request ), true );
		return is_array( $body ) ? $body : new WP_Error( 'ngtai_invalid_json', __( 'Invalid JSON body.', 'nextgentutors-ai-integration' ), [ 'status' => 400 ] );
	}

	private static function headers( WP_REST_Request $request ) {
		return NGTAI_Signature::normalize_headers( $request->get_headers() );
	}

	public static function agent_result( WP_REST_Request $request ) {
		$body = self::begin( $request, 'agent-result' );
		if ( $body instanceof WP_REST_Response || is_wp_error( $body ) ) {
			return $body;
		}
		return rest_ensure_response( NGTAI_Callback_Controller::handle_agent_result( $body, self::headers( $request ) ) );
	}

	public static function health_ping( WP_REST_Request $request ) {
		$body = self::begin( $request, 'health-ping' );
		if ( $body instanceof WP_REST_Response || is_wp_error( $body ) ) {
			return $body;
		}
		$headers = self::headers( $request );
		update_option( 'ngtai_last_agents_ping', current_time( 'mysql', true ), false );
		NGTAI_Audit::log( 'ngtai_callback_received', [ 'kind' => 'health_ping' ], (string) ( $headers['x-ngt-correlation-id'] ?? '' ) );
		return rest_ensure_response( [ 'success' => true ] );
	}

	public static function integration_result( WP_REST_Request $request ) {
		$body = self::begin( $request, 'integration-result' );
		if ( $body instanceof WP_REST_Response || is_wp_error( $body ) ) {
			return $body;
		}
		$body['action_name'] = 'integration.sync';
		try {
			$result = NGTAI_Result_Repository::store( new NGTAI_Agent_Result( $body ) );
		} catch ( InvalidArgumentException $exception ) {
			return new WP_Error( 'ngtai_invalid_result', $exception->getMessage(), [ 'status' => 422 ] );
		}
		NGTAI_Audit::log( 'ngtai_callback_received', [ 'kind' => 'integration_result', 'result' => $result ], (string) ( self::headers( $request )['x-ngt-correlation-id'] ?? '' ) );
		return rest_ensure_response( [ 'success' => true, 'idempotent' => 'duplicate' === $result ] );
	}

	public static function approval_request( WP_REST_Request $request ) {
		$body = self::begin( $request, 'approval-request' );
		if ( $body instanceof WP_REST_Response || is_wp_error( $body ) ) {
			return $body;
		}
		return rest_ensure_response( NGTAI_Callback_Controller::handle_approval_request( $body, self::headers( $request ) ) );
	}

	public static function notification_status( WP_REST_Request $request ) {
		$body = self::begin( $request, 'notification-status' );
		if ( $body instanceof WP_REST_Response || is_wp_error( $body ) ) {
			return $body;
		}
		NGTAI_Audit::log( 'ngtai_callback_received', [ 'kind' => 'notification_status', 'status' => $body['status'] ?? '', 'provider_reference' => $body['provider_reference'] ?? '' ] );
		return rest_ensure_response( [ 'success' => true ] );
	}
}
