<?php
/**
 * Blocks co-activation of obsolete content-enhancement NextGen plugins.
 *
 * Those packages register conflicting `ngt/v1` REST routes and `ngt_*` tables
 * that collide with Companion (`ngc_*` + legacy `ngt/v1` alias).
 *
 * @package NextGenCompanion
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deny-list guard for legacy NextGen platform plugins.
 */
class NGC_Legacy_Plugin_Guard {

	/**
	 * Plugin basenames / relative paths that must not run beside Companion.
	 *
	 * @return array<int, string>
	 */
	public static function denied_basenames(): array {
		$denied = array(
			'nextgen-tutors-core/nextgen-tutors-core.php',
			'nextgen-tutors/nextgen-tutors.php',
			'nextgen-tutors-plugin/nextgen-tutors.php',
			'nextgen-tutors-importer/nextgen-tutors-importer.php',
			'nextgen-tutors-importer.php',
		);

		/**
		 * Filter denied legacy plugin basenames.
		 *
		 * @param array<int, string> $denied Relative plugin paths.
		 */
		return apply_filters( 'ngc_denied_legacy_plugins', $denied );
	}

	/**
	 * Folder prefixes — any plugin file under these directories is denied.
	 *
	 * @return array<int, string>
	 */
	public static function denied_folder_prefixes(): array {
		return array(
			'nextgen-tutors-core',
			'nextgen-tutors-plugin',
			'nextgen-tutors-importer',
		);
	}

	/**
	 * Text Domain headers that identify forbidden packages.
	 *
	 * @return array<int, string>
	 */
	public static function denied_text_domains(): array {
		return array(
			'nextgen-tutors',
			'nextgen-tutors-core',
			'nextgen-tutors-importer',
		);
	}

