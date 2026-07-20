<?php
/**
 * System log query service — search, stats, import.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query layer for ngc_system_log.
 */
class NGC_System_Log_Service {

	/**
	 * Search log entries.
	 *
	 * @param array<string, mixed> $args Filters.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search( $args = [] ) {
		global $wpdb;
		$table  = NGC_Database::table( 'system_log' );
		if ( ! $table ) {
			return [];
		}

		$where  = [ '1=1' ];
		$values = [];

		foreach ( [ 'level', 'channel', 'source', 'correlation_id' ] as $key ) {
			if ( ! empty( $args[ $key ] ) ) {
				$where[]  = "{$key} = %s";
				$values[] = sanitize_text_field( (string) $args[ $key ] );
			}
		}
		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$values[] = (int) $args['user_id'];
		}
		if ( ! empty( $args['from'] ) ) {
			$where[]  = 'created_at >= %s';
			$values[] = sanitize_text_field( (string) $args['from'] ) . ' 00:00:00';
		}
		if ( ! empty( $args['to'] ) ) {
			$where[]  = 'created_at <= %s';
			$values[] = sanitize_text_field( (string) $args['to'] ) . ' 23:59:59';
		}
		if ( ! empty( $args['ids'] ) && is_array( $args['ids'] ) ) {
			$ids = array_map( 'intval', $args['ids'] );
			$ids = array_filter( $ids );
			if ( $ids ) {
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				$where[]      = "id IN ({$placeholders})";
				$values       = array_merge( $values, $ids );
			}
		}
		if ( ! empty( $args['q'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( (string) $args['q'] ) ) . '%';
			$where[]  = '(message LIKE %s OR context LIKE %s OR source LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		$limit    = max( 1, min( 10000, (int) ( $args['limit'] ?? 100 ) ) );
		$offset   = max( 0, (int) ( $args['offset'] ?? 0 ) );
		$values[] = $limit;
		$values[] = $offset;

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT %d OFFSET %d';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );

		foreach ( $rows as &$row ) {
			$row['context'] = json_decode( (string) ( $row['context'] ?? '{}' ), true ) ?: [];
		}
		return $rows;
	}

	/**
	 * Count matching rows.
	 *
	 * @param array<string, mixed> $args Filters.
	 * @return int
	 */
	public static function count( $args = [] ) {
		global $wpdb;
		$table = NGC_Database::table( 'system_log' );
		if ( ! $table ) {
			return 0;
		}
		$args['limit']  = 1;
		$args['offset'] = 0;
		unset( $args['limit'], $args['offset'] );

		$where  = [ '1=1' ];
		$values = [];
		if ( ! empty( $args['level'] ) ) {
			$where[]  = 'level = %s';
			$values[] = sanitize_text_field( (string) $args['level'] );
		}
		if ( ! empty( $args['channel'] ) ) {
			$where[]  = 'channel = %s';
			$values[] = sanitize_text_field( (string) $args['channel'] );
		}
		if ( ! empty( $args['from'] ) ) {
			$where[]  = 'created_at >= %s';
			$values[] = sanitize_text_field( (string) $args['from'] ) . ' 00:00:00';
		}
		if ( ! empty( $args['to'] ) ) {
			$where[]  = 'created_at <= %s';
			$values[] = sanitize_text_field( (string) $args['to'] ) . ' 23:59:59';
		}

		$sql = "SELECT COUNT(*) FROM {$table} WHERE " . implode( ' AND ', $where );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $values ? $wpdb->prepare( $sql, $values ) : $sql );
	}

	/**
	 * Aggregated stats for dashboard charts.
	 *
	 * @param array<string, mixed> $args Date filters.
	 * @return array<string, mixed>
	 */
	public static function stats( $args = [] ) {
		global $wpdb;
		$table = NGC_Database::table( 'system_log' );
		if ( ! $table ) {
			return [ 'by_level' => [], 'by_channel' => [], 'by_source' => [], 'by_day' => [], 'total' => 0 ];
		}

		$where  = '1=1';
		$values = [];
		if ( ! empty( $args['from'] ) ) {
			$where   .= ' AND created_at >= %s';
			$values[] = sanitize_text_field( (string) $args['from'] ) . ' 00:00:00';
		}
		if ( ! empty( $args['to'] ) ) {
			$where   .= ' AND created_at <= %s';
			$values[] = sanitize_text_field( (string) $args['to'] ) . ' 23:59:59';
		}

		$prepare = static function ( $sql ) use ( $wpdb, $values ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $values ? $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
		};

		$by_level   = $prepare( "SELECT level AS label, COUNT(*) AS count FROM {$table} WHERE {$where} GROUP BY level ORDER BY count DESC" );
		$by_channel = $prepare( "SELECT channel AS label, COUNT(*) AS count FROM {$table} WHERE {$where} GROUP BY channel ORDER BY count DESC LIMIT 12" );
		$by_source  = $prepare( "SELECT source AS label, COUNT(*) AS count FROM {$table} WHERE {$where} GROUP BY source ORDER BY count DESC LIMIT 12" );
		$by_day     = $prepare( "SELECT DATE(created_at) AS label, COUNT(*) AS count FROM {$table} WHERE {$where} GROUP BY DATE(created_at) ORDER BY label ASC LIMIT 30" );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) ( $values ? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $values ) ) : $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where}" ) );

		return [
			'by_level'   => $by_level,
			'by_channel' => $by_channel,
			'by_source'  => $by_source,
			'by_day'     => $by_day,
			'total'      => $total,
		];
	}

	/**
	 * Import rows from parsed CSV array.
	 *
	 * @param array<int, array<string, string>> $rows Parsed rows with headers.
	 * @return array<string, int>
	 */
	public static function import_rows( $rows ) {
		$imported = 0;
		$skipped  = 0;
		foreach ( $rows as $row ) {
			$message = trim( (string) ( $row['message'] ?? '' ) );
			if ( ! $message ) {
				++$skipped;
				continue;
			}
			$context = [];
			if ( ! empty( $row['context'] ) ) {
				$decoded = json_decode( $row['context'], true );
				$context = is_array( $decoded ) ? $decoded : [ 'raw' => $row['context'] ];
			}
			NGC_System_Log::write(
				$row['level'] ?? 'info',
				$row['source'] ?? 'import',
				$row['channel'] ?? 'import',
				$message,
				$context
			);
			++$imported;
		}
		return [ 'imported' => $imported, 'skipped' => $skipped ];
	}

	/**
	 * Flatten rows for export.
	 *
	 * @param array<int, array<string, mixed>> $rows Log rows.
	 * @return array<int, array<string, mixed>>
	 */
	public static function flatten_for_export( $rows ) {
		$out = [];
		foreach ( $rows as $row ) {
			$out[] = [
				'id'             => $row['id'] ?? '',
				'uuid'           => $row['uuid'] ?? '',
				'level'          => $row['level'] ?? '',
				'channel'        => $row['channel'] ?? '',
				'source'         => $row['source'] ?? '',
				'message'        => $row['message'] ?? '',
				'context'        => is_array( $row['context'] ?? null ) ? wp_json_encode( $row['context'] ) : (string) ( $row['context'] ?? '' ),
				'user_id'        => $row['user_id'] ?? '',
				'correlation_id' => $row['correlation_id'] ?? '',
				'ip_address'     => $row['ip_address'] ?? '',
				'created_at'     => $row['created_at'] ?? '',
			];
		}
		return $out;
	}
}
