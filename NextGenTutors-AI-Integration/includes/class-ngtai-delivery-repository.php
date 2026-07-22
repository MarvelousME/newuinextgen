<?php
/**
 * Delivery persistence.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prepared-SQL repository for asynchronous deliveries.
 */
final class NGTAI_Delivery_Repository {

	private const STATUSES = [ 'pending', 'processing', 'delivered', 'retry_pending', 'failed', 'dead_letter', 'cancelled' ];

	/** @param NGTAI_Event_Envelope $event Event. @return int|string */
	public static function insert_pending( NGTAI_Event_Envelope $event ) {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return 0;
		}
		$json = wp_json_encode( $event->to_array() );
		if ( false === $json ) {
			return 0;
		}
		$now    = gmdate( 'Y-m-d H:i:s' );
		$table  = NGTAI_Database::table( 'deliveries' );
		$result = $wpdb->insert(
			$table,
			[
				'event_id'        => $event->get( 'event_id' ),
				'event_type'      => $event->get( 'event_type' ),
				'schema_version'  => $event->get( 'schema_version' ),
				'correlation_id'  => $event->get( 'correlation_id' ),
				'status'          => 'pending',
				'attempt_count'   => 0,
				'next_attempt_at' => $now,
				'request_hash'    => hash( 'sha256', $json ),
				'payload_json'    => $json,
				'created_at'      => $now,
				'updated_at'      => $now,
			]
		);
		if ( false === $result ) {
			return self::find_by_event_id( (string) $event->get( 'event_id' ) ) ? 'duplicate' : 0;
		}
		return (int) $wpdb->insert_id;
	}

	/** @param int $limit Limit. @return array<int,array<string,mixed>> */
	public static function claim_due( $limit = 10 ) {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return [];
		}
		$table = NGTAI_Database::table( 'deliveries' );
		$now   = gmdate( 'Y-m-d H:i:s' );
		$limit = max( 1, min( 100, (int) $limit ) );
		$ids   = (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE status IN (%s,%s) AND (next_attempt_at IS NULL OR next_attempt_at <= %s) ORDER BY id ASC LIMIT %d",
				'pending',
				'retry_pending',
				$now,
				$limit
			)
		);
		$claimed = [];
		$token   = 'worker-' . substr( class_exists( 'NGTAI_Signature' ) ? NGTAI_Signature::uuid() : sha1( uniqid( '', true ) ), 0, 48 );
		foreach ( $ids as $id ) {
			$updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET status=%s, locked_at=%s, locked_by=%s, updated_at=%s WHERE id=%d AND status IN (%s,%s) AND (next_attempt_at IS NULL OR next_attempt_at <= %s)",
					'processing',
					$now,
					$token,
					$now,
					(int) $id,
					'pending',
					'retry_pending',
					$now
				)
			);
			if ( 1 === (int) $updated ) {
				$row = self::get( (int) $id );
				if ( $row ) {
					$claimed[] = $row;
				}
			}
		}
		return $claimed;
	}

	/** @param int $id ID. @param int $http HTTP status. @param string $response_hash Hash. @return bool */
	public static function mark_delivered( $id, $http, $response_hash ) {
		return self::transition( $id, 'delivered', [ 'http_status' => (int) $http, 'response_hash' => substr( $response_hash, 0, 64 ), 'delivered_at' => gmdate( 'Y-m-d H:i:s' ), 'last_error' => '' ] );
	}

	/** @param int $id ID. @param int $attempt Attempt. @param string $error Error. @param int $http HTTP status. @param int|null $retry_after Retry delay. @return bool */
	public static function schedule_retry( $id, $attempt, $error, $http, $retry_after = null ) {
		$attempt = max( 1, (int) $attempt );
		if ( $attempt >= 5 ) {
			return self::mark_dead_letter( $id, $error, $http );
		}
		$schedule = [ 0, 30, 120, 600, 1800 ];
		$delay    = null !== $retry_after ? max( 0, (int) $retry_after ) : $schedule[ $attempt - 1 ];
		return self::transition(
			$id,
			'retry_pending',
			[
				'attempt_count'   => $attempt,
				'next_attempt_at' => gmdate( 'Y-m-d H:i:s', time() + $delay ),
				'last_error'      => substr( $error, 0, 1000 ),
				'http_status'     => (int) $http,
			]
		);
	}

	/** @param int $id ID. @param string $error Error. @param int $http Status. @return bool */
	public static function mark_dead_letter( $id, $error, $http ) {
		return self::transition( $id, 'dead_letter', [ 'last_error' => substr( $error, 0, 1000 ), 'http_status' => (int) $http ] );
	}

	/** @param int $id ID. @param string $error Error. @param int $http Status. @return bool */
	public static function mark_failed( $id, $error, $http ) {
		return self::transition( $id, 'failed', [ 'last_error' => substr( $error, 0, 1000 ), 'http_status' => (int) $http ] );
	}

	/** @param int $id ID. @return bool */
	public static function mark_cancelled( $id ) {
		return self::transition( $id, 'cancelled', [] );
	}

	/** @return array<string,int> */
	public static function counts() {
		global $wpdb;
		$out = array_fill_keys( self::STATUSES, 0 );
		if ( ! isset( $wpdb ) ) {
			return $out;
		}
		$table = NGTAI_Database::table( 'deliveries' );
		$rows  = (array) $wpdb->get_results( $wpdb->prepare( "SELECT status, COUNT(*) total FROM {$table} WHERE status IN (%s,%s,%s,%s,%s,%s,%s) GROUP BY status", ...self::STATUSES ), ARRAY_A );
		foreach ( $rows as $row ) {
			if ( isset( $out[ $row['status'] ] ) ) {
				$out[ $row['status'] ] = (int) $row['total'];
			}
		}
		return $out;
	}

	/** @param int $seconds Lock age. @return int */
	public static function recover_locks( $seconds = 300 ) {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return 0;
		}
		$table = NGTAI_Database::table( 'deliveries' );
		return (int) $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status=%s, locked_at=NULL, locked_by='', updated_at=%s WHERE status=%s AND locked_at < %s",
				'retry_pending',
				gmdate( 'Y-m-d H:i:s' ),
				'processing',
				gmdate( 'Y-m-d H:i:s', time() - max( 1, (int) $seconds ) )
			)
		);
	}

	/** @param int $id ID. @return array<string,mixed>|null */
	public static function get( $id ) {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return null;
		}
		$table = NGTAI_Database::table( 'deliveries' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", (int) $id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/** @param string $event_id Event ID. @return array<string,mixed>|null */
	public static function find_by_event_id( $event_id ) {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return null;
		}
		$table = NGTAI_Database::table( 'deliveries' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE event_id=%s", $event_id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	/** @param array<string,mixed> $args Filters. @return array<int,array<string,mixed>> */
	public static function list_recent( $args = [] ) {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return [];
		}
		$args   = array_merge( [ 'status' => null, 'event_type' => null, 'limit' => 50 ], $args );
		$table  = NGTAI_Database::table( 'deliveries' );
		$where  = [ '1=%d' ];
		$values = [ 1 ];
		if ( in_array( $args['status'], self::STATUSES, true ) ) {
			$where[]  = 'status=%s';
			$values[] = $args['status'];
		}
		if ( is_string( $args['event_type'] ) && '' !== $args['event_type'] ) {
			$where[]  = 'event_type=%s';
			$values[] = $args['event_type'];
		}
		$values[] = max( 1, min( 200, (int) $args['limit'] ) );
		$sql      = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT %d';
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, ...$values ), ARRAY_A );
	}

	/** @param int $id ID. @param string $status Status. @param array<string,mixed> $extra Extra fields. @return bool */
	private static function transition( $id, $status, array $extra ) {
		global $wpdb;
		if ( ! isset( $wpdb ) || ! in_array( $status, self::STATUSES, true ) ) {
			return false;
		}
		$data = array_merge( $extra, [ 'status' => $status, 'locked_at' => null, 'locked_by' => '', 'updated_at' => gmdate( 'Y-m-d H:i:s' ) ] );
		return false !== $wpdb->update( NGTAI_Database::table( 'deliveries' ), $data, [ 'id' => (int) $id ] );
	}
}
