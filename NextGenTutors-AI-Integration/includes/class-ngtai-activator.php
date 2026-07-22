<?php
/**
 * Plugin activation.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Performs installation tasks.
 */
final class NGTAI_Activator {

	/**
	 * Activate the integration.
	 *
	 * @return void
	 */
	public static function activate() {
		NGTAI_Migrator::migrate();

		if ( class_exists( 'NGTAI_Cron' ) ) {
			NGTAI_Cron::schedule();
		}

		if ( function_exists( 'add_option' ) ) {
			add_option( 'ngtai_enabled', 1, '', false );
		}
	}
}
