<?php
/**
 * Bridges Companion cookie/tracking config to NextGenTutors-Plugin-Manager health checks.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aligns NGCPM diagnostics with Companion POPIA tracking cookies.
 */
class NGC_Plugin_Manager_Bridge {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_filter( 'ngcpm_tracking_cookie_names', [ __CLASS__, 'tracking_cookie_names' ] );
		add_filter( 'ngcpm_consent_cookie_names', [ __CLASS__, 'consent_cookie_names' ] );
		add_filter( 'ngcpm_frontend_cookies_configured', [ __CLASS__, 'is_configured' ] );
	}

	/**
	 * @return string[]
	 */
	public static function tracking_cookie_names() {
		return [
			NGC_Platform_Tracking::cookie_name( 'visitor_id' ),
			NGC_Platform_Tracking::cookie_name( 'session_id' ),
		];
	}

	/**
	 * @return string[]
	 */
	public static function consent_cookie_names() {
		return [
			NGC_Platform_Tracking::cookie_name( 'consent_status' ),
			'ngc_consent',
		];
	}

	/**
	 * Whether consent + tracking is enabled for the public site.
	 *
	 * @param bool $configured Incoming filter value.
	 * @return bool
	 */
	public static function is_configured( $configured ) {
		if ( $configured ) {
			return true;
		}
		if ( ! class_exists( 'NGC_Platform_Tracking' ) ) {
			return false;
		}
		if ( '1' === (string) get_option( 'ngc_tracking_disabled', '0' ) ) {
			return false;
		}
		return has_action( 'wp_footer', [ 'NGC_Platform_Tracking', 'render_cookie_banner' ] )
			|| '0' === (string) get_option( 'ngc_require_cookie_consent', '1' );
	}
}
