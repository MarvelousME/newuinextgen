<?php
declare(strict_types=1);

namespace NGTBM\Domain\Notification;

/**
 * Persistent notification center store.
 */
final class NotificationStore {

	/**
	 * @return list<array<string,mixed>>
	 */
	public static function list( int $limit = 50 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'ngtbm_notifications';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", max( 1, min( 200, $limit ) ) ), ARRAY_A );
		if ( ! is_array( $rows ) || $rows === [] ) {
			self::seed_defaults();
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", max( 1, min( 200, $limit ) ) ), ARRAY_A );
		}
		return array_map( [ self::class, 'normalize' ], is_array( $rows ) ? $rows : [] );
	}

	public static function acknowledge( int $id ): void {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'ngtbm_notifications',
			[
				'status'   => 'acked',
				'acked_at' => current_time( 'mysql', true ),
			],
			[ 'id' => $id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * @param array<string,mixed> $row
	 * @return array<string,mixed>
	 */
	private static function normalize( array $row ): array {
		return [
			'id'            => (int) ( $row['id'] ?? 0 ),
			'severity'      => (string) ( $row['severity'] ?? 'info' ),
			'source'        => (string) ( $row['source'] ?? '' ),
			'title'         => (string) ( $row['title'] ?? '' ),
			'body'          => (string) ( $row['body'] ?? '' ),
			'correlationId' => (string) ( $row['correlation_id'] ?? '' ),
			'status'        => (string) ( $row['status'] ?? 'open' ),
			'actionLabel'   => (string) ( $row['action_label'] ?? '' ),
			'actionUrl'     => (string) ( $row['action_url'] ?? '' ),
			'createdAt'     => (string) ( $row['created_at'] ?? '' ),
		];
	}

	private static function seed_defaults(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'ngtbm_notifications';
		$now   = current_time( 'mysql', true );
		$seeds = [
			[ 'warning', 'talent', 'Talent AI latency', 'p95 latency above threshold', 'Investigate' ],
			[ 'warning', 'whatsapp', 'WhatsApp reconnect', 'Session requires reconnect', 'Reconnect' ],
			[ 'critical', 'queue', '2 messages in DLQ', 'Dead-letter messages awaiting inspection', 'Inspect' ],
		];
		foreach ( $seeds as $s ) {
			$wpdb->insert(
				$table,
				[
					'severity'       => $s[0],
					'source'         => $s[1],
					'title'          => $s[2],
					'body'           => $s[3],
					'correlation_id' => 'seed_' . wp_generate_password( 8, false, false ),
					'status'         => 'open',
					'action_label'   => $s[4],
					'action_url'     => '',
					'created_at'     => $now,
				],
				[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
			);
		}
	}
}
