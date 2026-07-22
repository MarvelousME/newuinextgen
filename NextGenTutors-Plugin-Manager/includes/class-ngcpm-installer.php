<?php
/**
 * Plugin installation via WordPress upgrader.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Secure plugin installation.
 */
class NGCPM_Installer {

	const HTTP_TIMEOUT = 300;

	/**
	 * @param array<string, mixed> $def Registry row.
	 * @return bool
	 */
	public static function can_auto_install( $def ) {
		$source = (string) ( $def['source_type'] ?? '' );
		if ( NGCPM_Registry::SOURCE_WPORG === $source ) {
			return ! empty( $def['slug'] );
		}
		return self::has_package_source( $def );
	}

	/**
	 * @param array<string, mixed> $def Registry row.
	 * @return bool
	 */
	public static function has_package_source( $def ) {
		$source = (string) ( $def['source_type'] ?? '' );

		if ( NGCPM_Registry::SOURCE_WPORG === $source ) {
			return ! empty( $def['slug'] );
		}

		if ( self::resolve_local_path( $def ) ) {
			return true;
		}

		if ( NGCPM_Settings::remote_zips_enabled() ) {
			$registry_key = (string) ( $def['registry_key'] ?? $def['slug'] ?? '' );
			$whitelist    = NGCPM_Registry::remote_whitelist();
			$url          = $whitelist[ $registry_key ] ?? '';
			if ( $url && self::is_whitelisted_url( $url ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array<string, mixed> $def Registry row.
	 * @return string Absolute path to zip or empty.
	 */
	public static function resolve_local_path( $def ) {
		$path = (string) ( $def['package_path'] ?? '' );
		if ( $path && file_exists( $path ) ) {
			return wp_normalize_path( $path );
		}

		$registry_key = (string) ( $def['registry_key'] ?? '' );
		$candidates     = array_unique(
			array_filter(
				[
					sanitize_file_name( (string) ( $def['slug'] ?? '' ) ),
					sanitize_file_name( (string) ( $def['wporg_slug'] ?? '' ) ),
					sanitize_file_name( $registry_key ),
					sanitize_file_name( str_replace( '-', '', $registry_key ) ),
					$path ? sanitize_file_name( basename( $path, '.zip' ) ) : '',
				]
			)
		);

		foreach ( NGCPM_Settings::package_search_dirs() as $dir ) {
			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}
			if ( ! is_dir( $dir ) || ! is_readable( $dir ) ) {
				continue;
			}

			foreach ( $candidates as $name ) {
				if ( ! $name ) {
					continue;
				}
				$guess = trailingslashit( $dir ) . $name . '.zip';
				if ( file_exists( $guess ) && is_readable( $guess ) ) {
					return wp_normalize_path( $guess );
				}
			}

			// Fuzzy: masterstudy-lms registry key vs masterstudy-lms-learning-management-system.zip
			$zips = glob( trailingslashit( $dir ) . '*.zip' );
			if ( is_array( $zips ) ) {
				foreach ( $zips as $zip_path ) {
					$base = sanitize_file_name( basename( $zip_path, '.zip' ) );
					foreach ( $candidates as $name ) {
						if ( ! $name ) {
							continue;
						}
						if ( $base === $name || 0 === strpos( $base, $name ) || 0 === strpos( $name, $base ) ) {
							return wp_normalize_path( $zip_path );
						}
					}
				}
			}
		}

		return '';
	}

	/**
	 * List readable zip basenames across all package directories.
	 *
	 * @return array<int, string>
	 */
	public static function list_local_packages() {
		$found = [];
		foreach ( NGCPM_Settings::package_search_dirs() as $dir ) {
			if ( ! is_dir( $dir ) ) {
				continue;
			}
			foreach ( (array) glob( trailingslashit( $dir ) . '*.zip' ) as $zip ) {
				$found[] = basename( $zip );
			}
		}
		return array_values( array_unique( $found ) );
	}

	/**
	 * @param array<string, mixed> $def Registry row.
	 * @return bool
	 */
	/**
	 * Prefer local packages for this plugin; do not block network installs of other plugins
	 * merely because unrelated zips exist in the packages directory.
	 *
	 * @param array<string, mixed> $def Registry row.
	 * @return bool
	 */
	public static function should_skip_remote_download( $def ) {
		if ( self::resolve_local_path( $def ) ) {
			return true;
		}
		if ( NGCPM_Registry::SOURCE_MANUAL === ( $def['source_type'] ?? '' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Direct WordPress.org zip URL (no metadata API).
	 *
	 * @param array<string, mixed>|string $def_or_slug Registry row or slug string.
	 * @return string
	 */
	public static function wporg_zip_url( $def_or_slug ) {
		if ( is_array( $def_or_slug ) ) {
			$slug = sanitize_key( (string) ( $def_or_slug['wporg_slug'] ?? $def_or_slug['slug'] ?? '' ) );
		} else {
			$slug = sanitize_key( (string) $def_or_slug );
		}
		return 'https://downloads.wordpress.org/plugin/' . rawurlencode( $slug ) . '.latest-stable.zip';
	}

	/**
	 * Load WordPress upgrader dependencies for AJAX installs.
	 */
	private static function boot_upgrader() {
		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';

		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem || ! is_object( $wp_filesystem ) ) {
			ob_start();
			$creds = request_filesystem_credentials( admin_url( 'admin-ajax.php' ), '', false, false, null );
			ob_end_clean();
			if ( ! WP_Filesystem( $creds ) ) {
				NGCPM_Logger::log( 'install_failure', 'WP_Filesystem unavailable', [] );
			}
		}
	}

	/**
	 * @return string Empty when OK, otherwise error message.
	 */
	private static function filesystem_error() {
		global $wp_filesystem;
		if ( $wp_filesystem && is_object( $wp_filesystem ) ) {
			if ( ! $wp_filesystem->is_writable( WP_PLUGIN_DIR ) ) {
				return __( 'wp-content/plugins is not writable.', 'nextgentutors-plugin-manager' );
			}
			return '';
		}
		return __( 'WordPress filesystem is not initialized. Set FS_METHOD to direct or configure FTP credentials.', 'nextgentutors-plugin-manager' );
	}

	/**
	 * Install a single plugin.
	 *
	 * @param string $slug Registry slug key.
	 * @return array{success:bool,message:string,slug:string}
	 */
	public static function install( $slug, $args = [] ) {
		$overwrite = ! empty( $args['overwrite'] );
		$def       = NGCPM_Registry::get( $slug );
		if ( ! $def ) {
			return [ 'success' => false, 'message' => __( 'Unknown plugin.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}
		$def['registry_key'] = $slug;

		if ( ! current_user_can( 'install_plugins' ) ) {
			return [ 'success' => false, 'message' => __( 'Permission denied.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}

		NGCPM_Logger::log( 'install_started', 'Install started', [ 'slug' => $slug, 'overwrite' => $overwrite ] );
		self::boot_upgrader();

		if ( self::is_plugin_registered( $def ) ) {
			NGCPM_Logger::log( 'install_success', 'Already installed', [ 'slug' => $slug ] );
			return [ 'success' => true, 'message' => __( 'Already installed.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}

		$conflict = self::destination_folder_exists( $def );
		if ( $conflict && ! $overwrite ) {
			return [
				'success' => false,
				'code'    => 'folder_exists',
				'message' => sprintf(
					/* translators: %s: folder name */
					__( 'Destination folder already exists: %s', 'nextgentutors-plugin-manager' ),
					basename( $conflict )
				),
				'folder'  => basename( $conflict ),
				'slug'    => $slug,
			];
		}

		if ( $conflict && $overwrite ) {
			self::prepare_install_destination( $def, true );
		} else {
			self::prepare_install_destination( $def );
		}

		$local = self::resolve_local_path( $def );
		if ( $local ) {
			$result = self::run_upgrader_install( $slug, $local, $def, $overwrite );
			if ( ! empty( $result['success'] ) ) {
				$result['message'] = __( 'Installed from local package cache.', 'nextgentutors-plugin-manager' );
			}
			self::finish_install( $slug, $result );
			return $result;
		}

		$source = (string) ( $def['source_type'] ?? '' );
		if ( NGCPM_Registry::SOURCE_MANUAL === $source ) {
			$local_manual = self::resolve_local_path( $def );
			if ( $local_manual ) {
				$result = self::run_upgrader_install( $slug, $local_manual, $def, $overwrite );
				if ( ! empty( $result['success'] ) ) {
					$result['message'] = __( 'Installed from local package cache.', 'nextgentutors-plugin-manager' );
				}
				self::finish_install( $slug, $result );
				return $result;
			}
			$msg = self::manual_install_hint( $slug );
			NGCPM_Logger::log( 'manual_required', $msg, [ 'slug' => $slug ] );
			$result = [ 'success' => false, 'message' => $msg, 'slug' => $slug, 'status' => 'MANUAL_REQUIRED' ];
			self::finish_install( $slug, $result );
			return $result;
		}

		if ( NGCPM_Registry::SOURCE_WPORG === $source ) {
			if ( self::should_skip_remote_download( $def ) && ! self::resolve_local_path( $def ) ) {
				$result = [
					'success' => false,
					'message' => self::offline_install_hint( $slug, __( 'Local zip not found; network download disabled in offline mode.', 'nextgentutors-plugin-manager' ) ),
					'slug'    => $slug,
				];
				self::finish_install( $slug, $result );
				return $result;
			}
			$result = self::install_from_wporg( $def, $overwrite );
		} elseif ( self::has_package_source( $def ) ) {
			$result = self::install_from_zip( $def, $overwrite );
		} else {
			$msg = sprintf(
				/* translators: %s: packages directory path */
				__( 'Manual install required. Place %s.zip in %s', 'nextgentutors-plugin-manager' ),
				sanitize_file_name( (string) ( $def['slug'] ?? $slug ) ),
				NGCPM_Settings::local_zip_dir()
			);
			NGCPM_Logger::log( 'manual_required', $msg, [ 'slug' => $slug ] );
			$result = [ 'success' => false, 'message' => $msg, 'slug' => $slug, 'status' => 'MANUAL_REQUIRED' ];
		}

		self::finish_install( $slug, $result );
		return $result;
	}

	/**
	 * Public wrapper for WordPress.org / remote zip installs.
	 *
	 * @param string $slug Registry or wporg slug label for logging.
	 * @param string $url  Zip URL.
	 * @return array{success:bool,message:string,slug:string}
	 */
	public static function install_from_download_url_public( $slug, $url ) {
		self::boot_upgrader();
		return self::install_from_download_url( sanitize_key( $slug ), esc_url_raw( $url ) );
	}

	/**
	 * Install from a local filesystem zip path.
	 *
	 * @param string $slug    Label for logging.
	 * @param string $package Absolute zip path.
	 * @return array{success:bool,message:string,slug:string}
	 */
	public static function install_from_local_package( $slug, $package ) {
		self::boot_upgrader();
		if ( ! file_exists( $package ) ) {
			return [ 'success' => false, 'message' => __( 'Zip file not found.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}
		$result = self::run_upgrader_install( sanitize_key( $slug ), $package, null, false );
		if ( ! empty( $result['success'] ) && empty( $result['plugin_file'] ) ) {
			$found = self::discover_main_file( [ 'slug' => $slug, 'registry_key' => $slug ] );
			if ( ! $found && class_exists( 'ZipArchive' ) ) {
				$zip = new ZipArchive();
				if ( true === $zip->open( $package ) ) {
					$root = self::zip_root_folder( $zip );
					$zip->close();
					if ( $root ) {
						$found = self::find_plugin_file_in_dir( WP_PLUGIN_DIR . '/' . $root );
					}
				}
			}
			if ( $found ) {
				$result['plugin_file'] = $found;
			}
		}
		return $result;
	}

	/**
	 * Batch install missing auto-installable plugins; skip manual/premium.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function install_missing() {
		$scan    = NGCPM_Scanner::scan( false );
		$results = [];

		foreach ( $scan as $slug => $row ) {
			if ( ! empty( $row['installed'] ) ) {
				continue;
			}
			if ( empty( $row['can_auto_install'] ) ) {
				$results[] = [
					'success' => false,
					'slug'    => $slug,
					'message' => __( 'Skipped — manual or premium install required.', 'nextgentutors-plugin-manager' ),
					'skipped' => true,
				];
				continue;
			}
			$results[] = self::install( $slug );
		}

		return $results;
	}

	/**
	 * @param string               $slug   Registry slug.
	 * @param array<string, mixed> $result Install result.
	 */
	private static function finish_install( $slug, $result ) {
		NGCPM_Scanner::clear_cache();
		if ( ! empty( $result['success'] ) ) {
			NGCPM_Logger::log( 'install_success', $result['message'], [ 'slug' => $slug ] );
		} else {
			NGCPM_Logger::log( 'install_failure', $result['message'] ?? 'Install failed', [ 'slug' => $slug ] );
		}
	}

	/**
	 * @param array<string, mixed> $def Registry row.
	 * @return array{success:bool,message:string,slug:string}
	 */
	private static function install_from_wporg( $def, $overwrite = false ) {
		$slug = sanitize_key( (string) $def['slug'] );
		if ( ! $slug ) {
			return [ 'success' => false, 'message' => __( 'Invalid slug.', 'nextgentutors-plugin-manager' ), 'slug' => '' ];
		}

		NGCPM_Logger::log( 'install_direct_zip', 'Installing from WordPress.org zip URL', [ 'slug' => $slug ] );
		return self::install_from_download_url( $slug, self::wporg_zip_url( $def ), $overwrite );
	}

	/**
	 * Download remote zip to temp, then run upgrader (avoids short upgrader HTTP timeouts).
	 *
	 * @param string $slug Registry slug.
	 * @param string $url  HTTPS zip URL.
	 * @return array{success:bool,message:string,slug:string}
	 */
	private static function install_from_download_url( $slug, $url, $overwrite = false ) {
		$url = self::normalize_download_url( $url );
		self::begin_http_filters();
		$tmp = download_url( $url, self::HTTP_TIMEOUT );
		self::end_http_filters();

		if ( is_wp_error( $tmp ) ) {
			$message = self::offline_install_hint( $slug, self::clean_message( $tmp->get_error_message() ) );
			return [ 'success' => false, 'message' => $message, 'slug' => $slug ];
		}

		$result = self::run_upgrader_install( $slug, $tmp, NGCPM_Registry::get( $slug ), $overwrite );
		@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( ! empty( $result['success'] ) ) {
			$result['message'] = __( 'Installed from WordPress.org.', 'nextgentutors-plugin-manager' );
		}

		return $result;
	}

	/**
	 * Normalize common WordPress.org plugin page URLs into direct zip URLs.
	 *
	 * @param string $url Candidate URL from settings/UI.
	 * @return string
	 */
	private static function normalize_download_url( $url ) {
		$url = esc_url_raw( (string) $url );
		if ( ! $url ) {
			return '';
		}

		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return $url;
		}

		$host = strtolower( (string) ( $parts['host'] ?? '' ) );
		$path = trim( (string) ( $parts['path'] ?? '' ), '/' );
		if ( 'wordpress.org' !== $host && 'www.wordpress.org' !== $host ) {
			return $url;
		}

		if ( preg_match( '#^plugins/([a-z0-9-]+)/?$#i', $path, $m ) ) {
			return self::wporg_zip_url( sanitize_key( $m[1] ) );
		}

		return $url;
	}

	/**
	 * @param string $slug    Registry slug.
	 * @param string $detail  Network error detail.
	 * @return string
	 */
	private static function offline_install_hint( $slug, $detail ) {
		$def  = NGCPM_Registry::get( $slug );
		$zip  = sanitize_file_name( (string) ( $def['slug'] ?? $slug ) );
		$dirs = NGCPM_Settings::package_search_dirs();
		$dir  = $dirs[0] ?? NGCPM_Settings::local_zip_dir();
		$available = self::list_local_packages();
		$hint = sprintf(
			/* translators: 1: error detail 2: zip filename 3: directory path */
			__( 'Download failed: %1$s. Place %2$s.zip in %3$s or run docker/scripts/cache-wporg-zips.ps1 on the host.', 'nextgentutors-plugin-manager' ),
			$detail,
			$zip,
			$dir
		);
		if ( $available ) {
			$hint .= ' ' . sprintf(
				/* translators: %s: comma-separated zip list */
				__( 'Available local zips: %s', 'nextgentutors-plugin-manager' ),
				implode( ', ', array_slice( $available, 0, 12 ) )
			);
		}
		$hint .= ' ' . sprintf(
			/* translators: %s: plugin offline-packages path */
			__( 'Plugin bundle path: %s', 'nextgentutors-plugin-manager' ),
			trailingslashit( NGCPM_PLUGIN_DIR ) . 'offline-packages'
		);
		return self::clean_message( $hint );
	}

	/**
	 * @param string $message Raw message.
	 * @return string
	 */
	private static function clean_message( $message ) {
		$message = wp_strip_all_tags( html_entity_decode( (string) $message, ENT_QUOTES, 'UTF-8' ) );
		return trim( preg_replace( '/\s+/', ' ', $message ) );
	}

	/**
	 * Whether WordPress recognizes the registry plugin as installed.
	 *
	 * @param array<string, mixed> $def Registry row.
	 * @return bool
	 */
	public static function is_plugin_registered( $def ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$main_file = (string) ( $def['main_file'] ?? '' );
		if ( $main_file && file_exists( WP_PLUGIN_DIR . '/' . $main_file ) ) {
			$all = get_plugins();
			if ( isset( $all[ $main_file ] ) ) {
				return true;
			}
		}
		return (bool) self::discover_main_file( $def );
	}

	/**
	 * Find installed plugin file when registry main_file differs from package layout.
	 *
	 * @param array<string, mixed> $def Registry row.
	 * @return string Plugin path relative to plugins dir or empty.
	 */
	public static function discover_main_file( $def ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$needles = array_unique(
			array_filter(
				[
					(string) ( $def['slug'] ?? '' ),
					(string) ( $def['wporg_slug'] ?? '' ),
					(string) ( $def['registry_key'] ?? '' ),
					basename( dirname( (string) ( $def['main_file'] ?? '' ) ) ),
					preg_replace( '/\.\d.*$/', '', (string) ( $def['slug'] ?? '' ) ),
				]
			)
		);
		foreach ( get_plugins() as $file => $data ) {
			foreach ( $needles as $needle ) {
				if ( $needle && false !== stripos( $file, $needle ) ) {
					return $file;
				}
			}
		}
		return '';
	}

	/**
	 * Detect orphan plugin folder on disk (not registered in WordPress).
	 *
	 * @param array<string, mixed> $def Registry row.
	 * @return string Absolute path or empty.
	 */
	public static function destination_folder_exists( $def ) {
		$dirs = [];
		$dest = self::destination_dir( $def );
		if ( $dest ) {
			$dirs[] = $dest;
		}
		$slug = sanitize_file_name( (string) ( $def['slug'] ?? '' ) );
		if ( $slug ) {
			$dirs[] = wp_normalize_path( WP_PLUGIN_DIR . '/' . $slug );
		}
		foreach ( array_unique( array_filter( $dirs ) ) as $dir ) {
			if ( is_dir( $dir ) && ! self::is_plugin_registered( $def ) ) {
				return $dir;
			}
		}
		return '';
	}

	/**
	 * Expected plugin directory under wp-content/plugins.
	 *
	 * @param array<string, mixed> $def Registry row.
	 * @return string
	 */
	private static function destination_dir( $def ) {
		$main_file = (string) ( $def['main_file'] ?? '' );
		if ( ! $main_file ) {
			return '';
		}
		return wp_normalize_path( WP_PLUGIN_DIR . '/' . dirname( $main_file ) );
	}

	/**
	 * Remove broken/orphan plugin folders before install.
	 *
	 * @param array<string, mixed> $def Registry row.
	 * @param bool                 $force Remove even when registered (for retry).
	 * @return bool
	 */
	private static function prepare_install_destination( $def, $force = false ) {
		if ( ! $force && self::is_plugin_registered( $def ) ) {
			return true;
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem || ! is_object( $wp_filesystem ) ) {
			return false;
		}

		$dirs = [];
		$dest = self::destination_dir( $def );
		if ( $dest ) {
			$dirs[] = $dest;
		}

		$slug = sanitize_file_name( (string) ( $def['slug'] ?? '' ) );
		if ( $slug ) {
			$dirs[] = wp_normalize_path( WP_PLUGIN_DIR . '/' . $slug );
		}

		$dirs = array_unique( array_filter( $dirs ) );
		foreach ( $dirs as $dir ) {
			if ( is_dir( $dir ) ) {
				$wp_filesystem->delete( $dir, true );
				NGCPM_Logger::log( 'install_cleanup', 'Removed orphan plugin folder before install', [ 'dir' => basename( $dir ) ] );
			}
		}

		return true;
	}

	/**
	 * Human hint for premium/manual plugins (no "Download failed" wording).
	 *
	 * @param string $slug Registry key.
	 * @return string
	 */
	private static function manual_install_hint( $slug ) {
		$def      = NGCPM_Registry::get( $slug );
		$zip      = sanitize_file_name( (string) ( $def['slug'] ?? $slug ) );
		$optional = empty( $def['required'] );
		$target   = NGCPM_Settings::local_zip_dir();

		if ( $optional ) {
			return self::clean_message(
				sprintf(
					/* translators: 1: plugin name 2: zip filename 3: folder path */
					__( '%1$s is optional and premium. Skip it, or place your licensed %2$s.zip in %3$s then click Install again.', 'nextgentutors-plugin-manager' ),
					(string) ( $def['name'] ?? $slug ),
					$zip,
					$target
				)
			);
		}

		return self::clean_message(
			sprintf(
				/* translators: 1: plugin name 2: zip filename 3: folder path */
				__( '%1$s requires a manual zip. Place %2$s.zip in %3$s (or wp-content/ngcpm-packages), then click Install again.', 'nextgentutors-plugin-manager' ),
				(string) ( $def['name'] ?? $slug ),
				$zip,
				$target
			)
		);
	}

	/**
	 * @param string $slug    Registry slug.
	 * @param string $package Local filesystem path to zip.
	 * @param array<string, mixed>|null $def Registry row.
	 * @return array{success:bool,message:string,slug:string}
	 */
	private static function run_upgrader_install( $slug, $package, $def = null, $overwrite = false ) {
		$def = $def ?: NGCPM_Registry::get( $slug );
		if ( ! is_array( $def ) ) {
			$def = [ 'main_file' => '', 'slug' => $slug ];
		}

		$fs_error = self::filesystem_error();
		if ( $fs_error ) {
			return [ 'success' => false, 'message' => $fs_error, 'slug' => $slug ];
		}

		self::prepare_install_destination( $def );

		$result = self::run_upgrader_install_once( $slug, $package );
		if ( ! empty( $result['success'] ) ) {
			return $result;
		}

		$msg = (string) ( $result['message'] ?? '' );
		if ( false !== stripos( $msg, 'destination folder already exists' ) || false !== stripos( $msg, 'folder already exists' ) ) {
			if ( ! $overwrite ) {
				$folder = self::destination_folder_exists( $def );
				return [
					'success' => false,
					'code'    => 'folder_exists',
					'message' => sprintf(
						/* translators: %s: folder name */
						__( 'Destination folder already exists: %s', 'nextgentutors-plugin-manager' ),
						$folder ? basename( $folder ) : $slug
					),
					'folder'  => $folder ? basename( $folder ) : $slug,
					'slug'    => $slug,
				];
			}
			self::prepare_install_destination( $def, true );
			$retry = self::run_upgrader_install_once( $slug, $package );
			if ( ! empty( $retry['success'] ) ) {
				$retry['message'] = __( 'Installed after overwriting the previous folder.', 'nextgentutors-plugin-manager' );
				return $retry;
			}
			if ( self::is_plugin_registered( $def ) ) {
				return [ 'success' => true, 'message' => __( 'Plugin is installed — try Activate.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
			}
			return $retry;
		}

		return $result;
	}

	/**
	 * Single Plugin_Upgrader::install attempt.
	 *
	 * @param string $slug    Registry slug.
	 * @param string $package Zip path.
	 * @return array{success:bool,message:string,slug:string}
	 */
	private static function run_upgrader_install_once( $slug, $package ) {
		$def    = NGCPM_Registry::get( $slug );
		$direct = null;
		if ( is_array( $def ) ) {
			$def['registry_key'] = $slug;
		}

		if ( self::is_local_zip_package( $package ) ) {
			$direct = self::install_local_zip_direct( $slug, $package, is_array( $def ) ? $def : null );
			if ( ! empty( $direct['success'] ) ) {
				return $direct;
			}
		}

		$upgrader = self::run_wordpress_upgrader_install( $slug, $package );
		if ( ! empty( $upgrader['success'] ) ) {
			return $upgrader;
		}

		if ( is_array( $direct ) ) {
			return $direct;
		}

		return $upgrader;
	}

	/**
	 * @param string $package Package path or URL.
	 * @return bool
	 */
	private static function is_local_zip_package( $package ) {
		$package = wp_normalize_path( (string) $package );
		return (bool) ( $package && preg_match( '/\.zip$/i', $package ) && file_exists( $package ) && is_readable( $package ) );
	}

	/**
	 * WordPress Plugin_Upgrader install (remote zips and fallback).
	 *
	 * @param string $slug    Registry slug.
	 * @param string $package Zip path or URL.
	 * @return array{success:bool,message:string,slug:string}
	 */
	private static function run_wordpress_upgrader_install( $slug, $package ) {
		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$ok       = $upgrader->install( $package );

		if ( is_wp_error( $ok ) ) {
			return [ 'success' => false, 'message' => self::clean_message( $ok->get_error_message() ), 'slug' => $slug ];
		}
		if ( ! $ok ) {
			$err = $skin->get_errors();
			$msg = is_wp_error( $err ) && $err->has_errors() ? $err->get_error_message() : __( 'Install failed.', 'nextgentutors-plugin-manager' );
			return [ 'success' => false, 'message' => self::clean_message( $msg ), 'slug' => $slug ];
		}

		return [ 'success' => true, 'message' => __( 'Installed successfully.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
	}

	/**
	 * Reliable local zip install via ZipArchive (Docker-friendly).
	 *
	 * @param string                    $slug    Registry slug.
	 * @param string                    $package Absolute zip path.
	 * @param array<string, mixed>|null $def     Registry row.
	 * @return array{success:bool,message:string,slug:string}
	 */
	private static function install_local_zip_direct( $slug, $package, $def = null ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return [
				'success' => false,
				'message' => __( 'PHP ZipArchive extension is required for local zip installs.', 'nextgentutors-plugin-manager' ),
				'slug'    => $slug,
			];
		}

		$package = wp_normalize_path( $package );
		self::prepare_upgrade_workspace();

		$zip = new ZipArchive();
		if ( true !== $zip->open( $package ) ) {
			return [
				'success' => false,
				'message' => __( 'Could not open local zip archive.', 'nextgentutors-plugin-manager' ),
				'slug'    => $slug,
			];
		}

		$root = self::zip_root_folder( $zip );
		if ( ! $root ) {
			$zip->close();
			return [
				'success' => false,
				'message' => __( 'Local zip has no plugin folder root.', 'nextgentutors-plugin-manager' ),
				'slug'    => $slug,
			];
		}

		$work = wp_normalize_path( WP_CONTENT_DIR . '/upgrade/ngcpm-' . sanitize_file_name( $slug ) . '-' . wp_generate_password( 8, false, false ) );
		wp_mkdir_p( $work );

		if ( ! $zip->extractTo( $work ) ) {
			$zip->close();
			self::delete_path( $work );
			return [
				'success' => false,
				'message' => __( 'Could not extract local zip.', 'nextgentutors-plugin-manager' ),
				'slug'    => $slug,
			];
		}
		$zip->close();

		$source = wp_normalize_path( trailingslashit( $work ) . $root );
		if ( ! is_dir( $source ) ) {
			self::delete_path( $work );
			return [
				'success' => false,
				'message' => __( 'Extracted plugin folder not found in zip.', 'nextgentutors-plugin-manager' ),
				'slug'    => $slug,
			];
		}

		$def  = is_array( $def ) ? $def : ( NGCPM_Registry::get( $slug ) ?: [] );
		$dest = wp_normalize_path( WP_PLUGIN_DIR . '/' . $root );
		self::prepare_install_destination( array_merge( (array) $def, [ 'registry_key' => $slug ] ), true );

		if ( is_dir( $dest ) ) {
			self::delete_path( $dest );
		}

		$moved = @rename( $source, $dest ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $moved ) {
			global $wp_filesystem;
			if ( $wp_filesystem && is_object( $wp_filesystem ) ) {
				$moved = $wp_filesystem->move( $source, $dest, true );
			}
			if ( ! $moved ) {
				$moved = self::copy_dir( $source, $dest );
			}
		}

		self::delete_path( $work );

		if ( ! $moved ) {
			return [
				'success' => false,
				'message' => __( 'Could not move extracted plugin into wp-content/plugins.', 'nextgentutors-plugin-manager' ),
				'slug'    => $slug,
			];
		}

		if ( function_exists( 'wp_clean_plugins_cache' ) ) {
			wp_clean_plugins_cache( true );
		} else {
			wp_cache_delete( 'plugins', 'plugins' );
		}

		$row_def = array_merge(
			(array) $def,
			[
				'registry_key' => $slug,
				'slug'         => (string) ( $def['slug'] ?? $slug ),
			]
		);

		$main = '';
		if ( self::is_plugin_registered( $row_def ) ) {
			$main = self::discover_main_file( $row_def );
		}
		if ( ! $main ) {
			$main = self::discover_main_file( [ 'slug' => $root, 'registry_key' => $root ] );
		}
		if ( ! $main ) {
			$main = self::find_plugin_file_in_dir( $dest );
		}
		if ( ! $main ) {
			return [
				'success' => false,
				'message' => __( 'Plugin files copied but WordPress could not detect the main plugin file.', 'nextgentutors-plugin-manager' ),
				'slug'    => $slug,
			];
		}

		return [
			'success'     => true,
			'message'     => __( 'Installed from local zip.', 'nextgentutors-plugin-manager' ),
			'slug'        => $slug,
			'plugin_file' => $main,
		];
	}

	/**
	 * Locate a WordPress plugin header inside an extracted directory.
	 *
	 * @param string $dir Absolute plugin directory.
	 * @return string Relative plugin file or empty.
	 */
	public static function find_plugin_file_in_dir( $dir ) {
		$dir = wp_normalize_path( (string) $dir );
		if ( ! is_dir( $dir ) ) {
			return '';
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$relative_root = ltrim( str_replace( wp_normalize_path( WP_PLUGIN_DIR ), '', $dir ), '/\\' );
		foreach ( get_plugins() as $file => $_data ) {
			if ( $relative_root && ( $file === $relative_root || 0 === strpos( $file, $relative_root . '/' ) ) ) {
				return $file;
			}
		}
		$php_files = array_merge(
			(array) glob( trailingslashit( $dir ) . '*.php' ),
			(array) glob( trailingslashit( $dir ) . '*/*.php' )
		);
		foreach ( $php_files as $php ) {
			$contents = @file_get_contents( $php, false, null, 0, 8192 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( $contents && preg_match( '/^[ \t\/*#@]*Plugin Name:\s*(.+)$/mi', $contents ) ) {
				return ltrim( str_replace( wp_normalize_path( WP_PLUGIN_DIR ) . '/', '', wp_normalize_path( $php ) ), '/' );
			}
		}
		return '';
	}

	/**
	 * @param ZipArchive $zip Open zip archive.
	 * @return string Root folder name or empty.
	 */
	private static function zip_root_folder( ZipArchive $zip ) {
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$name = (string) $zip->getNameIndex( $i );
			if ( ! $name || false === strpos( $name, '/' ) ) {
				continue;
			}
			$root = strtok( $name, '/' );
			if ( $root && '__MACOSX' !== $root && '.DS_Store' !== $root ) {
				return sanitize_file_name( $root );
			}
		}
		return '';
	}

	/**
	 * Remove stale NGCPM extract workspaces.
	 */
	private static function prepare_upgrade_workspace() {
		$upgrade = wp_normalize_path( WP_CONTENT_DIR . '/upgrade' );
		if ( ! is_dir( $upgrade ) ) {
			wp_mkdir_p( $upgrade );
		}
		foreach ( (array) glob( trailingslashit( $upgrade ) . 'ngcpm-*' ) as $stale ) {
			self::delete_path( $stale );
		}
	}

	/**
	 * @param string $path Path to delete.
	 * @return bool
	 */
	private static function delete_path( $path ) {
		$path = wp_normalize_path( (string) $path );
		if ( ! $path || ! file_exists( $path ) ) {
			return true;
		}
		global $wp_filesystem;
		if ( $wp_filesystem && is_object( $wp_filesystem ) ) {
			return (bool) $wp_filesystem->delete( $path, true );
		}
		if ( is_file( $path ) ) {
			return (bool) @unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		$items = scandir( $path );
		if ( ! is_array( $items ) ) {
			return false;
		}
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			self::delete_path( trailingslashit( $path ) . $item );
		}
		return (bool) @rmdir( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * @param string $source Source directory.
	 * @param string $dest   Destination directory.
	 * @return bool
	 */
	private static function copy_dir( $source, $dest ) {
		$source = wp_normalize_path( $source );
		$dest   = wp_normalize_path( $dest );
		if ( ! is_dir( $source ) ) {
			return false;
		}
		if ( ! is_dir( $dest ) && ! wp_mkdir_p( $dest ) ) {
			return false;
		}
		$items = scandir( $source );
		if ( ! is_array( $items ) ) {
			return false;
		}
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$from = trailingslashit( $source ) . $item;
			$to   = trailingslashit( $dest ) . $item;
			if ( is_dir( $from ) ) {
				if ( ! self::copy_dir( $from, $to ) ) {
					return false;
				}
			} elseif ( ! @copy( $from, $to ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				return false;
			}
		}
		return true;
	}

	/**
	 * @param array<string, mixed> $def Registry row.
	 * @return array{success:bool,message:string,slug:string}
	 */
	private static function install_from_zip( $def, $overwrite = false ) {
		$slug         = (string) ( $def['slug'] ?? '' );
		$registry_key = (string) ( $def['registry_key'] ?? $slug );

		if ( NGCPM_Settings::remote_zips_enabled() ) {
			$whitelist = NGCPM_Registry::remote_whitelist();
			$url       = $whitelist[ $registry_key ] ?? '';
			if ( $url && self::is_whitelisted_url( $url ) ) {
				return self::install_from_download_url( $slug, $url, $overwrite );
			}
		}

		return [
			'success' => false,
			'message' => self::manual_install_hint( $registry_key ),
			'slug'    => $slug,
			'status'  => 'MANUAL_REQUIRED',
		];
	}

	/**
	 * @param string $url URL.
	 * @return bool
	 */
	private static function is_whitelisted_url( $url ) {
		$allowed = NGCPM_Registry::remote_whitelist();
		return in_array( esc_url_raw( $url ), array_map( 'esc_url_raw', $allowed ), true );
	}

	/**
	 * @param int $timeout Seconds.
	 * @return int
	 */
	public static function filter_http_timeout( $timeout ) {
		return max( (int) $timeout, self::HTTP_TIMEOUT );
	}

	/**
	 * @param array<string, mixed> $args Request args.
	 * @param string               $url  Request URL.
	 * @return array<string, mixed>
	 */
	public static function filter_http_request_args( $args, $url ) {
		if ( false !== strpos( $url, 'wordpress.org' ) ) {
			$args['timeout']   = max( (int) ( $args['timeout'] ?? 5 ), self::HTTP_TIMEOUT );
			$args['sslverify'] = apply_filters( 'https_local_ssl_verify', $args['sslverify'] ?? true );
		}
		return $args;
	}

	/**
	 * Attach outbound HTTP filters for plugin downloads.
	 */
	private static function begin_http_filters() {
		add_filter( 'http_request_timeout', [ __CLASS__, 'filter_http_timeout' ] );
		add_filter( 'http_request_args', [ __CLASS__, 'filter_http_request_args' ], 10, 2 );
	}

	/**
	 * Remove outbound HTTP filters.
	 */
	private static function end_http_filters() {
		remove_filter( 'http_request_args', [ __CLASS__, 'filter_http_request_args' ], 10 );
		remove_filter( 'http_request_timeout', [ __CLASS__, 'filter_http_timeout' ] );
	}

	/**
	 * Batch install missing auto-installable plugins.
	 *
	 * @param bool $activate_after Activate after each install.
	 * @return array<int, array<string, mixed>>
	 */
	public static function install_all_available( $activate_after = true ) {
		$scan    = NGCPM_Scanner::scan( false );
		$results = [];

		foreach ( NGCPM_Registry::sorted() as $slug => $def ) {
			$row = $scan[ $slug ] ?? [];
			if ( ! empty( $row['installed'] ) ) {
				continue;
			}
			if ( empty( $row['can_auto_install'] ) ) {
				$results[] = [
					'slug'    => $slug,
					'success' => false,
					'message' => __( 'Manual install required.', 'nextgentutors-plugin-manager' ),
					'status'  => 'MANUAL_REQUIRED',
				];
				continue;
			}
			$r = self::install( $slug );
			if ( $activate_after && ! empty( $r['success'] ) ) {
				NGCPM_Activator::activate( $slug );
			}
			$results[] = $r;
		}

		return $results;
	}
}
