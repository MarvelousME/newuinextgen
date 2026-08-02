<?php
/**
 * Real-time SSE stream for Mission Control dashboards.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ring buffer + SSE endpoint for live dashboard updates.
 */
final class NGC_Intelligence_Stream {

	public const OPTION = 'ngc_intelligence_live_buffer';
	public const MAX    = 300;

	/**
	 * @param string               $type    Event type.
	 * @param array<string, mixed> $payload Payload.
	 * @return int
	 */
	public static function push( $type, array $payload ) {
		$events = get_option( self::OPTION, [] );
		if ( ! is_array( $events ) ) {
			$events = [];
		}
		$id = 0;
		foreach ( $events as $e ) {
			$id = max( $id, (int) ( $e['id'] ?? 0 ) );
		}
		++$id;
		$events[] = [
			'id'      => $id,
			'type'    => sanitize_key( $type ),
			'at'      => gmdate( 'c' ),
			'payload' => $payload,
		];
		if ( count( $events ) > self::MAX ) {
			$events = array_slice( $events, -self::MAX );
		}
		update_option( self::OPTION, $events, false );
		return $id;
	}

	/**
	 * @param int $since_id Last seen ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function since( $since_id ) {
		$events = get_option( self::OPTION, [] );
		if ( ! is_array( $events ) ) {
			return [];
		}
		return array_values(
			array_filter(
				$events,
				static function ( $e ) use ( $since_id ) {
					return (int) ( $e['id'] ?? 0 ) > (int) $since_id;
				}
			)
		);
	}

	/**
	 * SSE REST handler (called via rest_pre_serve_request filter).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return void
	 */
	public static function rest_stream( WP_REST_Request $request ) {
		if ( ! NGC_Intelligence_Config::get()['sse_enabled'] ) {
			status_header( 503 );
			header( 'Content-Type: application/json' );
			echo wp_json_encode( [ 'error' => 'sse_disabled' ] );
			exit;
		}

		$since = max( 0, (int) $request->get_param( 'since' ) );
		$timeout = min( 55, max( 5, (int) $request->get_param( 'timeout' ) ) );
		$deadline = time() + $timeout;
		$last_id  = $since;

		nocache_headers();
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'X-Accel-Buffering: no' );

		while ( time() < $deadline && ! connection_aborted() ) {
			$new = self::since( $last_id );
			if ( $new ) {
				foreach ( $new as $event ) {
					$last_id = max( $last_id, (int) $event['id'] );
					echo 'id: ' . (int) $event['id'] . "\n";
					echo 'event: ' . esc_attr( (string) $event['type'] ) . "\n";
					echo 'data: ' . wp_json_encode( $event ) . "\n\n";
				}
				@ob_flush(); // phpcs:ignore
				flush();
			} else {
				echo ": keepalive\n\n";
				@ob_flush(); // phpcs:ignore
				flush();
			}
			sleep( 2 );
		}
		exit;
	}
}
