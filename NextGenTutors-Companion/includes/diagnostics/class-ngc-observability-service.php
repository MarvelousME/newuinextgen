<?php
/**
 * Unified observability facade — health, cron queues, logs, Hub delegation.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central observability snapshot for Mission Control, CLI, and REST.
 */
class NGC_Observability_Service {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'plugins_loaded', [ __CLASS__, 'sync_hub_delegation' ], 25 );
	}

	/**
	 * Ask Automation Hub to release duplicate crons when Companion is active.
	 */
	public static function sync_hub_delegation() {
		if ( ! class_exists( 'NGT_Hub_Companion_Delegate', false ) ) {
			return;
		}
		try {
			NGT_Hub_Companion_Delegate::sync_delegation();
		} catch ( Throwable $e ) {
			if ( class_exists( 'NGC_System_Log' ) ) {
				NGC_System_Log::warning(
					'observability',
					'hub_delegation',
					'Hub delegation sync failed: ' . $e->getMessage(),
					[ 'trace' => $e->getTraceAsString() ]
				);
			}
		}
	}

	/**
	 * Full observability snapshot.
	 *
	 * @return array<string, mixed>
	 */
	public static function snapshot() {
		return [
			'generated_at'   => gmdate( 'c' ),
			'health'         => self::health_summary(),
			'cron'           => self::cron_status(),
			'logs'           => self::log_stats(),
			'hub_delegation' => self::hub_delegation_status(),
			'ui_library'     => self::ui_library_status(),
		];
	}

	/**
	 * Orchestration Cockpit payload — connectivity, VPS/gateway, APIs, schedules, processes, alerts.
	 *
	 * @return array<string, mixed>
	 */
	public static function cockpit_snapshot() {
		$base = self::snapshot();
		$cfg  = self::cockpit_config();

		return array_merge(
			$base,
			[
				'status'         => self::global_status( $base ),
				'runtime'        => self::runtime_metrics(),
				'connectivity'   => self::connectivity_matrix( $cfg ),
				'apis'           => self::api_matrix(),
				'schedules'      => self::cron_status(),
				'processes'      => self::background_processes(),
				'agents'         => self::agent_swarm_status(),
				'alerts'         => self::live_alerts( $base ),
				'architecture'   => self::architecture_nodes( $cfg ),
				'config'         => $cfg,
				'emergency_stop' => class_exists( 'NGC_Agent_Control_Plane' ) ? NGC_Agent_Control_Plane::is_globally_paused() : false,
			]
		);
	}

	/**
	 * Saved cockpit identity / infrastructure hints (non-secret display fields).
	 *
	 * @return array<string, string>
	 */
	public static function cockpit_config() {
		$defaults = [
			'project_name' => (string) get_bloginfo( 'name' ),
			'domain'       => wp_parse_url( home_url(), PHP_URL_HOST ) ?: '',
			'da_user'      => '',
			'da_host'      => '',
			'vps_id'       => '',
			'gateway_url'  => class_exists( 'NGC_Agent_Gateway_Client' ) ? NGC_Agent_Gateway_Client::base_url() : '',
		];
		$saved = get_option( 'ngc_orchestration_cockpit_config', [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}
		$out = [];
		foreach ( $defaults as $k => $v ) {
			$out[ $k ] = sanitize_text_field( (string) ( $saved[ $k ] ?? $v ) );
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $input Config.
	 * @return array<string, string>
	 */
	public static function save_cockpit_config( array $input ) {
		$clean = [
			'project_name' => sanitize_text_field( (string) ( $input['project_name'] ?? '' ) ),
			'domain'       => sanitize_text_field( (string) ( $input['domain'] ?? '' ) ),
			'da_user'      => sanitize_text_field( (string) ( $input['da_user'] ?? '' ) ),
			'da_host'      => sanitize_text_field( (string) ( $input['da_host'] ?? '' ) ),
			'vps_id'       => sanitize_text_field( (string) ( $input['vps_id'] ?? '' ) ),
			'gateway_url'  => esc_url_raw( (string) ( $input['gateway_url'] ?? '' ) ),
		];
		update_option( 'ngc_orchestration_cockpit_config', $clean, false );
		return $clean;
	}

	/**
	 * @param array<string, mixed> $base Snapshot.
	 * @return array{label:string,level:string}
	 */
	private static function global_status( array $base ) {
		$health_ok = ! empty( $base['health']['ok'] );
		$errors    = (int) ( $base['logs']['errors_24h'] ?? 0 );
		$paused    = class_exists( 'NGC_Agent_Control_Plane' ) && NGC_Agent_Control_Plane::is_globally_paused();
		if ( $paused ) {
			return [ 'label' => 'EMERGENCY STOP', 'level' => 'danger' ];
		}
		if ( ! $health_ok || $errors > 20 ) {
			return [ 'label' => 'DEGRADED', 'level' => 'warning' ];
		}
		return [ 'label' => 'READY', 'level' => 'success' ];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function runtime_metrics() {
		$mem_limit = wp_convert_hr_to_bytes( (string) ini_get( 'memory_limit' ) );
		$mem_usage = memory_get_usage( true );
		$mem_pct   = $mem_limit > 0 ? min( 100, round( ( $mem_usage / $mem_limit ) * 100, 1 ) ) : 0;
		$disk_free = @disk_free_space( ABSPATH ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$disk_tot  = @disk_total_space( ABSPATH ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$disk_pct  = ( $disk_tot && $disk_tot > 0 ) ? round( ( 1 - ( $disk_free / $disk_tot ) ) * 100, 1 ) : null;

		$load = function_exists( 'sys_getloadavg' ) ? @sys_getloadavg() : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$cpu  = ( is_array( $load ) && isset( $load[0] ) ) ? round( (float) $load[0], 2 ) : null;

		return [
			'php_version'     => PHP_VERSION,
			'wp_version'      => get_bloginfo( 'version' ),
			'memory_usage'    => size_format( $mem_usage ),
			'memory_limit'    => size_format( $mem_limit ),
			'memory_pct'      => $mem_pct,
			'disk_used_pct'   => $disk_pct,
			'disk_free'       => $disk_free ? size_format( (int) $disk_free ) : null,
			'disk_total'      => $disk_tot ? size_format( (int) $disk_tot ) : null,
			'load_1m'         => $cpu,
			'server_software' => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['SERVER_SOFTWARE'] ) ) : '',
			'timezone'        => wp_timezone_string(),
			'history'         => self::runtime_history_sample( $mem_pct, $cpu ),
		];
	}

	/**
	 * Lightweight sparkline samples (current + recent option ring).
	 *
	 * @param float      $mem_pct Memory %.
	 * @param float|null $cpu     Load.
	 * @return array{cpu:array<int,float>,memory:array<int,float>}
	 */
	private static function runtime_history_sample( $mem_pct, $cpu ) {
		$key  = 'ngc_cockpit_runtime_ring';
		$ring = get_transient( $key );
		if ( ! is_array( $ring ) ) {
			$ring = [ 'cpu' => [], 'memory' => [] ];
		}
		$ring['memory'][] = (float) $mem_pct;
		$ring['cpu'][]    = null !== $cpu ? (float) $cpu : 0.0;
		$ring['memory']   = array_slice( $ring['memory'], -24 );
		$ring['cpu']      = array_slice( $ring['cpu'], -24 );
		set_transient( $key, $ring, HOUR_IN_SECONDS );
		return $ring;
	}

	/**
	 * @param array<string, string> $cfg Config.
	 * @return array<int, array<string, mixed>>
	 */
	private static function connectivity_matrix( array $cfg ) {
		$rows = [];

		// WordPress / DB.
		global $wpdb;
		$db_ok = false;
		try {
			$db_ok = (bool) $wpdb->get_var( 'SELECT 1' );
		} catch ( Throwable $e ) {
			$db_ok = false;
		}
		$rows[] = [
			'id'      => 'wordpress',
			'label'   => 'WordPress Core',
			'detail'  => get_bloginfo( 'version' ),
			'status'  => 'up',
			'latency' => null,
		];
		$rows[] = [
			'id'      => 'database',
			'label'   => 'MySQL / MariaDB',
			'detail'  => DB_NAME,
			'status'  => $db_ok ? 'up' : 'down',
			'latency' => null,
		];

		// Agent Gateway (VPS-side).
		$gw_status = 'unknown';
		$gw_detail = (string) ( $cfg['gateway_url'] ?: 'not configured' );
		$gw_ms     = null;
		if ( class_exists( 'NGC_Agent_Gateway_Client' ) ) {
			$t0  = microtime( true );
			$res = NGC_Agent_Gateway_Client::health();
			$gw_ms = (int) round( ( microtime( true ) - $t0 ) * 1000 );
			if ( is_wp_error( $res ) ) {
				$gw_status = 'down';
				$gw_detail = $res->get_error_message();
			} else {
				$gw_status = 'up';
				$gw_detail = (string) ( $res['service'] ?? 'agent-gateway' ) . ' · ' . NGC_Agent_Gateway_Client::base_url();
			}
		}
		$rows[] = [
			'id'      => 'agent_gateway',
			'label'   => 'Agent Gateway (VPS)',
			'detail'  => $gw_detail,
			'status'  => $gw_status,
			'latency' => $gw_ms,
		];

		$rest_t0 = microtime( true );
		$rest    = wp_remote_get(
			rest_url( 'ngc/v1/admin/version' ),
			[
				'timeout'   => 4,
				'sslverify' => false,
				'headers'   => [
					'X-WP-Nonce' => wp_create_nonce( 'wp_rest' ),
					'Cookie'     => isset( $_SERVER['HTTP_COOKIE'] ) ? (string) wp_unslash( $_SERVER['HTTP_COOKIE'] ) : '',
				],
			]
		);
		$rest_ms = (int) round( ( microtime( true ) - $rest_t0 ) * 1000 );
		$rest_ok = ! is_wp_error( $rest ) && (int) wp_remote_retrieve_response_code( $rest ) < 500;
		$rows[]  = [
			'id'      => 'rest_api',
			'label'   => 'Companion REST API',
			'detail'  => rest_url( 'ngc/v1/' ),
			'status'  => $rest_ok ? 'up' : 'down',
			'latency' => $rest_ms,
		];

		// Site front.
		$home_t0 = microtime( true );
		$home    = wp_remote_get( home_url( '/' ), [ 'timeout' => 5, 'redirection' => 0, 'sslverify' => false ] );
		$home_ms = (int) round( ( microtime( true ) - $home_t0 ) * 1000 );
		$code    = is_wp_error( $home ) ? 0 : (int) wp_remote_retrieve_response_code( $home );
		$rows[]  = [
			'id'      => 'public_site',
			'label'   => 'Public site',
			'detail'  => home_url( '/' ),
			'status'  => ( $code >= 200 && $code < 500 ) ? 'up' : 'down',
			'latency' => $home_ms,
		];

		if ( ! empty( $cfg['vps_id'] ) ) {
			$rows[] = [
				'id'      => 'vps',
				'label'   => 'Coolify / VPS target',
				'detail'  => $cfg['vps_id'],
				'status'  => 'configured',
				'latency' => null,
			];
		}
		if ( ! empty( $cfg['da_host'] ) ) {
			$rows[] = [
				'id'      => 'directadmin',
				'label'   => 'DirectAdmin host',
				'detail'  => trim( $cfg['da_user'] . '@' . $cfg['da_host'], '@' ),
				'status'  => 'configured',
				'latency' => null,
			];
		}

		return $rows;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function api_matrix() {
		$checks = [
			[ 'id' => 'ngc_admin_version', 'label' => 'Admin Shell API', 'path' => 'ngc/v1/admin/version' ],
			[ 'id' => 'ngc_intel_health', 'label' => 'Intelligence health', 'path' => 'ngc/v1/intelligence/health' ],
		];
		$out = [];
		foreach ( $checks as $c ) {
			$url = rest_url( $c['path'] );
			$t0  = microtime( true );
			$res = wp_remote_get(
				$url,
				[
					'timeout' => 4,
					'headers' => [
						'X-WP-Nonce' => wp_create_nonce( 'wp_rest' ),
						'Cookie'     => isset( $_SERVER['HTTP_COOKIE'] ) ? (string) wp_unslash( $_SERVER['HTTP_COOKIE'] ) : '',
					],
					'sslverify' => false,
				]
			);
			$ms   = (int) round( ( microtime( true ) - $t0 ) * 1000 );
			$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
			$out[] = [
				'id'      => $c['id'],
				'label'   => $c['label'],
				'path'    => $c['path'],
				'status'  => ( $code >= 200 && $code < 500 ) ? 'up' : 'down',
				'http'    => $code,
				'latency' => $ms,
			];
		}

		// WooCommerce presence.
		$out[] = [
			'id'      => 'woocommerce',
			'label'   => 'WooCommerce',
			'path'    => class_exists( 'WooCommerce' ) ? 'active' : 'inactive',
			'status'  => class_exists( 'WooCommerce' ) ? 'up' : 'idle',
			'http'    => null,
			'latency' => null,
		];

		return $out;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function background_processes() {
		$crons = _get_cron_array();
		$due   = 0;
		$soon  = 0;
		$now   = time();
		if ( is_array( $crons ) ) {
			foreach ( $crons as $ts => $hooks ) {
				if ( ! is_array( $hooks ) ) {
					continue;
				}
				if ( (int) $ts <= $now ) {
					$due += count( $hooks );
				} elseif ( (int) $ts <= $now + HOUR_IN_SECONDS ) {
					$soon += count( $hooks );
				}
			}
		}

		$action_scheduler = [
			'id'     => 'action_scheduler',
			'label'  => 'Action Scheduler',
			'status' => class_exists( 'ActionScheduler' ) ? 'running' : 'absent',
			'detail' => class_exists( 'ActionScheduler' ) ? sprintf( '%d past-due hooks tracked via WP-Cron map', $due ) : 'Not loaded',
		];

		return [
			[
				'id'     => 'wp_cron',
				'label'  => 'WP-Cron queue',
				'status' => $due > 25 ? 'warn' : 'ok',
				'detail' => sprintf( '%d due · %d within 1h', $due, $soon ),
			],
			$action_scheduler,
			[
				'id'     => 'publish_worker',
				'label'  => 'Content publish worker',
				'status' => class_exists( 'NGC_Publish_Worker' ) ? 'ready' : 'absent',
				'detail' => class_exists( 'NGC_Publish_Worker' ) ? 'NGC_Publish_Worker registered' : '—',
			],
			[
				'id'     => 'intelligence',
				'label'  => 'Intelligence collectors',
				'status' => class_exists( 'NGC_Intelligence' ) ? 'ready' : 'absent',
				'detail' => class_exists( 'NGC_Intelligence' ) ? 'Active' : '—',
			],
		];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function agent_swarm_status() {
		if ( ! class_exists( 'NGC_Agent_Control_Plane' ) ) {
			return [];
		}
		$global = NGC_Agent_Control_Plane::is_globally_paused();
		$out    = [];
		foreach ( NGC_Agent_Control_Plane::registry() as $id => $agent ) {
			$paused = $global || NGC_Agent_Control_Plane::is_agent_paused( $id ) || ! empty( $agent['paused'] );
			$out[]  = [
				'id'       => (string) $id,
				'name'     => (string) ( $agent['name'] ?? $id ),
				'autonomy' => (int) ( $agent['autonomy'] ?? 1 ),
				'tools'    => array_values( (array) ( $agent['tools'] ?? [] ) ),
				'status'   => $paused ? 'paused' : ( (string) ( $agent['status'] ?? 'active' ) ),
			];
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $base Snapshot.
	 * @return array<int, array<string, mixed>>
	 */
	private static function live_alerts( array $base ) {
		$alerts = [];
		$paused = class_exists( 'NGC_Agent_Control_Plane' ) && NGC_Agent_Control_Plane::is_globally_paused();
		if ( $paused ) {
			$alerts[] = [
				'level'   => 'error',
				'title'   => 'Emergency stop engaged',
				'message' => 'All agent actions are globally paused.',
				'ts'      => time(),
			];
		}
		if ( empty( $base['health']['ok'] ) ) {
			$alerts[] = [
				'level'   => 'warning',
				'title'   => 'Health scan not OK',
				'message' => (string) ( $base['health']['error'] ?? 'Quick health reported failure.' ),
				'ts'      => time(),
			];
		}
		$err24 = (int) ( $base['logs']['errors_24h'] ?? 0 );
		if ( $err24 > 0 ) {
			$alerts[] = [
				'level'   => $err24 > 20 ? 'error' : 'warning',
				'title'   => 'System log errors (24h)',
				'message' => sprintf( '%d error/critical entries in the last 24 hours.', $err24 ),
				'ts'      => time(),
			];
		}
		foreach ( (array) ( $base['cron'] ?? [] ) as $row ) {
			if ( 'warn' === ( $row['delegation'] ?? '' ) ) {
				$alerts[] = [
					'level'   => 'warning',
					'title'   => 'Duplicate cron still scheduled',
					'message' => (string) ( $row['label'] ?? $row['hook'] ),
					'ts'      => time(),
				];
			}
		}
		if ( ! $alerts ) {
			$alerts[] = [
				'level'   => 'info',
				'title'   => 'Console ready',
				'message' => 'No critical alerts. Cockpit polling live metrics.',
				'ts'      => time(),
			];
		}
		return array_slice( $alerts, 0, 40 );
	}

	/**
	 * @param array<string, string> $cfg Config.
	 * @return array<int, array<string, string>>
	 */
	private static function architecture_nodes( array $cfg ) {
		return [
			[ 'id' => 'edge', 'label' => 'CDN / Edge', 'group' => 'edge' ],
			[ 'id' => 'host', 'label' => 'Shared hosting · ' . ( $cfg['da_host'] ?: 'WordPress' ), 'group' => 'host' ],
			[ 'id' => 'wp', 'label' => 'WordPress + Companion', 'group' => 'app' ],
			[ 'id' => 'commerce', 'label' => 'WooCommerce / PayFast', 'group' => 'app' ],
			[ 'id' => 'booking', 'label' => 'Bookings / Matching', 'group' => 'app' ],
			[ 'id' => 'crm', 'label' => 'CRM / Support', 'group' => 'app' ],
			[ 'id' => 'gateway', 'label' => 'Agent Gateway', 'group' => 'vps' ],
			[ 'id' => 'vps', 'label' => 'VPS · ' . ( $cfg['vps_id'] ?: 'Coolify' ), 'group' => 'vps' ],
			[ 'id' => 'automation', 'label' => 'Automation / n8n', 'group' => 'vps' ],
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function health_summary() {
		if ( ! class_exists( 'NGC_Health_Scanner' ) ) {
			return [ 'ok' => false, 'error' => 'NGC_Health_Scanner missing' ];
		}
		try {
			return NGC_Health_Scanner::quick_scan();
		} catch ( Throwable $e ) {
			return [
				'ok'    => false,
				'error' => $e->getMessage(),
			];
		}
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function cron_status() {
		$hooks = [
			'ngc_daily_health_check'        => 'Companion daily health',
			'ngc_monthly_payout_batch'      => 'Companion monthly payout',
			'ngc_biweekly_payout_batch'     => 'Companion bi-weekly payout',
			'ngc_process_export_job'        => 'Export job processor',
			'ngc_run_scheduled_exports'       => 'Scheduled exports',
			'ngt_monthly_payout_calculation' => 'Hub monthly payout (should be off)',
			'ngt_daily_health_check'        => 'Hub daily health (should be off)',
		];

		$rows = [];
		foreach ( $hooks as $hook => $label ) {
			$next = wp_next_scheduled( $hook );
			$rows[] = [
				'hook'       => $hook,
				'label'      => $label,
				'scheduled'  => (bool) $next,
				'next_run'   => $next ? gmdate( 'c', (int) $next ) : null,
				'delegation' => self::cron_delegation_expectation( $hook ),
			];
		}
		return $rows;
	}

	/**
	 * @param string $hook Cron hook.
	 * @return string expected|warn|ok
	 */
	private static function cron_delegation_expectation( $hook ) {
		$companion = defined( 'NGC_VERSION' ) || class_exists( 'NGC_Plugin', false );
		if ( ! $companion ) {
			return 'ok';
		}
		if ( in_array( $hook, [ 'ngt_monthly_payout_calculation', 'ngt_daily_health_check' ], true ) ) {
			return wp_next_scheduled( $hook ) ? 'warn' : 'ok';
		}
		return 'ok';
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function log_stats() {
		global $wpdb;

		if ( ! class_exists( 'NGC_Database' ) ) {
			return [ 'ok' => false, 'total' => 0 ];
		}

		$table = NGC_Database::table( 'system_log' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			return [ 'ok' => false, 'total' => 0, 'error' => 'system_log table missing' ];
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$errors = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE level IN ('error','critical') AND created_at >= %s", gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) ) );

		return [
			'ok'            => true,
			'total'         => $total,
			'errors_24h'    => $errors,
			'admin_url'     => admin_url( 'admin.php?page=ngc-system-log' ),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function hub_delegation_status() {
		if ( ! class_exists( 'NGT_Hub_Companion_Delegate', false ) ) {
			return [
				'hub_active'        => class_exists( 'NGT_Hub', false ),
				'companion_active'    => defined( 'NGC_VERSION' ),
				'delegation_class'    => false,
				'rest_namespace'      => null,
			];
		}

		return [
			'hub_active'        => defined( 'NGT_HUB_VERSION' ),
			'companion_active'  => NGT_Hub_Companion_Delegate::companion_active(),
			'delegation_class'  => true,
			'rest_namespace'    => NGT_Hub_Companion_Delegate::rest_namespace(),
			'hub_payout_cron'   => (bool) wp_next_scheduled( 'ngt_monthly_payout_calculation' ),
			'hub_health_cron'   => (bool) wp_next_scheduled( 'ngt_daily_health_check' ),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function ui_library_status() {
		$candidates = [
			WP_CONTENT_DIR . '/ngt-ui-library/bootstrap/class-ngt-ui-bootstrap.php',
			dirname( NGC_PLUGIN_DIR ) . '/ui-library/bootstrap/class-ngt-ui-bootstrap.php',
		];
		foreach ( $candidates as $path ) {
			if ( file_exists( $path ) ) {
				return [
					'ok'   => true,
					'path' => $path,
				];
			}
		}
		return [
			'ok'    => false,
			'path'  => null,
			'error' => 'ui-library not found — deploy wp-content/ngt-ui-library',
		];
	}
}
