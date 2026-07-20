<?php
/**
 * Transient-based request rate limiting (hashed client fingerprint).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IP + user-agent hash rate limiter — never stores raw IP addresses.
 */
class NGC_Rate_Limiter {

	/**
	 * Check whether the client is within the rate limit.
	 *
	 * @param string $action Action key.
	 * @param int    $limit  Max requests per window.
	 * @param int    $window Window length in seconds.
	 * @return bool True when allowed; false when exceeded.
	 */
	public static function check( $action, $limit = 20, $window = 600 ) {
		$key  = self::transient_key( $action );
		$data = get_transient( $key );
		$now  = time();

		if ( ! is_array( $data ) || empty( $data['window_start'] ) || ( $now - (int) $data['window_start'] ) >= $window ) {
			$data = [
				'window_start' => $now,
				'count'        => 0,
			];
		}

		++$data['count'];
		$remaining = max( 0, $window - ( $now - (int) $data['window_start'] ) );
		$ttl       = $remaining > 0 ? $remaining : $window;
		set_transient( $key, $data, $ttl );

		/**
		 * Mirror rate-limit counters to object cache when available (same-site only).
		 * Distributed/CDN effectiveness remains NOT_VERIFIED without shared cache.
		 *
		 * @param string               $key  Transient key.
		 * @param array<string, mixed> $data Counter payload.
		 * @param int                  $ttl  TTL seconds.
		 */
		if ( wp_using_ext_object_cache() ) {
			wp_cache_set( $key, $data, 'ngc_rate_limit', $ttl );
		}

		return (int) $data['count'] <= (int) $limit;
	}

	/**
	 * Remaining requests for the current window.
	 *
	 * @param string $action Action key.
	 * @param int    $limit  Max requests.
	 * @param int    $window Window seconds.
	 * @return int
	 */
	public static function remaining( $action, $limit = 20, $window = 600 ) {
		$key  = self::transient_key( $action );
		$data = get_transient( $key );
		if ( ! is_array( $data ) || empty( $data['count'] ) ) {
			return (int) $limit;
		}
		$now = time();
		if ( empty( $data['window_start'] ) || ( $now - (int) $data['window_start'] ) >= $window ) {
			return (int) $limit;
		}
		return max( 0, (int) $limit - (int) $data['count'] );
	}

	/**
	 * @param string $action Action key.
	 * @return string
	 */
	private static function transient_key( $action ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '0.0.0.0'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$hash = hash( 'sha256', $ip . '|' . $ua );
		return 'ngc_rl_' . sanitize_key( $action ) . '_' . substr( $hash, 0, 32 );
	}
}
