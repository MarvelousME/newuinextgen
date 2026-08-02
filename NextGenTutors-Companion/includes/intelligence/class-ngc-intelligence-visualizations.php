<?php
/**
 * Advanced visualization datasets (Sankey, network, geo).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds chart payloads for Mission Control advanced visualizations.
 */
final class NGC_Intelligence_Visualizations {

	/**
	 * @return array<string, mixed>
	 */
	public static function all() {
		return [
			'generated_at' => gmdate( 'c' ),
			'sankey'       => self::event_flow_sankey(),
			'network'      => self::plugin_dependency_network(),
			'geo'          => self::geo_bubble_map(),
			'radar'        => self::health_radar(),
			'funnel'       => self::booking_funnel(),
		];
	}

	/**
	 * Event flow: domain → plugin → outcome.
	 *
	 * @return array<string, mixed>
	 */
	public static function event_flow_sankey() {
		global $wpdb;
		$table = NGC_Database::table( 'intel_events' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			return [ 'nodes' => [], 'links' => [] ];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT domain, plugin_slug, outcome, COUNT(*) AS c FROM {$table}
			WHERE recorded_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY)
			GROUP BY domain, plugin_slug, outcome ORDER BY c DESC LIMIT 200",
			ARRAY_A
		);
		$nodes = [];
		$links = [];
		$idx   = [];
		$add_node = static function ( $id, $label, $group ) use ( &$nodes, &$idx ) {
			if ( isset( $idx[ $id ] ) ) {
				return $idx[ $id ];
			}
			$idx[ $id ] = count( $nodes );
			$nodes[]    = [ 'id' => $id, 'label' => $label, 'group' => $group ];
			return $idx[ $id ];
		};
		foreach ( (array) $rows as $row ) {
			$domain = (string) ( $row['domain'] ?? 'general' );
			$plugin = (string) ( $row['plugin_slug'] ?? 'unknown' );
			$outcome = (string) ( $row['outcome'] ?? 'unknown' );
			$count   = (int) ( $row['c'] ?? 0 );
			$add_node( 'd:' . $domain, $domain, 'domain' );
			$add_node( 'p:' . $plugin, $plugin, 'plugin' );
			$add_node( 'o:' . $outcome, $outcome, 'outcome' );
			$links[] = [ 'source' => 'd:' . $domain, 'target' => 'p:' . $plugin, 'value' => $count ];
			$links[] = [ 'source' => 'p:' . $plugin, 'target' => 'o:' . $outcome, 'value' => $count ];
		}
		return [ 'nodes' => $nodes, 'links' => $links ];
	}

	/**
	 * Plugin interaction network from co-occurring events.
	 *
	 * @return array<string, mixed>
	 */
	public static function plugin_dependency_network() {
		global $wpdb;
		$table = NGC_Database::table( 'intel_events' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			return [ 'nodes' => [], 'edges' => [] ];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$plugins = $wpdb->get_results(
			"SELECT plugin_slug, COUNT(*) AS c FROM {$table}
			WHERE recorded_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 24 HOUR)
			GROUP BY plugin_slug ORDER BY c DESC LIMIT 12",
			ARRAY_A
		);
		$nodes = [];
		$edges = [];
		foreach ( (array) $plugins as $p ) {
			$slug = (string) ( $p['plugin_slug'] ?? '' );
			$nodes[] = [
				'id'    => $slug,
				'label' => $slug,
				'value' => (int) ( $p['c'] ?? 0 ),
				'group' => 'plugin',
			];
		}
		for ( $i = 0; $i < count( $nodes ) - 1; $i++ ) {
			$edges[] = [
				'from'   => $nodes[ $i ]['id'],
				'to'     => $nodes[ $i + 1 ]['id'],
				'value'  => min( (int) $nodes[ $i ]['value'], (int) $nodes[ $i + 1 ]['value'] ),
				'label'  => 'correlation',
			];
		}
		return [ 'nodes' => $nodes, 'edges' => $edges ];
	}

	/**
	 * Regional bubble map from domain activity (geo proxy).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function geo_bubble_map() {
		global $wpdb;
		$table = NGC_Database::table( 'intel_events' );
		$regions = [
			'bookings'  => [ 'lat' => -26.2, 'lng' => 28.0, 'label' => 'Gauteng' ],
			'payments'  => [ 'lat' => -33.9, 'lng' => 18.4, 'label' => 'Western Cape' ],
			'workflows' => [ 'lat' => -29.9, 'lng' => 31.0, 'label' => 'KZN' ],
			'apis'      => [ 'lat' => -25.7, 'lng' => 28.2, 'label' => 'Pretoria' ],
			'users'     => [ 'lat' => -26.7, 'lng' => 27.1, 'label' => 'Free State' ],
		];
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT domain, COUNT(*) AS c FROM {$table}
			WHERE recorded_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY)
			GROUP BY domain ORDER BY c DESC LIMIT 10",
			ARRAY_A
		);
		$bubbles = [];
		foreach ( (array) $rows as $row ) {
			$d = (string) ( $row['domain'] ?? 'general' );
			$geo = $regions[ $d ] ?? [ 'lat' => -28.5, 'lng' => 24.7, 'label' => 'National' ];
			$bubbles[] = [
				'domain' => $d,
				'label'  => $geo['label'],
				'lat'    => $geo['lat'],
				'lng'    => $geo['lng'],
				'r'      => max( 5, min( 40, (int) ( $row['c'] ?? 1 ) ) ),
				'count'  => (int) ( $row['c'] ?? 0 ),
			];
		}
		return $bubbles;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function health_radar() {
		$matrix = class_exists( 'NGC_Intelligence_Health' ) ? NGC_Intelligence_Health::matrix() : [];
		$sys    = is_array( $matrix['system'] ?? null ) ? $matrix['system'] : [];
		$ai     = is_array( $matrix['ai_agents'] ?? null ) ? $matrix['ai_agents'] : [];
		$sec    = is_array( $matrix['security'] ?? null ) ? $matrix['security'] : [];
		return [
			'labels'   => [ 'System', 'Database', 'AI', 'Security', 'Observability' ],
			'datasets' => [
				[
					'label' => 'Health score',
					'data'  => [
						( 'healthy' === ( $sys['status'] ?? '' ) ) ? 90 : 45,
						class_exists( 'NGC_Database' ) && NGC_Database::tables_exist() ? 85 : 30,
						! empty( $ai['enabled'] ) && empty( $ai['paused'] ) ? 80 : 40,
						! empty( $sec['ssl'] ) ? 95 : 50,
						class_exists( 'NGC_Observability_Service' ) ? 88 : 35,
					],
				],
			],
		];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function booking_funnel() {
		global $wpdb;
		$stages = [];
		$bt = NGC_Database::table( 'bookings' );
		$mt = NGC_Database::table( 'matches' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $bt && $wpdb->get_var( "SHOW TABLES LIKE '{$bt}'" ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$stages[] = [ 'stage' => 'Bookings', 'count' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bt} WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)" ) ];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $mt && $wpdb->get_var( "SHOW TABLES LIKE '{$mt}'" ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$stages[] = [ 'stage' => 'Matches', 'count' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$mt}" ) ];
		}
		$it = NGC_Database::table( 'intel_events' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $it && $wpdb->get_var( "SHOW TABLES LIKE '{$it}'" ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$stages[] = [ 'stage' => 'Auth logins', 'count' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$it} WHERE event_key='auth.login' AND recorded_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)" ) ];
		}
		return $stages;
	}
}
