<?php
/**
 * Live execution event stream for the Automation Studio monitor.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ring-buffer event bus with REST polling (WebSocket-compatible client protocol).
 */
class NGC_Studio_Stream {

	const OPTION_KEY = 'ngc_studio_live_events';
	const MAX_EVENTS = 200;

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'ngc_studio_workflow_executed', [ __CLASS__, 'on_workflow_executed' ], 10, 3 );
		add_action( 'ngc_studio_step_executed', [ __CLASS__, 'on_step_executed' ], 10, 1 );
	}

	/**
	 * @param array<string, mixed> $workflow Workflow.
	 * @param array<string, mixed> $output   Result.
	 * @param array<string, mixed> $ctx      Context.
	 */
	public static function on_workflow_executed( $workflow, $output, $ctx ) {
		self::push(
			'workflow.completed',
			[
				'workflow_id' => (int) ( $workflow['id'] ?? 0 ),
				'status'      => (string) ( $output['status'] ?? '' ),
				'duration_ms' => (int) ( $output['duration_ms'] ?? 0 ),
				'simulation'  => ! empty( $output['simulation'] ),
				'path'        => (array) ( $output['path'] ?? [] ),
				'trigger'     => (string) ( $ctx['trigger'] ?? '' ),
			]
		);
	}

	/**
	 * @param array<string, mixed> $event Step event payload.
	 */
	public static function on_step_executed( $event ) {
		self::push( 'workflow.step', $event );
	}

	/**
	 * @param string               $type    Event type.
	 * @param array<string, mixed> $payload Payload.
	 * @return int Event ID.
	 */
	public static function push( $type, $payload ) {
		$events = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $events ) ) {
			$events = [];
		}
		$max_id = 0;
		foreach ( $events as $event ) {
			$max_id = max( $max_id, (int) ( $event['id'] ?? 0 ) );
		}
		$id      = $max_id + 1;
		$events[] = [
			'id'      => $id,
			'type'    => sanitize_key( $type ),
			'at'      => current_time( 'mysql', true ),
			'payload' => $payload,
		];
		if ( count( $events ) > self::MAX_EVENTS ) {
			$events = array_slice( $events, -self::MAX_EVENTS );
		}
		update_option( self::OPTION_KEY, $events, false );
		return $id;
	}

	/**
	 * @param int $since_id Last seen event ID.
	 * @return array{events:array<int,array<string,mixed>>,last_id:int,stream:string}
	 */
	public static function poll( $since_id = 0 ) {
		$events  = get_option( self::OPTION_KEY, [] );
		$since   = max( 0, (int) $since_id );
		$fresh   = [];
		$last_id = $since;
		if ( is_array( $events ) ) {
			foreach ( $events as $event ) {
				$eid = (int) ( $event['id'] ?? 0 );
				if ( $eid > $since ) {
					$fresh[] = $event;
					$last_id = max( $last_id, $eid );
				}
			}
		}
		return [
			'events'   => $fresh,
			'last_id'  => $last_id,
			'stream'   => 'ngc_studio_live',
			'protocol' => 'poll',
		];
	}

	/**
	 * Stream events via Server-Sent Events (long-poll loop, ~25s per connection).
	 *
	 * @param int $since_id     Last seen event ID.
	 * @param int $max_seconds  Max connection duration.
	 */
	public static function render_sse( $since_id = 0, $max_seconds = 25 ) {
		if ( ob_get_level() ) {
			ob_end_clean();
		}
		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: text/event-stream; charset=utf-8' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Connection: keep-alive' );
		header( 'X-Accel-Buffering: no' );

		$since = max( 0, (int) $since_id );
		$end   = time() + max( 5, min( 60, (int) $max_seconds ) );

		while ( time() < $end ) {
			if ( connection_aborted() ) {
				break;
			}
			$chunk = self::poll( $since );
			if ( ! empty( $chunk['events'] ) ) {
				$chunk['protocol'] = 'sse';
				echo "event: ngc_studio_live\n";
				echo 'data: ' . wp_json_encode( $chunk ) . "\n\n";
				$since = (int) $chunk['last_id'];
			} else {
				echo ": heartbeat " . gmdate( 'c' ) . "\n\n";
			}
			if ( function_exists( 'wp_ob_end_flush_all' ) ) {
				wp_ob_end_flush_all();
			}
			flush();
			sleep( 1 );
		}
		exit;
	}

	/**
	 * @return array{ok:bool,events:int}
	 */
	public static function status() {
		$events = get_option( self::OPTION_KEY, [] );
		return [
			'ok'     => true,
			'events' => is_array( $events ) ? count( $events ) : 0,
		];
	}
}
