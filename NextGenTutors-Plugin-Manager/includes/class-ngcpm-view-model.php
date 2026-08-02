<?php
/**
 * Shared template variables for admin and shortcode views.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the data bag passed into templates/app.php.
 */
class NGCPM_View_Model {

	/**
	 * @param bool $readonly Read-only UI (no write actions).
	 * @param int  $log_limit Audit log entries for dashboard.
	 * @return array<string, mixed>
	 */
	public static function for_app( $readonly = false, $log_limit = 20 ) {
		NGCPM_Local_Packages::ensure_directories();
		$local_packages = NGCPM_Local_Packages::public_status();
		$scan = NGCPM_Scanner::scan( true );
		$health = NGCPM_Health::calculate( $scan );

		return [
			'scan'            => $scan,
			'health'          => $health,
			'ngt_stack'       => NGCPM_NGT_Stack::summary(),
			'steps'           => NGCPM_Health::setup_steps( $scan, $health ),
			'logs'            => $readonly ? [] : NGCPM_Logger::recent( $log_limit ),
			'readonly'        => $readonly,
			'diagnostics'     => [],
			'repair'          => NGCPM_Repair::detect_issues( $scan ),
			'queue_plan'      => NGCPM_Queue::build_plan( $scan ),
			'graph'           => NGCPM_UI::dependency_graph( $scan ),
			'inactive'        => NGCPM_UI::inactive_plugins( $scan ),
			'config_hub'      => NGCPM_UI::configuration_hub( $scan ),
			'exceptions'      => NGCPM_UI::exception_logs( 30 ),
			'env_label'       => NGCPM_UI::environment_label(),
			'notifications'   => $readonly ? [] : NGCPM_Notifications::get_visible(),
			'local_packages'  => $local_packages,
		];
	}

	/**
	 * Extract variables for template include.
	 *
	 * @param array<string, mixed> $vars View model.
	 */
	public static function render( $vars ) {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $vars, EXTR_SKIP );
		include NGCPM_PLUGIN_DIR . 'templates/app.php';
	}
}
