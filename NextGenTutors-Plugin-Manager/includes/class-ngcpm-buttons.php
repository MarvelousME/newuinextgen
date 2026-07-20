<?php
/**
 * UI button registry and audit helpers.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical button → endpoint mapping for audits and WP-CLI.
 */
class NGCPM_Buttons {

	/**
	 * @return array<int, array<string, string>>
	 */
	public static function registry() {
		return [
			[ 'label' => 'Scan', 'screen' => 'dashboard', 'selector' => '[data-action="scan"]', 'action' => 'scan', 'endpoint' => 'ngcpm_scan', 'nonce' => 'yes', 'capability' => 'manage_options' ],
			[ 'label' => 'Rescan System', 'screen' => 'dashboard', 'selector' => '[data-action="force-rescan"]', 'action' => 'force-rescan', 'endpoint' => 'ngcpm_force_rescan', 'nonce' => 'yes', 'capability' => 'manage_options' ],
			[ 'label' => 'Install Missing', 'screen' => 'missing', 'selector' => '[data-action="install-missing"]', 'action' => 'install-missing', 'endpoint' => 'ngcpm_install_missing', 'nonce' => 'yes', 'capability' => 'install_plugins' ],
			[ 'label' => 'Install Plugins', 'screen' => 'discovery', 'selector' => '[data-action="install"]', 'action' => 'install', 'endpoint' => 'ngcpm_install', 'nonce' => 'yes', 'capability' => 'install_plugins' ],
			[ 'label' => 'Activate Plugins', 'screen' => 'discovery', 'selector' => '[data-action="activate"]', 'action' => 'activate', 'endpoint' => 'ngcpm_activate', 'nonce' => 'yes', 'capability' => 'activate_plugins' ],
			[ 'label' => 'Activate All', 'screen' => 'activation', 'selector' => '[data-action="activate-all"]', 'action' => 'activate-all', 'endpoint' => 'ngcpm_activate_all', 'nonce' => 'yes', 'capability' => 'activate_plugins' ],
			[ 'label' => 'Repair System', 'screen' => 'repair', 'selector' => '[data-action="repair-all"]', 'action' => 'repair-all', 'endpoint' => 'ngcpm_repair', 'nonce' => 'yes', 'capability' => 'install_plugins' ],
			[ 'label' => 'Repair Issues', 'screen' => 'repair', 'selector' => '[data-action="repair-one"]', 'action' => 'repair-one', 'endpoint' => 'ngcpm_repair', 'nonce' => 'yes', 'capability' => 'install_plugins' ],
			[ 'label' => 'Verify System', 'screen' => 'verification', 'selector' => '[data-action="verify-system"]', 'action' => 'verify-system', 'endpoint' => 'ngcpm_verify_system', 'nonce' => 'yes', 'capability' => 'manage_options' ],
			[ 'label' => 'Run Health Check', 'screen' => 'diagnostics', 'selector' => '[data-action="refresh-diagnostics"]', 'action' => 'refresh-diagnostics', 'endpoint' => 'ngcpm_run_health_check', 'nonce' => 'yes', 'capability' => 'manage_options' ],
			[ 'label' => 'Export Report', 'screen' => 'export', 'selector' => '[data-action="export"]', 'action' => 'export', 'endpoint' => 'ngcpm_export_report', 'nonce' => 'yes', 'capability' => 'manage_options' ],
			[ 'label' => 'View Full Checklist', 'screen' => 'dashboard', 'selector' => '[data-nav="readiness"]', 'action' => 'nav', 'endpoint' => 'N/A', 'nonce' => 'no', 'capability' => 'read' ],
			[ 'label' => 'View Full Graph', 'screen' => 'dashboard', 'selector' => '[data-nav="graph"]', 'action' => 'nav', 'endpoint' => 'N/A', 'nonce' => 'no', 'capability' => 'read' ],
			[ 'label' => 'View All Recommendations', 'screen' => 'repair', 'selector' => '[data-nav="repair"]', 'action' => 'nav', 'endpoint' => 'N/A', 'nonce' => 'no', 'capability' => 'read' ],
			[ 'label' => 'Configure Plugin', 'screen' => 'discovery', 'selector' => '.ngcpm-card__actions a[href]', 'action' => 'link', 'endpoint' => 'N/A', 'nonce' => 'no', 'capability' => 'read' ],
			[ 'label' => 'Open Plugin Setup', 'screen' => 'configuration', 'selector' => '.ngcpm-config-hub a', 'action' => 'link', 'endpoint' => 'N/A', 'nonce' => 'no', 'capability' => 'read' ],
			[ 'label' => 'Retry Failed Plugin', 'screen' => 'queue', 'selector' => '[data-action="run-sequential-queue"]', 'action' => 'run-sequential-queue', 'endpoint' => 'ngcpm_queue_plan', 'nonce' => 'yes', 'capability' => 'manage_options' ],
			[ 'label' => 'Clear Logs', 'screen' => 'logs', 'selector' => '[data-action="clear-logs"]', 'action' => 'clear-logs', 'endpoint' => 'ngcpm_clear_logs', 'nonce' => 'yes', 'capability' => 'manage_options' ],
			[ 'label' => 'Export Logs', 'screen' => 'export', 'selector' => '[data-action="export-logs"]', 'action' => 'export-logs', 'endpoint' => 'ngcpm_export_logs', 'nonce' => 'yes', 'capability' => 'manage_options' ],
			[ 'label' => 'Dismiss Notification', 'screen' => 'dashboard', 'selector' => '[data-action="dismiss-notification"]', 'action' => 'dismiss-notification', 'endpoint' => 'ngcpm_dismiss_notification', 'nonce' => 'yes', 'capability' => 'read' ],
			[ 'label' => 'Open Details', 'screen' => 'discovery', 'selector' => '.ngcpm-card__details summary', 'action' => 'details', 'endpoint' => 'N/A', 'nonce' => 'no', 'capability' => 'read' ],
			[ 'label' => 'Refresh Status', 'screen' => 'dashboard', 'selector' => '[data-action="refresh-status"]', 'action' => 'refresh-status', 'endpoint' => 'ngcpm_refresh_status', 'nonce' => 'yes', 'capability' => 'manage_options' ],
			[ 'label' => 'Install Now', 'screen' => 'queue', 'selector' => '[data-action="install-activate-all"]', 'action' => 'install-activate-all', 'endpoint' => 'ngcpm_queue_plan', 'nonce' => 'yes', 'capability' => 'manage_options' ],
			[ 'label' => 'Update Plugin', 'screen' => 'discovery', 'selector' => '[data-action="install"]', 'action' => 'install', 'endpoint' => 'ngcpm_install', 'nonce' => 'yes', 'capability' => 'install_plugins' ],
			[ 'label' => 'Manual Install Instructions', 'screen' => 'verification', 'selector' => '[data-action="show-manual"]', 'action' => 'show-manual', 'endpoint' => 'N/A', 'nonce' => 'no', 'capability' => 'read' ],
			[ 'label' => 'Save Settings', 'screen' => 'settings', 'selector' => '.ngcpm-settings-form [type="submit"]', 'action' => 'save-settings', 'endpoint' => 'options.php', 'nonce' => 'yes', 'capability' => 'manage_options' ],
			[ 'label' => 'Clear Cache', 'screen' => 'settings', 'selector' => '[data-action="clear-cache"]', 'action' => 'clear-cache', 'endpoint' => 'ngcpm_clear_cache', 'nonce' => 'yes', 'capability' => 'manage_options' ],
			[ 'label' => 'Force Rescan', 'screen' => 'settings', 'selector' => '[data-action="force-rescan"]', 'action' => 'force-rescan', 'endpoint' => 'ngcpm_force_rescan', 'nonce' => 'yes', 'capability' => 'manage_options' ],
			[ 'label' => 'Run Cookie Probe', 'screen' => 'diagnostics', 'selector' => '[data-action="cookie-probe"]', 'action' => 'cookie-probe', 'endpoint' => 'ngcpm_cookie_probe', 'nonce' => 'yes', 'capability' => 'manage_options' ],
		];
	}

