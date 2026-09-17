<?php
/**
 * Session orchestration metrics + structured logs.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required counters from the master execution prompt.
 */
class NGC_Session_Observability {

	/**
	 * @param string               $metric Metric key.
	 * @param array<string, mixed> $ctx    Context (no secrets).
	 * @param string               $result Result.
	 * @param int                  $ms     Duration ms.
	 * @return void
	 */
	public static function event( $metric, array $ctx = [], $result = 'success', $ms = 0 ) {
		if ( class_exists( 'NGC_Metrics' ) ) {
			NGC_Metrics::inc( (string) $metric, 1 );
			if ( 'success' !== $result ) {
				NGC_Metrics::inc( (string) $metric . '_error', 1 );
			}
		}
		$payload = [
			'metric'          => (string) $metric,
			'result'          => (string) $result,
			'duration'        => (int) $ms,
			'correlation_id'  => (string) ( $ctx['correlation_id'] ?? '' ),
			'session_id'      => (int) ( $ctx['session_id'] ?? $ctx['id'] ?? 0 ),
			'booking_id'      => (int) ( $ctx['booking_id'] ?? 0 ),
			'order_id'        => (int) ( $ctx['order_id'] ?? 0 ),
			'user_id'         => (int) ( $ctx['user_id'] ?? 0 ),
			'operation'       => (string) ( $ctx['operation'] ?? $metric ),
		];
		if ( class_exists( 'NGC_System_Log_Service' ) && method_exists( 'NGC_System_Log_Service', 'write' ) ) {
			NGC_System_Log_Service::write( 'session', $result === 'success' ? 'info' : 'error', wp_json_encode( $payload ) );
		} elseif ( function_exists( 'error_log' ) ) {
			error_log( '[NGC session] ' . wp_json_encode( $payload ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
		do_action( 'ngc_session_observability', $metric, $payload );
	}

	/**
	 * @param string               $metric Metric.
	 * @param array<string, mixed> $ctx    Context.
	 * @return void
	 */
	public static function success( $metric, array $ctx = [] ) {
		self::event( $metric, $ctx, 'success' );
	}

	/**
	 * @param string               $metric Metric.
	 * @param array<string, mixed> $ctx    Context.
	 * @return void
	 */
	public static function failure( $metric, array $ctx = [] ) {
		self::event( $metric, $ctx, 'error' );
	}
}
