<?php
/**
 * Plugin activation.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Safe plugin activation.
 */
class NGCPM_Activator {

	/**
	 * @param string $slug Registry key.
	 * @return array{success:bool,message:string,slug:string}
	 */
	public static function activate( $slug ) {
		$def = NGCPM_Registry::get( $slug );
		if ( ! $def ) {
			return [ 'success' => false, 'message' => __( 'Unknown plugin.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return [ 'success' => false, 'message' => __( 'Permission denied.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}

		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$main_file = (string) $def['main_file'];
		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $main_file ) ) {
			$main_file = NGCPM_Installer::discover_main_file( array_merge( $def, [ 'registry_key' => $slug ] ) );
		}
		if ( ! $main_file || ! file_exists( WP_PLUGIN_DIR . '/' . $main_file ) ) {
			NGCPM_Logger::log( 'activation_failure', 'Plugin file missing', [ 'slug' => $slug ] );
			return [ 'success' => false, 'message' => __( 'Plugin not installed.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
		}

		// Non-silent so the plugin's activation hooks run (e.g. FluentCRM creates its fc_* tables).
		try {
			$result = activate_plugin( $main_file, '', false, false );
		} catch ( Throwable $e ) {
			$message = sprintf(
				/* translators: %s: activation exception message */
				__( 'Plugin activation failed safely: %s', 'nextgentutors-plugin-manager' ),
				wp_strip_all_tags( $e->getMessage() )
			);
			NGCPM_Logger::log( 'activation_exception', $message, [ 'slug' => $slug ] );
			NGCPM_Scanner::clear_cache();
			return [
				'success' => false,
				'message' => $message,
				'slug'    => $slug,
				'code'    => 'activation_exception',
			];
		}

		NGCPM_Scanner::clear_cache();

		if ( is_wp_error( $result ) ) {
			$message = NGCPM_Errors::activation_message( $slug, $result );
			NGCPM_Logger::log( 'activation_failure', $message, [ 'slug' => $slug ] );
			return [ 'success' => false, 'message' => $message, 'slug' => $slug, 'code' => $result->get_error_code() ];
		}

		NGCPM_Logger::log( 'activation_success', 'Plugin activated', [ 'slug' => $slug ] );
		return [ 'success' => true, 'message' => __( 'Plugin activated.', 'nextgentutors-plugin-manager' ), 'slug' => $slug ];
	}

	/**
	 * Activate all installed but inactive registry plugins.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function activate_all_inactive() {
		$scan    = NGCPM_Scanner::scan( false );
		$results = [];

		foreach ( $scan as $slug => $row ) {
			if ( ! empty( $row['installed'] ) && empty( $row['active'] ) ) {
				$results[] = self::activate( $slug );
			}
		}

		return $results;
	}
}
