<?php
declare(strict_types=1);

namespace NGTBM\Domain\Health;

use NGTBM\Domain\Subsystem\SubsystemRegistry;

/**
 * Aggregate platform health for Command Center.
 */
final class HealthAggregator {

	/**
	 * @return array<string,mixed>
	 */
	public static function snapshot( SubsystemRegistry $registry ): array {
		$subs     = $registry->all();
		$healthy  = 0;
		$degraded = 0;
		$offline  = 0;
		$attention = [];
		foreach ( $subs as $sub ) {
			$status = $sub->enabled ? strtolower( $sub->status ) : 'offline';
			if ( $status === 'healthy' || $status === 'operational' ) {
				++$healthy;
			} elseif ( $status === 'offline' ) {
				++$offline;
				$attention[] = [
					'severity' => 'critical',
					'source'   => $sub->id,
					'title'    => $sub->name . ' offline',
					'action'   => 'Investigate',
				];
			} else {
				++$degraded;
				$attention[] = [
					'severity' => 'warning',
					'source'   => $sub->id,
					'title'    => $sub->name . ' degraded',
					'action'   => 'Investigate',
				];
			}
		}
		$total = max( 1, count( $subs ) );
		$score = (int) round( ( $healthy / $total ) * 100 );
		$level = 'operational';
		if ( $offline > 0 || $score < 70 ) {
			$level = 'critical';
		} elseif ( $degraded > 0 || $score < 95 ) {
			$level = 'degraded';
		}

		$queue = [
			'pending' => (int) apply_filters( 'ngtbm_queue_pending', 0 ),
			'dlq'     => (int) apply_filters( 'ngtbm_queue_dlq', 0 ),
		];
		if ( $queue['dlq'] > 0 ) {
			$attention[] = [
				'severity' => 'critical',
				'source'   => 'queue',
				'title'    => $queue['dlq'] . ' messages in DLQ',
				'action'   => 'Inspect',
			];
		} elseif ( $queue['pending'] > 15 ) {
			$attention[] = [
				'severity' => 'warning',
				'source'   => 'queue',
				'title'    => $queue['pending'] . ' pending queue jobs',
				'action'   => 'Review',
			];
		}

		return [
			'level'       => $level,
			'score'       => $score,
			'subsystems'  => [
				'total'    => count( $subs ),
				'healthy'  => $healthy,
				'degraded' => $degraded,
				'offline'  => $offline,
			],
			'queue'       => $queue,
			'security'    => [
				'warnings' => (int) apply_filters( 'ngtbm_security_warnings', 0 ),
			],
			'last24h'     => [
				'evaluations' => (int) apply_filters( 'ngtbm_stats_evaluations_24h', 0 ),
				'bookings'    => (int) apply_filters( 'ngtbm_stats_bookings_24h', 0 ),
				'errors'      => (int) apply_filters( 'ngtbm_stats_errors_24h', 0 ),
				'dlq'         => $queue['dlq'],
			],
			'attention'   => $attention,
			'activity'    => (array) apply_filters( 'ngtbm_recent_activity', [] ),
		];
	}
}
