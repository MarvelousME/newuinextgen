<?php
/**
 * Privacy-safe structured logger.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGTAI_Logger {
	/**
	 * Write one structured entry.
	 *
	 * @param string               $severity  Severity.
	 * @param string               $component Component.
	 * @param string               $operation Operation.
	 * @param array<string,mixed>  $context   Context.
	 * @return void
	 */
	public static function log( $severity, $component, $operation, array $context = [] ) {
		$entry = [
			'timestamp' => gmdate( 'c' ),
			'severity'  => sanitize_key( $severity ),
			'component' => sanitize_key( $component ),
			'operation' => sanitize_key( $operation ),
		];
		$allowed = [ 'event_id', 'agent_run_id', 'correlation_id', 'request_id', 'outcome', 'duration_ms', 'http_status', 'retry_count', 'error_code' ];
		$safe    = self::scrub( $context );
		foreach ( $allowed as $field ) {
			if ( array_key_exists( $field, $safe ) ) {
				$entry[ $field ] = $safe[ $field ];
			}
		}
		if ( ! empty( $safe ) ) {
			$entry['context'] = $safe;
		}
		$json = wp_json_encode( $entry, JSON_UNESCAPED_SLASHES );
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && false !== $json ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $json );
		}
		$ring   = get_option( 'ngtai_log_ring', [] );
		$ring   = is_array( $ring ) ? $ring : [];
		$ring[] = $entry;
		update_option( 'ngtai_log_ring', array_slice( $ring, -200 ), false );
	}

	/**
	 * Recursively mask sensitive values.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	public static function scrub( $value ) {
		if ( ! is_array( $value ) ) {
			return is_scalar( $value ) || null === $value ? $value : '[unsupported]';
		}
		$safe = [];
		foreach ( $value as $key => $child ) {
			$name = strtolower( (string) $key );
			if ( preg_match( '/secret|token|password|authorization|cookie|email|phone|id_number/', $name ) ) {
				$safe[ $key ] = '[redacted]';
			} else {
				$safe[ $key ] = self::scrub( $child );
			}
		}
		return $safe;
	}

	/**
	 * Increment an option-backed counter.
	 *
	 * @param string $counter_option Counter option.
	 * @return int
	 */
	public static function bump( $counter_option ) {
		if ( 0 !== strpos( (string) $counter_option, 'ngtai_' ) ) {
			return 0;
		}
		$value = (int) get_option( $counter_option, 0 ) + 1;
		update_option( $counter_option, $value, false );
		return $value;
	}
}
