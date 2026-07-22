<?php
/**
 * HMAC request signing and verification.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements the version-one agents API signature contract.
 */
final class NGTAI_Signature {

	/**
	 * Build the canonical signing string.
	 *
	 * @param string|int $timestamp   Unix timestamp.
	 * @param string     $nonce       Nonce.
	 * @param string     $method      HTTP method.
	 * @param string     $path        Request path.
	 * @param string     $body_sha256 Lowercase SHA-256 body digest.
	 * @return string
	 */
	public static function canonical( $timestamp, $nonce, $method, $path, $body_sha256 ) {
		return (string) $timestamp . "\n"
			. (string) $nonce . "\n"
			. strtoupper( (string) $method ) . "\n"
			. (string) $path . "\n"
			. strtolower( (string) $body_sha256 );
	}

	/**
	 * Hash a raw request body.
	 *
	 * @param string $raw Raw body.
	 * @return string
	 */
	public static function body_sha256( $raw ) {
		return hash( 'sha256', (string) $raw );
	}

	/**
	 * Sign a canonical string.
	 *
	 * @param string $canonical Canonical string.
	 * @param string $secret    Shared secret.
	 * @return string
	 */
	public static function sign( $canonical, $secret ) {
		return hash_hmac( 'sha256', (string) $canonical, (string) $secret );
	}

	/**
	 * Build signed outbound request headers.
	 *
	 * @param string               $method   HTTP method.
	 * @param string               $path     Request path.
	 * @param string               $raw_body Raw request body.
	 * @param array<string,string> $extra    Correlation and idempotency values.
	 * @return array<string,string>
	 */
	public static function build_headers( $method, $path, $raw_body, array $extra = [] ) {
		$timestamp       = (string) time();
		$nonce           = self::uuid();
		$body_hash       = self::body_sha256( $raw_body );
		$correlation_id  = ! empty( $extra['correlation_id'] ) ? (string) $extra['correlation_id'] : self::uuid();
		$request_id      = self::uuid();
		$idempotency_key = isset( $extra['idempotency_key'] ) ? (string) $extra['idempotency_key'] : '';
		if ( 'POST' === strtoupper( (string) $method ) && '' === $idempotency_key ) {
			$idempotency_key = self::uuid();
		}
		$canonical = self::canonical( $timestamp, $nonce, $method, $path, $body_hash );

		$headers = [
			'X-NGT-Timestamp'      => $timestamp,
			'X-NGT-Nonce'          => $nonce,
			'X-NGT-Signature'      => 'v1=' . self::sign( $canonical, NGTAI_Config::secret() ),
			'X-NGT-Key-Id'         => NGTAI_Config::key_id(),
			'X-NGT-Correlation-ID' => $correlation_id,
			'X-NGT-Request-ID'     => $request_id,
			'X-NGT-Body-SHA256'    => $body_hash,
			'Content-Type'         => 'application/json',
		];
		if ( '' !== $idempotency_key ) {
			$headers['Idempotency-Key'] = $idempotency_key;
		}
		return $headers;
	}

	/**
	 * Verify signed inbound request data without claiming the nonce.
	 *
	 * @param string               $method        HTTP method.
	 * @param string               $path          Request path.
	 * @param string               $raw_body      Raw request body.
	 * @param array<string,mixed>  $lower_headers Lowercase request headers.
	 * @return true|WP_Error
	 */
	public static function verify( $method, $path, $raw_body, array $lower_headers ) {
		$headers  = self::normalize_headers( $lower_headers );
		$required = [ 'x-ngt-timestamp', 'x-ngt-nonce', 'x-ngt-signature', 'x-ngt-key-id' ];
		foreach ( $required as $name ) {
			if ( ! isset( $headers[ $name ] ) || '' === trim( $headers[ $name ] ) ) {
				return self::error( 'missing_header', 'A required signature header is missing.' );
			}
		}

		$timestamp_raw = trim( $headers['x-ngt-timestamp'] );
		if ( ! preg_match( '/^\d+$/', $timestamp_raw ) ) {
			return self::error( 'invalid_timestamp', 'The signature timestamp is malformed.' );
		}
		if ( abs( time() - (int) $timestamp_raw ) > NGTAI_Config::skew() ) {
			return self::error( 'timestamp_skew', 'The signature timestamp is outside the allowed skew.' );
		}

		$nonce = $headers['x-ngt-nonce'];
		if ( strlen( $nonce ) < 16 ) {
			return self::error( 'invalid_nonce', 'The signature nonce is malformed.' );
		}
		if ( ! hash_equals( (string) NGTAI_Config::key_id(), (string) $headers['x-ngt-key-id'] ) ) {
			return self::error( 'unknown_key', 'The signing key is not recognized.' );
		}

		$body_hash = self::body_sha256( $raw_body );
		if ( isset( $headers['x-ngt-body-sha256'] ) && ! hash_equals( $body_hash, strtolower( $headers['x-ngt-body-sha256'] ) ) ) {
			return self::error( 'body_digest_mismatch', 'The request body digest does not match.' );
		}

		$provided = trim( $headers['x-ngt-signature'] );
		if ( 0 === strpos( $provided, 'v1=' ) ) {
			$provided = substr( $provided, 3 );
		}
		$canonical = self::canonical( $timestamp_raw, $nonce, $method, $path, $body_hash );
		$expected  = self::sign( $canonical, NGTAI_Config::secret() );
		if ( ! preg_match( '/^[a-f0-9]{64}$/i', $provided ) || ! hash_equals( $expected, strtolower( $provided ) ) ) {
			return self::error( 'signature_mismatch', 'The request signature is invalid.' );
		}

		return true;
	}

