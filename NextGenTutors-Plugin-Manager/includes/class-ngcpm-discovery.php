<?php
/**
 * WordPress.org search and zip upload installs (admin-style).
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discover and install plugins outside the fixed registry.
 */
class NGCPM_Discovery {

	/**
	 * Search wordpress.org plugin directory.
	 *
	 * @param string $term Search term.
	 * @param int    $page Page number.
	 * @return array{success:bool,message:string,results:array<int,array<string,mixed>>}
	 */
	public static function search_wporg( $term, $page = 1 ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return [ 'success' => false, 'message' => __( 'Permission denied.', 'nextgentutors-plugin-manager' ), 'results' => [] ];
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$response = plugins_api(
			'query_plugins',
			[
				'per_page' => 12,
				'page'     => max( 1, (int) $page ),
				'search'   => sanitize_text_field( $term ),
				'fields'   => [
					'short_description' => true,
					'icons'             => true,
					'active_installs'   => true,
					'last_updated'      => true,
					'rating'            => true,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => NGCPM_Errors::clean( $response->get_error_message() ),
				'results' => [],
			];
		}

		$results = [];
		foreach ( (array) ( $response->plugins ?? [] ) as $plugin ) {
			$slug = sanitize_key( (string) ( $plugin->slug ?? '' ) );
			if ( ! $slug ) {
				continue;
			}
			$icon = '';
			if ( ! empty( $plugin->icons['2x'] ) ) {
				$icon = (string) $plugin->icons['2x'];
			} elseif ( ! empty( $plugin->icons['1x'] ) ) {
				$icon = (string) $plugin->icons['1x'];
			}
			$plugin_file = self::resolve_installed_file( $slug );
			$results[]   = [
				'slug'        => $slug,
				'name'        => (string) ( $plugin->name ?? $slug ),
				'description' => wp_trim_words( NGCPM_Errors::clean( (string) ( $plugin->short_description ?? '' ) ), 24 ),
				'version'     => (string) ( $plugin->version ?? '' ),
				'installed'   => (bool) $plugin_file || file_exists( WP_PLUGIN_DIR . '/' . $slug ),
				'active'      => $plugin_file ? is_plugin_active( $plugin_file ) : self::slug_is_active( $slug ),
				'plugin_file' => $plugin_file,
				'icon'        => esc_url_raw( $icon ),
				'rating'      => (int) ( $plugin->rating ?? 0 ),
				'installs'    => (int) ( $plugin->active_installs ?? 0 ),
			];
		}

		return [
			'success' => true,
			'message' => sprintf(
				/* translators: %d: result count */
				__( '%d plugin(s) found on WordPress.org.', 'nextgentutors-plugin-manager' ),
				count( $results )
			),
			'results' => $results,
		];
	}

	/**
	 * @param string $slug Plugin directory slug.
	 * @return string Relative main file or empty.
	 */
	public static function resolve_installed_file( $slug ) {
		$slug = sanitize_key( $slug );
		if ( ! $slug ) {
			return '';
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$dir = wp_normalize_path( WP_PLUGIN_DIR . '/' . $slug );
		if ( is_dir( $dir ) ) {
			$found = NGCPM_Installer::find_plugin_file_in_dir( $dir );
			if ( $found ) {
				return $found;
			}
		}
		return NGCPM_Installer::discover_main_file( [ 'slug' => $slug, 'registry_key' => $slug ] );
	}

	/**
	 * @param string $slug Plugin slug.
	 * @return bool
	 */
	private static function slug_is_active( $slug ) {
		$file = self::resolve_installed_file( $slug );
		if ( ! $file ) {
			return false;
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( $file );
	}

	/**
	 * Install any wordpress.org plugin by slug.
	 *
	 * @param string $wporg_slug Plugin directory slug.
	 * @param bool   $activate   Activate after install when permitted.
	 * @return array{success:bool,message:string,slug:string}
	 */
	public static function install_wporg_slug( $wporg_slug, $activate = true ) {
		$wporg_slug = sanitize_key( $wporg_slug );
		if ( ! $wporg_slug ) {
			return [ 'success' => false, 'message' => __( 'Invalid plugin slug.', 'nextgentutors-plugin-manager' ), 'slug' => '' ];
		}
		if ( ! current_user_can( 'install_plugins' ) ) {
			return [ 'success' => false, 'message' => __( 'Permission denied.', 'nextgentutors-plugin-manager' ), 'slug' => $wporg_slug ];
		}

		$existing = self::resolve_installed_file( $wporg_slug );
		if ( $existing ) {
			$result = [
				'success'     => true,
				'message'     => __( 'Already installed.', 'nextgentutors-plugin-manager' ),
				'slug'        => $wporg_slug,
				'plugin_file' => $existing,
			];
		} else {
			NGCPM_Errors::begin_guard( 'install', $wporg_slug );
			$url    = NGCPM_Installer::wporg_zip_url( $wporg_slug );
			$result = NGCPM_Installer::install_from_download_url_public( $wporg_slug, $url );
			if ( ! empty( $result['success'] ) ) {
				$result['message'] = sprintf(
					/* translators: %s: plugin slug */
					__( 'Installed %s from WordPress.org.', 'nextgentutors-plugin-manager' ),
					$wporg_slug
				);
				$result['plugin_file'] = self::resolve_installed_file( $wporg_slug );
			}
		}

		if ( ! empty( $result['success'] ) && $activate && current_user_can( 'activate_plugins' ) ) {
			$activated = self::activate_plugin_file( $result['plugin_file'] ?? self::resolve_installed_file( $wporg_slug ) );
			if ( ! empty( $activated['success'] ) ) {
				$result['message'] .= ' ' . __( 'Activated.', 'nextgentutors-plugin-manager' );
				$result['active']   = true;
			}
		}

		return $result;
	}

	/**
	 * Handle uploaded plugin zip (same flow as Plugins → Add New → Upload).
	 *
	 * @param array<string, mixed> $file     $_FILES row.
	 * @param bool                 $activate Activate after install when permitted.
	 * @return array{success:bool,message:string,slug:string}
	 */
	public static function install_uploaded_zip( $file, $activate = true ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return [ 'success' => false, 'message' => __( 'Permission denied.', 'nextgentutors-plugin-manager' ), 'slug' => '' ];
		}

		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return [ 'success' => false, 'message' => __( 'No zip file received.', 'nextgentutors-plugin-manager' ), 'slug' => '' ];
		}

		$name = (string) ( $file['name'] ?? '' );
		if ( ! preg_match( '/\.zip$/i', $name ) ) {
			return [ 'success' => false, 'message' => __( 'Only .zip plugin archives are allowed.', 'nextgentutors-plugin-manager' ), 'slug' => '' ];
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$overrides = [
			'test_form' => false,
			'mimes'     => [ 'zip' => 'application/zip' ],
		];
		$uploaded  = wp_handle_upload( $file, $overrides );
		if ( isset( $uploaded['error'] ) ) {
			return [ 'success' => false, 'message' => NGCPM_Errors::clean( $uploaded['error'] ), 'slug' => '' ];
		}

		$slug_guess = sanitize_file_name( basename( $name, '.zip' ) );
		$slug_guess = sanitize_key( preg_replace( '/\.\d.*$/', '', $slug_guess ) ?: $slug_guess );
		NGCPM_Errors::begin_guard( 'install', $slug_guess );

		$dest_dir = NGCPM_Settings::local_zip_dir();
		if ( ! is_dir( $dest_dir ) ) {
			wp_mkdir_p( $dest_dir );
		}
		$archive_copy = trailingslashit( $dest_dir ) . sanitize_file_name( basename( $name ) );
		if ( ! @copy( $uploaded['file'], $archive_copy ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			NGCPM_Logger::log( 'upload_cache_miss', 'Could not cache uploaded zip', [ 'dest' => $archive_copy ] );
		}

		$result = NGCPM_Installer::install_from_local_package( $slug_guess, $uploaded['file'] );
		@unlink( $uploaded['file'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( ! empty( $result['success'] ) ) {
			$plugin_file = (string) ( $result['plugin_file'] ?? '' );
			if ( ! $plugin_file ) {
				$plugin_file = self::resolve_installed_file( $slug_guess );
			}
			$result['plugin_file'] = $plugin_file;
			$result['message']     = sprintf(
				/* translators: %s: zip filename */
				__( 'Uploaded and installed %s.', 'nextgentutors-plugin-manager' ),
				basename( $name )
			);

			if ( $activate && $plugin_file && current_user_can( 'activate_plugins' ) ) {
				$activated = self::activate_plugin_file( $plugin_file );
				if ( ! empty( $activated['success'] ) ) {
					$result['message'] .= ' ' . __( 'Activated.', 'nextgentutors-plugin-manager' );
					$result['active']   = true;
				}
			}
		}

		return $result;
	}

	/**
	 * Activate / deactivate / delete an installed plugin by relative main file.
	 *
	 * @param string $op          activate|deactivate|delete.
	 * @param string $plugin_file Relative plugin file.
	 * @return array{success:bool,message:string,slug:string}
	 */
	public static function manage_installed( $op, $plugin_file ) {
		$plugin_file = self::sanitize_plugin_file( $plugin_file );
		if ( ! $plugin_file ) {
			return [ 'success' => false, 'message' => __( 'Invalid plugin file.', 'nextgentutors-plugin-manager' ), 'slug' => '' ];
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all = get_plugins();
		if ( ! isset( $all[ $plugin_file ] ) ) {
			return [ 'success' => false, 'message' => __( 'Plugin is not installed.', 'nextgentutors-plugin-manager' ), 'slug' => '' ];
		}

		$name = (string) ( $all[ $plugin_file ]['Name'] ?? $plugin_file );
		$slug = dirname( $plugin_file );
		if ( '.' === $slug ) {
			$slug = basename( $plugin_file, '.php' );
		}

		switch ( $op ) {
			case 'activate':
				return self::activate_plugin_file( $plugin_file, $name, $slug );
			case 'deactivate':
				if ( ! current_user_can( 'deactivate_plugins' ) ) {
					return [ 'success' => false, 'message' => __( 'Permission denied.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
				}
				deactivate_plugins( $plugin_file, false, is_network_admin() );
				NGCPM_Scanner::clear_cache();
				return [
					'success' => true,
					'message' => sprintf(
						/* translators: %s: plugin name */
						__( '%s deactivated.', 'nextgentutors-plugin-manager' ),
						$name
					),
					'slug'    => $slug,
				];
			case 'delete':
				if ( ! current_user_can( 'delete_plugins' ) ) {
					return [ 'success' => false, 'message' => __( 'Permission denied.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
				}
				if ( ! function_exists( 'delete_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				deactivate_plugins( $plugin_file, false, is_network_admin() );
				$deleted = delete_plugins( [ $plugin_file ] );
				if ( is_wp_error( $deleted ) ) {
					return [ 'success' => false, 'message' => NGCPM_Errors::clean( $deleted->get_error_message() ), 'slug' => $slug ];
				}
				NGCPM_Scanner::clear_cache();
				return [
					'success' => true,
					'message' => sprintf(
						/* translators: %s: plugin name */
						__( '%s deleted.', 'nextgentutors-plugin-manager' ),
						$name
					),
					'slug'    => $slug,
				];
			default:
				return [ 'success' => false, 'message' => __( 'Unknown operation.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}
	}

	/**
	 * @param string $plugin_file Relative plugin file.
	 * @param string $name        Display name.
	 * @param string $slug        Slug label.
	 * @return array{success:bool,message:string,slug:string}
	 */
	public static function activate_plugin_file( $plugin_file, $name = '', $slug = '' ) {
		$plugin_file = self::sanitize_plugin_file( $plugin_file );
		if ( ! $plugin_file ) {
			return [ 'success' => false, 'message' => __( 'Plugin file missing.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return [ 'success' => false, 'message' => __( 'Permission denied.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}
		// Block obsolete content-enhancement packages (ngt/v1 + ngt_* collide with Companion).
		if ( class_exists( 'NGC_Legacy_Plugin_Guard' ) && NGC_Legacy_Plugin_Guard::is_denied( $plugin_file ) ) {
			return [
				'success' => false,
				'message' => __( 'Blocked: legacy NextGen plugin conflicts with Companion (see content-enhancement audit).', 'nextgentutors-plugin-manager' ),
				'slug'    => $slug,
			];
		}
		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$result = activate_plugin( $plugin_file, '', false, true );
		NGCPM_Scanner::clear_cache();
		if ( is_wp_error( $result ) ) {
			return [ 'success' => false, 'message' => NGCPM_Errors::clean( $result->get_error_message() ), 'slug' => $slug ];
		}
		return [
			'success' => true,
			'message' => sprintf(
				/* translators: %s: plugin name */
				__( '%s activated.', 'nextgentutors-plugin-manager' ),
				$name ?: $plugin_file
			),
			'slug'    => $slug,
		];
	}

	/**
	 * @param string $plugin_file Relative path.
	 * @return string
	 */
	private static function sanitize_plugin_file( $plugin_file ) {
		$plugin_file = str_replace( '\\', '/', (string) $plugin_file );
		$plugin_file = ltrim( $plugin_file, '/' );
		if ( ! $plugin_file || false !== strpos( $plugin_file, '..' ) ) {
			return '';
		}
		if ( ! preg_match( '/\.php$/i', $plugin_file ) ) {
			return '';
		}
		return $plugin_file;
	}
}
