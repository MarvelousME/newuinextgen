<?php
/**
 * WordPress → Agent Gateway signed client.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin HTTP client to services/ngt-agent-gateway.
 */
final class NGC_Agent_Gateway_Client {

	/**
	 * @return string
	 */
	public static function base_url() {
		if ( defined( 'NGT_AGENT_GATEWAY_URL' ) && NGT_AGENT_GATEWAY_URL ) {
			return untrailingslashit( (string) NGT_AGENT_GATEWAY_URL );
		}
		return 'http://127.0.0.1:8787';
	}

	/**
	 * @return string
	 */
	public static function shared_secret() {
		return defined( 'NGT_GATEWAY_SHARED_SECRET' ) ? (string) NGT_GATEWAY_SHARED_SECRET : '';
	}

	/**
	 * @return array<string, mixed>|WP_Error
	 */
	public static function health() {
		$url = self::base_url() . '/health';
		$res = wp_remote_get( $url, [ 'timeout' => 5, 'redirection' => 0 ] );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( 200 !== $code || ! is_array( $body ) ) {
			return new WP_Error( 'ngc_gateway_health', __( 'Agent Gateway health check failed.', 'nextgencompanion' ), [ 'status' => $code ] );
		}
		return $body;
	}

	/**
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function submit_task( array $payload ) {
		$path = '/v1/tasks';
		return self::signed_post( $path, $payload );
	}

	/**
	 * Discover MCP capabilities via Agent Gateway (always returns approved=false).
	 *
	 * @param string $endpoint Remote or local MCP endpoint URL.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function mcp_discover( $endpoint ) {
		return self::signed_post(
			'/v1/mcp/discover',
			[ 'endpoint' => (string) $endpoint ]
		);
	}

	/**
	 * Execute an allowlisted MCP tool after human capability approval.
	 *
	 * @param string               $endpoint MCP endpoint.
	 * @param string               $tool     Tool name.
	 * @param array<string, mixed> $args     Args.
	 * @param bool                 $approved Must be true (capability approval gate).
	 * @return array<string, mixed>|WP_Error
	 */
	public static function mcp_execute( $endpoint, $tool, array $args = [], $approved = false ) {
		if ( ! $approved ) {
			return new WP_Error( 'ngc_mcp_not_approved', __( 'MCP tool capability is not approved.', 'nextgencompanion' ) );
		}
		return self::signed_post(
			'/v1/mcp/execute',
			[
				'endpoint'      => (string) $endpoint,
				'tool'          => (string) $tool,
				'args'          => $args,
				'tool_approved' => true,
			]
		);
	}

	/**
	 * @param string               $path Path.
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function signed_post( $path, array $payload ) {
		$secret = self::shared_secret();
		if ( '' === $secret ) {
			return new WP_Error( 'ngc_gateway_secret', __( 'NGT_GATEWAY_SHARED_SECRET is not configured.', 'nextgencompanion' ) );
		}
		$ts   = (string) (int) round( microtime( true ) * 1000 );
		$sig  = hash_hmac( 'sha256', $ts . '.POST.' . $path, $secret );
		$url  = self::base_url() . $path;
		$res  = wp_remote_post(
			$url,
			[
				'timeout'     => 20,
				'redirection' => 0,
				'headers'     => [
					'Content-Type'    => 'application/json',
					'X-NGT-Timestamp' => $ts,
					'X-NGT-Signature' => $sig,
				],
				'body'        => wp_json_encode( $payload ),
			]
		);
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $body ) ) {
			return new WP_Error( 'ngc_gateway_bad_json', __( 'Invalid gateway response.', 'nextgencompanion' ), [ 'status' => $code ] );
		}
		if ( $code >= 400 ) {
			return new WP_Error( 'ngc_gateway_http', (string) ( $body['error'] ?? 'gateway_error' ), [ 'status' => $code, 'body' => $body ] );
		}
		return $body;
	}
}
