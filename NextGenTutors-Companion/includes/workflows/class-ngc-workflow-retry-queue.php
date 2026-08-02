<?php
/**
 * Failed workflow retry queue.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persist and retry failed workflow steps.
 */
class NGC_Workflow_Retry_Queue {

	const OPTION_KEY = 'ngc_workflow_retry_queue';

	/**
	 * @param string               $workflow Workflow key.
	 * @param array<string, mixed> $context  Context.
	 * @param string               $step     Failed step.
	 * @param string               $error    Error message.
	 */
	public static function enqueue( $workflow, $context, $step, $error ) {
		// Prefer durable queue when platform kernel is available.
		if ( class_exists( 'NGC_Durable_Queue' ) && class_exists( 'NGC_Platform' ) && ! ( '1' === (string) get_option( NGC_Platform::OPTION_KILL_SWITCH, '' ) ) ) {
			NGC_Durable_Queue::enqueue(
				NGC_Queue_Worker::QUEUE_WORKFLOW,
				[
					'type'     => 'workflow.execute',
					'action'   => 'retry_workflow',
					'workflow' => sanitize_key( (string) $workflow ),
					'step'     => sanitize_key( (string) $step ),
					'context'  => $context,
					'error'    => sanitize_text_field( (string) $error ),
				],
				[
					'idempotency_key' => 'retry:' . sanitize_key( (string) $workflow ) . ':' . md5( wp_json_encode( $context ) . $step ),
					'priority'        => 90,
					'delay_seconds'   => HOUR_IN_SECONDS,
				]
			);
		}

		$queue = self::all();
		$queue[] = [
			'id'         => wp_generate_uuid4(),
			'workflow'   => sanitize_key( $workflow ),
			'step'       => sanitize_key( $step ),
			'context'    => $context,
			'error'      => sanitize_text_field( $error ),
			'attempts'   => 0,
			'created_at' => gmdate( 'c' ),
			'next_retry' => gmdate( 'c', time() + HOUR_IN_SECONDS ),
		];
		update_option( self::OPTION_KEY, array_slice( $queue, -200 ), false );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function all() {
		$queue = get_option( self::OPTION_KEY, [] );
		return is_array( $queue ) ? $queue : [];
	}

	/**
	 * @param string $id Queue item ID.
	 * @return array<string, mixed>|null
	 */
	public static function get( $id ) {
		foreach ( self::all() as $item ) {
			if ( ( $item['id'] ?? '' ) === $id ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * @param string $id Item ID.
	 */
	public static function remove( $id ) {
		$queue = array_values(
			array_filter(
				self::all(),
				static function ( $item ) use ( $id ) {
					return ( $item['id'] ?? '' ) !== $id;
				}
			)
		);
		update_option( self::OPTION_KEY, $queue, false );
	}

	/**
	 * Retry a queued item.
	 *
	 * @param string $id Item ID.
	 * @return array<string, mixed>
	 */
	public static function retry( $id ) {
		$item = self::get( $id );
		if ( ! $item ) {
			return [ 'ok' => false, 'message' => __( 'Retry item not found.', 'nextgencompanion' ) ];
		}
		$result = NGC_Workflow_Orchestrator::run( $item['workflow'], $item['context'], true );
		if ( ! empty( $result['ok'] ) ) {
			self::remove( $id );
		} else {
			$queue = self::all();
			foreach ( $queue as &$row ) {
				if ( ( $row['id'] ?? '' ) === $id ) {
					$row['attempts']   = (int) ( $row['attempts'] ?? 0 ) + 1;
					$row['last_error'] = $result['message'] ?? 'retry failed';
					$row['next_retry'] = gmdate( 'c', time() + ( 2 * HOUR_IN_SECONDS ) );
				}
			}
			update_option( self::OPTION_KEY, $queue, false );
		}
		return $result;
	}

	/**
	 * Hook cron for automatic retries.
	 */
	public static function init() {
		add_action( 'ngc_workflow_retry_cron', [ __CLASS__, 'process_due' ] );
		if ( ! wp_next_scheduled( 'ngc_workflow_retry_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'ngc_workflow_retry_cron' );
		}
	}

	/**
	 * Process due retries.
	 */
	public static function process_due() {
		$now = time();
		foreach ( self::all() as $item ) {
			$due = strtotime( $item['next_retry'] ?? '' );
			if ( $due && $due <= $now ) {
				self::retry( $item['id'] );
			}
		}
	}
}
