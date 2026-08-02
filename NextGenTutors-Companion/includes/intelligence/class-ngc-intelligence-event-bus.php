<?php
/**
 * Central event ingestion, enrichment, persistence, and broadcast.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Intelligence event bus — single entry for all operational events.
 */
final class NGC_Intelligence_Event_Bus {

	/**
	 * Ingest a structured event.
	 *
	 * @param array<string, mixed> $raw Raw event.
	 * @return int Event row ID or 0.
	 */
	public static function ingest( array $raw ) {
		if ( ! NGC_Intelligence_Config::is_enabled() ) {
			return 0;
		}
		if ( ! NGC_Intelligence_Config::should_sample() && empty( $raw['force'] ) ) {
			return 0;
		}

		$event = NGC_Intelligence_Schema::normalize( $raw );
		if ( is_wp_error( $event ) ) {
			if ( class_exists( 'NGC_System_Log' ) ) {
				NGC_System_Log::warning( 'intelligence', 'ingest', $event->get_error_message(), $raw );
			}
			return 0;
		}

		unset( $event['force'] );
		$event = self::enrich( $event );
		$id    = self::persist( $event );

		if ( $id > 0 ) {
			NGC_Intelligence_Stream::push( 'event.ingested', [
				'id'         => $id,
				'event_key'  => $event['event_key'],
				'severity'   => $event['severity'],
				'domain'     => $event['domain'],
				'plugin_slug'=> $event['plugin_slug'],
			] );
			NGC_Intelligence_Kpi_Engine::touch_bucket( $event );
			NGC_Intelligence_Alerts::evaluate( $event );
			/**
			 * Fires after an intelligence event is stored.
			 *
			 * @param array<string, mixed> $event Normalized event.
			 * @param int                  $id    Row ID.
			 */
			do_action( 'ngc_intelligence_event_ingested', $event, $id );
		}

		return $id;
	}

	/**
	 * @param array<string, mixed> $event Event.
	 * @return array<string, mixed>
	 */
	private static function enrich( array $event ) {
		$config = NGC_Intelligence_Config::get();
		if ( ! empty( $config['mask_pii'] ) ) {
			if ( isset( $event['payload']['email'] ) ) {
				$event['payload']['email'] = self::mask_email( (string) $event['payload']['email'] );
			}
			if ( isset( $event['payload']['phone'] ) ) {
				$event['payload']['phone'] = '***' . substr( (string) $event['payload']['phone'], -4 );
			}
		}
		$event['context']['site_url'] = site_url();
		$event['context']['php']      = PHP_VERSION;
		$event['context']['wp']       = get_bloginfo( 'version' );
		return $event;
	}

