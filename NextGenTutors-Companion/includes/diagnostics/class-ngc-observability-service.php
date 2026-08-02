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
