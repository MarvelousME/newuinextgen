<?php
/**
 * Plugin bootstrap loader and install integrity checks.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads required plugin files and reports incomplete installs safely.
 */
class NGC_Loader {

	/**
	 * Files that must exist before the plugin can boot.
	 *
	 * @var string[]
	 */
	private static $required_files = [
		'includes/adapters/interface-ngc-integration-adapter.php',
		'includes/class-ngc-plugin.php',
	];

	/**
	 * Boot plugin core files.
	 *
	 * @return bool
	 */
	public static function boot() {
		foreach ( self::$required_files as $relative ) {
			$absolute = NGC_PLUGIN_DIR . $relative;
			if ( ! file_exists( $absolute ) ) {
				self::report_missing_install( $absolute );
				return false;
			}
		}

		require_once NGC_PLUGIN_DIR . 'includes/adapters/interface-ngc-integration-adapter.php';
		require_once NGC_PLUGIN_DIR . 'includes/class-ngc-plugin.php';

		return true;
	}

	/**
	 * @param string $missing_path Absolute missing file path.
	 */
	private static function report_missing_install( $missing_path ) {
		error_log( '[NextGenCompanion] Missing required file: ' . $missing_path ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

		add_action(
			'admin_notices',
			static function () use ( $missing_path ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'NextGen Companion: incomplete install.', 'nextgencompanion' ) . '</strong> ';
				echo esc_html__( 'Install NextGenTutors-Companion to wp-content/plugins/NextGenTutors-Companion/.', 'nextgencompanion' );
				echo '<br><code style="word-break:break-all">' . esc_html( $missing_path ) . '</code></p></div>';
			}
		);
	}
}
