<?php
/**
 * Signed agents API client.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bounded, signed WordPress HTTP transport.
 */
final class NGTAI_Api_Client {

	/**
	 * @param string                   $method HTTP method.
	 * @param string                   $path API path.
	 * @param array<string,mixed>|null $payload_or_null Payload.
	 * @param string                   $idempotency_key Idempotency key.
	 * @param string                   $correlation_id Correlation ID.
	 * @return array<string,mixed>
	 */
	public static function request( $method, $path, $payload_or_null, $idempotency_key, $correlation_id ) {
		if ( ! NGTAI_Config::configured() ) {
			return self::failure( 'agents_api_unconfigured', true );
		}
		if ( function_exists( 'get_transient' ) && get_transient( 'ngtai_circuit_open' ) ) {
			return self::failure( 'circuit_open', true );
		}

		$base   = method_exists( 'NGTAI_Config', 'url' ) ? NGTAI_Config::url() : NGTAI_Config::endpoint();
		$parts  = function_exists( 'wp_parse_url' ) ? wp_parse_url( $base ) : parse_url( $base );
		$host   = strtolower( (string) ( $parts['host'] ?? '' ) );
		$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
		$local  = in_array( $host, [ 'localhost', '127.0.0.1' ], true );
		$dev    = NGTAI_Config::demo_mode() || ( defined( 'NGTAI_ALLOW_INSECURE_DEV' ) && NGTAI_ALLOW_INSECURE_DEV );
		if ( 'https' !== $scheme && ! ( $local && $dev ) ) {
			return self::failure( 'https_required', false );
		}
		$allowed = array_map( 'strtolower', (array) NGTAI_Config::allowed_hosts() );
		if ( ! in_array( $host, $allowed, true ) ) {
			return self::failure( 'host_not_allowlisted', false );
		}

		$raw_body = null === $payload_or_null ? '' : wp_json_encode( $payload_or_null );
		if ( false === $raw_body ) {
			return self::failure( 'json_encode_failed', false );
		}
		$headers = NGTAI_Signature::build_headers(
			strtoupper( $method ),
			$path,
			$raw_body,
			[ 'idempotency_key' => $idempotency_key, 'correlation_id' => $correlation_id ]
		);
		$headers['User-Agent'] = 'NextGenTutors-AI-Integration/' . ( defined( 'NGTAI_VERSION' ) ? NGTAI_VERSION : 'unknown' );
		$response = wp_remote_request(
			rtrim( $base, '/' ) . '/' . ltrim( $path, '/' ),
			[
				'method'             => strtoupper( $method ),
				'timeout'            => NGTAI_Config::timeout(),
				'redirection'        => 0,
				'reject_unsafe_urls' => true,
				'sslverify'          => true,
				'headers'            => $headers,
				'body'               => $raw_body,
			]
		);
		if ( is_wp_error( $response ) ) {
			self::record_retryable_failure();
			self::log( $method, $path, 0, false, $correlation_id );
			return self::failure( 'connection_error', true );
		}

		$status    = (int) wp_remote_retrieve_response_code( $response );
		$raw       = (string) wp_remote_retrieve_body( $response );
		$decoded   = json_decode( $raw, true );
		$body      = JSON_ERROR_NONE === json_last_error() ? $decoded : $raw;
		$retryable = in_array( $status, [ 408, 429, 500, 502, 503, 504 ], true );
		$ok        = $status >= 200 && $status < 300;
		if ( $ok ) {
			self::clear_failures();
		} elseif ( $retryable ) {
			self::record_retryable_failure();
		}
		$result = [ 'ok' => $ok, 'status' => $status, 'body' => $body, 'retryable' => $retryable ];
		if ( ! $ok ) {
			$result['error'] = 'http_' . $status;
		}
		if ( 429 === $status ) {
			$header = wp_remote_retrieve_header( $response, 'retry-after' );
			$result['retry_after'] = self::retry_after_seconds( $header );
		}
		self::log( $method, $path, $status, $ok, $correlation_id );
		return $result;
	}

	/** @param NGTAI_Event_Envelope $e Event. @param string $idempotency_key Key. @param string $correlation_id Correlation. @return array<string,mixed> */
	public static function post_event( NGTAI_Event_Envelope $e, $idempotency_key, $correlation_id = '' ) {
		$correlation_id = '' !== $correlation_id ? $correlation_id : (string) $e->get( 'correlation_id' );
		return self::request( 'POST', '/v1/events', $e->to_array(), $idempotency_key, $correlation_id );
	}

	/** @param array<string,mixed> $task Task. @return array<string,mixed> */
	public static function post_task( array $task ) {
		$correlation = (string) ( $task['correlation_id'] ?? NGTAI_Signature::uuid() );
		$idempotency = 'task:' . (string) ( $task['task_id'] ?? hash( 'sha256', wp_json_encode( $task ) ) );
		return self::request( 'POST', '/v1/agents/tasks', $task, $idempotency, $correlation );
	}

	/** @return array<string,mixed> */
	public static function health() {
		return self::request( 'GET', '/v1/health', null, 'health:' . gmdate( 'YmdHi' ), NGTAI_Signature::uuid() );
	}

	/** @param string $error Error. @param bool $retryable Retryable. @return array<string,mixed> */
	private static function failure( $error, $retryable ) {
		return [ 'ok' => false, 'status' => 0, 'body' => null, 'error' => $error, 'retryable' => $retryable ];
	}

	/** @return void */
	private static function record_retryable_failure() {
		if ( ! function_exists( 'get_transient' ) || ! function_exists( 'set_transient' ) ) {
			return;
		}
		$count = (int) get_transient( 'ngtai_circuit_failures' ) + 1;
		set_transient( 'ngtai_circuit_failures', $count, 300 );
		if ( $count >= 5 ) {
			set_transient( 'ngtai_circuit_open', 1, 60 );
		}
	}

	/** @return void */
	private static function clear_failures() {
		if ( function_exists( 'delete_transient' ) ) {
			delete_transient( 'ngtai_circuit_failures' );
			delete_transient( 'ngtai_circuit_open' );
		}
	}

	/** @param mixed $value Header. @return int */
	private static function retry_after_seconds( $value ) {
		if ( is_numeric( $value ) ) {
			return max( 0, (int) $value );
		}
		$timestamp = strtotime( (string) $value );
		return false === $timestamp ? 0 : max( 0, $timestamp - time() );
	}

	/** @param string $method Method. @param string $path Path. @param int $status Status. @param bool $ok Success. @param string $correlation Correlation. @return void */
	private static function log( $method, $path, $status, $ok, $correlation ) {
		if ( class_exists( 'NGTAI_Logger' ) ) {
			NGTAI_Logger::log( $ok ? 'info' : 'warning', 'api_client', 'request', [ 'method' => strtoupper( $method ), 'path' => $path, 'status' => $status, 'ok' => $ok, 'correlation_id' => $correlation ] );
		}
	}
}
