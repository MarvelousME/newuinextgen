<?php
/**
 * Amelia plugin bootstrap — tables, default service, API/direct mode for Docker.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Idempotent Amelia configuration for local stacks and tutor sync.
 */
class NGC_Amelia_Bootstrap {

	public const DIRECT_MODE_KEY = 'ngc-internal-direct';

	/**
	 * Nested trusted-sync depth (workflow / CLI / local stack).
	 *
	 * @var int
	 */
	private static $trusted_sync_depth = 0;

	/**
	 * @return bool
	 */
	public static function is_active() {
		return defined( 'AMELIA_VERSION' ) || class_exists( '\AmeliaBooking\Plugin' );
	}

	/**
	 * Docker / demo stacks that may auto-seed integration data.
	 *
	 * @return bool
	 */
	public static function is_local_stack() {
		if ( defined( 'NGC_ALLOW_DEMO_SEED' ) && NGC_ALLOW_DEMO_SEED ) {
			return true;
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}
		$env = getenv( 'NGC_ALLOW_DEMO_SEED' );
		if ( false !== $env && null !== $env && in_array( strtolower( trim( (string) $env ) ), [ '1', 'true', 'yes', 'on' ], true ) ) {
			return true;
		}
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		return in_array( $host, [ 'localhost', '127.0.0.1' ], true )
			|| ( is_string( $host ) && str_ends_with( $host, '.local' ) );
	}

	/**
	 * Whether elevated Amelia writes (command bus / DB fallback) are permitted.
	 *
	 * @return bool
	 */
	public static function allows_elevated_sync() {
		if ( self::is_local_stack() ) {
			return true;
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}
		if ( self::$trusted_sync_depth > 0 ) {
			return true;
		}
		if ( current_user_can( 'manage_options' ) || current_user_can( 'ngc_review_tutors' ) ) {
			return true;
		}
		return (bool) apply_filters( 'ngc_amelia_allows_elevated_sync', false );
	}

	/**
	 * Mark trusted workflow execution (orchestrator).
	 */
	public static function begin_trusted_sync() {
		++self::$trusted_sync_depth;
	}

	/**
	 * End trusted workflow execution scope.
	 */
	public static function end_trusted_sync() {
		self::$trusted_sync_depth = max( 0, self::$trusted_sync_depth - 1 );
	}

	/**
	 * Whether schema seeding (default service/category) is allowed.
	 *
	 * @return bool
	 */
	public static function allows_schema_seed() {
		return self::is_local_stack()
			|| ( defined( 'WP_CLI' ) && WP_CLI )
			|| current_user_can( 'manage_options' );
	}

	/**
	 * Full bootstrap: tables, service, API key / direct mode.
	 *
	 * @param bool $force Re-seed service when option missing.
	 * @return array<string, mixed>
	 */
	public static function bootstrap( $force = false ) {
		if ( ! self::is_active() ) {
			return [ 'ok' => false, 'reason' => 'amelia_inactive' ];
		}

		$tables  = self::ensure_tables();
		$service = self::ensure_default_service( $force );
		$api     = self::ensure_api_key();

		return [
			'ok'         => $tables['ok'] && $service > 0,
			'tables'     => $tables,
			'service_id' => $service,
			'api'        => $api,
			'direct'     => self::uses_direct_mode(),
		];
	}

	/**
	 * @return bool
	 */
	public static function uses_direct_mode() {
		$key = (string) get_option( 'ngc_amelia_api_key', '' );
		return self::DIRECT_MODE_KEY === $key || (bool) get_option( 'ngc_amelia_direct_mode', false );
	}

	/**
	 * Install Amelia files, create DB tables, then activate (avoids fatal when tables missing).
	 *
	 * @return array<string, mixed>
	 */
	public static function safe_install_and_activate() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$main       = 'ameliabooking/ameliabooking.php';
		$plugin_dir = WP_PLUGIN_DIR . '/ameliabooking';
		$disabled   = WP_PLUGIN_DIR . '/ameliabooking.disabled';

