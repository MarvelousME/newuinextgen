<?php
/**
 * NextGen Automation Hub — bootstrap.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub {

	const VERSION            = NGT_HUB_VERSION;
	const DB_VERSION         = NGT_HUB_DB_VERSION;
	const OPTION_WORKFLOWS   = 'ngt_workflows_json';
	const OPTION_HUB_SETTINGS = 'ngt_hub_settings';

	/** @var bool */
	private static $booted = false;

	public static function init(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		self::load_includes();

		register_activation_hook( NGT_HUB_FILE, [ __CLASS__, 'activate' ] );
		register_deactivation_hook( NGT_HUB_FILE, [ __CLASS__, 'deactivate' ] );
		add_action( 'plugins_loaded', [ __CLASS__, 'boot' ] );
	}

	private static function load_includes(): void {
		$files = [
			'class-ngt-hub-companion-delegate.php',
			'class-ngt-hub-database.php',
			'class-ngt-hub-security.php',
			'class-ngt-hub-data-model.php',
			'class-ngt-hub-matching.php',
			'class-ngt-hub-notifications.php',
			'class-ngt-hub-gamification.php',
			'class-ngt-hub-payouts.php',
			'class-ngt-hub-lessons.php',
			'class-ngt-hub-dashboard.php',
			'class-ngt-hub-calendar.php',
			'class-ngt-hub-auth.php',
			'class-ngt-hub-forms.php',
			'class-ngt-hub-rtm.php',
			'class-ngt-hub-workflows.php',
			'class-ngt-hub-rest.php',
			'class-ngt-hub-admin.php',
			'class-ngt-hub-integrations.php',
			'class-ngt-hub-intelligence-bridge.php',
		];

		foreach ( $files as $file ) {
			require_once NGT_HUB_DIR . 'includes/' . $file;
		}
	}

	public static function boot(): void {
		load_plugin_textdomain( 'nextgen-automation-hub', false, dirname( plugin_basename( NGT_HUB_FILE ) ) . '/languages' );

		NGT_Hub_Companion_Delegate::sync_delegation();

		NGT_Hub_Database::maybe_upgrade();
		NGT_Hub_Data_Model::register_hooks();
		NGT_Hub_Security::register_hooks();
		NGT_Hub_Matching::register_hooks();
		NGT_Hub_Notifications::register_hooks();
		NGT_Hub_Gamification::register_hooks();
		NGT_Hub_Payouts::register_hooks();
		NGT_Hub_Lessons::register_hooks();
		NGT_Hub_Dashboard::register_hooks();
		NGT_Hub_Calendar::register_hooks();
		NGT_Hub_Auth::register_hooks();
		NGT_Hub_Forms::register_hooks();
		NGT_Hub_RTM::register_hooks();
		NGT_Hub_Workflows::register_hooks();
		NGT_Hub_REST::register_hooks();
		NGT_Hub_Admin::register_hooks();
		NGT_Hub_Integrations::register_hooks();
	}

	public static function activate(): void {
		self::load_includes();
		NGT_Hub_Database::install();
		NGT_Hub_Data_Model::install();
		NGT_Hub_Workflows::import_bundled();
		NGT_Hub_Payouts::schedule_cron();
		NGT_Hub_Workflows::schedule_health_cron();
		update_option( 'ngt_hub_db_version', self::DB_VERSION, false );
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		NGT_Hub_Payouts::unschedule_cron();
		NGT_Hub_Workflows::unschedule_health_cron();
	}

	public static function table( string $name ): string {
		return NGT_Hub_Database::table( $name );
	}

	public static function fire_event( string $event_key, string $source = 'system', int $user_id = 0, int $object_id = 0, array $payload = [] ): void {
		NGT_Hub_Workflows::fire_event( $event_key, $source, $user_id, $object_id, $payload );
	}

	public static function get_workflows(): array {
		return NGT_Hub_Workflows::get_workflows();
	}
}
