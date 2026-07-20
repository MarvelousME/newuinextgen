<?php
/**
 * Shared asset localization.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue UI assets and NGCPM JS config.
 */
class NGCPM_Assets {

	/**
	 * Admin UI script modules (load order).
	 *
	 * @return array<string, string> Handle => relative path under assets/js/.
	 */
	private static function script_modules() {
		return [
			'ngcpm-core'         => 'modules/ngcpm-core.js',
			'ngcpm-ui-feedback'  => 'modules/ngcpm-ui-feedback.js',
			'ngcpm-modal'        => 'modules/ngcpm-modal.js',
			'ngcpm-navigation'   => 'modules/ngcpm-navigation.js',
			'ngcpm-tour'         => 'modules/ngcpm-tour.js',
			'ngcpm-queue'        => 'modules/ngcpm-queue.js',
			'ngcpm-repair'       => 'modules/ngcpm-repair.js',
			'ngcpm-diagnostics'  => 'modules/ngcpm-diagnostics.js',
			'ngcpm-notifications'=> 'modules/ngcpm-notifications.js',
			'ngcpm-command'      => 'modules/ngcpm-command.js',
			'ngcpm-actions'      => 'modules/ngcpm-actions.js',
			'ngcpm-interactions' => 'modules/ngcpm-interactions.js',
			'ngcpm-admin-ui'     => 'admin-ui.js',
		];
	}

