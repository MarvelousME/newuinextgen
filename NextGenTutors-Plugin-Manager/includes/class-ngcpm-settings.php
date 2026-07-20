<?php
/**
 * Settings API.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin settings.
 */
class NGCPM_Settings {

	const OPTION_GROUP       = 'ngcpm_settings';
	const OPTION_FRONTEND    = 'ngcpm_enable_frontend';
	const OPTION_REMOTE      = 'ngcpm_enable_remote_zips';
	const OPTION_LOCAL_DIR   = 'ngcpm_local_zip_dir';
	const OPTION_AUTO_LOCAL  = 'ngcpm_auto_install_local_zips';
	const OPTION_REGISTRY    = 'ngcpm_custom_registry';
	const OPTION_REMOTE_ZIPS = 'ngcpm_remote_zip_urls';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'register' ] );
	}

	/**
	 * Default options on activation.
	 */
	public static function install_defaults() {
		add_option( self::OPTION_FRONTEND, '1', '', false );
		add_option( self::OPTION_REMOTE, '0', '', false );
		add_option( self::OPTION_LOCAL_DIR, trailingslashit( WP_CONTENT_DIR ) . 'ngcpm-packages', '', false );
		add_option( self::OPTION_AUTO_LOCAL, '1', '', false );
		add_option( self::OPTION_REGISTRY, [], '', false );
		add_option( self::OPTION_REMOTE_ZIPS, [], '', false );
	}

	/**
	 * Register settings.
	 */
	public static function register() {
		register_setting( self::OPTION_GROUP, self::OPTION_FRONTEND, [
			'type'              => 'string',
			'sanitize_callback' => static function ( $v ) { return $v ? '1' : '0'; },
			'default'           => '1',
		] );
		register_setting( self::OPTION_GROUP, self::OPTION_REMOTE, [
			'type'              => 'string',
			'sanitize_callback' => static function ( $v ) { return $v ? '1' : '0'; },
			'default'           => '0',
		] );
		register_setting( self::OPTION_GROUP, self::OPTION_LOCAL_DIR, [
			'type'              => 'string',
			'sanitize_callback' => [ __CLASS__, 'sanitize_dir' ],
		] );
		register_setting( self::OPTION_GROUP, self::OPTION_AUTO_LOCAL, [
			'type'              => 'string',
			'sanitize_callback' => static function ( $v ) { return $v ? '1' : '0'; },
			'default'           => '1',
		] );
		register_setting( self::OPTION_GROUP, self::OPTION_REMOTE_ZIPS, [
			'type'              => 'array',
			'sanitize_callback' => [ __CLASS__, 'sanitize_remote_urls' ],
		] );
	}

	/**
	 * @param string $dir Directory path.
	 * @return string
	 */
	public static function sanitize_dir( $dir ) {
		$dir = wp_normalize_path( (string) $dir );
		if ( ! $dir ) {
			return trailingslashit( WP_CONTENT_DIR ) . 'ngcpm-packages';
		}
		return $dir;
	}

	/**
	 * @param mixed $urls URLs array.
	 * @return array<string, string>
	 */
	public static function sanitize_remote_urls( $urls ) {
		if ( ! is_array( $urls ) ) {
			return [];
		}
		$clean = [];
		foreach ( $urls as $slug => $url ) {
			$url = esc_url_raw( (string) $url );
			if ( $url && 0 !== strpos( $url, 'https://' ) ) {
				continue;
			}
			if ( $url ) {
				$clean[ sanitize_key( $slug ) ] = $url;
			}
		}
		return $clean;
	}

	/**
	 * @return bool
	 */
	public static function frontend_enabled() {
		return '1' === (string) get_option( self::OPTION_FRONTEND, '1' );
	}

	/**
	 * @return bool
	 */
	public static function remote_zips_enabled() {
		return '1' === (string) get_option( self::OPTION_REMOTE, '0' );
	}

	/**
	 * Directories searched for local plugin zips (first match wins).
	 *
	 * @return array<int, string>
	 */
	public static function package_search_dirs() {
		$dirs = [
			self::local_zip_dir(),
			trailingslashit( WP_CONTENT_DIR ) . 'ngcpm-packages',
			trailingslashit( NGCPM_PLUGIN_DIR ) . 'offline-packages',
		];
		$dirs = array_map( [ __CLASS__, 'sanitize_dir' ], $dirs );
		return array_values( array_unique( array_filter( $dirs ) ) );
	}

	/**
	 * @return bool
	 */
	public static function auto_install_local_enabled() {
		return '1' === (string) get_option( self::OPTION_AUTO_LOCAL, '1' );
	}

	/**
	 * Resolved local zip directory (env/constant override, then option).
	 *
	 * @return string
	 */
	public static function local_zip_dir() {
		if ( defined( 'NGCPM_LOCAL_ZIP_DIR' ) && NGCPM_LOCAL_ZIP_DIR ) {
			return self::sanitize_dir( (string) NGCPM_LOCAL_ZIP_DIR );
		}
		$env = getenv( 'NGCPM_LOCAL_ZIP_DIR' );
		if ( is_string( $env ) && '' !== $env ) {
			return self::sanitize_dir( $env );
		}
		$dir = (string) get_option( self::OPTION_LOCAL_DIR, trailingslashit( WP_CONTENT_DIR ) . 'ngcpm-packages' );
		return self::sanitize_dir( $dir );
	}
}
