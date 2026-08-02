<?php
/**
 * NextGen Intelligence Platform — public SDK facade.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central reporting SDK: NGC_Intelligence::emit( $event ).
 */
final class NGC_Intelligence {

	/**
	 * Boot intelligence subsystem.
	 */
	public static function init() {
		if ( false === get_option( NGC_Intelligence_Config::OPTION, false ) ) {
			update_option( NGC_Intelligence_Config::OPTION, NGC_Intelligence_Config::defaults(), false );
		}
		NGC_Intelligence_Config::get();
		NGC_Intelligence_Registry::init();
		NGC_Intelligence_Collectors::init();
		NGC_Intelligence_Retention::init();

		add_action( 'ngc_daily_health_check', [ __CLASS__, 'emit_health_snapshot' ] );
	}

	/**
	 * SDK entry — publish a structured operational event.
	 *
	 * @param array<string, mixed> $event Event payload.
	 * @return int Row ID.
	 */
	public static function emit( array $event ) {
		return NGC_Intelligence_Event_Bus::ingest( $event );
	}

	/**
	 * Register plugin metadata for Mission Control discovery.
	 *
	 * @param array<string, mixed> $definition Plugin definition.
	 */
	public static function register_plugin( array $definition ) {
		NGC_Intelligence_Registry::register_plugin( $definition );
	}

	/**
	 * Daily health snapshot event.
	 */
	public static function emit_health_snapshot() {
		if ( ! class_exists( 'NGC_Observability_Service' ) ) {
			return;
		}
		self::emit(
			[
				'event_key'   => 'platform.health.snapshot',
				'plugin_slug' => 'companion',
				'module'      => 'observability',
				'severity'    => 'info',
				'outcome'     => 'success',
				'message'     => 'Daily health snapshot',
				'payload'     => NGC_Observability_Service::snapshot(),
				'source'      => 'cron',
				'force'       => true,
			]
		);
	}
}