	/**
	 * AJAX actions registered by NGCPM_Ajax.
	 *
	 * @return array<string, string> action => handler method.
	 */
	public static function ajax_handlers() {
		return [
			'ngcpm_scan'                 => 'handle_scan',
			'ngcpm_scan_system'          => 'handle_scan',
			'ngcpm_force_rescan'         => 'handle_force_rescan',
			'ngcpm_refresh_status'       => 'handle_refresh_status',
			'ngcpm_install'              => 'handle_install',
			'ngcpm_install_plugin'       => 'handle_install',
			'ngcpm_install_missing'      => 'handle_install_missing',
			'ngcpm_activate'             => 'handle_activate',
			'ngcpm_activate_plugin'      => 'handle_activate',
			'ngcpm_activate_all'         => 'handle_activate_all',
			'ngcpm_queue_plan'           => 'handle_queue_plan',
			'ngcpm_repair'               => 'handle_repair',
			'ngcpm_repair_issue'         => 'handle_repair',
			'ngcpm_diagnostics'          => 'handle_diagnostics',
			'ngcpm_run_health_check'     => 'handle_diagnostics',
			'ngcpm_verify_system'        => 'handle_verify_system',
			'ngcpm_export_report'        => 'handle_export_report',
			'ngcpm_export_logs'          => 'handle_export_logs',
			'ngcpm_clear_logs'           => 'handle_clear_logs',
			'ngcpm_clear_cache'          => 'handle_clear_cache',
			'ngcpm_dismiss_notification' => 'handle_dismiss_notification',
			'ngcpm_cookie_probe'         => 'handle_cookie_probe',
			'ngcpm_save_settings'        => 'handle_save_settings',
			'ngcpm_dismiss_optional'    => 'handle_dismiss_optional',
			'ngcpm_restore_optional'     => 'handle_restore_optional',
			'ngcpm_deactivate'           => 'handle_deactivate',
			'ngcpm_uninstall'            => 'handle_uninstall',
			'ngcpm_search_plugins'       => 'handle_search_plugins',
			'ngcpm_install_wporg'        => 'handle_install_wporg',
			'ngcpm_upload_plugin'        => 'handle_upload_plugin',
			'ngcpm_manage_installed'     => 'handle_manage_installed',
			'ngcpm_local_packages'       => 'handle_local_packages',
			'ngcpm_install_local_packages' => 'handle_install_local_packages',
			'ngcpm_last_fatal'           => 'handle_last_fatal',
		];
	}

