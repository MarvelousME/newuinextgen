<?php
/**
 * Real-data repository and internal helper layer.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRUD/query helpers for platform entities.
 */
class NGC_Platform_Repository {

	/** @var string[] */
	private static $table_map = [
		'bookings'      => 'bookings',
		'matches'       => 'matches',
		'invoices'      => 'invoices',
		'wallets'       => 'wallet_ledger',
		'reviews'       => 'reviews',
		'ratings'       => 'ratings',
		'payouts'       => 'payouts',
		'earnings'      => 'earnings',
		'analytics'     => 'analytics_events',
		'audit'         => 'audit_log',
		'sessions'      => 'user_sessions',
		'user_profiles' => 'user_profiles',
		'visitors'      => 'visitor_profiles',
		'acquisition'   => 'acquisition_sources',
		'affiliates'    => 'affiliate_clicks',
		'attribution'   => 'attribution_links',
		'conversions'   => 'conversion_events',
		'snapshots'     => 'metric_snapshots',
		'demo_seed'     => 'demo_seed_log',
		'consent'       => 'consent_log',
	];

	/**
	 * Resolve table name for export by entity key.
	 *
	 * @param string $entity Entity key.
	 * @return string
	 */
	public static function table_for_export( $entity ) {
		return self::table_for( $entity );
	}

	/**
	 * Resolve table by entity key.
	 *
	 * @param string $entity Entity key.
	 * @return string
	 */
	private static function table_for( $entity ) {
		$key = self::$table_map[ $entity ] ?? '';
		return $key ? NGC_Database::table( $key ) : '';
	}

	/**
	 * @param string $entity Entity.
	 * @param int    $id     Record ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_by_id( $entity, $id ) {
		global $wpdb;
		$table = self::table_for( $entity );
		if ( ! $table || $id <= 0 ) {
			return null;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return $row ?: null;
	}

	/**
	 * @param string               $entity Entity.
	 * @param array<string, mixed> $args   Filters.
	 * @return array<int, array<string, mixed>>
	 */
	public static function list( $entity, $args = [] ) {
		global $wpdb;
		$table = self::table_for( $entity );
		if ( ! $table ) {
			return [];
		}

		$where  = [ '1=1' ];
		$values = [];
		foreach ( $args as $key => $value ) {
			if ( in_array( $key, [ 'limit', 'offset', 'order_by', 'order' ], true ) ) {
				continue;
			}
			$where[]  = sanitize_key( $key ) . ' = %s';
			$values[] = (string) $value;
		}

		$order_by = sanitize_key( $args['order_by'] ?? 'id' );
		$order    = 'asc' === strtolower( (string) ( $args['order'] ?? 'desc' ) ) ? 'ASC' : 'DESC';
		$limit    = max( 1, min( 500, (int) ( $args['limit'] ?? 20 ) ) );
		$offset   = max( 0, (int) ( $args['offset'] ?? 0 ) );
		$sql      = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . " ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d";
		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
	}

