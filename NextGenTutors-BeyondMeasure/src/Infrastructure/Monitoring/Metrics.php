<?php
declare(strict_types=1);

namespace NGTBM\Infrastructure\Monitoring;

/**
 * Lightweight request counters for health headers.
 */
final class Metrics {

	public static function inc( string $name, int $by = 1 ): void {
		$key = 'ngtbm_metric_' . sanitize_key( $name );
		$val = (int) get_option( $key, 0 );
		update_option( $key, $val + $by, false );
	}

	public static function get( string $name ): int {
		return (int) get_option( 'ngtbm_metric_' . sanitize_key( $name ), 0 );
	}
}
