<?php
/**
 * Plugin deactivation.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Performs non-destructive deactivation tasks.
 */
final class NGTAI_Deactivator {

	/**
	 * Deactivate the integration without deleting data.
	 *
	 * @return void
	 */
	public static function deactivate() {
		if ( class_exists( 'NGTAI_Cron' ) ) {
			NGTAI_Cron::clear();
		}
	}
}
