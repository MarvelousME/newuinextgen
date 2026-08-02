<?php
/**
 * Threshold-based alert evaluation.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates operational alerts from events and KPI thresholds.
 */
final class NGC_Intelligence_Alerts {

	/**
	 * @param array<string, mixed> $event Normalized event.
	 */
	public static function evaluate( array $event ) {
		if ( in_array( $event['severity'], [ 'error', 'critical' ], true ) ) {
			NGC_Intelligence_Dispatch::notify(
				'error',
				sprintf( 'Error: %s', $event['event_key'] ),
				$event['message'] ?: $event['event_key'],
				[
					'plugin_slug'    => $event['plugin_slug'],
					'correlation_id' => $event['correlation_id'],
					'severity'       => $event['severity'],
				]
			);
		}

		if ( 'booking.failed' === $event['event_key'] || ( 'bookings' === $event['domain'] && 'failure' === $event['outcome'] ) ) {
			self::maybe_booking_failure_alert();
		}
	}

	/**
	 * Check rolling booking failure threshold.
	 */
	private static function maybe_booking_failure_alert() {
		global $wpdb;
		$table = NGC_Database::table( 'intel_events' );
		$config = NGC_Intelligence_Config::get();
		$threshold = max( 1, (int) $config['alert_booking_failures'] );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
			return;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE domain='bookings' AND outcome='failure' AND recorded_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR)"
		);
		if ( $count >= $threshold ) {
			NGC_Intelligence_Dispatch::notify(
				'critical',
				__( 'Booking failure threshold breached', 'nextgencompanion' ),
				sprintf( '%d booking failures in the last hour', $count ),
				[ 'count' => $count, 'threshold' => $threshold ],
				true
			);
		}
	}
}
