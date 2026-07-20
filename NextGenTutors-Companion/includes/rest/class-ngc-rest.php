<?php
/**
 * REST API base registration.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST namespace bootstrap.
 */
class NGC_Rest {

	/**
	 * API namespace.
	 */
	const NAMESPACE = 'ngc/v1';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * Register all REST route groups.
	 */
	public static function register_routes() {
		NGC_Rest_Dashboard::register();
		NGC_Rest_Matching::register();
		NGC_Rest_Finance::register();
		NGC_Rest_Bookings::register();
		NGC_Rest_Reviews::register();
		NGC_Rest_Admin::register();
		NGC_Rest_Platform::register();
		NGC_Rest_Platform_Services::register();
		NGC_Rest_Ai::register();
		NGC_Rest_Studio::register();
		NGC_Rest_Section_Cms::register();
		NGC_Rest_Marketplace::register();
		NGC_Rest_Page_Forms_Registry::register();
		NGC_Rest_System_Log::register();
		NGC_Rest_Metrics::register();
		NGC_Rest_Legacy_Alias::register_alias_routes();
	}

	/**
	 * Permission: logged-in user.
	 *
	 * @return bool
	 */
	public static function require_login() {
		return is_user_logged_in();
	}

	/**
	 * Permission: manage options or ngc admin cap.
	 *
	 * @return bool
	 */
	public static function require_admin() {
		return current_user_can( 'manage_options' ) || current_user_can( 'ngc_admin_operations' );
	}

	/**
	 * Permission: support staff.
	 *
	 * @return bool
	 */
	public static function require_support() {
		return current_user_can( 'ngc_manage_matches' ) || self::require_admin();
	}

	/**
	 * Permission: public endpoint with rate limiting.
	 *
	 * Classification: PUBLIC_SAFE (throttled).
	 *
	 * @param string $action Rate-limit action key.
	 * @param int    $limit  Max requests.
	 * @param int    $window Window seconds.
	 * @return bool|WP_Error
	 */
	public static function public_throttled( $action = 'rest_public', $limit = 30, $window = 600 ) {
		if ( ! class_exists( 'NGC_Rate_Limiter' ) ) {
			return true;
		}
		if ( ! NGC_Rate_Limiter::check( $action, $limit, $window ) ) {
			return new WP_Error(
				'ngc_rate_limited',
				__( 'Too many requests. Please try again later.', 'nextgencompanion' ),
				[ 'status' => 429 ]
			);
		}
		return true;
	}

	/**
	 * Standard error response.
	 *
	 * Accepts a WP_Error or shorthand: error_response( 'code', 'message', 404 ).
	 *
	 * @param WP_Error|string $error   Error object or code.
	 * @param string          $message Message when using shorthand.
	 * @param int             $status  HTTP status when using shorthand.
	 * @return WP_REST_Response
	 */
	public static function error_response( $error, $message = '', $status = 400 ) {
		if ( ! is_wp_error( $error ) ) {
			$error = new WP_Error(
				(string) $error,
				(string) $message,
				[ 'status' => (int) $status ]
			);
		}
		$http_status = 400;
		$data        = $error->get_error_data();
		if ( is_array( $data ) && isset( $data['status'] ) ) {
			$http_status = (int) $data['status'];
		}
		return new WP_REST_Response(
			[ 'error' => $error->get_error_message(), 'code' => $error->get_error_code() ],
			$http_status
		);
	}
}