	/**
	 * Register styles and scripts.
	 */
	public static function enqueue() {
		wp_enqueue_style(
			'ngcpm-admin-ui',
			NGCPM_PLUGIN_URL . 'assets/css/admin-ui.css',
			[],
			NGCPM_VERSION
		);

		wp_enqueue_style(
			'ngcpm-beyond-infinity-admin-ui',
			NGCPM_PLUGIN_URL . 'assets/css/nextgen-beyond-infinity-admin.css',
			[ 'ngcpm-admin-ui' ],
			NGCPM_VERSION
		);

		wp_enqueue_script(
			'ngcpm-beyond-infinity-admin-js',
			NGCPM_PLUGIN_URL . 'assets/js/nextgen-beyond-infinity-admin.js',
			[],
			NGCPM_VERSION,
			true
		);

		if ( defined( 'NGC_PLUGIN_URL' ) && defined( 'NGC_VERSION' ) ) {
			wp_enqueue_style( 'ngc-button-processing', NGC_PLUGIN_URL . 'assets/css/ngc-button-processing.css', [], NGC_VERSION );
			wp_enqueue_script( 'ngc-button-processing', NGC_PLUGIN_URL . 'assets/js/ngc-button-processing.js', [], NGC_VERSION, true );
		}

		$modules = self::script_modules();
		$deps    = wp_script_is( 'ngc-button-processing', 'registered' ) || wp_script_is( 'ngc-button-processing', 'enqueued' )
			? [ 'ngc-button-processing' ]
			: [];
		foreach ( $modules as $handle => $relative ) {
			wp_enqueue_script(
				$handle,
				NGCPM_PLUGIN_URL . 'assets/js/' . $relative,
				$deps,
				NGCPM_VERSION,
				true
			);
			$deps = [ $handle ];
		}

		$first_handle = array_key_first( $modules );
		if ( $first_handle ) {
			wp_localize_script( $first_handle, 'NGCPM', self::js_config() );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function js_config() {
		$manual = [];
		foreach ( NGCPM_Registry::get_all() as $slug => $row ) {
			if ( ! empty( $row['notes'] ) ) {
				$manual[ $slug ] = (string) $row['notes'];
			}
		}

		$companion_url = '';
		if ( class_exists( 'NGC_Plugin' ) || file_exists( WP_PLUGIN_DIR . '/NextGenTutors-Companion/nextgencompanion.php' ) ) {
			$companion_url = admin_url( 'admin.php?page=ngc-health' );
		}

		return [
			'ajax'        => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'ngcpm_ajax' ),
			'canInstall'  => current_user_can( 'install_plugins' ),
			'canActivate' => current_user_can( 'activate_plugins' ),
			'settingsUrl' => admin_url( 'admin.php?page=' . NGCPM_ADMIN_PAGE . '-settings' ),
			'companionUrl'=> $companion_url,
			'localPackages' => NGCPM_Local_Packages::public_status(),
			'manualNotes' => $manual,
			'plugins'     => array_values(
				array_map(
					static function ( $slug, $row ) {
						return [
							'slug'   => $slug,
							'name'   => $row['name'] ?? $slug,
							'status' => $row['health_status'] ?? '',
						];
					},
					array_keys( NGCPM_Registry::get_all() ),
					NGCPM_Registry::get_all()
				)
			),
			'i18n'        => [
				'scanning'     => __( 'Scanning…', 'nextgentutors-plugin-manager' ),
				'installing'   => __( 'Installing…', 'nextgentutors-plugin-manager' ),
				'activating'   => __( 'Activating…', 'nextgentutors-plugin-manager' ),
				'done'         => __( 'Complete', 'nextgentutors-plugin-manager' ),
				'error'        => __( 'Action failed', 'nextgentutors-plugin-manager' ),
				'confirmBatch' => __( 'Install and activate all available plugins?', 'nextgentutors-plugin-manager' ),
				'queueDone'    => __( 'Installation finished', 'nextgentutors-plugin-manager' ),
				'rateLimited'  => __( 'Too many requests. Please wait.', 'nextgentutors-plugin-manager' ),
				'repairing'    => __( 'Repairing…', 'nextgentutors-plugin-manager' ),
				'sequential'   => __( 'Processing queue…', 'nextgentutors-plugin-manager' ),
				'manualTitle'  => __( 'Manual install required', 'nextgentutors-plugin-manager' ),
				'diagnosticsLoading' => __( 'Running diagnostics…', 'nextgentutors-plugin-manager' ),
				'networkError' => __( 'Could not reach the server. If you see a critical error page, open wp-content/debug.log or run Site Recovery below.', 'nextgentutors-plugin-manager' ),
				'emptyResponse' => __( 'Empty server response — PHP may have crashed during the request.', 'nextgentutors-plugin-manager' ),
				'fatalHint' => __( 'WordPress fatal error during this request. Check wp-content/debug.log or Plugin Manager → Exception Logs.', 'nextgentutors-plugin-manager' ),
				'invalidResponse' => __( 'Unexpected server response', 'nextgentutors-plugin-manager' ),
				'confirmUninstall' => __( 'Uninstall this optional plugin and delete its files?', 'nextgentutors-plugin-manager' ),
				'searching' => __( 'Searching WordPress.org…', 'nextgentutors-plugin-manager' ),
				'noResults' => __( 'No plugins found.', 'nextgentutors-plugin-manager' ),
				'installFromOrg' => __( 'Install', 'nextgentutors-plugin-manager' ),
				'pickZip' => __( 'Choose a .zip file first.', 'nextgentutors-plugin-manager' ),
				'installingLocal' => __( 'Installing from local packages…', 'nextgentutors-plugin-manager' ),
				'localInstallDone' => __( 'Local package install finished.', 'nextgentutors-plugin-manager' ),
				'pathCopied' => __( 'Directory path copied.', 'nextgentutors-plugin-manager' ),
				'optionalInstalled' => __( 'Installed — activate manually when ready.', 'nextgentutors-plugin-manager' ),
				'notificationDismissed' => __( 'Notification dismissed.', 'nextgentutors-plugin-manager' ),
				'cookieProbe'    => __( 'Running cookie probe…', 'nextgentutors-plugin-manager' ),
				'cookieProbeVerify' => __( 'Verifying cookie round-trip…', 'nextgentutors-plugin-manager' ),
				'confirmClearLogs' => __( 'Clear all audit logs?', 'nextgentutors-plugin-manager' ),
				'folderExistsTitle'   => __( 'Folder already exists', 'nextgentutors-plugin-manager' ),
				'folderExistsMessage' => __( 'The folder "{folder}" already exists for {plugin}. Overwrite it and reinstall?', 'nextgentutors-plugin-manager' ),
				'folderOverwrite'     => __( 'Overwrite & install', 'nextgentutors-plugin-manager' ),
				'confirmCancel'       => __( 'Cancel', 'nextgentutors-plugin-manager' ),
				'installCancelled'    => __( 'Install cancelled.', 'nextgentutors-plugin-manager' ),
				'tourNext'            => __( 'Next', 'nextgentutors-plugin-manager' ),
				'tourFinish'          => __( 'Finish tour', 'nextgentutors-plugin-manager' ),
				'tourDone'            => __( 'Setup tour complete — you can reopen it from the help button.', 'nextgentutors-plugin-manager' ),
			],
		];
	}
}
