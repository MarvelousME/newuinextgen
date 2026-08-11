<?php
/**
 * Module bootstrap — loads all companion services on plugins_loaded.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central module loader.
 */
class NGC_Plugin_Bootstrap {

	/**
	 * Module classes initialized via ::init().
	 *
	 * @var string[]
	 */
	private static $modules = [
		'NGC_Roles',
		'NGC_Post_Types',
		'NGC_Tutor_Seeder',
		'NGC_Page_Forms_Registry',
		'NGC_Page_Forms_Registry_Admin',
		'NGC_Marketplace',
		'NGC_Tutor_Demo_Admin',
		'NGC_Audit',
		'NGC_Registration',
		'NGC_Child_Learners',
		'NGC_Section_CMS',
		'NGC_Subjects_CMS',
		'NGC_Workflow_Orchestrator',
		'NGC_Workflows',
		'NGC_Transactional_Mail',
		'NGC_Matching',
		'NGC_Smart_Matching',
		'NGC_Forms',
		'NGC_Exception_Log',
		'NGC_Core_Loader',
		'NGC_System_Log',
		'NGC_System_Log_Admin',
		'NGC_Bookings',
		'NGC_Meetings',
		'NGC_Session_Orchestrator',
		'NGC_Session_Classroom',
		'NGC_Product_Provisioner',
		'NGC_Payments',
		'NGC_PayFast',
		'NGC_Parent_Checkout',
		'NGC_Invoices',
		'NGC_Wallet',
		'NGC_Tutor_Lifecycle',
		'NGC_Reviews',
		'NGC_Verification',
		'NGC_Self_Healing',
		'NGC_Platform_Tracking',
		'NGC_Popia_Consent',
		'NGC_Plugin_Manager_Bridge',
		'NGC_Integrate_Runtime',
		'NGC_Integrations_Bootstrap',
		'NGC_Content_Pack_Bridge',
		'NGC_AutomatorWP_Integration',
		'NGC_AutomatorWP_Importer',
		'NGC_Workflow_Integrate_Executor',
		'NGC_Studio',
		'NGC_Studio_Forms',
		'NGC_Studio_Email',
		'NGC_Studio_Notifications',
		'NGC_Studio_Dashboards',
		'NGC_Studio_Stream',
		'NGC_Studio_Admin',
		'NGC_Visual_Builder',
		'NGC_Builder_Admin',
		'NGC_Tutor_Calendar_Service',
		'NGC_Fluentcrm',
		'NGC_Fluent_Support',
		'NGC_Lms',
		'NGC_Amelia',
		'NGC_Rest',
		'NGC_Rest_Tutor_Calendar',
		'NGC_Shortcodes',
		'NGC_Admin',
		'NGC_Business_Profile_Admin',
		'NGC_Provisioning_Admin',
		'NGC_Provisioning_Engine',
		'NGC_Workflow_Admin',
		'NGC_Platform_Admin',
		'NGC_Gamification',
		'NGC_Export_Scheduler',
		'NGC_Audit_Service',
		'NGC_Ai_Diagnostics',
		'NGC_AI_Admin',
		'NGC_Memory_Service',
		'NGC_Memory_Admin',
		'NGC_Talent_Service',
		'NGC_Talent_Admin',
		'NGC_Control_Plane_Bridge',
		'NGC_Platform_Services_Admin',
		'NGC_UI_Library',
		'NGC_UI_Library_Admin',
		'NGC_NGT_UI_Bridge',
		'NGC_Legacy_Plugin_Guard',
		'NGC_Automation_Hub_Bridge',
		'NGC_Agent_Control_Plane',
		'NGC_Fraud_Engine',
		'NGC_Safeguarding',
		'NGC_Domain_Event_Bridge',
		'NGC_Agent_Ops_Admin',
		'NGC_Safeguarding_Admin',
		'NGC_Privacy',
		'NGC_Metrics',
		'NGC_Observability_Service',
		'NGC_Orchestration_Cockpit_Admin',
		'NGC_Intelligence',
		'NGC_Platform',
		'NGC_Admin_Shell',
		'NGC_Payout_Scheduler',
		'NGC_Demo',
		'NGC_Agentic_Admin',
		'NGC_Education_Admin',
		'NGC_Publish_Worker',
	];

	/**
	 * Safely initialize module classes without fataling the site.
	 *
	 * @param string $class Class name.
	 */
	private static function safe_init( $class ) {
		if ( ! class_exists( $class ) ) {
			error_log( sprintf( '[NextGenCompanion] Bootstrap skipped missing class: %s', $class ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}
		if ( ! method_exists( $class, 'init' ) ) {
			error_log( sprintf( '[NextGenCompanion] Bootstrap class missing init(): %s', $class ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}
		call_user_func( [ $class, 'init' ] );
	}

	/**
	 * Initialize all modules.
	 */
	public static function init() {
		NGC_Core_Loader::preload_classes();

		foreach ( self::$modules as $class ) {
			self::safe_init( $class );
		}

		if ( class_exists( 'NGC_Workflow_Email_Templates' ) && method_exists( 'NGC_Workflow_Email_Templates', 'install_defaults' ) ) {
			NGC_Workflow_Email_Templates::install_defaults();
		}

		if ( class_exists( 'NGC_Database' ) && method_exists( 'NGC_Database', 'tables_exist' ) && ! NGC_Database::tables_exist() ) {
			NGC_Database::create_tables();
		}

		// Shared UI library (Magic UI conversions + canonical renderer).
		$ui_bridge = NGC_PLUGIN_DIR . 'includes/ui-library/class-ngc-ui-library-bridge.php';
		if ( is_readable( $ui_bridge ) ) {
			require_once $ui_bridge;
			if ( class_exists( 'NGC_UI_Library_Bridge' ) ) {
				NGC_UI_Library_Bridge::init();
			}
		}
	}
}