	/**
	 * @param array<string, mixed> $event Event.
	 * @return int
	 */
	private static function persist( array $event ) {
		global $wpdb;
		if ( ! class_exists( 'NGC_Database' ) ) {
			return 0;
		}
		$table = NGC_Database::table( 'intel_events' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			NGC_Database::create_tables();
		}

		$ok = $wpdb->insert(
			$table,
			[
				'uuid'           => $event['uuid'],
				'event_key'      => $event['event_key'],
				'plugin_slug'    => $event['plugin_slug'],
				'module'         => $event['module'],
				'feature'        => $event['feature'],
				'domain'         => $event['domain'],
				'severity'       => $event['severity'],
				'outcome'        => $event['outcome'],
				'user_id'        => $event['user_id'],
				'correlation_id' => $event['correlation_id'],
				'request_id'     => $event['request_id'],
				'duration_ms'    => $event['duration_ms'],
				'message'        => $event['message'],
				'payload'        => wp_json_encode( $event['payload'] ),
				'context'        => wp_json_encode( $event['context'] ),
				'source'         => $event['source'],
				'recorded_at'    => $event['recorded_at'],
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Query events for data grid.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array{rows: array<int, array<string, mixed>>, total: int}
	 */
	public static function query( array $args = [] ) {
		global $wpdb;
		$table = NGC_Database::table( 'intel_events' );
		$page  = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per   = min( 100, max( 10, (int) ( $args['per_page'] ?? 25 ) ) );
		$offset = ( $page - 1 ) * $per;

		$where  = [ '1=1' ];
		$params = [];

		if ( ! empty( $args['domain'] ) ) {
			$where[]  = 'domain = %s';
			$params[] = sanitize_key( (string) $args['domain'] );
		}
		if ( ! empty( $args['plugin_slug'] ) ) {
			$where[]  = 'plugin_slug = %s';
			$params[] = sanitize_key( (string) $args['plugin_slug'] );
		}
		if ( ! empty( $args['severity'] ) ) {
			$where[]  = 'severity = %s';
			$params[] = sanitize_key( (string) $args['severity'] );
		}
		if ( ! empty( $args['event_key'] ) ) {
			$where[]  = 'event_key LIKE %s';
			$params[] = '%' . $wpdb->esc_like( sanitize_key( (string) $args['event_key'] ) ) . '%';
		}
		if ( ! empty( $args['module'] ) ) {
			$where[]  = 'module = %s';
			$params[] = sanitize_key( (string) $args['module'] );
		}
		if ( ! empty( $args['feature'] ) ) {
			$where[]  = 'feature = %s';
			$params[] = sanitize_key( (string) $args['feature'] );
		}
		if ( ! empty( $args['search'] ) ) {
			$where[]  = 'message LIKE %s';
			$params[] = '%' . $wpdb->esc_like( sanitize_text_field( (string) $args['search'] ) ) . '%';
		}

		if ( ! empty( $args['since_id'] ) ) {
			$where[]  = 'id < %d';
			$params[] = (int) $args['since_id'];
			$page     = 1;
			$offset   = 0;
		}

		$sql_where = implode( ' AND ', $where );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$sql_where}", $params ) );

		$params[] = $per;
		$params[] = $offset;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, uuid, event_key, plugin_slug, module, feature, domain, severity, outcome, user_id, correlation_id, duration_ms, message, recorded_at FROM {$table} WHERE {$sql_where} ORDER BY id DESC LIMIT %d OFFSET %d",
				$params
			),
			ARRAY_A
		);

		return [
			'rows'  => is_array( $rows ) ? $rows : [],
			'total' => $total,
			'page'  => $page,
			'pages' => (int) ceil( $total / $per ),
		];
	}

	/**
	 * @param int $id Event ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_by_id( $id ) {
		global $wpdb;
		$id = (int) $id;
		if ( $id <= 0 ) {
			return null;
		}
		$table = NGC_Database::table( 'intel_events' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
			ARRAY_A
		);
		if ( ! is_array( $row ) ) {
			return null;
		}
		if ( ! empty( $row['payload'] ) ) {
			$row['payload'] = json_decode( (string) $row['payload'], true );
		}
		if ( ! empty( $row['context'] ) ) {
			$row['context'] = json_decode( (string) $row['context'], true );
		}
		return $row;
	}

	/**
	 * Export events as CSV string.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return string
	 */
	public static function export_csv( array $args = [] ) {
		$args['per_page'] = min( 5000, max( 100, (int) ( $args['per_page'] ?? 1000 ) ) );
		$args['page']     = 1;
		$result           = self::query( $args );
		$lines            = [ 'id,event_key,plugin_slug,module,feature,domain,severity,outcome,user_id,message,recorded_at' ];
		foreach ( $result['rows'] as $row ) {
			$lines[] = implode(
				',',
				array_map(
					static function ( $v ) {
						return '"' . str_replace( '"', '""', (string) $v ) . '"';
					},
					[
						$row['id'] ?? '',
						$row['event_key'] ?? '',
						$row['plugin_slug'] ?? '',
						$row['module'] ?? '',
						$row['feature'] ?? '',
						$row['domain'] ?? '',
						$row['severity'] ?? '',
						$row['outcome'] ?? '',
						$row['user_id'] ?? '',
						$row['message'] ?? '',
						$row['recorded_at'] ?? '',
					]
				)
			);
		}
		NGC_Intelligence_Audit::log( 'events.export', [ 'count' => count( $result['rows'] ) ] );
		return implode( "\n", $lines );
	}

	/**
	 * @param string $email Email.
	 * @return string
	 */
	private static function mask_email( $email ) {
		if ( ! is_email( $email ) ) {
			return '***';
		}
		$parts = explode( '@', $email );
		return substr( $parts[0], 0, 1 ) . '***@' . $parts[1];
	}
}
