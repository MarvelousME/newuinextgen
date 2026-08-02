<?php
/**
 * Default module/screen catalog — business-capability hierarchy.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the enterprise navigation taxonomy and known screens.
 */
final class NGC_Admin_Catalog {

	/**
	 * Business categories (capability groups).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function categories() {
		return [
			'platform'       => [ 'label' => __( 'Platform', 'nextgencompanion' ), 'order' => 1, 'icon' => 'dashicons-dashboard' ],
			'education'      => [ 'label' => __( 'Education', 'nextgencompanion' ), 'order' => 10, 'icon' => 'dashicons-welcome-learn-more' ],
			'operations'     => [ 'label' => __( 'Operations', 'nextgencompanion' ), 'order' => 20, 'icon' => 'dashicons-calendar-alt' ],
			'commerce'       => [ 'label' => __( 'Commerce', 'nextgencompanion' ), 'order' => 30, 'icon' => 'dashicons-cart' ],
			'crm'            => [ 'label' => __( 'CRM', 'nextgencompanion' ), 'order' => 40, 'icon' => 'dashicons-groups' ],
			'ai'             => [ 'label' => __( 'AI Platform', 'nextgencompanion' ), 'order' => 50, 'icon' => 'dashicons-rest-api' ],
			'website'        => [ 'label' => __( 'Website', 'nextgencompanion' ), 'order' => 60, 'icon' => 'dashicons-admin-appearance' ],
			'reporting'      => [ 'label' => __( 'Reporting', 'nextgencompanion' ), 'order' => 70, 'icon' => 'dashicons-chart-area' ],
			'development'    => [ 'label' => __( 'Development', 'nextgencompanion' ), 'order' => 80, 'icon' => 'dashicons-editor-code' ],
			'administration' => [ 'label' => __( 'Administration', 'nextgencompanion' ), 'order' => 90, 'icon' => 'dashicons-admin-settings' ],
			// Legacy aliases mapped for existing screen category keys.
			'command'        => [ 'label' => __( 'Platform', 'nextgencompanion' ), 'order' => 1, 'icon' => 'dashicons-dashboard' ],
			'communications' => [ 'label' => __( 'CRM', 'nextgencompanion' ), 'order' => 40, 'icon' => 'dashicons-email' ],
			'automation'     => [ 'label' => __( 'Operations', 'nextgencompanion' ), 'order' => 21, 'icon' => 'dashicons-networking' ],
			'content'        => [ 'label' => __( 'Website', 'nextgencompanion' ), 'order' => 60, 'icon' => 'dashicons-admin-page' ],
			'infrastructure' => [ 'label' => __( 'Administration', 'nextgencompanion' ), 'order' => 91, 'icon' => 'dashicons-shield' ],
			'settings'       => [ 'label' => __( 'Administration', 'nextgencompanion' ), 'order' => 92, 'icon' => 'dashicons-admin-generic' ],
		];
	}

	/**
	 * Seed modules + screens into the registry.
	 */
	public static function register_defaults() {
		self::register_modules();
		self::register_screens();
		self::register_badges();
	}

