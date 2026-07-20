<?php
/**
 * Deactivate, uninstall, and dismiss optional registry plugins.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin lifecycle beyond install/activate.
 */
class NGCPM_Lifecycle {

	const OPTION_SKIPPED = 'ngcpm_skipped_optional';

	/**
	 * @return string[]
	 */
	public static function skipped_slugs() {
		$list = get_option( self::OPTION_SKIPPED, [] );
		return is_array( $list ) ? array_values( array_filter( array_map( 'sanitize_key', $list ) ) ) : [];
	}

	/**
	 * @param string $slug Registry key.
	 * @return bool
	 */
	public static function is_skipped( $slug ) {
		return in_array( sanitize_key( $slug ), self::skipped_slugs(), true );
	}

	/**
	 * Dismiss an optional plugin from readiness queue (does not remove files).
	 *
	 * @param string $slug Registry key.
	 * @return array{success:bool,message:string,slug:string}
	 */
	public static function dismiss_optional( $slug ) {
		$def = NGCPM_Registry::get( $slug );
		if ( ! $def ) {
			return [ 'success' => false, 'message' => __( 'Unknown plugin.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}
		if ( ! empty( $def['required'] ) ) {
			return [ 'success' => false, 'message' => __( 'Required plugins cannot be dismissed.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}

		$list = self::skipped_slugs();
		if ( ! in_array( $slug, $list, true ) ) {
			$list[] = $slug;
			update_option( self::OPTION_SKIPPED, $list, false );
		}

		NGCPM_Scanner::clear_cache();
		NGCPM_Logger::log( 'optional_dismissed', 'Optional plugin dismissed from queue', [ 'slug' => $slug ] );

		return [
			'success' => true,
			'message' => sprintf(
				/* translators: %s: plugin name */
				__( '%s marked as not needed. You can restore it anytime or install a different plugin from Add Plugin.', 'nextgentutors-plugin-manager' ),
				(string) ( $def['name'] ?? $slug )
			),
			'slug'    => $slug,
		];
	}

	/**
	 * Restore dismissed optional plugin to the readiness list.
	 *
	 * @param string $slug Registry key.
	 * @return array{success:bool,message:string,slug:string}
	 */
	public static function restore_optional( $slug ) {
		$list = array_values( array_diff( self::skipped_slugs(), [ sanitize_key( $slug ) ] ) );
		update_option( self::OPTION_SKIPPED, $list, false );
		NGCPM_Scanner::clear_cache();
		return [
			'success' => true,
			'message' => __( 'Plugin restored to the discovery list.', 'nextgentutors-plugin-manager' ),
			'slug'    => $slug,
		];
	}

	/**
	 * Resolve main plugin file for registry row.
	 *
	 * @param array<string, mixed> $def Registry row.
	 * @return string
	 */
	public static function main_file( $def ) {
		$main = (string) ( $def['main_file'] ?? '' );
		if ( $main && file_exists( WP_PLUGIN_DIR . '/' . $main ) ) {
			return $main;
		}
		$key = (string) ( $def['registry_key'] ?? $def['slug'] ?? '' );
		return NGCPM_Installer::discover_main_file( array_merge( $def, [ 'registry_key' => $key ] ) );
	}

	/**
	 * Deactivate registry plugin.
	 *
	 * @param string $slug Registry key.
	 * @return array{success:bool,message:string,slug:string}
	 */
	public static function deactivate( $slug ) {
		$def = NGCPM_Registry::get( $slug );
		if ( ! $def ) {
			return [ 'success' => false, 'message' => __( 'Unknown plugin.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}
		if ( ! current_user_can( 'deactivate_plugins' ) ) {
			return [ 'success' => false, 'message' => __( 'Permission denied.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}

		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$main = self::main_file( array_merge( $def, [ 'registry_key' => $slug ] ) );
		if ( ! $main ) {
			return [ 'success' => false, 'message' => __( 'Plugin is not installed.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}

		NGCPM_Errors::begin_guard( 'deactivate', $slug );
		deactivate_plugins( $main, false, is_network_admin() );
		NGCPM_Scanner::clear_cache();

		NGCPM_Logger::log( 'deactivated', 'Plugin deactivated', [ 'slug' => $slug, 'file' => $main ] );
		return [
			'success' => true,
			'message' => sprintf(
				/* translators: %s: plugin name */
				__( '%s deactivated.', 'nextgentutors-plugin-manager' ),
				(string) ( $def['name'] ?? $slug )
			),
			'slug'    => $slug,
		];
	}

	/**
	 * Deactivate and delete plugin files.
	 *
	 * @param string $slug Registry key.
	 * @return array{success:bool,message:string,slug:string}
	 */
	public static function uninstall( $slug ) {
		$def = NGCPM_Registry::get( $slug );
		if ( ! $def ) {
			return [ 'success' => false, 'message' => __( 'Unknown plugin.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}
		if ( ! empty( $def['required'] ) ) {
			return [ 'success' => false, 'message' => __( 'Required plugins cannot be uninstalled from here.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}
		if ( ! current_user_can( 'delete_plugins' ) ) {
			return [ 'success' => false, 'message' => __( 'Permission denied.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}

		if ( ! function_exists( 'delete_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$main = self::main_file( array_merge( $def, [ 'registry_key' => $slug ] ) );
		if ( ! $main ) {
			self::dismiss_optional( $slug );
			return [
				'success' => true,
				'message' => __( 'Plugin was not installed — removed from your optional list.', 'nextgentutors-plugin-manager' ),
				'slug'    => $slug,
			];
		}

		NGCPM_Errors::begin_guard( 'uninstall', $slug );
		deactivate_plugins( $main, false, is_network_admin() );

		$deleted = delete_plugins( [ $main ] );
		if ( is_wp_error( $deleted ) ) {
			return [ 'success' => false, 'message' => NGCPM_Errors::clean( $deleted->get_error_message() ), 'slug' => $slug ];
		}

		self::dismiss_optional( $slug );
		NGCPM_Scanner::clear_cache();
		NGCPM_Logger::log( 'uninstalled', 'Plugin uninstalled', [ 'slug' => $slug, 'file' => $main ] );

		return [
			'success' => true,
			'message' => sprintf(
				/* translators: %s: plugin name */
				__( '%s uninstalled and removed from optional recommendations.', 'nextgentutors-plugin-manager' ),
				(string) ( $def['name'] ?? $slug )
			),
			'slug'    => $slug,
		];
	}

	/**
	 * Dismiss + uninstall optional plugin in one step.
	 *
	 * @param string $slug Registry key.
	 * @return array{success:bool,message:string,slug:string}
	 */
	public static function dismiss_and_remove( $slug ) {
		$def = NGCPM_Registry::get( $slug );
		if ( ! $def || ! empty( $def['required'] ) ) {
			return self::dismiss_optional( $slug );
		}

		$main = self::main_file( array_merge( $def, [ 'registry_key' => $slug ] ) );
		if ( $main && file_exists( WP_PLUGIN_DIR . '/' . $main ) ) {
			return self::uninstall( $slug );
		}

		return self::dismiss_optional( $slug );
	}
}
