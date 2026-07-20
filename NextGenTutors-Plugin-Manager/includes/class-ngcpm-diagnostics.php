<?php
/**
 * System diagnostics probes.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Real environment and integration checks.
 */
class NGCPM_Diagnostics {

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function run_all() {
		$checks = [
			self::check_php_version(),
			self::check_wp_version(),
			self::check_memory(),
			self::check_database(),
			self::check_wporg_api(),
			self::check_rest_api(),
			self::check_cron(),
			self::check_packages_dir(),
			self::check_plugin_registry(),
		];

		return apply_filters( 'ngcpm_diagnostics_checks', $checks );
	}

	/**
	 * @param string $name    Check name.
	 * @param string $status  PASS|FAIL|WARNING|INFO.
	 * @param string $evidence Evidence.
	 * @param string $recommendation Recommendation.
	 * @return array<string, mixed>
	 */
	private static function row( $name, $status, $evidence, $recommendation = '' ) {
		return [
			'name'           => $name,
			'status'         => $status,
			'evidence'       => $evidence,
			'recommendation' => $recommendation,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function check_php_version() {
		$ok = version_compare( PHP_VERSION, '7.4', '>=' );
		return self::row(
			__( 'PHP version', 'nextgentutors-plugin-manager' ),
			$ok ? 'PASS' : 'FAIL',
			PHP_VERSION,
			$ok ? '' : __( 'Upgrade PHP to 7.4 or higher.', 'nextgentutors-plugin-manager' )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function check_wp_version() {
		global $wp_version;
		$ok = version_compare( (string) $wp_version, '6.0', '>=' );
		return self::row(
			__( 'WordPress version', 'nextgentutors-plugin-manager' ),
			$ok ? 'PASS' : 'WARNING',
			(string) $wp_version,
			$ok ? '' : __( 'Upgrade WordPress to 6.0+.', 'nextgentutors-plugin-manager' )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function check_memory() {
		$limit = ini_get( 'memory_limit' );
		$bytes = wp_convert_hr_to_bytes( $limit );
		$ok    = $bytes >= 134217728 || -1 === $bytes;
		return self::row(
			__( 'PHP memory limit', 'nextgentutors-plugin-manager' ),
			$ok ? 'PASS' : 'WARNING',
			$limit ?: 'unknown',
			$ok ? '' : __( 'Recommend 128M or higher for batch installs.', 'nextgentutors-plugin-manager' )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function check_database() {
		global $wpdb;
		$ok = ! empty( $wpdb->dbh );
		return self::row(
			__( 'Database connection', 'nextgentutors-plugin-manager' ),
			$ok ? 'PASS' : 'FAIL',
			$ok ? __( 'Connected', 'nextgentutors-plugin-manager' ) : __( 'Not connected', 'nextgentutors-plugin-manager' ),
			$ok ? '' : __( 'Verify database credentials.', 'nextgentutors-plugin-manager' )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function check_wporg_api() {
		$api_response = wp_remote_get(
			'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=woocommerce',
			[
				'timeout'   => 15,
				'sslverify' => apply_filters( 'https_local_ssl_verify', true ),
			]
		);
		$api_ok = ! is_wp_error( $api_response ) && 200 === (int) wp_remote_retrieve_response_code( $api_response );

		$zip_response = wp_remote_head(
			NGCPM_Installer::wporg_zip_url( 'woocommerce' ),
			[
				'timeout'   => 15,
				'sslverify' => apply_filters( 'https_local_ssl_verify', true ),
			]
		);
		$zip_ok = ! is_wp_error( $zip_response ) && in_array( (int) wp_remote_retrieve_response_code( $zip_response ), [ 200, 302 ], true );

		$ok = $api_ok || $zip_ok;
		$evidence = [];
		if ( $api_ok ) {
			$evidence[] = __( 'Plugin API reachable', 'nextgentutors-plugin-manager' );
		} elseif ( is_wp_error( $api_response ) ) {
			$evidence[] = 'API: ' . $api_response->get_error_message();
		} else {
			$evidence[] = 'API: HTTP ' . (int) wp_remote_retrieve_response_code( $api_response );
		}
		if ( $zip_ok ) {
			$evidence[] = __( 'Direct zip downloads reachable', 'nextgentutors-plugin-manager' );
		} elseif ( is_wp_error( $zip_response ) ) {
			$evidence[] = 'ZIP: ' . $zip_response->get_error_message();
		} else {
			$evidence[] = 'ZIP: HTTP ' . (int) wp_remote_retrieve_response_code( $zip_response );
		}

		return self::row(
			__( 'WordPress.org plugin API', 'nextgentutors-plugin-manager' ),
			$ok ? 'PASS' : 'FAIL',
			implode( ' · ', $evidence ),
			$ok ? '' : __( 'Allow outbound HTTPS to api.wordpress.org and downloads.wordpress.org.', 'nextgentutors-plugin-manager' )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function check_rest_api() {
		$url      = rest_url( 'wp/v2/types' );
		$response = wp_remote_get( $url, [ 'timeout' => 10, 'sslverify' => apply_filters( 'https_local_ssl_verify', false ) ] );
		$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
		$ok       = $code >= 200 && $code < 300;
		return self::row(
			__( 'REST API', 'nextgentutors-plugin-manager' ),
			$ok ? 'PASS' : 'FAIL',
			$ok ? 'GET ' . $url . ' → ' . $code : ( is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . $code ),
			$ok ? '' : __( 'Fix permalinks or security plugins blocking REST.', 'nextgentutors-plugin-manager' )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function check_cron() {
		$disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
		return self::row(
			__( 'WP-Cron', 'nextgentutors-plugin-manager' ),
			$disabled ? 'WARNING' : 'PASS',
			$disabled ? __( 'DISABLE_WP_CRON is true', 'nextgentutors-plugin-manager' ) : __( 'Enabled', 'nextgentutors-plugin-manager' ),
			$disabled ? __( 'Configure system cron for wp-cron.php.', 'nextgentutors-plugin-manager' ) : ''
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function check_packages_dir() {
		$dir = NGCPM_Settings::local_zip_dir();
		wp_mkdir_p( $dir );
		$writable = is_dir( $dir ) && wp_is_writable( $dir );
		return self::row(
			__( 'Package directory', 'nextgentutors-plugin-manager' ),
			$writable ? 'PASS' : 'FAIL',
			$writable ? __( 'Writable', 'nextgentutors-plugin-manager' ) : __( 'Not writable', 'nextgentutors-plugin-manager' ),
			$writable ? '' : __( 'Ensure ngcpm-packages directory is writable.', 'nextgentutors-plugin-manager' )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function check_plugin_registry() {
		$scan   = NGCPM_Scanner::scan( true );
		$health = NGCPM_Health::calculate( $scan );
		$ok     = (int) ( $health['required_ready'] ?? 0 ) >= (int) ( $health['required_total'] ?? 1 );
		return self::row(
			__( 'Plugin registry', 'nextgentutors-plugin-manager' ),
			$ok ? 'PASS' : 'WARNING',
			sprintf(
				/* translators: 1: ready count 2: required total */
				__( '%1$d/%2$d required ready', 'nextgentutors-plugin-manager' ),
				(int) ( $health['required_ready'] ?? 0 ),
				(int) ( $health['required_total'] ?? 0 )
			),
			$ok ? '' : __( 'Install and activate required plugins.', 'nextgentutors-plugin-manager' )
		);
	}
}
