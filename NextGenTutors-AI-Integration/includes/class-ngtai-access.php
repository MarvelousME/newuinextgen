<?php
/**
 * Access helpers.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capability checks for admin and REST surfaces.
 */
final class NGTAI_Access {

	/**
	 * Determine whether the current user can manage AI operations.
	 *
	 * @return bool
	 */
	public static function can_manage() {
		return function_exists( 'current_user_can' )
			&& ( current_user_can( 'manage_options' ) || current_user_can( 'ngc_ai_ops' ) );
	}

	/**
	 * Determine whether the current user can approve governed actions.
	 *
	 * @return bool
	 */
	public static function can_approve() {
		return function_exists( 'current_user_can' )
			&& (
				current_user_can( 'manage_options' )
				|| current_user_can( 'ngc_ai_ops' )
				|| current_user_can( 'ngc_manage_matches' )
			);
	}

	/**
	 * REST permission callback.
	 *
	 * @return bool
	 */
	public static function rest_can_manage() {
		return self::can_manage();
	}
}