		if ( ! is_dir( $plugin_dir ) && is_dir( $disabled ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
			rename( $disabled, $plugin_dir );
		}

		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $main ) ) {
			return [ 'ok' => false, 'reason' => 'not_installed' ];
		}

		// Broken state: active but schema missing — deactivate before bootstrap.
		if ( is_plugin_active( $main ) && ! self::table_exists( 'amelia_customer_bookings' ) ) {
			deactivate_plugins( $main, true );
		}

		$tables = self::ensure_tables( true );
		if ( empty( $tables['ok'] ) ) {
			return [
				'ok'     => false,
				'reason' => 'tables_failed',
				'tables' => $tables,
			];
		}

		if ( ! is_plugin_active( $main ) ) {
			$activated = activate_plugin( $main, '', false, true );
			if ( is_wp_error( $activated ) ) {
				return [
					'ok'      => false,
					'reason'  => 'activate_failed',
					'message' => $activated->get_error_message(),
				];
			}
		}

		$boot = self::bootstrap( true );
		$boot['activated'] = true;
		$boot['tables']    = $tables;

		if ( class_exists( 'NGC_System_Log' ) ) {
			NGC_System_Log::info(
				'amelia',
				'integration',
				'Amelia safe install and activate completed',
				$boot
			);
		}

		return $boot;
	}

	/**
	 * @param bool $load_plugin Load Amelia main file when inactive (for schema creation only).
	 * @return array<string, mixed>
	 */
	public static function ensure_tables( $load_plugin = false ) {
		if ( ! self::is_active() && $load_plugin ) {
			$main = WP_PLUGIN_DIR . '/ameliabooking/ameliabooking.php';
			if ( file_exists( $main ) ) {
				include_once $main;
			}
		}

		if ( ! self::is_active() && ! $load_plugin ) {
			return [ 'ok' => false, 'reason' => 'amelia_inactive' ];
		}

		if ( self::table_exists( 'amelia_users' ) && self::table_exists( 'amelia_customer_bookings' ) ) {
			return [ 'ok' => true, 'status' => 'ready' ];
		}

		if ( class_exists( '\AmeliaBooking\Infrastructure\WP\InstallActions\ActivationDatabaseHook' ) ) {
			\AmeliaBooking\Infrastructure\WP\InstallActions\ActivationDatabaseHook::init();
		}

		if ( class_exists( '\AmeliaBooking\Infrastructure\WP\InstallActions\ActivationSettingsHook' ) ) {
			\AmeliaBooking\Infrastructure\WP\InstallActions\ActivationSettingsHook::init();
		}

		if ( class_exists( '\AmeliaBooking\Infrastructure\WP\InstallActions\ActivationRolesHook' ) ) {
			\AmeliaBooking\Infrastructure\WP\InstallActions\ActivationRolesHook::init();
		}

		$ready = self::table_exists( 'amelia_users' ) && self::table_exists( 'amelia_customer_bookings' );

		return [
			'ok'     => $ready,
			'status' => $ready ? 'created' : 'missing',
		];
	}

	/**
	 * @param bool $force Force re-resolve default service ID.
	 * @return int
	 */
	public static function ensure_default_service( $force = false ) {
		global $wpdb;

		if ( ! self::table_exists( 'amelia_services' ) ) {
			return 0;
		}

		$option_id = (int) get_option( 'ngc_amelia_default_service_id', 0 );
		if ( ! $force && $option_id > 0 && self::service_exists( $option_id ) ) {
			return $option_id;
		}

		$services_table = $wpdb->prefix . 'amelia_services';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = (int) $wpdb->get_var( "SELECT id FROM {$services_table} WHERE status = 'visible' ORDER BY id ASC LIMIT 1" );
		if ( $existing > 0 ) {
			update_option( 'ngc_amelia_default_service_id', $existing, false );
			return $existing;
		}

		$category_id = self::ensure_default_category();
		if ( $category_id <= 0 ) {
			return 0;
		}

		if ( ! self::allows_schema_seed() ) {
			return 0;
		}

		$inserted = $wpdb->insert(
			$services_table,
			[
				'name'         => '1-on-1 Tutoring Session',
				'description'  => 'Private tutoring session booked through NextGen Tutors.',
				'color'        => '#1788FB',
				'price'        => 350,
				'status'       => 'visible',
				'categoryId'   => $category_id,
				'minCapacity'  => 1,
				'maxCapacity'  => 1,
				'duration'     => 60,
				'timeBefore'   => 0,
				'timeAfter'    => 0,
				'priority'     => 'least_occupied',
				'position'     => 1,
				'show'         => 1,
				'aggregatedPrice' => 1,
			],
			[ '%s', '%s', '%s', '%f', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%d', '%d' ]
		);

		if ( ! $inserted ) {
			return 0;
		}

		$service_id = (int) $wpdb->insert_id;
		update_option( 'ngc_amelia_default_service_id', $service_id, false );

		return $service_id;
	}

	/**
	 * @return int
	 */
	private static function ensure_default_category() {
		global $wpdb;

		$table = $wpdb->prefix . 'amelia_categories';
		if ( ! self::table_exists( 'amelia_categories' ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = (int) $wpdb->get_var( "SELECT id FROM {$table} ORDER BY id ASC LIMIT 1" );
		if ( $existing > 0 ) {
			return $existing;
		}

		if ( ! self::allows_schema_seed() ) {
			return 0;
		}

		$wpdb->insert(
			$table,
			[
				'status'   => 'visible',
				'name'     => 'Tutoring',
				'position' => 1,
				'color'    => '#1788FB',
			],
			[ '%s', '%s', '%d', '%s' ]
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Sync or create API key option used by NGC adapters.
	 *
	 * @return array<string, mixed>
	 */
	public static function ensure_api_key() {
		$current = (string) get_option( 'ngc_amelia_api_key', '' );
		if ( $current && self::DIRECT_MODE_KEY !== $current ) {
			return [ 'ok' => true, 'status' => 'api_key_present' ];
		}

		$discovered = self::discover_plain_api_key();
		if ( $discovered ) {
			update_option( 'ngc_amelia_api_key', $discovered, false );
			delete_option( 'ngc_amelia_direct_mode' );
			return [ 'ok' => true, 'status' => 'api_key_synced' ];
		}

		if ( ! self::is_local_stack() ) {
			return [ 'ok' => false, 'status' => 'api_key_missing' ];
		}

		update_option( 'ngc_amelia_api_key', self::DIRECT_MODE_KEY, false );
		update_option( 'ngc_amelia_direct_mode', '1', false );

		return [ 'ok' => true, 'status' => 'direct_mode' ];
	}

	/**
	 * @return string
	 */
	public static function discover_plain_api_key() {
		$settings = get_option( 'amelia_settings' );
		if ( is_string( $settings ) ) {
			$settings = json_decode( $settings, true );
		}

		if ( is_array( $settings ) && ! empty( $settings['apiKey'] ) && is_string( $settings['apiKey'] ) ) {
			return sanitize_text_field( $settings['apiKey'] );
		}

		$legacy = get_option( 'amelia_api_key' );
		if ( is_string( $legacy ) && $legacy ) {
			return sanitize_text_field( $legacy );
		}

		return '';
	}

	/**
	 * @param string $suffix Table suffix without prefix (e.g. amelia_users).
	 * @return bool
	 */
	public static function table_exists( $suffix ) {
		global $wpdb;
		$table = $wpdb->prefix . $suffix;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (bool) $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
	}

	/**
	 * @param int $service_id Service ID.
	 * @return bool
	 */
	public static function service_exists( $service_id ) {
		global $wpdb;
		$service_id = (int) $service_id;
		if ( $service_id <= 0 || ! self::table_exists( 'amelia_services' ) ) {
			return false;
		}
		$table = $wpdb->prefix . 'amelia_services';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d", $service_id ) );
	}

	/**
	 * @return int
	 */
	public static function resolve_admin_user_id() {
		$admins = get_users(
			[
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			]
		);
		return ! empty( $admins[0] ) ? (int) $admins[0] : 0;
	}

	/**
	 * Default Mon–Fri business hours for new providers.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function default_week_days() {
		$days = [];
		for ( $i = 1; $i <= 5; $i++ ) {
			$days[] = [
				'dayIndex'  => $i,
				'startTime' => '09:00:00',
				'endTime'   => '17:00:00',
			];
		}
		return $days;
	}

	/**
	 * @param string $suffix Amelia table suffix (e.g. amelia_users).
	 * @return string
	 */
	public static function table_name( $suffix ) {
		global $wpdb;
		$prefix = ( isset( $wpdb->prefix ) ) ? $wpdb->prefix : 'wp_';
		return $prefix . preg_replace( '/[^a-z0-9_]/', '', (string) $suffix );
	}

	/**
	 * @param int $employee_id Amelia provider ID.
	 * @return bool
	 */
	public static function provider_exists( $employee_id ) {
		global $wpdb;
		$employee_id = (int) $employee_id;
		if ( $employee_id <= 0 || ! self::table_exists( 'amelia_users' ) ) {
			return false;
		}
		$table = self::table_name( 'amelia_users' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE id = %d AND type = 'provider' LIMIT 1",
				$employee_id
			)
		);
	}
}
