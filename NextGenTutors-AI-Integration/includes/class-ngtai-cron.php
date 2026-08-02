<?php
/**
 * Integration schedules and workers.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGTAI_Cron {
	private const HOOKS = [
		'ngtai_outbox_flush'      => 'ngtai_minute',
		'ngtai_nonce_purge_tick'  => 'daily',
		'ngtai_health_tick'       => 'ngtai_five_minutes',
		'ngtai_lock_recovery_tick'=> 'ngtai_five_minutes',
	];

	public static function init() {
		add_filter( 'cron_schedules', [ __CLASS__, 'schedules' ] );
		add_action( 'ngtai_outbox_flush', [ __CLASS__, 'flush' ] );
		add_action( 'ngtai_nonce_purge_tick', [ __CLASS__, 'purge' ] );
		add_action( 'ngtai_health_tick', [ __CLASS__, 'health' ] );
		add_action( 'ngtai_lock_recovery_tick', [ __CLASS__, 'recover' ] );
	}

	public static function schedules( $schedules ) {
		$schedules['ngtai_minute']       = [ 'interval' => 60, 'display' => __( 'Every minute (NGTAI)', 'nextgentutors-ai-integration' ) ];
		$schedules['ngtai_five_minutes'] = [ 'interval' => 300, 'display' => __( 'Every five minutes (NGTAI)', 'nextgentutors-ai-integration' ) ];
		return $schedules;
	}

	public static function schedule() {
		add_filter( 'cron_schedules', [ __CLASS__, 'schedules' ] );
		foreach ( self::HOOKS as $hook => $schedule ) {
			if ( ! wp_next_scheduled( $hook ) ) {
				wp_schedule_event( time() + 30, $schedule, $hook );
			}
		}
	}

	public static function clear() {
		foreach ( array_keys( self::HOOKS ) as $hook ) {
			wp_clear_scheduled_hook( $hook );
		}
	}

	public static function flush() {
		if ( class_exists( 'NGTAI_Outbox_Bridge' ) ) {
			$stats = NGTAI_Outbox_Bridge::dispatch_batch();
			$stats['sent'] = (int) ( $stats['delivered'] ?? 0 );
			do_action( 'ngtai_outbox_flush_complete', $stats );
		}
	}

	public static function purge() {
		if ( class_exists( 'NGTAI_Nonce_Store' ) ) {
			NGTAI_Nonce_Store::purge_expired();
		}
	}

	public static function health() {
		$snapshot = NGTAI_Health::snapshot();
		update_option(
			'ngtai_last_health',
			NGTAI_Logger::scrub(
				[
					'checked_at' => gmdate( 'c' ),
					'public'     => $snapshot['public'] ?? [],
					'agents_api' => [
						'ok'     => (bool) ( $snapshot['agents_api']['ok'] ?? false ),
						'status' => (int) ( $snapshot['agents_api']['status'] ?? 0 ),
						'error'  => (string) ( $snapshot['agents_api']['error'] ?? '' ),
					],
					'counts'     => $snapshot['counts'] ?? [],
				]
			),
			false
		);
	}

	public static function recover() {
		if ( class_exists( 'NGTAI_Delivery_Repository' ) ) {
			update_option(
				'ngtai_last_lock_recovery',
				[ 'recovered' => NGTAI_Delivery_Repository::recover_locks( 300 ), 'checked_at' => gmdate( 'c' ) ],
				false
			);
		}
	}
}
