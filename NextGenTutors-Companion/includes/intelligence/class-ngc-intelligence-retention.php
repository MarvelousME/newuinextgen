<?php
/**
 * Event retention and KPI bucket cleanup (background processing).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scheduled retention for intelligence tables.
 */
final class NGC_Intelligence_Retention {

	public const CRON_HOOK = 'ngc_intelligence_retention_sweep';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( self::CRON_HOOK, [ __CLASS__, 'sweep' ] );
		add_action( 'init', [ __CLASS__, 'ensure_scheduled' ] );
	}

	/**
	 * Schedule daily retention if missing.
	 */
	public static function ensure_scheduled() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Purge events and closed notifications past retention window.
	 */
	public static function sweep() {
		if ( ! NGC_Intelligence_Config::is_enabled() ) {
			return;
		}
		global $wpdb;
		$days = max( 7, (int) NGC_Intelligence_Config::get()['retention_days'] );
		$events = NGC_Database::table( 'intel_events' );
		$kpis   = NGC_Database::table( 'intel_kpi_hourly' );
		$notes  = NGC_Database::table( 'intel_notifications' );

		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		foreach ( [ $events, $kpis ] as $table ) {
			if ( ! $table ) {
				continue;
			}
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
				$col = ( $table === $kpis ) ? 'bucket_hour' : 'recorded_at';
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE {$col} < %s LIMIT 5000", $cutoff ) );
			}
		}

		if ( $notes ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$notes}'" ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$notes} WHERE status IN ('acknowledged','closed') AND created_at < %s LIMIT 2000",
						$cutoff
					)
				);
			}
		}

		NGC_Intelligence_Audit::log( 'retention.sweep', [ 'days' => $days, 'cutoff' => $cutoff ] );
		NGC_Intelligence::emit(
			[
				'event_key'   => 'intelligence.retention.sweep',
				'plugin_slug' => 'companion',
				'module'      => 'intelligence',
				'severity'    => 'info',
				'outcome'     => 'success',
				'message'     => 'Retention sweep completed',
				'payload'     => [ 'retention_days' => $days ],
				'source'      => 'cron',
				'force'       => true,
			]
		);
	}
}
