<?php
/**
 * Injectable demo clock (Phase 14 §14.4).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Freezable / advanceable clock for demo schedulers.
 */
final class NGC_Demo_Clock {

	public const OPTION_OFFSET = 'ngc_demo_clock_offset_seconds';
	public const OPTION_FROZEN = 'ngc_demo_clock_frozen_ts';

	/**
	 * Hook registration.
	 */
	public static function init() {
		// Domain code should call NGC_Demo_Clock::now(); WP current_time filter for demo-aware callers.
		add_filter( 'ngc_demo_now', [ __CLASS__, 'filter_now' ] );
	}

	/**
	 * Unix timestamp (GMT).
	 *
	 * @return int
	 */
	public static function now() {
		$frozen = (int) get_option( self::OPTION_FROZEN, 0 );
		if ( $frozen > 0 ) {
			return $frozen + (int) get_option( self::OPTION_OFFSET, 0 );
		}
		return time() + (int) get_option( self::OPTION_OFFSET, 0 );
	}

	/**
	 * @return string Y-m-d (Africa/Johannesburg when possible).
	 */
	public static function today() {
		$tz = class_exists( 'DateTimeZone' ) ? new DateTimeZone( 'Africa/Johannesburg' ) : null;
		$dt = $tz ? ( new DateTimeImmutable( '@' . self::now() ) )->setTimezone( $tz ) : new DateTimeImmutable( '@' . self::now() );
		return $dt->format( 'Y-m-d' );
	}

	/**
	 * @param int $seconds Seconds to advance.
	 * @return int New now().
	 */
	public static function advance( $seconds ) {
		$seconds = (int) $seconds;
		$offset  = (int) get_option( self::OPTION_OFFSET, 0 ) + $seconds;
		update_option( self::OPTION_OFFSET, $offset, false );
		/**
		 * Fires after demo clock advance — run same scheduler paths.
		 *
		 * @param int $seconds Advanced.
		 * @param int $now     New timestamp.
		 */
		do_action( 'ngc_demo_clock_advanced', $seconds, self::now() );
		self::run_scheduled_hooks();
		return self::now();
	}

	/**
	 * Freeze at current logical time.
	 */
	public static function freeze() {
		update_option( self::OPTION_FROZEN, self::now() - (int) get_option( self::OPTION_OFFSET, 0 ), false );
	}

	/**
	 * Clear freeze + offset.
	 */
	public static function reset() {
		delete_option( self::OPTION_OFFSET );
		delete_option( self::OPTION_FROZEN );
	}

	/**
	 * @param int $default Default.
	 * @return int
	 */
	public static function filter_now( $default ) {
		unset( $default );
		return self::now();
	}

	/**
	 * MySQL datetime at offset from now.
	 *
	 * @param string $modifier e.g. '+2 days', '-1 hour'.
	 * @return string
	 */
	public static function mysql( $modifier = 'now' ) {
		$ts = self::now();
		if ( 'now' !== $modifier && '' !== $modifier ) {
			$ts = strtotime( $modifier, $ts );
		}
		return gmdate( 'Y-m-d H:i:s', (int) $ts );
	}

	/**
	 * Trigger common WP cron hooks used by Companion (demo-safe).
	 */
	public static function run_scheduled_hooks() {
		$hooks = [
			'ngc_safeguarding_sla_tick',
			'ngc_privacy_retention_tick',
			'ngc_metrics_push_tick',
			'ngc_process_reminders',
		];
		foreach ( $hooks as $hook ) {
			do_action( $hook );
		}
	}

	/**
	 * @return array{now:int,today:string,offset:int,frozen:int}
	 */
	public static function status() {
		return [
			'now'    => self::now(),
			'today'  => self::today(),
			'offset' => (int) get_option( self::OPTION_OFFSET, 0 ),
			'frozen' => (int) get_option( self::OPTION_FROZEN, 0 ),
			'iso'    => gmdate( 'c', self::now() ),
		];
	}
}
