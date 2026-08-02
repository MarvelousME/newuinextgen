<?php
/**
 * In-app notification center for Mission Control.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Operational notifications with deduplication.
 */
final class NGC_Intelligence_Notifications {

	/**
	 * @param string               $type    info|warning|error|critical|success.
	 * @param string               $title   Title.
	 * @param string               $message Message.
	 * @param array<string, mixed> $meta    Meta.
	 * @param bool                 $dedupe  Dedupe by title hash.
	 * @return int
	 */
	public static function create( $type, $title, $message, array $meta = [], $dedupe = false ) {
		global $wpdb;
		$table = NGC_Database::table( 'intel_notifications' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			NGC_Database::create_tables();
		}

		$hash = md5( $type . '|' . $title );
		if ( $dedupe ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$existing = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE dedupe_hash = %s AND status = 'open' AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR)",
					$hash
				)
			);
			if ( $existing ) {
				return $existing;
			}
		}

		$wpdb->insert(
			$table,
			[
				'uuid'         => wp_generate_uuid4(),
				'type'         => sanitize_key( $type ),
				'title'        => sanitize_text_field( $title ),
				'message'      => sanitize_textarea_field( $message ),
				'meta'         => wp_json_encode( $meta ),
				'status'       => 'open',
				'dedupe_hash'  => $hash,
				'ack_user_id'  => 0,
				'created_at'   => gmdate( 'Y-m-d H:i:s' ),
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' ]
		);

		$id = (int) $wpdb->insert_id;
		if ( $id > 0 ) {
			NGC_Intelligence_Stream::push(
				'notification.created',
				[
					'id'    => $id,
					'type'  => $type,
					'title' => $title,
				]
			);
		}
		return $id;
	}

	/**
	 * @param array<string, mixed> $args Args.
	 * @return array{rows: array<int, array<string, mixed>>, total: int}
	 */
	public static function list( array $args = [] ) {
		global $wpdb;
		$table  = NGC_Database::table( 'intel_notifications' );
		$status = sanitize_key( (string) ( $args['status'] ?? 'open' ) );
		$limit  = min( 100, max( 5, (int) ( $args['limit'] ?? 30 ) ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, uuid, type, title, message, status, created_at FROM {$table} WHERE status = %s ORDER BY id DESC LIMIT %d",
				$status,
				$limit
			),
			ARRAY_A
		);
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status ) );

		return [
			'rows'  => is_array( $rows ) ? $rows : [],
			'total' => $total,
		];
	}

	/**
	 * @param int $id Notification ID.
	 * @return bool
	 */
	public static function acknowledge( $id ) {
		global $wpdb;
		$table = NGC_Database::table( 'intel_notifications' );
		$ok    = $wpdb->update(
			$table,
			[
				'status'      => 'acknowledged',
				'ack_user_id' => get_current_user_id(),
				'ack_at'      => gmdate( 'Y-m-d H:i:s' ),
			],
			[ 'id' => (int) $id ],
			[ '%s', '%d', '%s' ],
			[ '%d' ]
		);
		if ( $ok ) {
			NGC_Intelligence_Stream::push( 'notification.ack', [ 'id' => (int) $id ] );
			NGC_Intelligence_Audit::log( 'notification.acknowledged', [ 'id' => (int) $id ] );
		}
		return (bool) $ok;
	}
}