	/**
	 * Plugin Name headers (exact match only).
	 *
	 * @return array<int, string>
	 */
	public static function denied_plugin_names(): array {
		return array(
			'NextGen Tutors Core',
			'NextGen Tutors Importer',
		);
	}

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		add_filter( 'pre_update_option_active_plugins', array( __CLASS__, 'strip_denied_from_active' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'deactivate_if_active' ), 1 );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
		add_filter( 'plugin_action_links', array( __CLASS__, 'filter_action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( __CLASS__, 'filter_action_links' ), 10, 2 );
	}

	/**
	 * Whether a plugin file is explicitly allowed (Companion stack).
	 *
	 * @param string $plugin Relative plugin file.
	 */
	private static function is_companion_stack( string $plugin ): bool {
		$plugin = strtolower( $plugin );
		if ( false !== strpos( $plugin, 'nextgentutors-companion/' ) ) {
			return true;
		}
		if ( false !== strpos( $plugin, 'nextgencompanion.php' ) ) {
			return true;
		}
		if ( false !== strpos( $plugin, 'nextgentutors-plugin-manager/' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Whether a plugin file is denied.
	 *
	 * @param string $plugin Relative plugin file (e.g. folder/plugin.php).
	 */
	public static function is_denied( string $plugin ): bool {
		$plugin = str_replace( '\\', '/', ltrim( $plugin, '/' ) );
		if ( '' === $plugin || self::is_companion_stack( $plugin ) ) {
			return false;
		}

		foreach ( self::denied_basenames() as $denied ) {
			$denied = str_replace( '\\', '/', ltrim( $denied, '/' ) );
			if ( 0 === strcasecmp( $plugin, $denied ) ) {
				return true;
			}
		}

		foreach ( self::denied_folder_prefixes() as $folder ) {
			$folder = trim( $folder, '/' );
			if ( '' !== $folder && 0 === stripos( $plugin, $folder . '/' ) ) {
				return true;
			}
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			return false;
		}

		$path = WP_PLUGIN_DIR . '/' . $plugin;
		if ( ! is_readable( $path ) ) {
			return false;
		}

		$data = get_plugin_data( $path, false, false );
		$name = isset( $data['Name'] ) ? (string) $data['Name'] : '';
		$domain = isset( $data['TextDomain'] ) ? (string) $data['TextDomain'] : '';

		foreach ( self::denied_text_domains() as $blocked_domain ) {
			if ( '' !== $domain && 0 === strcasecmp( $domain, $blocked_domain ) ) {
				return true;
			}
		}

		foreach ( self::denied_plugin_names() as $blocked_name ) {
			if ( '' !== $name && 0 === strcasecmp( $name, $blocked_name ) ) {
				return true;
			}
		}

		// Legacy standalone "NextGen Tutors" plugin (not Companion).
		if ( '' !== $name && 0 === strcasecmp( $name, 'NextGen Tutors' ) ) {
			if ( 0 !== strcasecmp( $domain, 'nextgencompanion' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Structured log when plugins are blocked.
	 *
	 * @param array<int, string> $removed Plugin basenames.
	 * @param bool               $forced  Whether force-deactivated.
	 */
	private static function log_blocked( array $removed, bool $forced = false ): void {
		if ( ! $removed ) {
			return;
		}
		if ( class_exists( 'NGC_System_Log' ) ) {
			NGC_System_Log::write(
				'warning',
				'legacy_plugin_guard',
				'security',
				'Blocked legacy NextGen plugin activation',
				array(
					'plugins' => $removed,
					'forced'  => $forced,
				)
			);
			return;
		}
		if ( function_exists( 'error_log' ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[NextGenCompanion] Blocked legacy plugin activation: ' . implode( ', ', $removed ) );
		}
	}

	/**
	 * Remove denied plugins before they are persisted as active.
	 *
	 * @param mixed $plugins New active list.
	 * @param mixed $old     Previous list.
	 * @return mixed
	 */
	public static function strip_denied_from_active( $plugins, $old ) {
		unset( $old );
		if ( ! is_array( $plugins ) ) {
			return $plugins;
		}
		$clean   = array();
		$removed = array();
		foreach ( $plugins as $plugin ) {
			$plugin = (string) $plugin;
			if ( self::is_denied( $plugin ) ) {
				$removed[] = $plugin;
				continue;
			}
			$clean[] = $plugin;
		}
		if ( $removed ) {
			set_transient(
				'ngc_legacy_plugin_blocked',
				array(
					'plugins' => $removed,
					'time'    => time(),
				),
				HOUR_IN_SECONDS
			);
			self::log_blocked( $removed );
		}
		return $clean;
	}

	/**
	 * Force-deactivate if somehow still active.
	 */
	public static function deactivate_if_active(): void {
		if ( ! function_exists( 'is_plugin_active' ) || ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$active  = (array) get_option( 'active_plugins', array() );
		$to_stop = array();
		foreach ( $active as $plugin ) {
			if ( self::is_denied( (string) $plugin ) ) {
				$to_stop[] = $plugin;
			}
		}
		if ( ! $to_stop ) {
			return;
		}
		deactivate_plugins( $to_stop, true );
		set_transient(
			'ngc_legacy_plugin_blocked',
			array(
				'plugins' => $to_stop,
				'time'    => time(),
				'forced'  => true,
			),
			HOUR_IN_SECONDS
		);
		self::log_blocked( $to_stop, true );
	}

	/**
	 * Remove Activate action for denied plugins.
	 *
	 * @param array<string, string> $actions Actions.
	 * @param string                $plugin  Plugin file.
	 * @return array<string, string>
	 */
	public static function filter_action_links( $actions, $plugin ): array {
		$actions = is_array( $actions ) ? $actions : array();
		if ( self::is_denied( (string) $plugin ) ) {
			unset( $actions['activate'] );
			$actions['ngc_blocked'] = '<span style="color:#b32d2e;">' . esc_html__( 'Blocked — conflicts with Companion', 'nextgencompanion' ) . '</span>';
		}
		return $actions;
	}

	/**
	 * Admin notice after a block event.
	 */
	public static function admin_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$payload = get_transient( 'ngc_legacy_plugin_blocked' );
		if ( ! is_array( $payload ) || empty( $payload['plugins'] ) ) {
			return;
		}
		delete_transient( 'ngc_legacy_plugin_blocked' );
		$list = array_map( 'esc_html', (array) $payload['plugins'] );
		$html = sprintf(
			/* translators: %s: comma-separated plugin paths */
			esc_html__( 'NextGen Companion blocked a legacy content-enhancement plugin that conflicts with ngt/v1 and ngc_* data.', 'nextgencompanion' )
			. ' <code>%s</code> '
			. esc_html__( 'See content-enhancement/README.md and audit-reports/content-enhancement/.', 'nextgencompanion' ),
			implode( '</code>, <code>', $list )
		);
		echo wp_kses_post( '<div class="notice notice-error"><p>' . $html . '</p></div>' );
	}
}
