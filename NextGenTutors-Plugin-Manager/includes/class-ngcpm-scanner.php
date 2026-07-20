<?php
/**
 * Plugin status scanner.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scans installed/active/version state for registry plugins.
 */
class NGCPM_Scanner {

	const CACHE_OPTION = 'ngcpm_scan_cache';

	/**
	 * Run full scan and optionally cache.
	 *
	 * @param bool $use_cache Use cached scan if fresh.
	 * @return array<string, array<string, mixed>>
	 */
	public static function scan( $use_cache = false ) {
		if ( $use_cache ) {
			$cached = get_transient( self::CACHE_OPTION );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed = get_plugins();
		$active    = (array) get_option( 'active_plugins', [] );
		if ( is_multisite() ) {
			$network = array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) );
			$active  = array_merge( $active, $network );
		}
		$results   = [];

		foreach ( NGCPM_Registry::sorted() as $slug => $def ) {
			$results[ $slug ] = self::scan_one( $slug, $def, $installed, $active );
		}

		set_transient( self::CACHE_OPTION, $results, 5 * MINUTE_IN_SECONDS );
		update_option( 'ngcpm_last_scan_time', time(), false );
		NGCPM_Logger::log( 'scan', 'Dependency scan completed', [ 'count' => count( $results ) ] );

		return $results;
	}

	/**
	 * @param string                      $registry_key Registry array key.
	 * @param array<string, mixed>        $def          Registry row.
	 * @param array<string, array<mixed>> $installed    All plugins.
	 * @param string[]                    $active       Active plugin files.
	 * @return array<string, mixed>
	 */
	private static function scan_one( $registry_key, $def, $installed, $active ) {
		$main_file = (string) ( $def['main_file'] ?? '' );
		$discovered = '';
		if ( ! isset( $installed[ $main_file ] ) ) {
			$discovered = NGCPM_Installer::discover_main_file( array_merge( $def, [ 'registry_key' => $registry_key ] ) );
			if ( $discovered ) {
				$main_file = $discovered;
			}
		}
		$installed_flag = isset( $installed[ $main_file ] );
		$active_flag    = in_array( $main_file, $active, true );
		$version        = $installed_flag ? (string) ( $installed[ $main_file ]['Version'] ?? '' ) : '';
		$required_ver   = (string) ( $def['required_version'] ?? '' );
		$source_type    = (string) ( $def['source_type'] ?? NGCPM_Registry::SOURCE_MANUAL );
		$row_def        = array_merge( $def, [ 'registry_key' => $registry_key ] );
		$skipped        = NGCPM_Lifecycle::is_skipped( $registry_key );

		$health = self::derive_health( $row_def, $installed_flag, $active_flag, $version, $required_ver, $source_type, $skipped );
		$local_zip = NGCPM_Installer::resolve_local_path( $row_def );

		return array_merge(
			$row_def,
			[
				'installed'         => $installed_flag,
				'active'            => $active_flag,
				'installed_version' => $version,
				'version_ok'        => self::version_ok( $version, $required_ver ),
				'health_status'     => $health,
				'can_auto_install'  => ! $skipped && NGCPM_Installer::can_auto_install( $row_def ),
				'can_activate'      => $installed_flag && ! $active_flag,
				'is_skipped'        => $skipped,
				'can_deactivate'    => $installed_flag && $active_flag && current_user_can( 'deactivate_plugins' ),
				'can_uninstall'     => $installed_flag && empty( $def['required'] ) && current_user_can( 'delete_plugins' ),
				'can_dismiss'       => empty( $def['required'] ) && ! $skipped,
				'local_zip_path'    => $local_zip,
				'local_zip_ready'   => (bool) $local_zip,
			]
		);
	}

	/**
	 * @param array<string, mixed> $def            Registry.
	 * @param bool                 $installed      Installed.
	 * @param bool                 $active         Active.
	 * @param string               $version        Installed version.
	 * @param string               $required_ver   Required version.
	 * @param string               $source_type    Source.
	 * @return string
	 */
	private static function derive_health( $def, $installed, $active, $version, $required_ver, $source_type, $skipped = false ) {
		if ( $skipped && ! $installed ) {
			return 'SKIPPED';
		}
		if ( ! $installed ) {
			if ( NGCPM_Registry::SOURCE_MANUAL === $source_type && ! NGCPM_Installer::has_package_source( $def ) ) {
				return 'MANUAL_REQUIRED';
			}
			return 'MISSING';
		}
		if ( ! $active ) {
			return 'INACTIVE';
		}
		if ( $required_ver && $version && ! self::version_ok( $version, $required_ver ) ) {
			return 'VERSION_OUTDATED';
		}
		return 'READY';
	}

	/**
	 * @param string $current  Current version.
	 * @param string $required Required minimum.
	 * @return bool
	 */
	public static function version_ok( $current, $required ) {
		if ( ! $required || ! $current ) {
			return true;
		}
		return version_compare( $current, $required, '>=' );
	}

	/**
	 * Clear scan cache.
	 */
	public static function clear_cache() {
		delete_transient( self::CACHE_OPTION );
	}
}
