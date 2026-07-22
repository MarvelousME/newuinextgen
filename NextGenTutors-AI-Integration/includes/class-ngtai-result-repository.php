<?php
/**
 * Agent result persistence.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prepared-SQL repository for versioned agent results.
 */
final class NGTAI_Result_Repository {

	/** @param NGTAI_Agent_Result $result Result. @return int|string */
	public static function insert( NGTAI_Agent_Result $result ) {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return 0;
		}
		$data        = $result->to_array();
		$result_json = wp_json_encode( $data['result'] );
		$error_json  = null === $data['error'] ? null : wp_json_encode( $data['error'] );
		if ( false === $result_json || false === $error_json ) {
			return 0;
		}
		$now    = gmdate( 'Y-m-d H:i:s' );
		$stored = $wpdb->insert(
			NGTAI_Database::table( 'agent_results' ),
			[
				'agent_run_id'    => $data['agent_run_id'],
				'result_version'  => $data['result_version'],
				'event_id'        => $data['event_id'],
				'correlation_id'  => $data['correlation_id'],
				'agent_name'      => $data['agent_name'],
				'action_name'     => $data['action_name'],
				'status'          => $data['status'],
				'policy_decision' => $data['policy_decision'],
				'approval_id'     => $data['approval_id'],
				'result_json'     => $result_json,
				'error_json'      => $error_json,
				'received_at'     => $now,
				'created_at'      => $now,
				'updated_at'      => $now,
			]
		);
		if ( false === $stored ) {
			return self::find_version( $data['agent_run_id'], $data['result_version'] ) ? 'duplicate' : 0;
		}
		return (int) $wpdb->insert_id;
	}

	/** @param NGTAI_Agent_Result|array<string,mixed> $result Result. @return int|string */
	public static function store( $result ) {
		if ( $result instanceof NGTAI_Agent_Result ) {
			return self::insert( $result );
		}
		if ( is_array( $result ) ) {
			return self::insert_record( $result );
		}
		return 0;
	}

	/**
	 * Persist a callback-shaped result row.
	 *
	 * @param array<string,mixed> $record Record.
	 * @return int|string
	 */
	private static function insert_record( array $record ) {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return 0;
		}
		$agent_run_id   = sanitize_text_field( (string) ( $record['agent_run_id'] ?? '' ) );
		$result_version = (int) ( $record['result_version'] ?? 1 );
		if ( '' === $agent_run_id || $result_version < 1 ) {
			return 0;
		}
		$now = gmdate( 'Y-m-d H:i:s' );
		$stored = $wpdb->insert(
			NGTAI_Database::table( 'agent_results' ),
			[
				'agent_run_id'    => $agent_run_id,
				'result_version'  => $result_version,
				'event_id'        => sanitize_text_field( (string) ( $record['event_id'] ?? '' ) ),
				'correlation_id'  => sanitize_text_field( (string) ( $record['correlation_id'] ?? '' ) ),
				'agent_name'      => sanitize_text_field( (string) ( $record['agent_name'] ?? '' ) ),
				'action_name'     => self::sanitize_action_name( (string) ( $record['action_name'] ?? '' ) ),
				'status'          => sanitize_key( (string) ( $record['status'] ?? 'received' ) ),
				'policy_decision' => isset( $record['policy_decision'] ) ? sanitize_key( (string) $record['policy_decision'] ) : null,
				'approval_id'     => isset( $record['approval_id'] ) ? sanitize_text_field( (string) $record['approval_id'] ) : null,
				'result_json'     => is_string( $record['result_json'] ?? null ) ? $record['result_json'] : wp_json_encode( $record['result'] ?? [] ),
				'error_json'      => isset( $record['error_json'] ) ? $record['error_json'] : null,
				'received_at'     => $now,
				'created_at'      => $now,
				'updated_at'      => $now,
			]
		);
		if ( false === $stored ) {
			return self::find_version( $agent_run_id, $result_version ) ? 'duplicate' : 0;
		}
		return (int) $wpdb->insert_id;
	}

	/** @param int $id ID. @return bool */
	public static function mark_applied( $id ) {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return false;
		}
		$now = gmdate( 'Y-m-d H:i:s' );
		return false !== $wpdb->update( NGTAI_Database::table( 'agent_results' ), [ 'applied_at' => $now, 'updated_at' => $now ], [ 'id' => (int) $id ] );
	}

	/** @param int $id ID. @return array<string,mixed>|null */
	public static function get( $id ) {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return null;
		}
		$table = NGTAI_Database::table( 'agent_results' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", (int) $id ), ARRAY_A );
		return self::hydrate( $row );
	}

	/** @param string $agent_run_id Run ID. @param int $version Version. @return array<string,mixed>|null */
	public static function find_version( $agent_run_id, $version ) {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return null;
		}
		$table = NGTAI_Database::table( 'agent_results' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE agent_run_id=%s AND result_version=%d", $agent_run_id, (int) $version ), ARRAY_A );
		return self::hydrate( $row );
	}

	/** @param string $event_id Event ID. @return array<int,array<string,mixed>> */
	public static function find_by_event_id( $event_id ) {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return [];
		}
		$table = NGTAI_Database::table( 'agent_results' );
		$rows  = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE event_id=%s ORDER BY result_version DESC", $event_id ), ARRAY_A );
		return array_values( array_filter( array_map( [ __CLASS__, 'hydrate' ], $rows ) ) );
	}

	/** @param array<string,mixed> $args Filters. @return array<int,array<string,mixed>> */
	public static function list_recent( $args = [] ) {
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return [];
		}
		$args   = array_merge( [ 'status' => null, 'agent_name' => null, 'limit' => 50 ], $args );
		$table  = NGTAI_Database::table( 'agent_results' );
		$where  = [ '1=%d' ];
		$values = [ 1 ];
		foreach ( [ 'status', 'agent_name' ] as $field ) {
			if ( is_string( $args[ $field ] ) && '' !== $args[ $field ] ) {
				$where[]  = "{$field}=%s";
				$values[] = $args[ $field ];
			}
		}
		$values[] = max( 1, min( 200, (int) $args['limit'] ) );
		$rows     = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT %d', ...$values ), ARRAY_A );
		return array_values( array_filter( array_map( [ __CLASS__, 'hydrate' ], $rows ) ) );
	}

	/** @param mixed $row Row. @return array<string,mixed>|null */
	private static function hydrate( $row ) {
		if ( ! is_array( $row ) ) {
			return null;
		}
		$row['result'] = json_decode( (string) $row['result_json'], true );
		$row['error']  = null === $row['error_json'] ? null : json_decode( (string) $row['error_json'], true );
		return $row;
	}

	/**
	 * Preserve dotted action identifiers used by agents-api contracts.
	 *
	 * @param string $value Action name.
	 * @return string
	 */
	private static function sanitize_action_name( $value ) {
		$value = strtolower( sanitize_text_field( (string) $value ) );
		return (string) preg_replace( '/[^a-z0-9._-]/', '', $value );
	}
}