	/**
	 * Normalize request headers to lowercase first-string values.
	 *
	 * @param array<string,mixed> $headers Headers.
	 * @return array<string,string>
	 */
	public static function normalize_headers( array $headers ) {
		$normalized = [];
		foreach ( $headers as $name => $value ) {
			if ( is_array( $value ) ) {
				$value = reset( $value );
			}
			$normalized[ strtolower( (string) $name ) ] = is_scalar( $value ) ? (string) $value : '';
		}
		return $normalized;
	}

	/**
	 * Generate a version-four UUID.
	 *
	 * @return string
	 */
	public static function uuid() {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return wp_generate_uuid4();
		}
		$data    = random_bytes( 16 );
		$data[6] = chr( ( ord( $data[6] ) & 0x0f ) | 0x40 );
		$data[8] = chr( ( ord( $data[8] ) & 0x3f ) | 0x80 );
		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
	}

	/**
	 * Constant-time signature comparison retained for existing callers.
	 *
	 * @param string $expected Expected value.
	 * @param string $provided Provided value.
	 * @return bool
	 */
	public static function signatures_match( $expected, $provided ) {
		return '' !== (string) $expected && '' !== (string) $provided && hash_equals( (string) $expected, (string) $provided );
	}

	/**
	 * Compatibility wrapper for the previous canonical method.
	 *
	 * @param string     $method          HTTP method.
	 * @param string     $path            Request path.
	 * @param string|int $timestamp       Unix timestamp.
	 * @param string     $nonce           Nonce.
	 * @param string     $tenant          Unused legacy tenant.
	 * @param string     $idempotency_key Unused legacy key.
	 * @param string     $body_sha256     Body digest.
	 * @return string
	 */
	public static function canonical_string( $method, $path, $timestamp, $nonce, $tenant, $idempotency_key, $body_sha256 ) {
		unset( $tenant, $idempotency_key );
		return self::canonical( $timestamp, $nonce, $method, $path, $body_sha256 );
	}

	/**
	 * Compatibility wrapper for previous outbound callers.
	 *
	 * @param string $method          HTTP method.
	 * @param string $path            Request path.
	 * @param string $raw_body        Raw body.
	 * @param string $idempotency_key Idempotency key.
	 * @param string $correlation_id  Correlation ID.
	 * @return array<string,string>
	 */
	public static function outbound_headers( $method, $path, $raw_body, $idempotency_key, $correlation_id ) {
		return self::build_headers(
			$method,
			$path,
			$raw_body,
			[
				'idempotency_key' => $idempotency_key,
				'correlation_id'  => $correlation_id,
			]
		);
	}

	/**
	 * Compatibility wrapper for inbound callers.
	 *
	 * @param string              $method   HTTP method.
	 * @param string              $path     Request path.
	 * @param string              $raw_body Raw body.
	 * @param array<string,mixed> $headers  Headers.
	 * @return true|WP_Error
	 */
	public static function verify_inbound( $method, $path, $raw_body, array $headers ) {
		return self::verify( $method, $path, $raw_body, $headers );
	}

	/**
	 * Create a standardized unauthorized error.
	 *
	 * @param string $code    Error code suffix.
	 * @param string $message Error message.
	 * @return WP_Error
	 */
	private static function error( $code, $message ) {
		return new WP_Error( 'ngtai_' . $code, $message, [ 'status' => 401 ] );
	}
}
