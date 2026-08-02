<?php
/**
 * Plugin bootstrap.
 *
 * @package NextGenTutorsMissionControl
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Boot Mission Control modules.
 */
final class NGTMC_Plugin {

	public static function init() {
		require_once NGTMC_PLUGIN_DIR . 'includes/class-ngtmc-orchestrator.php';
		require_once NGTMC_PLUGIN_DIR . 'includes/class-ngtmc-overrides.php';
		require_once NGTMC_PLUGIN_DIR . 'includes/class-ngtmc-admin.php';
		require_once NGTMC_PLUGIN_DIR . 'includes/class-ngtmc-ajax.php';
		require_once NGTMC_PLUGIN_DIR . 'includes/class-ngtmc-intelligence.php';

		NGTMC_Overrides::init();
		NGTMC_Admin::init();
		NGTMC_Ajax::init();

		register_activation_hook( NGTMC_PLUGIN_FILE, [ __CLASS__, 'activate' ] );
	}

	public static function activate() {
		if ( ! get_option( NGTMC_Overrides::OPTION_KEY ) ) {
			update_option( NGTMC_Overrides::OPTION_KEY, NGTMC_Overrides::defaults(), false );
		}
		if ( ! get_option( NGTMC_Orchestrator::STATE_OPTION ) ) {
			update_option(
				NGTMC_Orchestrator::STATE_OPTION,
				[
					'status'     => 'READY',
					'updated_at' => gmdate( 'c' ),
					'source'     => 'mission-control',
				],
				false
			);
		}
	}
}
