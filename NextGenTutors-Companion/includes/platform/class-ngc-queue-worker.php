<?php
/**
 * Queue worker — concurrency, bulkheads, circuit breaker, cron tick.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processes durable queue messages.
 */
final class NGC_Queue_Worker {

	public const CRON_HOOK = 'ngc_queue_tick';
	public const QUEUE_DEFAULT = 'default';
	public const QUEUE_WORKFLOW = 'workflow';
	public const QUEUE_SAFEGUARD = 'safeguard';
	public const QUEUE_FRAUD = 'fraud';
	public const QUEUE_NOTIFY = 'notify';

	/** @var array<string,int> Max inflight per queue (bulkhead). */
	private static $bulkheads = [
		'default'   => 10,
		'workflow'  => 8,
		'safeguard' => 4,
		'fraud'     => 4,
		'notify'    => 6,
	];

	/** @var array<string,array{failures:int,open_until:int}> */
	private static $circuits = [];

	/**
	 * Init cron + handlers.
	 */
	public static function init() {
		add_action( self::CRON_HOOK, [ __CLASS__, 'tick' ] );
		add_filter( 'cron_schedules', [ __CLASS__, 'schedules' ] );
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 30, 'ngc_every_minute', self::CRON_HOOK );
		}
		add_action( 'ngc_queue_process_message', [ __CLASS__, 'dispatch_message' ], 10, 2 );
	}

	/**
	 * @param array $schedules Schedules.
	 * @return array
	 */
	public static function schedules( $schedules ) {
		if ( ! isset( $schedules['ngc_every_minute'] ) ) {
			$schedules['ngc_every_minute'] = [
				'interval' => 60,
				'display'  => 'Every Minute (NGC Queue)',
			];
		}
		return $schedules;
	}

	/**
	 * Cron tick — process a small batch when CLI worker absent.
	 */
	public static function tick() {
		if ( get_transient( 'ngc_queue_cli_worker_alive' ) ) {
			return;
		}
		self::work( [ 'max_messages' => 10, 'queues' => array_keys( self::$bulkheads ) ] );
	}

	/**
	 * Work loop.
	 *
	 * @param array $args Args: max_messages, queues, worker_id, visibility.
	 * @return array Stats.
	 */
	public static function work( array $args = [] ) {
		$max      = isset( $args['max_messages'] ) ? (int) $args['max_messages'] : 25;
		$queues   = isset( $args['queues'] ) && is_array( $args['queues'] ) ? $args['queues'] : array_keys( self::$bulkheads );
		$worker   = isset( $args['worker_id'] ) ? (string) $args['worker_id'] : ( 'wp-' . getmypid() . '-' . wp_generate_password( 6, false ) );
		$vis      = isset( $args['visibility'] ) ? (int) $args['visibility'] : 90;
		$processed = 0;
		$acked     = 0;
		$nacked    = 0;

		foreach ( $queues as $queue ) {
			$queue = sanitize_key( (string) $queue );
			if ( self::circuit_open( $queue ) ) {
				continue;
			}
			$remaining = $max - $processed;
			if ( $remaining <= 0 ) {
				break;
			}
			$limit   = min( $remaining, self::$bulkheads[ $queue ] ?? 5 );
			$claimed = NGC_Durable_Queue::claim( $queue, $worker, $limit, $vis );
			foreach ( $claimed as $msg ) {
				$processed++;
				$result = self::process_one( $msg );
				if ( is_wp_error( $result ) ) {
					NGC_Durable_Queue::nack( $msg->message_id, $msg->lease_token, $result->get_error_message() );
					self::circuit_fail( $queue );
					$nacked++;
				} else {
					NGC_Durable_Queue::ack( $msg->message_id, $msg->lease_token );
					self::circuit_success( $queue );
					$acked++;
				}
			}
		}

		return compact( 'processed', 'acked', 'nacked', 'worker' );
	}

	/**
	 * Process a single claimed message.
	 *
	 * @param object $msg Message row.
	 * @return true|WP_Error
	 */
	public static function process_one( $msg ) {
		$payload = is_array( $msg->payload_decoded ?? null )
			? $msg->payload_decoded
			: (array) json_decode( (string) ( $msg->payload ?? '' ), true );

		$type = isset( $payload['type'] ) ? (string) $payload['type'] : 'noop';
		if ( ! empty( $msg->trace_id ) ) {
			NGC_Platform_Observability::set_trace_id( (string) $msg->trace_id );
		}

		try {
			/**
			 * Filter handlers by type. Return true|WP_Error.
			 *
			 * @param mixed  $result  Default null.
			 * @param array  $payload Payload.
			 * @param object $msg     Message.
			 */
			$result = apply_filters( 'ngc_queue_handle_' . sanitize_key( $type ), null, $payload, $msg );
			if ( null === $result ) {
				$result = self::dispatch_message( $type, $payload );
			}
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			return true;
		} catch ( Throwable $e ) {
			return new WP_Error( 'ngc_queue_handler_exception', $e->getMessage() );
		}
	}

	/**
	 * Built-in type dispatch.
	 *
	 * @param string $type    Type.
	 * @param array  $payload Payload.
	 * @return true|WP_Error|null
	 */
	public static function dispatch_message( $type, array $payload ) {
		switch ( $type ) {
			case 'workflow.execute':
				return NGC_Workflow_Authority::execute_job( $payload );
			case 'notify.dispatch':
				if ( class_exists( 'NGC_Notifications' ) && method_exists( 'NGC_Notifications', 'dispatch_queued' ) ) {
					return NGC_Notifications::dispatch_queued( $payload );
				}
				return true;
			case 'safeguard.sla':
				do_action( 'ngc_safeguard_sla_tick', $payload );
				return true;
			case 'fraud.hold':
				do_action( 'ngc_fraud_hold_process', $payload );
				return true;
			case 'recon.run':
				return NGC_Reconciliation::run( $payload );
			case 'memory.ingest':
				if ( class_exists( 'NGC_Memory_Ingestion_Worker' ) ) {
					return NGC_Memory_Ingestion_Worker::handle( true, $payload, null );
				}
				return true;
			case 'talent.evaluate':
				if ( class_exists( 'NGC_Talent_Ingestion_Worker' ) ) {
					return NGC_Talent_Ingestion_Worker::handle( true, $payload, null );
				}
				return true;
			case 'noop':
				return true;
			default:
				/**
				 * Generic handler.
				 *
				 * @param mixed  $result  Default WP_Error unknown.
				 * @param string $type    Type.
				 * @param array  $payload Payload.
				 */
				$handled = apply_filters( 'ngc_queue_handle', new WP_Error( 'ngc_queue_unknown_type', 'Unknown queue type: ' . $type ), $type, $payload );
				return $handled;
		}
	}

	/**
	 * @param string $queue Queue.
	 * @return bool
	 */
	private static function circuit_open( $queue ) {
		$c = self::$circuits[ $queue ] ?? null;
		if ( ! $c ) {
			return false;
		}
		return ! empty( $c['open_until'] ) && time() < (int) $c['open_until'];
	}

	/**
	 * @param string $queue Queue.
	 */
	private static function circuit_fail( $queue ) {
		if ( ! isset( self::$circuits[ $queue ] ) ) {
			self::$circuits[ $queue ] = [ 'failures' => 0, 'open_until' => 0 ];
		}
		self::$circuits[ $queue ]['failures']++;
		if ( self::$circuits[ $queue ]['failures'] >= 5 ) {
			self::$circuits[ $queue ]['open_until'] = time() + 60;
			self::$circuits[ $queue ]['failures']   = 0;
			if ( class_exists( 'NGC_Metrics' ) ) {
				NGC_Metrics::inc( 'queue_circuit_open_total', 1, [ 'queue' => $queue ] );
			}
		}
	}

	/**
	 * @param string $queue Queue.
	 */
	private static function circuit_success( $queue ) {
		self::$circuits[ $queue ] = [ 'failures' => 0, 'open_until' => 0 ];
	}

	/**
	 * Mark CLI worker alive so cron skips.
	 *
	 * @param int $ttl Seconds.
	 */
	public static function mark_cli_alive( $ttl = 120 ) {
		set_transient( 'ngc_queue_cli_worker_alive', 1, max( 30, (int) $ttl ) );
	}
}
