<?php
/**
 * Platform observability — metrics, traces, alerts.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trace IDs + alert wiring to Intelligence.
 */
final class NGC_Platform_Observability {

	/** @var string */
	private static $trace_id = '';

	/**
	 * Init REST middleware for traceparent.
	 */
	public static function init() {
		add_filter( 'rest_request_before_callbacks', [ __CLASS__, 'rest_trace' ], 5, 3 );
		add_filter( 'rest_post_dispatch', [ __CLASS__, 'rest_trace_header' ], 10, 3 );
	}

	/**
	 * @param mixed           $response Response.
	 * @param array           $handler  Handler.
	 * @param WP_REST_Request $request  Request.
	 * @return mixed
	 */
	public static function rest_trace( $response, $handler, $request ) {
		$tp = $request instanceof WP_REST_Request ? (string) $request->get_header( 'traceparent' ) : '';
		if ( $tp && preg_match( '/^[0-9a-f]{2}-([0-9a-f]{32})-/i', $tp, $m ) ) {
			self::$trace_id = strtolower( $m[1] );
		} else {
			self::$trace_id = bin2hex( random_bytes( 16 ) );
		}
		return $response;
	}

	/**
	 * @param mixed            $result  Result.
	 * @param WP_REST_Server   $server  Server.
	 * @param WP_REST_Request  $request Request.
	 * @return mixed
	 */
	public static function rest_trace_header( $result, $server, $request ) {
		if ( $result instanceof WP_REST_Response && self::$trace_id ) {
			$span = bin2hex( random_bytes( 8 ) );
			$result->header( 'traceparent', '00-' . self::$trace_id . '-' . $span . '-01' );
			$result->header( 'x-ngc-trace-id', self::$trace_id );
		}
		return $result;
	}

	/**
	 * @return string
	 */
	public static function current_trace_id() {
		if ( self::$trace_id === '' ) {
			self::$trace_id = bin2hex( random_bytes( 16 ) );
		}
		return self::$trace_id;
	}

	/**
	 * @param string $id Trace id.
	 */
	public static function set_trace_id( $id ) {
		self::$trace_id = preg_replace( '/[^a-f0-9]/', '', strtolower( (string) $id ) );
	}

	/**
	 * Alert via Intelligence channels when available.
	 *
	 * @param string $code Code.
	 * @param array  $ctx  Context.
	 */
	public static function alert( $code, array $ctx = [] ) {
		$payload = array_merge(
			[
				'code'     => sanitize_key( (string) $code ),
				'trace_id' => self::current_trace_id(),
				'tenant'   => NGC_Tenant_Context::id(),
				'at'       => gmdate( 'c' ),
			],
			$ctx
		);
		do_action( 'ngc_intelligence_alert', $payload['code'], $payload );
		if ( class_exists( 'NGC_Intelligence_Alerts' ) && method_exists( 'NGC_Intelligence_Alerts', 'raise' ) ) {
			NGC_Intelligence_Alerts::raise( $payload['code'], $payload );
		} elseif ( class_exists( 'NGC_Intelligence_Dispatch' ) && method_exists( 'NGC_Intelligence_Dispatch', 'send' ) ) {
			NGC_Intelligence_Dispatch::send( 'platform_alert', $payload );
		}
		if ( class_exists( 'NGC_Metrics' ) ) {
			NGC_Metrics::inc( 'platform_alerts_total', 1, [ 'code' => $payload['code'] ] );
		}
	}

	/**
	 * Alert when DLQ grows.
	 */
	public static function alert_dlq_growth() {
		$stats = NGC_Durable_Queue::stats();
		$open  = (int) ( $stats['dlq_open'] ?? 0 );
		if ( $open >= 5 ) {
			self::alert( 'queue_dlq_growth', [ 'dlq_open' => $open ] );
		}
	}
}
