<?php
/**
 * AJAX handlers.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Secure AJAX endpoints.
 */
class NGCPM_Ajax {

	/**
	 * Hook registration.
	 */
	public static function init() {
		foreach ( NGCPM_Buttons::ajax_handlers() as $action => $method ) {
			if ( method_exists( __CLASS__, $method ) ) {
				add_action( 'wp_ajax_' . $action, [ __CLASS__, $method ] );
			}
		}

		// Deprecated batch aliases — still registered, return structured error.
		foreach ( [ 'ngcpm_install_all', 'ngcpm_install_activate_all' ] as $legacy ) {
			add_action( 'wp_ajax_' . $legacy, [ __CLASS__, 'handle_install_all' ] );
		}
	}

	/**
	 * Verify nonce and capability.
	 *
	 * @param string $cap Capability.
	 */
	private static function verify( $cap = 'manage_options' ) {
		check_ajax_referer( 'ngcpm_ajax', 'nonce' );
		if ( ! current_user_can( $cap ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'nextgentutors-plugin-manager' ) ], 403 );
		}
	}

	/**
	 * Validate registry slug from POST.
	 *
	 * @return string
	 */
	private static function post_registry_slug() {
		$slug = sanitize_key( wp_unslash( $_POST['slug'] ?? '' ) );
		if ( ! $slug || ! NGCPM_Registry::get( $slug ) ) {
			wp_send_json_error( [ 'message' => __( 'Unknown or invalid plugin.', 'nextgentutors-plugin-manager' ) ] );
		}
		return $slug;
	}

	/**
	 * Reject legacy atomic batch endpoints — use sequential queue instead.
	 */
	private static function reject_legacy_batch() {
		NGCPM_Logger::log( 'batch_deprecated', 'Legacy batch endpoint blocked', [] );
		wp_send_json_error(
			[
				'message'  => __( 'Atomic batch is disabled. Use the sequential install queue.', 'nextgentutors-plugin-manager' ),
				'code'     => 'use_sequential_queue',
				'plan_url' => 'ngcpm_queue_plan',
			],
			410
		);
	}

	/**
	 * Send single action result — error when operation failed.
	 *
	 * @param array<string, mixed> $result  Operation result.
	 * @param array<string, mixed> $extra   Extra response keys.
	 */
	private static function send_action_result( $result, $extra = [] ) {
		$scan    = NGCPM_Scanner::scan( true );
		$payload = array_merge(
			[
				'result' => $result,
				'health' => NGCPM_Health::calculate( $scan ),
				'scan'   => self::public_scan( $scan ),
			],
			$extra
		);

		if ( empty( $result['success'] ) ) {
			$message = NGCPM_Errors::clean( $result['message'] ?? __( 'Action failed.', 'nextgentutors-plugin-manager' ) );
			wp_send_json_error(
				array_merge(
					$payload,
					[
						'message' => $message,
						'code'    => $result['code'] ?? '',
						'fatal'   => NGCPM_Errors::last_fatal(),
					]
				)
			);
		}

		wp_send_json_success( $payload );
	}

	/**
	 * Standard scan response payload.
	 *
	 * @param bool $clear_cache Clear cache before scan.
	 * @return array<string, mixed>
	 */
	private static function scan_payload( $clear_cache = false ) {
		if ( $clear_cache ) {
			NGCPM_Scanner::clear_cache();
		}
		$scan = NGCPM_Scanner::scan( false );
		update_option( 'ngcpm_last_scan_time', time(), false );
		$health = NGCPM_Health::calculate( $scan );
		return [
			'scan'            => self::public_scan( $scan ),
			'health'          => $health,
			'steps'           => NGCPM_Health::setup_steps( $scan, $health ),
			'verification'    => NGCPM_UI::verification_rows( $scan ),
			'notifications'   => NGCPM_Notifications::get_visible(),
			'local_packages'  => NGCPM_Local_Packages::public_status(),
		];
	}

	/**
	 * Scan plugins.
	 */
	public static function handle_scan() {
		self::verify();
		try {
			NGCPM_Logger::log( 'scan', 'System scan completed', [] );
			wp_send_json_success( self::scan_payload( true ) );
		} catch ( Throwable $e ) {
			NGCPM_Logger::log( 'scan_failure', $e->getMessage(), [] );
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	/**
	 * Alias: force rescan clears cache then scans.
	 */
	public static function handle_force_rescan() {
		self::handle_scan();
	}

	/**
	 * Refresh status without clearing cache.
	 */
	public static function handle_refresh_status() {
		self::verify();
		try {
			wp_send_json_success( self::scan_payload( false ) );
		} catch ( Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	/**
	 * Install single plugin.
	 */
	public static function handle_install() {
		self::verify( 'install_plugins' );
		NGCPM_Rate_Limiter::enforce( 'install' );
		$slug      = self::post_registry_slug();
		$overwrite = ! empty( $_POST['overwrite'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		try {
			NGCPM_Errors::begin_guard( 'install', $slug );
			$result = NGCPM_Installer::install( $slug, [ 'overwrite' => $overwrite ] );
			self::send_action_result( $result );
		} catch ( Throwable $e ) {
			NGCPM_Logger::log( 'install_failure', $e->getMessage(), [ 'slug' => $slug ] );
			wp_send_json_error(
				[
					'message' => $e->getMessage(),
					'code'    => 'install_exception',
				],
				500
			);
		}
	}

	/**
	 * Install all auto-installable missing plugins.
	 */
	public static function handle_install_missing() {
		self::verify( 'install_plugins' );
		NGCPM_Rate_Limiter::enforce( 'install' );
		try {
			$results = NGCPM_Installer::install_missing();
			$scan    = NGCPM_Scanner::scan( true );
			wp_send_json_success(
				[
					'results' => $results,
					'health'  => NGCPM_Health::calculate( $scan ),
					'scan'    => self::public_scan( $scan ),
					'message' => __( 'Install missing completed.', 'nextgentutors-plugin-manager' ),
				]
			);
		} catch ( Throwable $e ) {
			NGCPM_Logger::log( 'install_missing_failure', $e->getMessage(), [] );
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	/**
	 * Activate single plugin.
	 */
	public static function handle_activate() {
		self::verify( 'activate_plugins' );
		NGCPM_Rate_Limiter::enforce( 'activate' );
		$slug   = self::post_registry_slug();
		NGCPM_Errors::begin_guard( 'activate', $slug );
		$result = NGCPM_Activator::activate( $slug );
		self::send_action_result( $result );
	}

	/**
	 * Activate all installed inactive plugins (real batch).
	 */
	public static function handle_activate_all() {
		self::verify( 'activate_plugins' );
		NGCPM_Rate_Limiter::enforce( 'activate' );
		try {
			$results = NGCPM_Activator::activate_all_inactive();
			$scan    = NGCPM_Scanner::scan( true );
			$failed  = array_filter(
				$results,
				static function ( $row ) {
					return empty( $row['success'] );
				}
			);
			NGCPM_Logger::log(
				'activate_all',
				sprintf( 'Activated %d plugin(s), %d failed', count( $results ) - count( $failed ), count( $failed ) ),
				[]
			);
			if ( $failed ) {
				wp_send_json_error(
					[
						'results' => $results,
						'health'  => NGCPM_Health::calculate( $scan ),
						'scan'    => self::public_scan( $scan ),
						'message' => __( 'Some plugins failed to activate.', 'nextgentutors-plugin-manager' ),
					]
				);
			}
			wp_send_json_success(
				[
					'results' => $results,
					'health'  => NGCPM_Health::calculate( $scan ),
					'scan'    => self::public_scan( $scan ),
					'message' => __( 'All inactive plugins activated.', 'nextgentutors-plugin-manager' ),
				]
			);
		} catch ( Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	/**
	 * Install all available free plugins (deprecated).
	 */
	public static function handle_install_all() {
		self::verify( 'install_plugins' );
		self::reject_legacy_batch();
	}

	/**
	 * Return ordered queue plan for sequential client processing.
	 */
	public static function handle_queue_plan() {
		self::verify( 'manage_options' );
		$scan = NGCPM_Scanner::scan( true );
		wp_send_json_success( [
			'plan'   => NGCPM_Queue::build_plan( $scan ),
			'health' => NGCPM_Health::calculate( $scan ),
			'scan'   => self::public_scan( $scan ),
		] );
	}

	/**
	 * Execute a single repair strategy.
	 */
	public static function handle_repair() {
		$strategy = sanitize_key( wp_unslash( $_POST['strategy'] ?? '' ) );
		if ( ! in_array( $strategy, [ 'install', 'activate' ], true ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid repair strategy.', 'nextgentutors-plugin-manager' ) ] );
		}
		if ( 'activate' === $strategy ) {
			self::verify( 'activate_plugins' );
		} else {
			self::verify( 'install_plugins' );
		}
		NGCPM_Rate_Limiter::enforce( 'repair' );
		$slug   = self::post_registry_slug();
		$result = NGCPM_Repair::execute( $slug, $strategy );
		self::send_action_result( $result );
	}

	/**
	 * Run system diagnostics probes.
	 */
	public static function handle_diagnostics() {
		self::verify();
		try {
			$checks = array_merge( NGCPM_Diagnostics::run_all(), NGCPM_Cookies::run_checks() );
			NGCPM_Logger::log( 'health_check', 'Diagnostics run', [ 'count' => count( $checks ) ] );
			wp_send_json_success( [ 'checks' => $checks ] );
		} catch ( Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	/**
	 * Verify system — scan + verification matrix.
	 */
	public static function handle_verify_system() {
		self::verify();
		try {
			$payload = self::scan_payload( true );
			$payload['checks'] = NGCPM_Diagnostics::run_all();
			NGCPM_Logger::log( 'verify_system', 'Verification run', [] );
			wp_send_json_success( $payload );
		} catch ( Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	/**
	 * Export JSON report.
	 */
	public static function handle_export_report() {
		self::verify();
		try {
			$report = NGCPM_Health::export_report();
			NGCPM_Logger::log( 'export_report', 'Report exported', [] );
			wp_send_json_success( $report );
		} catch ( Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	/**
	 * Export logs as JSON via AJAX.
	 */
	public static function handle_export_logs() {
		self::verify();
		try {
			wp_send_json_success(
				[
					'exported_at' => gmdate( 'c' ),
					'logs'        => NGCPM_Logger::recent( NGCPM_LOG_LIMIT ),
				]
			);
		} catch ( Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	/**
	 * Clear scan cache.
	 */
	public static function handle_clear_cache() {
		self::verify();
		NGCPM_Scanner::clear_cache();
		NGCPM_Logger::log( 'clear_cache', 'Scan cache cleared', [] );
		wp_send_json_success( [
			'message' => __( 'Cache cleared.', 'nextgentutors-plugin-manager' ),
			'health'  => NGCPM_Health::calculate( NGCPM_Scanner::scan( true ) ),
		] );
	}

	/**
	 * Clear logs.
	 */
	public static function handle_clear_logs() {
		self::verify();
		NGCPM_Logger::clear();
		NGCPM_Logger::log( 'clear_logs', 'Audit logs cleared', [] );
		wp_send_json_success( [ 'message' => __( 'Logs cleared.', 'nextgentutors-plugin-manager' ) ] );
	}

	/**
	 * Dismiss admin notification.
	 */
	public static function handle_dismiss_notification() {
		check_ajax_referer( 'ngcpm_ajax', 'nonce' );
		$id    = sanitize_key( wp_unslash( $_POST['id'] ?? '' ) );
		$hash  = sanitize_text_field( wp_unslash( $_POST['hash'] ?? '' ) );
		$scope = sanitize_key( wp_unslash( $_POST['scope'] ?? 'user' ) );

		$cap = 'global' === $scope ? 'manage_options' : 'read';
		if ( ! current_user_can( $cap ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'nextgentutors-plugin-manager' ) ], 403 );
		}

		try {
			$result = NGCPM_Notifications::dismiss( $id, $hash, $scope );
			if ( empty( $result['success'] ) ) {
				wp_send_json_error( [ 'message' => $result['message'] ] );
			}
			wp_send_json_success( [ 'message' => $result['message'] ] );
		} catch ( Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	/**
	 * Cookie probe — step init|verify.
	 */
	public static function handle_cookie_probe() {
		self::verify();
		$step = sanitize_key( wp_unslash( $_POST['step'] ?? 'init' ) );
		try {
			if ( 'verify' === $step ) {
				$token = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
				$browser = ! empty( $_POST['browser_confirmed'] );
				$result = NGCPM_Cookies::probe_verify( $token, $browser );
				if ( empty( $result['success'] ) ) {
					wp_send_json_error( $result );
				}
				wp_send_json_success( $result );
			}
			$result = NGCPM_Cookies::probe_init();
			if ( empty( $result['success'] ) ) {
				wp_send_json_error( $result );
			}
			wp_send_json_success( $result );
		} catch ( Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	/**
	 * Settings are saved via Settings API; AJAX returns guidance.
	 */
	public static function handle_save_settings() {
		self::verify();
		wp_send_json_error(
			[
				'message' => __( 'Use the Settings page form to save options.', 'nextgentutors-plugin-manager' ),
				'code'    => 'use_settings_form',
				'url'     => admin_url( 'admin.php?page=' . NGCPM_ADMIN_PAGE . '-settings' ),
			],
			400
		);
	}

	/**
	 * Dismiss optional registry plugin from queue.
	 */
	public static function handle_dismiss_optional() {
		self::verify( 'install_plugins' );
		$slug   = self::post_registry_slug();
		$result = NGCPM_Lifecycle::dismiss_optional( $slug );
		self::send_action_result( $result );
	}

	/**
	 * Restore dismissed optional plugin.
	 */
	public static function handle_restore_optional() {
		self::verify( 'install_plugins' );
		$slug   = self::post_registry_slug();
		$result = NGCPM_Lifecycle::restore_optional( $slug );
		self::send_action_result( $result );
	}

	/**
	 * Deactivate registry plugin.
	 */
	public static function handle_deactivate() {
		self::verify( 'deactivate_plugins' );
		$slug   = self::post_registry_slug();
		$result = NGCPM_Lifecycle::deactivate( $slug );
		self::send_action_result( $result );
	}

	/**
	 * Uninstall optional registry plugin.
	 */
	public static function handle_uninstall() {
		self::verify( 'delete_plugins' );
		$slug = self::post_registry_slug();
		if ( empty( $_POST['confirm'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error(
				[
					'message' => __( 'Confirmation required to uninstall.', 'nextgentutors-plugin-manager' ),
					'code'    => 'confirm_required',
				]
			);
		}
		$result = NGCPM_Lifecycle::uninstall( $slug );
		self::send_action_result( $result );
	}

	/**
	 * Search wordpress.org plugins.
	 */
	public static function handle_search_plugins() {
		self::verify( 'install_plugins' );
		$term = sanitize_text_field( wp_unslash( $_POST['term'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$page = max( 1, (int) ( $_POST['page'] ?? 1 ) );
		if ( strlen( $term ) < 2 ) {
			wp_send_json_error( [ 'message' => __( 'Enter at least 2 characters to search.', 'nextgentutors-plugin-manager' ) ] );
		}
		$result = NGCPM_Discovery::search_wporg( $term, $page );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( $result );
		}
		wp_send_json_success( $result );
	}

	/**
	 * Install plugin by WordPress.org slug.
	 */
	public static function handle_install_wporg() {
		self::verify( 'install_plugins' );
		NGCPM_Rate_Limiter::enforce( 'install' );
		$wporg_slug = sanitize_key( wp_unslash( $_POST['wporg_slug'] ?? '' ) );
		if ( ! $wporg_slug ) {
			wp_send_json_error( [ 'message' => __( 'Invalid plugin slug.', 'nextgentutors-plugin-manager' ) ] );
		}
		$activate = ! isset( $_POST['activate'] ) || '0' !== (string) wp_unslash( $_POST['activate'] ?? '1' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		NGCPM_Errors::begin_guard( 'install', $wporg_slug );
		$result = NGCPM_Discovery::install_wporg_slug( $wporg_slug, $activate );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( array_merge( $result, [ 'fatal' => NGCPM_Errors::last_fatal() ] ) );
		}
		NGCPM_Scanner::clear_cache();
		wp_send_json_success( [ 'result' => $result, 'message' => $result['message'] ] );
	}

	/**
	 * Upload and install plugin zip.
	 */
	public static function handle_upload_plugin() {
		self::verify( 'install_plugins' );
		NGCPM_Rate_Limiter::enforce( 'install' );
		if ( empty( $_FILES['plugin_zip'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No file uploaded.', 'nextgentutors-plugin-manager' ) ] );
		}
		$activate = ! isset( $_POST['activate'] ) || '0' !== (string) wp_unslash( $_POST['activate'] ?? '1' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		NGCPM_Errors::begin_guard( 'install', 'upload' );
		$result = NGCPM_Discovery::install_uploaded_zip( $_FILES['plugin_zip'], $activate ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( array_merge( $result, [ 'fatal' => NGCPM_Errors::last_fatal() ] ) );
		}
		NGCPM_Scanner::clear_cache();
		wp_send_json_success( [ 'result' => $result, 'message' => $result['message'] ] );
	}

	/**
	 * Activate / deactivate / delete an installed (non-registry) plugin.
	 */
	public static function handle_manage_installed() {
		$op = sanitize_key( wp_unslash( $_POST['op'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$cap = 'activate_plugins';
		if ( 'deactivate' === $op ) {
			$cap = 'deactivate_plugins';
		} elseif ( 'delete' === $op ) {
			$cap = 'delete_plugins';
		}
		self::verify( $cap );
		if ( 'delete' === $op && empty( $_POST['confirm'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error(
				[
					'message' => __( 'Confirmation required to delete.', 'nextgentutors-plugin-manager' ),
					'code'    => 'confirm_required',
				]
			);
		}
		$plugin_file = wp_unslash( $_POST['plugin_file'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$result      = NGCPM_Discovery::manage_installed( $op, $plugin_file );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( $result );
		}
		wp_send_json_success( $result );
	}

	/**
	 * Local zip directory inventory.
	 */
	public static function handle_local_packages() {
		self::verify();
		wp_send_json_success( NGCPM_Local_Packages::public_status() );
	}

	/**
	 * Install all registry plugins that have matching local zips.
	 */
	public static function handle_install_local_packages() {
		self::verify( 'install_plugins' );
		NGCPM_Rate_Limiter::enforce( 'install' );
		try {
			$results = NGCPM_Local_Packages::install_pending( true );
			$scan    = NGCPM_Scanner::scan( true );
			wp_send_json_success(
				[
					'results'         => $results,
					'health'          => NGCPM_Health::calculate( $scan ),
					'scan'            => self::public_scan( $scan ),
					'local_packages'  => NGCPM_Local_Packages::public_status(),
					'message'         => __( 'Local package install completed.', 'nextgentutors-plugin-manager' ),
				]
			);
		} catch ( Throwable $e ) {
			NGCPM_Logger::log( 'local_install_failure', $e->getMessage(), [] );
			wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
		}
	}

	/**
	 * Return last recorded fatal error for diagnostics UI.
	 */
	public static function handle_last_fatal() {
		self::verify();
		wp_send_json_success( [ 'fatal' => NGCPM_Errors::last_fatal() ] );
	}

	/**
	 * Strip internal paths from scan for JSON responses.
	 *
	 * @param array<string, array<string, mixed>> $scan Scan.
	 * @return array<string, array<string, mixed>>
	 */
	private static function public_scan( $scan ) {
		$public = [];
		foreach ( $scan as $slug => $row ) {
			unset( $row['package_path'] );
			$public[ $slug ] = $row;
		}
		return $public;
	}
}
