<?php
/**
 * MySQL durable queue with leases and priority.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue / claim / ack / nack / delay.
 */
final class NGC_Durable_Queue {

	public const STATUS_PENDING    = 'pending';
	public const STATUS_PROCESSING = 'processing';
	public const STATUS_DONE       = 'done';
	public const STATUS_FAILED     = 'failed';

	/**
	 * Init.
	 */
	public static function init() {
		// Schema handles tables; no hooks.
	}

	/**
	 * Enqueue a message.
	 *
	 * @param string $queue_name Queue.
	 * @param array  $payload    Payload.
	 * @param array  $args       Optional: priority, delay_seconds, idempotency_key, max_attempts, trace_id.
	 * @return string|WP_Error message_id
	 */
	public static function enqueue( $queue_name, array $payload, array $args = [] ) {
		global $wpdb;
		$table   = NGC_Platform_Schema::table( 'queue_messages' );
		$tenant  = NGC_Tenant_Context::id();
		$queue   = sanitize_key( (string) $queue_name );
		$idem    = isset( $args['idempotency_key'] ) ? (string) $args['idempotency_key'] : '';
		$fp      = hash( 'sha256', wp_json_encode( $payload ) );
		$trace   = isset( $args['trace_id'] ) ? (string) $args['trace_id'] : NGC_Platform_Observability::current_trace_id();
		$prio    = isset( $args['priority'] ) ? (int) $args['priority'] : 100;
		$max     = isset( $args['max_attempts'] ) ? max( 1, (int) $args['max_attempts'] ) : 8;
		$delay_s = isset( $args['delay_seconds'] ) ? max( 0, (int) $args['delay_seconds'] ) : 0;

		if ( $idem !== '' ) {
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT message_id FROM {$table} WHERE tenant_id = %d AND idempotency_key = %s AND status IN ('pending','processing','done') LIMIT 1",
					$tenant,
					$idem
				)
			);
			if ( $existing ) {
				if ( class_exists( 'NGC_Metrics' ) ) {
					NGC_Metrics::inc( 'workflow_duplicate_suppressed_total', 1, [ 'queue' => $queue ] );
				}
				return (string) $existing;
			}
		}

		$mid = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'q_', true );
		$now = current_time( 'mysql', true );
		$delay_until = null;
		if ( $delay_s > 0 ) {
			$delay_until = gmdate( 'Y-m-d H:i:s', time() + $delay_s );
		}

		$ok = $wpdb->insert(
			$table,
			[
				'tenant_id'         => $tenant,
				'queue_name'        => $queue,
				'message_id'        => $mid,
				'idempotency_key'   => $idem,
				'priority'          => $prio,
				'status'            => self::STATUS_PENDING,
				'attempts'          => 0,
				'max_attempts'      => $max,
				'delay_until'       => $delay_until,
				'payload'           => wp_json_encode( $payload ),
				'fingerprint'       => $fp,
				'trace_id'          => $trace,
				'created_at'        => $now,
				'updated_at'        => $now,
			],
			[ '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( ! $ok ) {
			return new WP_Error( 'ngc_queue_enqueue_failed', 'Failed to enqueue message.' );
		}

		if ( class_exists( 'NGC_Metrics' ) ) {
			NGC_Metrics::inc( 'queue_enqueued_total', 1, [ 'queue' => $queue ] );
		}

		return $mid;
	}

	/**
	 * Claim up to $limit messages (MySQL FOR UPDATE pattern via transaction).
	 *
	 * @param string $queue_name Queue.
	 * @param string $worker_id  Lease owner.
	 * @param int    $limit      Max messages.
	 * @param int    $visibility Visibility timeout seconds.
	 * @return array<int,object>
	 */
	public static function claim( $queue_name, $worker_id, $limit = 5, $visibility = 60 ) {
		global $wpdb;
		$table = NGC_Platform_Schema::table( 'queue_messages' );
		$tenant = NGC_Tenant_Context::id();
		$queue  = sanitize_key( (string) $queue_name );
		$limit  = max( 1, min( 50, (int) $limit ) );
		$now    = gmdate( 'Y-m-d H:i:s' );
		$vis    = gmdate( 'Y-m-d H:i:s', time() + max( 15, (int) $visibility ) );
		$token  = wp_generate_password( 24, false );

		$wpdb->query( 'START TRANSACTION' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE tenant_id = %d AND queue_name = %s AND status = %s
				AND (delay_until IS NULL OR delay_until <= %s)
				AND (visibility_until IS NULL OR visibility_until <= %s)
				ORDER BY priority ASC, id ASC
				LIMIT %d
				FOR UPDATE",
				$tenant,
				$queue,
				self::STATUS_PENDING,
				$now,
				$now,
				$limit
			)
		);

		$claimed = [];
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$wpdb->update(
					$table,
					[
						'status'            => self::STATUS_PROCESSING,
						'lease_owner'       => (string) $worker_id,
						'lease_token'       => $token,
						'visibility_until'  => $vis,
						'attempts'          => (int) $row->attempts + 1,
						'updated_at'        => current_time( 'mysql', true ),
					],
					[ 'id' => (int) $row->id ],
					[ '%s', '%s', '%s', '%s', '%d', '%s' ],
					[ '%d' ]
				);
				$row->status           = self::STATUS_PROCESSING;
				$row->lease_owner      = (string) $worker_id;
				$row->lease_token      = $token;
				$row->visibility_until = $vis;
				$row->attempts         = (int) $row->attempts + 1;
				$row->payload_decoded  = json_decode( (string) $row->payload, true );
				$claimed[]             = $row;
			}
		}
		$wpdb->query( 'COMMIT' );

		return $claimed;
	}

	/**
	 * Heartbeat lease.
	 *
	 * @param string $message_id Message.
	 * @param string $token      Lease token.
	 * @param int    $visibility Seconds.
	 * @return bool
	 */
	public static function heartbeat( $message_id, $token, $visibility = 60 ) {
		global $wpdb;
		$table = NGC_Platform_Schema::table( 'queue_messages' );
		$vis   = gmdate( 'Y-m-d H:i:s', time() + max( 15, (int) $visibility ) );
		$n     = $wpdb->update(
			$table,
			[ 'visibility_until' => $vis, 'updated_at' => current_time( 'mysql', true ) ],
			[
				'message_id'  => (string) $message_id,
				'lease_token' => (string) $token,
				'status'      => self::STATUS_PROCESSING,
			],
			[ '%s', '%s' ],
			[ '%s', '%s', '%s' ]
		);
		return false !== $n && $n > 0;
	}

	/**
	 * Acknowledge success.
	 *
	 * @param string $message_id Message.
	 * @param string $token      Lease.
	 * @return bool
	 */
	public static function ack( $message_id, $token ) {
		global $wpdb;
		$table = NGC_Platform_Schema::table( 'queue_messages' );
		$n     = $wpdb->update(
			$table,
			[
				'status'           => self::STATUS_DONE,
				'lease_owner'      => '',
				'lease_token'      => '',
				'visibility_until' => null,
				'updated_at'       => current_time( 'mysql', true ),
			],
			[
				'message_id'  => (string) $message_id,
				'lease_token' => (string) $token,
			],
			[ '%s', '%s', '%s', '%s', '%s' ],
			[ '%s', '%s' ]
		);
		if ( class_exists( 'NGC_Metrics' ) && $n ) {
			NGC_Metrics::inc( 'queue_acked_total', 1 );
		}
		return false !== $n && $n > 0;
	}

	/**
	 * Negative ack — delay or DLQ.
	 *
	 * @param string $message_id Message.
	 * @param string $token      Lease.
	 * @param string $error      Error.
	 * @return bool
	 */
	public static function nack( $message_id, $token, $error = '' ) {
		global $wpdb;
		$table = NGC_Platform_Schema::table( 'queue_messages' );
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE message_id = %s AND lease_token = %s LIMIT 1",
				(string) $message_id,
				(string) $token
			)
		);
		if ( ! $row ) {
			return false;
		}

		$attempts = (int) $row->attempts;
		$max      = (int) $row->max_attempts;
		if ( $attempts >= $max ) {
			NGC_Queue_DLQ::move( $row, $error ?: 'max_attempts' );
			$wpdb->update(
				$table,
				[
					'status'      => self::STATUS_FAILED,
					'last_error'  => substr( (string) $error, 0, 2000 ),
					'lease_owner' => '',
					'lease_token' => '',
					'updated_at'  => current_time( 'mysql', true ),
				],
				[ 'id' => (int) $row->id ],
				[ '%s', '%s', '%s', '%s', '%s' ],
				[ '%d' ]
			);
			return true;
		}

		$delay = self::backoff_seconds( $attempts );
		$wpdb->update(
			$table,
			[
				'status'           => self::STATUS_PENDING,
				'delay_until'      => gmdate( 'Y-m-d H:i:s', time() + $delay ),
				'visibility_until' => null,
				'lease_owner'      => '',
				'lease_token'      => '',
				'last_error'       => substr( (string) $error, 0, 2000 ),
				'updated_at'       => current_time( 'mysql', true ),
			],
			[ 'id' => (int) $row->id ],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ],
			[ '%d' ]
		);
		if ( class_exists( 'NGC_Metrics' ) ) {
			NGC_Metrics::inc( 'queue_nacked_total', 1 );
		}
		return true;
	}

	/**
	 * Exponential + decorrelated jitter backoff.
	 *
	 * @param int $attempt Attempt number.
	 * @return int
	 */
	public static function backoff_seconds( $attempt ) {
		$base = (int) min( 3600, pow( 2, max( 0, (int) $attempt ) ) );
		$jitter = wp_rand( 0, max( 1, (int) ( $base * 0.5 ) ) );
		return max( 1, $base + $jitter );
	}

	/**
	 * Stats per queue.
	 *
	 * @return array
	 */
	public static function stats() {
		global $wpdb;
		$table  = NGC_Platform_Schema::table( 'queue_messages' );
		$tenant = NGC_Tenant_Context::id();
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT queue_name, status, COUNT(*) AS c FROM {$table} WHERE tenant_id = %d GROUP BY queue_name, status",
				$tenant
			),
			ARRAY_A
		);
		$out = [];
		foreach ( (array) $rows as $r ) {
			$q = $r['queue_name'];
			if ( ! isset( $out[ $q ] ) ) {
				$out[ $q ] = [];
			}
			$out[ $q ][ $r['status'] ] = (int) $r['c'];
		}
		$dlq = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . NGC_Platform_Schema::table( 'queue_dlq' ) . ' WHERE tenant_id = %d AND replayed = 0',
				$tenant
			)
		);
		return [ 'queues' => $out, 'dlq_open' => $dlq ];
	}
}
