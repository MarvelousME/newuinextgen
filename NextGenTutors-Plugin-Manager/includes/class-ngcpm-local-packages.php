<?php
/**
 * Local plugin zip directory detection and auto-install.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scans configured zip directories and installs matching registry plugins.
 */
class NGCPM_Local_Packages {

	/**
	 * Ensure all package search directories exist.
	 */
	public static function ensure_directories() {
		foreach ( NGCPM_Settings::package_search_dirs() as $dir ) {
			if ( $dir && ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}
		}
		self::mirror_bundled_zips();
	}

	/**
	 * Copy bundled offline zips into the primary packages dir (Docker volume).
	 */
	public static function mirror_bundled_zips() {
		$primary = NGCPM_Settings::local_zip_dir();
		$bundled = trailingslashit( NGCPM_PLUGIN_DIR ) . 'offline-packages';
		if ( ! is_dir( $primary ) || ! is_dir( $bundled ) || ! is_readable( $bundled ) ) {
			return;
		}
		foreach ( (array) glob( trailingslashit( $bundled ) . '*.zip' ) as $zip ) {
			$dest = trailingslashit( $primary ) . basename( $zip );
			if ( file_exists( $dest ) ) {
				continue;
			}
			if ( is_writable( $primary ) ) {
				@copy( $zip, $dest ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		}
	}

	/**
	 * Registry plugins that have a local zip but are not installed yet.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function pending_installs() {
		$scan    = NGCPM_Scanner::scan( true );
		$pending = [];

		foreach ( NGCPM_Registry::sorted() as $slug => $def ) {
			$row = $scan[ $slug ] ?? [];
			if ( ! empty( $row['installed'] ) || ! empty( $row['is_skipped'] ) ) {
				continue;
			}
			$def['registry_key'] = $slug;
			$local               = NGCPM_Installer::resolve_local_path( $def );
			if ( ! $local ) {
				continue;
			}
			$pending[] = [
				'slug' => $slug,
				'name' => (string) ( $def['name'] ?? $slug ),
				'zip'  => basename( $local ),
				'path' => wp_normalize_path( $local ),
				'dir'  => wp_normalize_path( dirname( $local ) ),
			];
		}

		return $pending;
	}

	/**
	 * All readable zip files across search directories (first directory wins per basename).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function inventory() {
		self::ensure_directories();
		$items = [];
		$seen  = [];

		foreach ( NGCPM_Settings::package_search_dirs() as $dir ) {
			if ( ! is_dir( $dir ) || ! is_readable( $dir ) ) {
				continue;
			}
			foreach ( (array) glob( trailingslashit( $dir ) . '*.zip' ) as $path ) {
				$basename = basename( $path );
				if ( isset( $seen[ $basename ] ) ) {
					continue;
				}
				$seen[ $basename ] = true;
				$match             = self::match_zip_to_registry( $basename );
				$items[]           = [
					'file'          => $basename,
					'path'          => wp_normalize_path( $path ),
					'dir'           => wp_normalize_path( $dir ),
					'size'          => (int) @filesize( $path ), // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					'matched_slug'  => (string) ( $match['slug'] ?? '' ),
					'matched_name'  => (string) ( $match['name'] ?? '' ),
					'installed'     => ! empty( $match['installed'] ),
					'active'        => ! empty( $match['active'] ),
					'pending'       => ! empty( $match['pending'] ),
				];
			}
		}

		return $items;
	}

	/**
	 * @param string $basename Zip filename.
	 * @return array<string, mixed>
	 */
	private static function match_zip_to_registry( $basename ) {
		$scan = NGCPM_Scanner::scan( true );
		foreach ( NGCPM_Registry::sorted() as $slug => $def ) {
			$def['registry_key'] = $slug;
			$local               = NGCPM_Installer::resolve_local_path( $def );
			if ( ! $local || basename( $local ) !== $basename ) {
				continue;
			}
			$row = $scan[ $slug ] ?? [];
			return [
				'slug'      => $slug,
				'name'      => (string) ( $def['name'] ?? $slug ),
				'installed' => ! empty( $row['installed'] ),
				'active'    => ! empty( $row['active'] ),
				'pending'   => empty( $row['installed'] ) && empty( $row['is_skipped'] ),
			];
		}
		return [];
	}

	/**
	 * Install every registry plugin that has a matching local zip.
	 *
	 * @param bool $activate_required Activate required plugins after install.
	 * @return array<int, array<string, mixed>>
	 */
	public static function install_pending( $activate_required = true ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return [
				[
					'success' => false,
					'message' => __( 'Permission denied.', 'nextgentutors-plugin-manager' ),
					'slug'    => '',
				],
			];
		}

		$results = [];
		foreach ( self::pending_installs() as $item ) {
			$slug = (string) $item['slug'];
			$def  = NGCPM_Registry::get( $slug );
			$r    = NGCPM_Installer::install( $slug );
			if ( $activate_required && ! empty( $r['success'] ) && ! empty( $def['required'] ) && current_user_can( 'activate_plugins' ) ) {
				NGCPM_Activator::activate( $slug );
			}
			$results[] = array_merge(
				$r,
				[
					'name' => (string) $item['name'],
					'zip'  => (string) $item['zip'],
				]
			);
		}

		NGCPM_Scanner::clear_cache();
		return $results;
	}

	/**
	 * Auto-install on Plugin Manager screen when enabled and zips are waiting.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function maybe_auto_install() {
		if ( ! NGCPM_Settings::auto_install_local_enabled() || ! current_user_can( 'install_plugins' ) ) {
			return [];
		}
		if ( get_transient( 'ngcpm_local_auto_install_lock' ) ) {
			return [];
		}
		$pending = self::pending_installs();
		if ( empty( $pending ) ) {
			return [];
		}
		set_transient( 'ngcpm_local_auto_install_lock', 1, 2 * MINUTE_IN_SECONDS );
		NGCPM_Logger::log( 'local_auto_install', 'Auto-installing from local zip directory', [ 'count' => count( $pending ) ] );
		return self::install_pending( true );
	}

	/**
	 * Payload for admin UI and JS config.
	 *
	 * @return array<string, mixed>
	 */
	public static function public_status() {
		self::ensure_directories();
		$primary = NGCPM_Settings::local_zip_dir();
		$pending = self::pending_installs();

		return [
			'primary_dir'   => $primary,
			'search_dirs'   => NGCPM_Settings::package_search_dirs(),
			'bundle_dir'    => trailingslashit( NGCPM_PLUGIN_DIR ) . 'offline-packages',
			'exists'        => is_dir( $primary ),
			'writable'      => is_dir( $primary ) && is_writable( $primary ),
			'inventory'     => self::inventory(),
			'pending'       => $pending,
			'pending_count' => count( $pending ),
			'zip_count'     => count( self::inventory() ),
			'auto_enabled'  => NGCPM_Settings::auto_install_local_enabled(),
		];
	}
}
