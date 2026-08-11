<?php
declare(strict_types=1);

namespace NGTBM\Domain\Audit;

/**
 * Control-plane audit mirror.
 */
final class AuditLog {

	/**
	 * @param array<string,mixed> $detail
	 */
	public static function write( string $action, string $resource, string $resource_id, array $detail = [] ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'ngtbm_audit',
			[
				'actor_id'    => get_current_user_id(),
				'action'      => sanitize_key( $action ),
				'resource'    => sanitize_key( $resource ),
				'resource_id' => substr( sanitize_text_field( $resource_id ), 0, 128 ),
				'detail'      => wp_json_encode( $detail ) ?: '{}',
				'created_at'  => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * @return list<array<string,mixed>>
	 */
	public static function list( int $limit = 50 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'ngtbm_audit';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", max( 1, min( 200, $limit ) ) ), ARRAY_A );
		$out  = [];
		foreach ( is_array( $rows ) ? $rows : [] as $row ) {
			$out[] = [
				'id'         => (int) $row['id'],
				'actorId'    => (int) $row['actor_id'],
				'action'     => (string) $row['action'],
				'resource'   => (string) $row['resource'],
				'resourceId' => (string) $row['resource_id'],
				'detail'     => json_decode( (string) $row['detail'], true ) ?: [],
				'createdAt'  => (string) $row['created_at'],
			];
		}
		return $out;
	}
}