	/**
	 * @param string               $entity Entity.
	 * @param array<string, mixed> $args   Filters.
	 * @return int
	 */
	public static function count( $entity, $args = [] ) {
		global $wpdb;
		$table = self::table_for( $entity );
		if ( ! $table ) {
			return 0;
		}
		$where  = [ '1=1' ];
		$values = [];
		foreach ( $args as $key => $value ) {
			$where[]  = sanitize_key( $key ) . ' = %s';
			$values[] = (string) $value;
		}
		$sql = "SELECT COUNT(*) FROM {$table} WHERE " . implode( ' AND ', $where );
		if ( empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var( $sql );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $values ) );
	}

	/**
	 * @param string $entity Entity.
	 * @param string $term   Search term.
	 * @param string $column Column.
	 * @param int    $limit  Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search( $entity, $term, $column = 'id', $limit = 20 ) {
		global $wpdb;
		$table = self::table_for( $entity );
		if ( ! $table ) {
			return [];
		}
		$column = sanitize_key( $column );
		$limit  = max( 1, min( 200, (int) $limit ) );
		$like   = '%' . $wpdb->esc_like( (string) $term ) . '%';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$column} LIKE %s ORDER BY id DESC LIMIT %d", $like, $limit ), ARRAY_A );
	}

	/**
	 * @param string               $entity Entity.
	 * @param array<string, mixed> $data   Data.
	 * @return int|WP_Error
	 */
	public static function create( $entity, $data ) {
		global $wpdb;
		$table = self::table_for( $entity );
		if ( ! $table ) {
			return new WP_Error( 'ngc_entity_not_supported', __( 'Unsupported entity.', 'nextgencompanion' ) );
		}
		$valid = self::validate( $entity, $data );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$payload = self::map_to_response( $entity, $data );
		$ok      = $wpdb->insert( $table, $payload );
		if ( ! $ok ) {
			return new WP_Error( 'ngc_repo_insert_failed', __( 'Failed to create record.', 'nextgencompanion' ) );
		}
		return (int) $wpdb->insert_id;
	}

	/**
	 * @param string               $entity Entity.
	 * @param int                  $id     Record ID.
	 * @param array<string, mixed> $data   Data.
	 * @return bool|WP_Error
	 */
	public static function update( $entity, $id, $data ) {
		global $wpdb;
		$table = self::table_for( $entity );
		if ( ! $table ) {
			return new WP_Error( 'ngc_entity_not_supported', __( 'Unsupported entity.', 'nextgencompanion' ) );
		}
		if ( $id <= 0 ) {
			return new WP_Error( 'ngc_invalid_id', __( 'Invalid record ID.', 'nextgencompanion' ) );
		}
		$valid = self::validate( $entity, $data, true );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$payload = self::map_to_response( $entity, $data );
		unset( $payload['id'] );
		$updated = $wpdb->update( $table, $payload, [ 'id' => $id ] );
		return false !== $updated;
	}

	/**
	 * @param string $entity Entity.
	 * @param int    $id     ID.
	 * @return bool|WP_Error
	 */
	public static function delete_or_archive( $entity, $id ) {
		global $wpdb;
		$table = self::table_for( $entity );
		if ( ! $table || $id <= 0 ) {
			return new WP_Error( 'ngc_invalid_delete', __( 'Invalid delete request.', 'nextgencompanion' ) );
		}
		$existing = self::get_by_id( $entity, $id );
		if ( ! $existing ) {
			return true;
		}
		if ( array_key_exists( 'status', $existing ) ) {
			return false !== $wpdb->update( $table, [ 'status' => 'archived' ], [ 'id' => $id ] );
		}
		return false !== $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * @param string               $entity   Entity.
	 * @param array<string, mixed> $data     Data.
	 * @param bool                 $is_update Update mode.
	 * @return true|WP_Error
	 */
	public static function validate( $entity, $data, $is_update = false ) {
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'ngc_invalid_payload', __( 'Payload must be an array.', 'nextgencompanion' ) );
		}
		if ( ! $is_update && empty( $data ) ) {
			return new WP_Error( 'ngc_empty_payload', __( 'Payload is empty.', 'nextgencompanion' ) );
		}
		return true;
	}

	/**
	 * @param string               $entity Entity.
	 * @param array<string, mixed> $data   Data.
	 * @return array<string, mixed>
	 */
	public static function map_to_response( $entity, $data ) {
		$mapped = [];
		foreach ( $data as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( is_numeric( $value ) ) {
				$mapped[ $key ] = 0 + $value;
			} elseif ( is_array( $value ) || is_object( $value ) ) {
				$mapped[ $key ] = wp_json_encode( $value );
			} else {
				$mapped[ $key ] = sanitize_text_field( (string) $value );
			}
		}
		if ( ! isset( $mapped['created_at'] ) ) {
			$mapped['created_at'] = current_time( 'mysql', true );
		}
		if ( array_key_exists( 'updated_at', $mapped ) || in_array( $entity, [ 'user_profiles', 'visitors' ], true ) ) {
			$mapped['updated_at'] = current_time( 'mysql', true );
		}
		return $mapped;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function verify_schema() {
		global $wpdb;
		$missing = [];
		foreach ( self::$table_map as $entity => $key ) {
			$table = NGC_Database::table( $key );
			if ( ! $table ) {
				$missing[] = $entity;
				continue;
			}
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = (bool) $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
			if ( ! $exists ) {
				$missing[] = $entity;
			}
		}
		return [
			'ok'      => empty( $missing ),
			'missing' => $missing,
		];
	}
}

