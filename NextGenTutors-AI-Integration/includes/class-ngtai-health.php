<?php
/**
 * Public and operational health.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGTAI_Health {
	public static function public_health() {
		$counts = class_exists( 'NGTAI_Delivery_Repository' ) ? NGTAI_Delivery_Repository::counts() : [];
		$db_ok  = class_exists( 'NGTAI_Migrator' ) && defined( 'NGTAI_Migrator::DB_VERSION' ) && NGTAI_Migrator::DB_VERSION === get_option( 'ngtai_db_version', '' );
		return [
			'status'    => $db_ok && class_exists( 'NGC_Plugin' ) ? 'ok' : 'degraded',
			'version'   => NGTAI_VERSION,
			'enabled'   => NGTAI_Config::enabled(),
			'companion' => class_exists( 'NGC_Plugin' ) ? 'available' : 'missing',
			'database'  => $db_ok ? 'ready' : 'migrating',
			'outbox'    => [
				'pending'     => (int) ( $counts['pending'] ?? 0 ),
				'failed'      => (int) ( $counts['failed'] ?? 0 ),
				'dead_letter' => (int) ( $counts['dead_letter'] ?? 0 ),
			],
		];
	}

	public static function snapshot() {
		global $wpdb;
		$tables = [];
		foreach ( [ 'callback_nonces', 'deliveries', 'agent_results', 'approvals' ] as $name ) {
			$table = NGTAI_Database::table( $name );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$tables[ $name ] = $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		}
		$cron = [];
		foreach ( [ 'ngtai_outbox_flush', 'ngtai_nonce_purge_tick', 'ngtai_health_tick', 'ngtai_lock_recovery_tick' ] as $hook ) {
			$cron[ $hook ] = wp_next_scheduled( $hook ) ?: null;
		}
		return [
			'public'            => self::public_health(),
			'db_schema_version' => get_option( 'ngtai_db_version', '' ),
			'tables'            => $tables,
			'cron'              => $cron,
			'agents_api'        => NGTAI_Config::configured() ? NGTAI_Api_Client::health() : [ 'ok' => false, 'status' => 0, 'error' => 'unconfigured', 'body' => null ],
			'config'            => NGTAI_Config::public_status(),
			'counts'            => class_exists( 'NGTAI_Delivery_Repository' ) ? NGTAI_Delivery_Repository::counts() : [],
			'last_delivery'     => get_option( 'ngtai_last_delivery', null ),
			'last_callback'     => get_option( 'ngtai_last_callback', null ),
			'last_agents_ping'  => get_option( 'ngtai_last_agents_ping', null ),
			'signature_failures'=> (int) get_option( 'ngtai_signature_failure_total', 0 ),
			'replays'           => (int) get_option( 'ngtai_duplicate_event_total', 0 ),
			'lock_recovery'     => get_option( 'ngtai_last_lock_recovery', null ),
		];
	}
}
