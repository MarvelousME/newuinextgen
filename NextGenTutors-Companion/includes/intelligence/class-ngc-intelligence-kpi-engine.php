<?php
/**
 * KPI aggregation and executive dashboard metrics.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calculates live KPIs from domain tables + intelligence events.
 */
final class NGC_Intelligence_Kpi_Engine {

	/**
	 * Executive dashboard payload.
	 *
	 * @return array<string, mixed>
	 */
	public static function executive_dashboard() {
		return [
			'generated_at' => gmdate( 'c' ),
			'kpis'         => self::kpi_cards(),
			'series'       => [
				'bookings_7d' => self::bookings_series( 7 ),
				'errors_7d'   => self::errors_series( 7 ),
				'api_7d'      => self::api_series( 7 ),
			],
			'health'       => self::health_matrix(),
			'health_full'  => class_exists( 'NGC_Intelligence_Health' ) ? NGC_Intelligence_Health::matrix() : [],
			'workflows'    => self::workflow_stats(),
			'plugins'      => NGC_Intelligence_Registry::all()['plugins'] ?? [],
			'audit'        => class_exists( 'NGC_Intelligence_Audit' ) ? NGC_Intelligence_Audit::recent( 10 ) : [],
			'deployment'   => self::deployment_status(),
		];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function kpi_cards() {
		global $wpdb;
		$bookings_today = 0;
		$revenue_today  = 0.0;
		$pending_matches = 0;
		$errors_24h     = 0;
		$active_users   = 0;
		$workflows_run  = 0;

		if ( class_exists( 'NGC_Database' ) ) {
			$bt = NGC_Database::table( 'bookings' );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$bt}'" ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$bookings_today = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bt} WHERE DATE(created_at) = CURDATE()" );
			}
			$pt = NGC_Database::table( 'payments' );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$pt}'" ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$revenue_today = (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM {$pt} WHERE status='completed' AND DATE(created_at)=CURDATE()" );
			}
			$mt = NGC_Database::table( 'matches' );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$mt}'" ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$pending_matches = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$mt} WHERE status IN ('pending','proposed')" );
			}
			$wt = NGC_Database::table( 'workflow_runs' );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wt}'" ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$workflows_run = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wt} WHERE DATE(created_at)=CURDATE()" );
			}
			$sl = NGC_Database::table( 'system_log' );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$sl}'" ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$errors_24h = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sl} WHERE level IN ('error','critical') AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 24 HOUR)" );
			}
		}

		$it = NGC_Database::table( 'intel_events' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$it}'" ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$active_users = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$it} WHERE user_id > 0 AND recorded_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 24 HOUR)" );
		}

		return [
			[
				'key'   => 'bookings_today',
				'label' => __( 'Bookings today', 'nextgencompanion' ),
				'value' => $bookings_today,
				'trend' => self::trend_delta( 'bookings', 1 ),
				'drill' => [ 'domain' => 'bookings' ],
			],
			[
				'key'   => 'revenue_today',
				'label' => __( 'Revenue today', 'nextgencompanion' ),
				'value' => $revenue_today,
				'format'=> 'currency',
				'drill' => [ 'domain' => 'payments' ],
			],
			[
				'key'   => 'pending_matches',
				'label' => __( 'Pending matches', 'nextgencompanion' ),
				'value' => $pending_matches,
				'drill' => [ 'domain' => 'matching' ],
			],
			[
				'key'   => 'errors_24h',
				'label' => __( 'Errors (24h)', 'nextgencompanion' ),
				'value' => $errors_24h,
				'severity' => $errors_24h > 25 ? 'warning' : 'ok',
				'drill' => [ 'severity' => 'error' ],
			],
			[
				'key'   => 'active_users_24h',
				'label' => __( 'Active users (24h)', 'nextgencompanion' ),
				'value' => $active_users,
				'drill' => [ 'domain' => 'users' ],
			],
			[
				'key'   => 'workflows_today',
				'label' => __( 'Workflow runs today', 'nextgencompanion' ),
				'value' => $workflows_run,
				'drill' => [ 'domain' => 'workflows' ],
			],
			[
				'key'   => 'api_requests_24h',
				'label' => __( 'API requests (24h)', 'nextgencompanion' ),
				'value' => self::count_events( "event_key='api.rest.request' AND recorded_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 24 HOUR)" ),
				'drill' => [ 'event_key' => 'api.rest.request' ],
			],
			[
				'key'   => 'notifications_open',
				'label' => __( 'Open alerts', 'nextgencompanion' ),
				'value' => self::open_notifications_count(),
				'drill' => [ 'severity' => 'error' ],
			],
		];
	}

	/**
	 * @param string $where SQL where clause (no WHERE keyword).
	 * @return int
	 */
	private static function count_events( $where ) {
		global $wpdb;
		$table = NGC_Database::table( 'intel_events' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $table || ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where}" );
	}

	/**
	 * @return int
	 */
	private static function open_notifications_count() {
		global $wpdb;
		$table = NGC_Database::table( 'intel_notifications' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $table || ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status='open'" );
	}

	/**
	 * @param array<string, mixed> $event Event.
	 */
	public static function touch_bucket( array $event ) {
		global $wpdb;
		$table = NGC_Database::table( 'intel_kpi_hourly' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			return;
		}
		$hour = gmdate( 'Y-m-d H:00:00' );
		$metric = $event['domain'] . '.' . $event['event_key'];
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE bucket_hour = %s AND metric_key = %s AND plugin_slug = %s",
				$hour,
				$metric,
				$event['plugin_slug']
			)
		);
		if ( $existing ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET event_count = event_count + 1, error_count = error_count + %d WHERE id = %d", // phpcs:ignore
					in_array( $event['severity'], [ 'error', 'critical' ], true ) ? 1 : 0,
					$existing
				)
			);
		} else {
			$uuid = class_exists( 'NGC_Uuid' ) ? NGC_Uuid::generate() : wp_generate_uuid4();
			$wpdb->insert(
				$table,
				[
					'bucket_hour'  => $hour,
					'metric_key'   => $metric,
					'plugin_slug'  => $event['plugin_slug'],
					'domain'       => $event['domain'],
					'event_count'  => 1,
					'error_count'  => in_array( $event['severity'], [ 'error', 'critical' ], true ) ? 1 : 0,
					'uuid'         => $uuid,
				],
				[ '%s', '%s', '%s', '%s', '%d', '%d', '%s' ]
			);
		}
	}

	/**
	 * @param string $domain Domain.
	 * @param int    $days   Days.
	 * @return float
	 */
	private static function trend_delta( $domain, $days ) {
		unset( $domain, $days );
		return 0.0;
	}

	/**
	 * @param int $days Days.
	 * @return array<int, array<string, mixed>>
	 */
	private static function bookings_series( $days ) {
		global $wpdb;
		$table = NGC_Database::table( 'bookings' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) AS d, COUNT(*) AS c FROM {$table} WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL %d DAY) GROUP BY DATE(created_at) ORDER BY d ASC",
				$days
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @param int $days Days.
	 * @return array<int, array<string, mixed>>
	 */
	private static function errors_series( $days ) {
		global $wpdb;
		$table = NGC_Database::table( 'intel_events' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(recorded_at) AS d, COUNT(*) AS c FROM {$table} WHERE severity IN ('error','critical') AND recorded_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY) GROUP BY DATE(recorded_at) ORDER BY d ASC",
				$days
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @param int $days Days.
	 * @return array<int, array<string, mixed>>
	 */
	private static function api_series( $days ) {
		global $wpdb;
		$table = NGC_Database::table( 'intel_events' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(recorded_at) AS d, COUNT(*) AS c FROM {$table} WHERE event_key='api.rest.request' AND recorded_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY) GROUP BY DATE(recorded_at) ORDER BY d ASC",
				$days
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function deployment_status() {
		return [
			'companion_version' => defined( 'NGC_VERSION' ) ? NGC_VERSION : null,
			'theme_version'     => defined( 'BI_VERSION' ) ? BI_VERSION : null,
			'mission_control'   => defined( 'NGTMC_VERSION' ) ? NGTMC_VERSION : null,
			'last_orchestrator' => get_option( 'ngt_system_orchestrator_state', [] ),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function health_matrix() {
		$health = [
			'companion' => defined( 'NGC_VERSION' ),
			'theme'     => function_exists( 'bi_pages_registry' ) || false !== stripos( wp_get_theme()->get_stylesheet(), 'beyondinfinity' ),
			'database'  => class_exists( 'NGC_Database' ),
		];
		if ( class_exists( 'NGC_Observability_Service' ) ) {
			$health['observability'] = true;
			$health['hub_delegation'] = NGC_Observability_Service::hub_delegation_status();
		}
		return $health;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function workflow_stats() {
		global $wpdb;
		$table = NGC_Database::table( 'workflow_runs' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			return [ 'today' => 0, 'failed' => 0 ];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$today = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at)=CURDATE()" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$failed = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status='failed' AND DATE(created_at)=CURDATE()" );
		return [ 'today' => $today, 'failed' => $failed ];
	}

	/**
	 * Drill-down by level.
	 *
	 * @param string               $level Level.
	 * @param array<string, mixed> $ctx   Context.
	 * @return array<string, mixed>
	 */
	public static function drill_down( $level, array $ctx = [] ) {
		switch ( $level ) {
			case 'executive':
				return [ 'kpis' => self::kpi_cards(), 'level' => 'executive' ];
			case 'domain':
				return array_merge(
					[ 'level' => 'domain', 'domain' => $ctx['domain'] ?? '' ],
					NGC_Intelligence_Event_Bus::query(
						[
							'domain'   => $ctx['domain'] ?? '',
							'per_page' => 50,
						]
					)
				);
			case 'plugin':
				return array_merge(
					[ 'level' => 'plugin', 'plugin_slug' => $ctx['plugin_slug'] ?? '' ],
					NGC_Intelligence_Event_Bus::query(
						[
							'plugin_slug' => $ctx['plugin_slug'] ?? '',
							'per_page'    => 50,
						]
					)
				);
			case 'module':
				return array_merge(
					[ 'level' => 'module' ],
					NGC_Intelligence_Event_Bus::query(
						[
							'plugin_slug' => $ctx['plugin_slug'] ?? '',
							'module'      => $ctx['module'] ?? '',
							'per_page'    => 50,
						]
					)
				);
			case 'feature':
				return array_merge(
					[ 'level' => 'feature' ],
					NGC_Intelligence_Event_Bus::query(
						[
							'feature'  => $ctx['feature'] ?? '',
							'per_page' => 50,
						]
					)
				);
			case 'event':
				return array_merge(
					[ 'level' => 'event' ],
					NGC_Intelligence_Event_Bus::query(
						[
							'event_key' => $ctx['event_key'] ?? '',
							'per_page'  => 50,
						]
					)
				);
			case 'diagnostics':
				return [
					'level'   => 'diagnostics',
					'event'   => NGC_Intelligence_Event_Bus::get_by_id( (int) ( $ctx['id'] ?? 0 ) ),
					'health'  => class_exists( 'NGC_Intelligence_Health' ) ? NGC_Intelligence_Health::matrix() : [],
				];
			default:
				return [ 'kpis' => self::kpi_cards(), 'level' => 'executive' ];
		}
	}
}