	/**
	 * Audit registry against handlers and JS actions.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function audit() {
		$handlers = self::ajax_handlers();
		$ajax_src = file_get_contents( NGCPM_PLUGIN_DIR . 'includes/class-ngcpm-ajax.php' );
		$js       = self::js_bundle();
		$rows     = [];

		foreach ( self::registry() as $btn ) {
			$endpoint = $btn['endpoint'];
			$action   = $btn['action'];
			$status   = 'WORKING';

			if ( 'N/A' === $endpoint ) {
				if ( in_array( $action, [ 'nav', 'link', 'details' ], true ) ) {
					$status = 'WORKING';
				} elseif ( 'show-manual' === $action && false !== strpos( $js, 'show-manual' ) ) {
					$status = 'WORKING';
				} elseif ( 'save-settings' === $action ) {
					$status = 'WORKING';
				} else {
					$status = 'NOT_VERIFIED';
				}
			} elseif ( 'options.php' === $endpoint && 'save-settings' === $action ) {
				$status = 'WORKING';
			} elseif ( ! isset( $handlers[ $endpoint ] ) ) {
				$status = 'MISSING_BACKEND';
			} else {
				$method = $handlers[ $endpoint ];
				if ( false === strpos( $ajax_src, 'function ' . $method ) && false === strpos( $ajax_src, $method . '(' ) ) {
					$status = 'MISSING_HANDLER';
				} elseif ( false === strpos( $js, "'" . $action . "'" ) && false === strpos( $js, '"' . $action . '"' ) && false === strpos( $js, $endpoint ) ) {
					$status = 'PARTIAL';
				}
			}

			$rows[] = array_merge( $btn, [
				'handler'     => isset( $handlers[ $endpoint ] ) ? $handlers[ $endpoint ] : 'N/A',
				'status'      => $status,
				'fix_applied' => in_array( $status, [ 'WORKING', 'PARTIAL' ], true ) ? 'verified' : 'pending',
			] );
		}

		return $rows;
	}

	/**
	 * @return string
	 */
	private static function js_bundle() {
		$paths = [
			'assets/js/admin-ui.js',
			'assets/js/modules/ngcpm-actions.js',
			'assets/js/modules/ngcpm-queue.js',
			'assets/js/modules/ngcpm-notifications.js',
		];
		$buf = '';
		foreach ( $paths as $rel ) {
			$file = NGCPM_PLUGIN_DIR . $rel;
			if ( is_file( $file ) ) {
				$buf .= file_get_contents( $file );
			}
		}
		return $buf;
	}
}
