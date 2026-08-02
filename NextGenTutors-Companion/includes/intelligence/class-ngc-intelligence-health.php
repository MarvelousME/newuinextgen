<?php
/**
 * Plugin and service health monitoring for Mission Control.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggregates health checks from registry + observability.
 */
final class NGC_Intelligence_Health {

	/**
	 * Full health matrix for operational dashboard.
	 *
	 * @return array<string, mixed>
	 */
	public static function matrix() {
		return [
			'generated_at'    => gmdate( 'c' ),
			'system'          => self::system_health(),
			'plugins'         => self::plugin_health(),
			'services'        => self::service_health(),
			'infrastructure'  => self::infrastructure_health(),
			'ai_agents'       => self::ai_health(),
			'cron_queues'     => self::cron_health(),
			'security'        => self::security_health(),
			'compliance'      => self::compliance_health(),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function system_health() {
		$obs = class_exists( 'NGC_Observability_Service' ) ? NGC_Observability_Service::snapshot() : [];
		$health = is_array( $obs['health'] ?? null ) ? $obs['health'] : [];
		return [
			'status'  => ! empty( $health['ok'] ) ? 'healthy' : 'degraded',
			'detail'  => $health,
			'companion_version' => defined( 'NGC_VERSION' ) ? NGC_VERSION : null,
			'wp_version'        => get_bloginfo( 'version' ),
			'php_version'       => PHP_VERSION,
		];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function plugin_health() {
		$registry = NGC_Intelligence_Registry::all();
		$plugins  = is_array( $registry['plugins'] ?? null ) ? $registry['plugins'] : [];
		$rows     = [];

		foreach ( $plugins as $slug => $def ) {
			$active = self::plugin_is_active( $slug );
			$rows[] = [
				'slug'     => $slug,
				'name'     => $def['name'] ?? $slug,
				'version'  => $def['version'] ?? '',
				'active'   => $active,
				'status'   => $active ? 'healthy' : 'inactive',
				'features' => $def['features'] ?? [],
			];
		}
		return $rows;
	}

	/**
	 * @param string $slug Registry slug.
	 * @return bool
	 */
	private static function plugin_is_active( $slug ) {
		$map = [
			'companion'       => defined( 'NGC_VERSION' ),
			'mission-control' => defined( 'NGTMC_VERSION' ),
			'automation-hub'  => defined( 'NGT_HUB_VERSION' ) || class_exists( 'NGT_Hub_Plugin', false ),
			'ai-integration'  => defined( 'NGTAI_VERSION' ) || class_exists( 'NGTAI_Plugin', false ),
			'plugin-manager'  => defined( 'NGCPM_VERSION' ) || class_exists( 'NGCPM_Plugin', false ),
			'theme'           => function_exists( 'bi_pages_registry' ) || false !== stripos( wp_get_theme()->get_stylesheet(), 'beyondinfinity' ),
		];
		return ! empty( $map[ $slug ] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function service_health() {
		global $wpdb;
		$tables_ok = class_exists( 'NGC_Database' ) && NGC_Database::tables_exist();
		$intel_ok  = false;
		if ( class_exists( 'NGC_Database' ) ) {
			$t = NGC_Database::table( 'intel_events' );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$intel_ok = (bool) $wpdb->get_var( "SHOW TABLES LIKE '{$t}'" );
		}
		return [
			'database'    => $tables_ok ? 'healthy' : 'degraded',
			'intelligence'=> $intel_ok ? 'healthy' : 'missing',
			'rest_api'    => rest_url( 'ngc/v1/' ) ? 'healthy' : 'unknown',
			'observability' => class_exists( 'NGC_Observability_Service' ) ? 'healthy' : 'missing',
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function infrastructure_health() {
		$mem = function_exists( 'memory_get_usage' ) ? memory_get_usage( true ) : 0;
		$limit = ini_get( 'memory_limit' );
		return [
			'memory_usage_mb' => round( $mem / 1048576, 1 ),
			'memory_limit'    => $limit,
			'object_cache'    => wp_using_ext_object_cache() ? 'external' : 'default',
			'cron_disabled'   => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
			'debug_mode'      => defined( 'WP_DEBUG' ) && WP_DEBUG,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function ai_health() {
		$paused = (bool) get_option( 'ngtai_global_pause', false );
		$enabled = (bool) get_option( 'ngtai_enabled', false );
		return [
			'plugin_active' => defined( 'NGTAI_VERSION' ) || class_exists( 'NGTAI_Plugin', false ),
			'enabled'       => $enabled,
			'paused'        => $paused,
			'status'        => $paused ? 'paused' : ( $enabled ? 'active' : 'disabled' ),
		];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function cron_health() {
		return class_exists( 'NGC_Observability_Service' )
			? NGC_Observability_Service::cron_status()
			: [];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function security_health() {
		global $wpdb;
		$failed_logins = 0;
		$table = class_exists( 'NGC_Database' ) ? NGC_Database::table( 'intel_events' ) : '';
		if ( $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$failed_logins = (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$table} WHERE event_key='auth.login_failed' AND recorded_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 24 HOUR)"
				);
			}
		}
		return [
			'ssl'              => is_ssl(),
			'failed_logins_24h'=> $failed_logins,
			'force_ssl_admin'  => defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function compliance_health() {
		return [
			'privacy_module' => class_exists( 'NGC_Privacy' ),
			'safeguarding'   => class_exists( 'NGC_Safeguarding' ),
			'audit_service'  => class_exists( 'NGC_Audit_Service' ),
			'demo_mode'      => (bool) get_option( 'ngc_demo_mode', false ),
			'mask_pii'       => NGC_Intelligence_Config::get()['mask_pii'] ?? true,
		];
	}
}
