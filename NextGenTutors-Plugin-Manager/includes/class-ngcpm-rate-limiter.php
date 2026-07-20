<?php
/**
 * AJAX rate limiting for write operations.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Per-user transient rate limits.
 */
class NGCPM_Rate_Limiter {

	const LIMIT  = 40;
	const WINDOW = 600;

	/**
	 * @param string $action Action key.
	 * @return bool True if allowed.
	 */
	public static function allow( $action ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$key   = 'ngcpm_rl_' . $user_id . '_' . sanitize_key( $action );
		$count = (int) get_transient( $key );

		if ( $count >= self::LIMIT ) {
			NGCPM_Logger::log( 'rate_limited', 'Rate limit exceeded', [ 'action' => $action ] );
			return false;
		}

		set_transient( $key, $count + 1, self::WINDOW );
		return true;
	}

	/**
	 * Send 429 JSON error when limited.
	 *
	 * @param string $action Action key.
	 */
	public static function enforce( $action ) {
		if ( ! self::allow( $action ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Too many requests. Please wait and try again.', 'nextgentutors-plugin-manager' ),
					'code'    => 'rate_limited',
				],
				429
			);
		}
	}
}
