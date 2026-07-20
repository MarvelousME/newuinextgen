<?php
/**
 * FluentCRM legacy shim — orchestrator owns registration workflows.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inbound Amelia-style CRM hooks only; upsert handled by NGC_Fluentcrm_Adapter.
 */
class NGC_Fluentcrm {

	/**
	 * Hook registration.
	 */
	public static function init() {
		if ( ! class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
			add_action( 'admin_notices', [ __CLASS__, 'missing_notice' ] );
			return;
		}
		// Orchestrator handles CRM for registration workflows.
		add_action( 'ngc_fluentcrm_bootstrap', [ __CLASS__, 'bootstrap' ] );
	}

	/**
	 * Bootstrap lists/tags on demand.
	 */
	public static function bootstrap() {
		$adapter = new NGC_Fluentcrm_Adapter();
		$adapter->bootstrap_assets();
	}

	/**
	 * Admin notice when FluentCRM is not installed.
	 */
	public static function missing_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-info is-dismissible"><p>';
		esc_html_e( 'NextGen Companion: FluentCRM is not active. CRM tagging is disabled; WordPress/email workflows continue.', 'nextgencompanion' );
		echo '</p></div>';
	}
}