	/**
	 * Modules.
	 */
	private static function register_modules() {
		$modules = [
			[ 'slug' => 'mission-control', 'label' => 'Mission Control', 'category' => 'command', 'order' => 1, 'icon' => 'dashicons-superhero' ],
			[ 'slug' => 'tutors', 'label' => 'Tutors', 'category' => 'education', 'order' => 10, 'icon' => 'dashicons-groups' ],
			[ 'slug' => 'students', 'label' => 'Students', 'category' => 'education', 'order' => 11, 'icon' => 'dashicons-id' ],
			[ 'slug' => 'parents', 'label' => 'Parents', 'category' => 'education', 'order' => 12, 'icon' => 'dashicons-admin-users' ],
			[ 'slug' => 'bookings', 'label' => 'Bookings', 'category' => 'operations', 'order' => 20, 'icon' => 'dashicons-calendar-alt' ],
			[ 'slug' => 'matching', 'label' => 'Matching', 'category' => 'operations', 'order' => 21, 'icon' => 'dashicons-randomize' ],
			[ 'slug' => 'payments', 'label' => 'Payments', 'category' => 'commerce', 'order' => 30, 'icon' => 'dashicons-money-alt' ],
			[ 'slug' => 'ai', 'label' => 'AI Platform', 'category' => 'ai', 'order' => 50, 'icon' => 'dashicons-rest-api' ],
			[ 'slug' => 'automation', 'label' => 'Automation', 'category' => 'automation', 'order' => 60, 'icon' => 'dashicons-networking' ],
			[ 'slug' => 'reports', 'label' => 'Reports', 'category' => 'reporting', 'order' => 70, 'icon' => 'dashicons-chart-area' ],
			[ 'slug' => 'content', 'label' => 'Content', 'category' => 'content', 'order' => 80, 'icon' => 'dashicons-admin-appearance' ],
			[ 'slug' => 'platform', 'label' => 'Platform', 'category' => 'platform', 'order' => 90, 'icon' => 'dashicons-database-view' ],
			[ 'slug' => 'system', 'label' => 'System', 'category' => 'infrastructure', 'order' => 100, 'icon' => 'dashicons-shield' ],
			[ 'slug' => 'plugins', 'label' => 'Plugins', 'category' => 'settings', 'order' => 105, 'icon' => 'dashicons-admin-plugins' ],
			[ 'slug' => 'settings', 'label' => 'Settings', 'category' => 'settings', 'order' => 110, 'icon' => 'dashicons-admin-settings' ],
		];
		foreach ( $modules as $m ) {
			NGC_Admin_Registry::register_module( $m );
		}
	}

