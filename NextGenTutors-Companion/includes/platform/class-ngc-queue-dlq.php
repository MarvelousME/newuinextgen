<?php
/**
 * Dead-letter queue for poison messages.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Move / inspect / replay DLQ entries.
 */
final class NGC_Queue_DLQ {

	/**
	 * Move a queue row into DLQ.
	 *
	 * @param object $row   Queue message row.
	 * @param string $reason Reason.
	 * @return int|false Insert id.
	 */
	public static function move( $row, $reason = '' ) {
		global $wpdb;
		$table = NGC_Platform_Schema::table( 'queue_dlq' );
		$ok    = $wpdb->insert(
			$table,
			[
				'tenant_id'   => (int) ( $row->tenant_id ?? NGC_Tenant_Context::id() ),
				'queue_name'  => (string) ( $row->queue_name ?? 'default' ),
				'message_id'  => (string) ( $row->message_id ?? '' ),
				'original_id' => (int) ( $row->id ?? 0 ),
				'payload'     => (string) ( $row->payload ?? '' ),
				'fingerprint' => (string) ( $row->fingerprint ?? '' ),
				'reason'      => substr( (string) $reason, 0, 2000 ),
				'attempts'    => (int) ( $row->attempts ?? 0 ),
				'replayed'    => 0,
				'created_at'  => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s' ]
		);

		if ( class_exists( 'NGC_Metrics' ) ) {
			NGC_Metrics::inc( 'queue_dlq_total', 1 );
		}

		if ( class_exists( 'NGC_Platform_Observability' ) ) {
			NGC_Platform_Observability::alert_dlq_growth();
		}

		return $ok ? (int) $wpdb->insert_id : false;
	}

	/**
	 * List open DLQ entries.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public static function list_open( $limit = 50 ) {
		global $wpdb;
		$table  = NGC_Platform_Schema::table( 'queue_dlq' );
		$tenant = NGC_Tenant_Context::id();
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE tenant_id = %d AND replayed = 0 ORDER BY id DESC LIMIT %d",
				$tenant,
				max( 1, min( 200, (int) $limit ) )
			)
		);
	}

	/**
	 * Replay a DLQ entry back onto the durable queue.
	 *
	 * @param int $dlq_id DLQ id.
	 * @return string|WP_Error New message id.
	 */
	public static function replay( $dlq_id ) {
		global $wpdb;
		$table  = NGC_Platform_Schema::table( 'queue_dlq' );
		$tenant = NGC_Tenant_Context::id();
		$row    = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND tenant_id = %d LIMIT 1",
				(int) $dlq_id,
				$tenant
			)
		);
		if ( ! $row ) {
			return new WP_Error( 'ngc_dlq_missing', 'DLQ entry not found.' );
		}
		$payload = json_decode( (string) $row->payload, true );
		if ( ! is_array( $payload ) ) {
			$payload = [ 'raw' => (string) $row->payload ];
		}
		$mid = NGC_Durable_Queue::enqueue(
			(string) $row->queue_name,
			$payload,
			[
				'idempotency_key' => 'replay:' . (string) $row->message_id . ':' . (string) $dlq_id,
				'priority'        => 50,
			]
		);
		if ( is_wp_error( $mid ) ) {
			return $mid;
		}
		$wpdb->update(
			$table,
			[ 'replayed' => 1 ],
			[ 'id' => (int) $dlq_id ],
			[ '%d' ],
			[ '%d' ]
		);
		return $mid;
	}
}
