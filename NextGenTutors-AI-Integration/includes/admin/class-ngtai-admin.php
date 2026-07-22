<?php
/**
 * Admin menu and assets.
 *
 * @package NextGenTutorsAIIntegration
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
final class NGTAI_Admin {
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ], 99 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
	}
	public static function menu() {
		$parent = 'ngc-operations';
		global $menu, $submenu;
		if ( empty( $submenu[ $parent ] ) ) {
			add_menu_page( __( 'AI Integration', 'nextgentutors-ai-integration' ), __( 'AI Integration', 'nextgentutors-ai-integration' ), 'manage_options', 'ngtai', [ 'NGTAI_Settings_Page', 'render' ], 'dashicons-rest-api' );
			$parent = 'ngtai';
		}
		$pages = [
			[ 'ngtai-settings', __( 'AI Settings', 'nextgentutors-ai-integration' ), 'NGTAI_Settings_Page' ],
			[ 'ngtai-health', __( 'AI Health', 'nextgentutors-ai-integration' ), 'NGTAI_Health_Page' ],
			[ 'ngtai-events', __( 'AI Events', 'nextgentutors-ai-integration' ), 'NGTAI_Events_Page' ],
			[ 'ngtai-approvals', __( 'AI Approvals', 'nextgentutors-ai-integration' ), 'NGTAI_Approvals_Page' ],
			[ 'ngtai-agent-ops', __( 'Agent Operations', 'nextgentutors-ai-integration' ), 'NGTAI_Agent_Ops_Page' ],
		];
		foreach ( $pages as $page ) {
			add_submenu_page( $parent, $page[1], $page[1], 'manage_options', $page[0], [ $page[2], 'render' ] );
		}
	}
	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'ngtai' ) ) {
			return;
		}
		wp_enqueue_style( 'ngtai-admin', NGTAI_PLUGIN_URL . 'assets/css/admin.css', [], NGTAI_VERSION );
		wp_enqueue_script( 'ngtai-admin', NGTAI_PLUGIN_URL . 'assets/js/admin.js', [], NGTAI_VERSION, true );
	}
}