	/**
	 * Known screens mapped to business modules.
	 */
	private static function register_screens() {
		$screens = [
			// Command
			[
				'slug'       => 'ngtmc-mission-control',
				'title'      => __( 'Mission Control', 'nextgencompanion' ),
				'module'     => 'mission-control',
				'category'   => 'platform',
				'order'      => 1,
				'callback'   => [ 'NGTMC_Admin', 'render' ],
				'keywords'   => [ 'dashboard', 'health', 'overview', 'orchestrator' ],
				'dependencies' => [ 'NGTMC_Admin' ],
			],
			[
				'slug'     => 'ngc-operations',
				'title'    => __( 'Activity Centre', 'nextgencompanion' ),
				'module'   => 'matching',
				'category' => 'platform',
				'order'    => 5,
				'callback' => [ 'NGC_Admin', 'render_dashboard' ],
				'keywords' => [ 'companion', 'overview', 'operations', 'activity' ],
			],
			// Tutors / matching
			[
				'slug'       => 'ngc-applications',
				'title'      => __( 'Tutor Applications', 'nextgencompanion' ),
				'menu_title' => __( 'Tutor Applications', 'nextgencompanion' ),
				'module'     => 'tutors',
				'category'   => 'education',
				'order'      => 10,
				'capability' => 'ngc_review_tutors',
				'callback'   => [ 'NGC_Admin', 'render_applications' ],
				'badge_key'  => 'tutor_applications',
				'keywords'   => [ 'tutors', 'applications', 'review' ],
			],
			[
				'slug'       => 'ngc-matches',
				'title'      => __( 'Matches', 'nextgencompanion' ),
				'module'     => 'matching',
				'category'   => 'operations',
				'order'      => 20,
				'capability' => 'ngc_manage_matches',
				'callback'   => [ 'NGC_Admin', 'render_matches' ],
				'keywords'   => [ 'match', 'assign', 'pairing' ],
			],
			[
				'slug'       => 'ngc-payouts',
				'title'      => __( 'Payouts', 'nextgencompanion' ),
				'module'     => 'payments',
				'category'   => 'commerce',
				'order'      => 30,
				'capability' => 'ngc_manage_payouts',
				'callback'   => [ 'NGC_Admin', 'render_payouts' ],
				'keywords'   => [ 'payout', 'finance', 'tutor pay' ],
			],
			// System ops under NextGen
			[
				'slug'     => 'ngc-health',
				'title'    => __( 'System Health', 'nextgencompanion' ),
				'module'   => 'system',
				'category' => 'platform',
				'order'    => 6,
				'callback' => [ 'NGC_Admin', 'render_health' ],
				'keywords' => [ 'health', 'status', 'checks' ],
			],
			[
				'slug'     => 'ngc-errors',
				'title'    => __( 'Errors & Exceptions', 'nextgencompanion' ),
				'module'   => 'system',
				'category' => 'administration',
				'order'    => 101,
				'callback' => [ 'NGC_Exception_Log', 'render_dashboard' ],
				'badge_key'=> 'errors',
				'keywords' => [ 'errors', 'exceptions', 'logs' ],
			],
			[
				'slug'     => 'ngc-gamification',
				'title'    => __( 'Gamification', 'nextgencompanion' ),
				'module'   => 'students',
				'category' => 'education',
				'order'    => 15,
				'callback' => [ 'NGC_Platform_Services_Admin', 'render_gamification' ],
			],
			[
				'slug'     => 'ngc-exports',
				'title'    => __( 'Exports', 'nextgencompanion' ),
				'module'   => 'reports',
				'category' => 'reporting',
				'order'    => 72,
				'callback' => [ 'NGC_Platform_Services_Admin', 'render_exports' ],
			],
			[
				'slug'       => 'ngc-audit',
				'title'      => __( 'Audit Log', 'nextgencompanion' ),
				'module'     => 'system',
				'category'   => 'infrastructure',
				'order'      => 102,
				'capability' => 'ngc_view_audit',
				'callback'   => [ 'NGC_Platform_Services_Admin', 'render_audit' ],
			],
			[
				'slug'     => 'ngc-ai-diagnostics',
				'title'    => __( 'AI Diagnostics', 'nextgencompanion' ),
				'module'   => 'ai',
				'category' => 'ai',
				'order'    => 55,
				'callback' => [ 'NGC_Platform_Services_Admin', 'render_diagnostics' ],
			],
			[
				'slug'       => 'ngc-system-log',
				'title'      => __( 'System Log', 'nextgencompanion' ),
				'module'     => 'system',
				'category'   => 'infrastructure',
				'order'      => 103,
				'capability' => 'ngc_view_audit',
				'callback'   => [ 'NGC_System_Log_Admin', 'render_page' ],
			],
			[
				'slug'     => 'ngc-ai-suite',
				'title'    => __( 'AI Suite', 'nextgencompanion' ),
				'module'   => 'ai',
				'category' => 'ai',
				'order'    => 50,
				'callback' => [ 'NGC_AI_Admin', 'render_page' ],
			],
			[
				'slug'     => 'ngc-agent-ops',
				'title'    => __( 'Agent Operations', 'nextgencompanion' ),
				'module'   => 'ai',
				'category' => 'ai',
				'order'    => 52,
				'callback' => [ 'NGC_Agent_Ops_Admin', 'render_page' ],
			],
			[
				'slug'     => 'ngc-safeguarding',
				'title'    => __( 'Safeguarding', 'nextgencompanion' ),
				'module'   => 'system',
				'category' => 'infrastructure',
				'order'    => 104,
				'callback' => [ 'NGC_Safeguarding_Admin', 'render_page' ],
				'badge_key'=> 'safeguarding',
			],
			[
				'slug'     => 'ngc-fraud-cases',
				'title'    => __( 'Fraud Cases', 'nextgencompanion' ),
				'module'   => 'system',
				'category' => 'infrastructure',
				'order'    => 105,
				'callback' => [ 'NGC_Safeguarding_Admin', 'render_fraud_page' ],
				'badge_key'=> 'fraud',
			],
			[
				'slug'     => 'ngc-page-registry',
				'title'    => __( 'Page Registry', 'nextgencompanion' ),
				'module'   => 'content',
				'category' => 'content',
				'order'    => 82,
				'callback' => [ 'NGC_Page_Forms_Registry_Admin', 'render' ],
			],
			[
				'slug'     => 'ngc-business-profile',
				'title'    => __( 'Business Profile', 'nextgencompanion' ),
				'module'   => 'settings',
				'category' => 'settings',
				'order'    => 110,
				'callback' => [ 'NGC_Business_Profile_Admin', 'render' ],
			],
			[
				'slug'     => 'ngc-ui-import',
				'title'    => __( 'UI Import & Merge', 'nextgencompanion' ),
				'module'   => 'content',
				'category' => 'content',
				'order'    => 84,
				'callback' => [ 'NGC_UI_Library_Admin', 'render' ],
			],
			[
				'slug'     => 'ngc-home-sections',
				'title'    => __( 'Home Sections', 'nextgencompanion' ),
				'module'   => 'content',
				'category' => 'content',
				'order'    => 80,
				'callback' => [ 'NGC_Section_CMS', 'render_admin' ],
			],
			// Platform
			[
				'slug'     => 'ngc-platform',
				'title'    => __( 'Platform Verification', 'nextgencompanion' ),
				'module'   => 'platform',
				'category' => 'platform',
				'order'    => 90,
				'callback' => [ 'NGC_Platform_Admin', 'render_data_source_verification' ],
			],
			[
				'slug'     => 'ngc-platform-verify',
				'title'    => __( 'Data Source Verification', 'nextgencompanion' ),
				'module'   => 'platform',
				'category' => 'platform',
				'order'    => 91,
				'callback' => [ 'NGC_Platform_Admin', 'render_data_source_verification' ],
			],
			[
				'slug'     => 'ngc-platform-demo',
				'title'    => __( 'Demo Journey Manager', 'nextgencompanion' ),
				'module'   => 'platform',
				'category' => 'platform',
				'order'    => 92,
				'callback' => [ 'NGC_Platform_Admin', 'render_demo_journey_manager' ],
			],
			[
				'slug'     => 'ngc-demo-control',
				'title'    => __( 'Demo Control Centre', 'nextgencompanion' ),
				'module'   => 'platform',
				'category' => 'platform',
				'order'    => 93,
				'callback' => [ 'NGC_Demo_Admin', 'render' ],
			],
			[
				'slug'     => 'ngc-platform-analytics',
				'title'    => __( 'Analytics Dashboard', 'nextgencompanion' ),
				'module'   => 'reports',
				'category' => 'reporting',
				'order'    => 70,
				'callback' => [ 'NGC_Platform_Admin', 'render_analytics_dashboard' ],
			],
			[
				'slug'     => 'ngc-platform-profiling',
				'title'    => __( 'User Profiling', 'nextgencompanion' ),
				'module'   => 'reports',
				'category' => 'reporting',
				'order'    => 71,
				'callback' => [ 'NGC_Platform_Admin', 'render_user_profiling_dashboard' ],
			],
			[
				'slug'     => 'ngc-platform-acquisition',
				'title'    => __( 'Acquisition', 'nextgencompanion' ),
				'module'   => 'reports',
				'category' => 'reporting',
				'order'    => 73,
				'callback' => [ 'NGC_Platform_Admin', 'render_acquisition_dashboard' ],
			],
			[
				'slug'     => 'ngc-platform-affiliates',
				'title'    => __( 'Affiliate Tracking', 'nextgencompanion' ),
				'module'   => 'reports',
				'category' => 'reporting',
				'order'    => 74,
				'callback' => [ 'NGC_Platform_Admin', 'render_affiliate_tracking_dashboard' ],
			],
			[
				'slug'     => 'ngc-platform-cookies',
				'title'    => __( 'Cookie Tracking', 'nextgencompanion' ),
				'module'   => 'settings',
				'category' => 'settings',
				'order'    => 112,
				'callback' => [ 'NGC_Platform_Admin', 'render_cookie_tracking_settings' ],
			],
			[
				'slug'     => 'ngc-platform-privacy',
				'title'    => __( 'Privacy / Consent', 'nextgencompanion' ),
				'module'   => 'settings',
				'category' => 'settings',
				'order'    => 113,
				'callback' => [ 'NGC_Platform_Admin', 'render_privacy_consent_settings' ],
			],
			[
				'slug'     => 'ngc-platform-observability',
				'title'    => __( 'Observability', 'nextgencompanion' ),
				'module'   => 'system',
				'category' => 'infrastructure',
				'order'    => 106,
				'callback' => [ 'NGC_Platform_Admin', 'render_observability_settings' ],
			],
			[
				'slug'     => 'ngc-platform-health',
				'title'    => __( 'Data Health Checks', 'nextgencompanion' ),
				'module'   => 'system',
				'category' => 'infrastructure',
				'order'    => 107,
				'callback' => [ 'NGC_Platform_Admin', 'render_data_health_checks' ],
			],
			[
				'slug'     => 'ngc-platform-repair',
				'title'    => __( 'Self-Healing Repair', 'nextgencompanion' ),
				'module'   => 'system',
				'category' => 'infrastructure',
				'order'    => 108,
				'callback' => [ 'NGC_Platform_Admin', 'render_self_healing_tools' ],
			],
			// Workflows / Automation
			[
				'slug'     => 'ngc-workflows',
				'title'    => __( 'Workflow Triggers', 'nextgencompanion' ),
				'module'   => 'automation',
				'category' => 'automation',
				'order'    => 60,
				'callback' => [ 'NGC_Workflow_Admin', 'render_trigger_manager' ],
			],
			[
				'slug'     => 'ngc-workflow-triggers',
				'title'    => __( 'Trigger Manager', 'nextgencompanion' ),
				'module'   => 'automation',
				'category' => 'automation',
				'order'    => 61,
				'callback' => [ 'NGC_Workflow_Admin', 'render_trigger_manager' ],
			],
			[
				'slug'     => 'ngc-workflow-fluentcrm',
				'title'    => __( 'FluentCRM Status', 'nextgencompanion' ),
				'module'   => 'automation',
				'category' => 'communications',
				'order'    => 42,
				'callback' => [ 'NGC_Workflow_Admin', 'render_fluentcrm' ],
			],
			[
				'slug'     => 'ngc-workflow-amelia',
				'title'    => __( 'Amelia Status', 'nextgencompanion' ),
				'module'   => 'bookings',
				'category' => 'operations',
				'order'    => 22,
				'callback' => [ 'NGC_Workflow_Admin', 'render_amelia' ],
			],
			[
				'slug'     => 'ngc-workflow-masterstudy',
				'title'    => __( 'MasterStudy Status', 'nextgencompanion' ),
				'module'   => 'students',
				'category' => 'education',
				'order'    => 16,
				'callback' => [ 'NGC_Workflow_Admin', 'render_masterstudy' ],
			],
			[
				'slug'     => 'ngc-workflow-emails',
				'title'    => __( 'Email Templates', 'nextgencompanion' ),
				'module'   => 'automation',
				'category' => 'communications',
				'order'    => 40,
				'callback' => [ 'NGC_Workflow_Admin', 'render_emails' ],
			],
			[
				'slug'     => 'ngc-workflow-logs',
				'title'    => __( 'Workflow Logs', 'nextgencompanion' ),
				'module'   => 'automation',
				'category' => 'automation',
				'order'    => 64,
				'callback' => [ 'NGC_Workflow_Admin', 'render_logs' ],
			],
			[
				'slug'     => 'ngc-workflow-retries',
				'title'    => __( 'Retry Queue', 'nextgencompanion' ),
				'module'   => 'automation',
				'category' => 'automation',
				'order'    => 65,
				'callback' => [ 'NGC_Workflow_Admin', 'render_retries' ],
				'badge_key'=> 'workflow_retries',
			],
			[
				'slug'     => 'ngc-workflow-verification',
				'title'    => __( 'Workflow Verification', 'nextgencompanion' ),
				'module'   => 'automation',
				'category' => 'automation',
				'order'    => 66,
				'callback' => [ 'NGC_Workflow_Admin', 'render_verification' ],
			],
			[
				'slug'     => 'ngc-workflow-integrate',
				'title'    => __( 'Integrate Specs', 'nextgencompanion' ),
				'module'   => 'automation',
				'category' => 'automation',
				'order'    => 67,
				'callback' => [ 'NGC_Workflow_Admin', 'render_integrate_specs' ],
			],
			[
				'slug'     => 'ngc-automation-studio',
				'title'    => __( 'Automation Studio', 'nextgencompanion' ),
				'module'   => 'automation',
				'category' => 'automation',
				'order'    => 62,
				'callback' => [ 'NGC_Studio_Admin', 'render_app' ],
			],
			[
				'slug'         => 'ngt-hub',
				'title'        => __( 'Automation Hub', 'nextgencompanion' ),
				'module'       => 'automation',
				'category'     => 'automation',
				'order'        => 63,
				'capability'   => 'ngt_manage_hub',
				'callback'     => [ 'NGT_Hub_Admin', 'render_page' ],
				'dependencies' => [ 'NGT_Hub_Admin' ],
			],
			// Plugin Manager
			[
				'slug'         => 'ui-ux-pro-max',
				'title'        => __( 'Plugin Manager', 'nextgencompanion' ),
				'module'       => 'plugins',
				'category'     => 'settings',
				'order'        => 105,
				'callback'     => [ 'NGCPM_Admin', 'render' ],
				'dependencies' => [ 'NGCPM_Admin' ],
			],
			[
				'slug'         => 'ui-ux-pro-max-settings',
				'title'        => __( 'Plugin Manager Settings', 'nextgencompanion' ),
				'module'       => 'plugins',
				'category'     => 'settings',
				'order'        => 106,
				'callback'     => [ 'NGCPM_Admin', 'render_settings' ],
				'dependencies' => [ 'NGCPM_Admin' ],
			],
			// AI Integration
			[
				'slug'         => 'ngtai-settings',
				'title'        => __( 'AI Settings', 'nextgencompanion' ),
				'module'       => 'ai',
				'category'     => 'ai',
				'order'        => 51,
				'callback'     => [ 'NGTAI_Settings_Page', 'render' ],
				'dependencies' => [ 'NGTAI_Settings_Page' ],
			],
			[
				'slug'         => 'ngtai-health',
				'title'        => __( 'AI Health', 'nextgencompanion' ),
				'module'       => 'ai',
				'category'     => 'ai',
				'order'        => 53,
				'callback'     => [ 'NGTAI_Health_Page', 'render' ],
				'dependencies' => [ 'NGTAI_Health_Page' ],
			],
			[
				'slug'         => 'ngtai-events',
				'title'        => __( 'AI Events', 'nextgencompanion' ),
				'module'       => 'ai',
				'category'     => 'ai',
				'order'        => 54,
				'callback'     => [ 'NGTAI_Events_Page', 'render' ],
				'dependencies' => [ 'NGTAI_Events_Page' ],
			],
			[
				'slug'         => 'ngtai-approvals',
				'title'        => __( 'AI Approvals', 'nextgencompanion' ),
				'module'       => 'ai',
				'category'     => 'ai',
				'order'        => 56,
				'callback'     => [ 'NGTAI_Approvals_Page', 'render' ],
				'dependencies' => [ 'NGTAI_Approvals_Page' ],
				'badge_key'    => 'ai_approvals',
			],
			[
				'slug'         => 'ngtai-agent-ops',
				'title'        => __( 'AI Agent Ops', 'nextgencompanion' ),
				'module'       => 'ai',
				'category'     => 'ai',
				'order'        => 57,
				'callback'     => [ 'NGTAI_Agent_Ops_Page', 'render' ],
				'dependencies' => [ 'NGTAI_Agent_Ops_Page' ],
			],
			// UI Library
			[
				'slug'         => 'ngt-ui-library',
				'title'        => __( 'UI Library', 'nextgencompanion' ),
				'module'       => 'content',
				'category'     => 'content',
				'order'        => 85,
				'callback'     => [ 'NGT_UI_Admin', 'render_registry' ],
				'dependencies' => [ 'NGT_UI_Admin' ],
			],
			[
				'slug'         => 'ngt-ui-preview',
				'title'        => __( 'UI Preview', 'nextgencompanion' ),
				'module'       => 'content',
				'category'     => 'content',
				'order'        => 86,
				'callback'     => [ 'NGT_UI_Admin', 'render_preview' ],
				'dependencies' => [ 'NGT_UI_Admin' ],
			],
			[
				'slug'         => 'ngt-ui-diagnostics',
				'title'        => __( 'UI Diagnostics', 'nextgencompanion' ),
				'module'       => 'content',
				'category'     => 'content',
				'order'        => 87,
				'callback'     => [ 'NGT_UI_Admin', 'render_diagnostics' ],
				'dependencies' => [ 'NGT_UI_Admin' ],
			],
			// Enterprise admin — Theme Designer + Component Library
			[
				'slug'     => 'ngt-admin-theme-designer',
				'title'    => __( 'Theme Designer', 'nextgencompanion' ),
				'module'   => 'settings',
				'category' => 'administration',
				'order'    => 200,
				'icon'     => 'dashicons-art',
				'callback' => [ 'NGC_Admin_Theme', 'render_designer' ],
				'keywords' => [ 'theme', 'tokens', 'branding', 'typography' ],
			],
			[
				'slug'     => 'ngt-admin-components',
				'title'    => __( 'Admin UI Components', 'nextgencompanion' ),
				'module'   => 'platform',
				'category' => 'development',
				'order'    => 210,
				'icon'     => 'dashicons-screenoptions',
				'callback' => [ 'NGC_Admin_Components', 'render_library' ],
				'keywords' => [ 'components', 'library', 'preview' ],
			],
			// Education drill-down placeholders (nav hierarchy)
			[
				'slug'        => 'ngt-edu-students',
				'title'       => __( 'Students', 'nextgencompanion' ),
				'module'      => 'students',
				'category'    => 'education',
				'order'       => 11,
				'placeholder' => true,
				'callback'    => [ 'NGC_Admin_Layout', 'render_placeholder' ],
				'keywords'    => [ 'students', 'directory' ],
			],
			[
				'slug'        => 'ngt-edu-student-directory',
				'title'       => __( 'Student Directory', 'nextgencompanion' ),
				'module'      => 'students',
				'category'    => 'education',
				'order'       => 12,
				'nav_parent'  => 'ngt-edu-students',
				'placeholder' => true,
				'callback'    => [ 'NGC_Admin_Layout', 'render_placeholder' ],
			],
			[
				'slug'        => 'ngt-edu-attendance',
				'title'       => __( 'Attendance', 'nextgencompanion' ),
				'module'      => 'students',
				'category'    => 'education',
				'order'       => 13,
				'nav_parent'  => 'ngt-edu-students',
				'placeholder' => true,
				'callback'    => [ 'NGC_Admin_Layout', 'render_placeholder' ],
			],
			[
				'slug'        => 'ngt-edu-assessments',
				'title'       => __( 'Assessments', 'nextgencompanion' ),
				'module'      => 'students',
				'category'    => 'education',
				'order'       => 14,
				'nav_parent'  => 'ngt-edu-students',
				'placeholder' => true,
				'callback'    => [ 'NGC_Admin_Layout', 'render_placeholder' ],
			],
			[
				'slug'        => 'ngt-edu-certificates',
				'title'       => __( 'Certificates', 'nextgencompanion' ),
				'module'      => 'students',
				'category'    => 'education',
				'order'       => 15,
				'nav_parent'  => 'ngt-edu-students',
				'placeholder' => true,
				'callback'    => [ 'NGC_Admin_Layout', 'render_placeholder' ],
			],
			[
				'slug'        => 'ngt-edu-parents',
				'title'       => __( 'Parents', 'nextgencompanion' ),
				'module'      => 'parents',
				'category'    => 'education',
				'order'       => 16,
				'placeholder' => true,
				'callback'    => [ 'NGC_Admin_Layout', 'render_placeholder' ],
			],
			[
				'slug'        => 'ngt-edu-lessons',
				'title'       => __( 'Lessons', 'nextgencompanion' ),
				'module'      => 'bookings',
				'category'    => 'education',
				'order'       => 17,
				'placeholder' => true,
				'callback'    => [ 'NGC_Admin_Layout', 'render_placeholder' ],
			],
			[
				'slug'        => 'ngt-edu-subjects',
				'title'       => __( 'Subjects', 'nextgencompanion' ),
				'module'      => 'tutors',
				'category'    => 'education',
				'order'       => 18,
				'placeholder' => true,
				'callback'    => [ 'NGC_Admin_Layout', 'render_placeholder' ],
			],
		];

		foreach ( $screens as $screen ) {
			NGC_Admin_Registry::register_screen( $screen );
		}
	}

	/**
	 * Badge providers.
	 */
	private static function register_badges() {
		NGC_Admin_Registry::register_badge_provider(
			'tutor_applications',
			static function () {
				if ( ! class_exists( 'NGC_Marketplace' ) ) {
					return 0;
				}
				if ( method_exists( 'NGC_Marketplace', 'count_pending_applications' ) ) {
					return (int) NGC_Marketplace::count_pending_applications();
				}
				return 0;
			}
		);
		NGC_Admin_Registry::register_badge_provider(
			'errors',
			static function () {
				if ( class_exists( 'NGC_Exception_Log' ) && method_exists( 'NGC_Exception_Log', 'open_count' ) ) {
					return (int) NGC_Exception_Log::open_count();
				}
				return 0;
			}
		);
		NGC_Admin_Registry::register_badge_provider(
			'ai_approvals',
			static function () {
				if ( class_exists( 'NGTAI_Approvals' ) && method_exists( 'NGTAI_Approvals', 'pending_count' ) ) {
					return (int) NGTAI_Approvals::pending_count();
				}
				return 0;
			}
		);
	}
}
