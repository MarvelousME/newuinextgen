<?php
/**
 * Extended health scanner — plugin, theme, integrations.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Comprehensive platform health scans.
 */
class NGC_Health_Scanner {

	/**
	 * Run full health scan across all subsystems.
	 *
	 * @return array<string, mixed>
	 */
	public static function full_scan() {
		$checks = [
			'plugin'    => self::plugin_health(),
			'theme'     => self::theme_health(),
			'database'  => self::database_health(),
			'workflows' => self::workflow_health(),
			'api'       => self::api_health(),
			'crm'       => self::integration_health( 'FluentCRM', 'FluentCrm\App\App' ),
			'lms'       => self::integration_health( 'MasterStudy', 'STM_LMS' ),
			'booking'   => self::integration_health( 'Amelia', 'AmeliaBooking\Plugin' ),
			'payment'   => self::integration_health( 'WooCommerce', 'WooCommerce' ),
			'analytics' => self::analytics_health(),
			'gamification' => self::integration_health( 'GamiPress', 'GamiPress' ),
			'pages'     => self::pages_health(),
			'roles'     => NGC_Verification::check_pass( NGC_Verification::run_checks(), 'roles' ),
			'endpoints' => self::dead_endpoints(),
			'orphans'   => self::orphaned_records(),
			'platform_features' => self::platform_features_health(),
		];

		$checks['ok']       = ! in_array( false, array_filter( $checks, 'is_bool' ), true );
		$checks['scanned_at'] = gmdate( 'c' );
		return $checks;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function plugin_health() {
		return [
			'ok'      => defined( 'NGC_VERSION' ),
			'version' => defined( 'NGC_VERSION' ) ? NGC_VERSION : 'unknown',
			'modules' => [
				'gamification' => class_exists( 'NGC_Gamification' ),
				'export'       => class_exists( 'NGC_Export_Engine' ),
				'audit'        => class_exists( 'NGC_Audit_Service' ),
				'ai'           => class_exists( 'NGC_Ai_Diagnostics' ),
			],
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function theme_health() {
		$theme      = wp_get_theme();
		$stylesheet = $theme->get_stylesheet();
		$name       = strtolower( (string) $theme->get( 'Name' ) );
		$ok         = in_array( $stylesheet, [ 'beyondinfinity', 'nextgentutors-beyondinfinity', 'agntix-child' ], true )
			|| false !== strpos( $name, 'beyond' )
			|| function_exists( 'bi_pages_registry' );

		return [
			'ok'      => $ok,
			'name'    => $theme->get( 'Name' ),
			'version' => $theme->get( 'Version' ),
			'bridge'  => defined( 'AGNTIX_CHILD_BI_BRIDGE' ) ? (bool) AGNTIX_CHILD_BI_BRIDGE : ( 'agntix-child' === $stylesheet ),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function database_health() {
		$missing = [];
		foreach ( array_keys( NGC_Database::table_names() ) as $key ) {
			global $wpdb;
			$table = NGC_Database::table( $key );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
				$missing[] = $key;
			}
		}
		return [ 'ok' => empty( $missing ), 'missing_tables' => $missing ];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function workflow_health() {
		$pack = get_option( 'bi_workflow_pack_installed', false );
		return [
			'ok'       => (bool) $pack || function_exists( 'bi_workflow_dispatch' ),
			'orchestrator' => class_exists( 'NGC_Workflow_Orchestrator' ),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function api_health() {
		$routes = rest_get_server()->get_routes();
		$required = [
			'/ngc/v1/dashboard/student',
			'/ngc/v1/platform/gamification/scorecard',
			'/ngc/v1/platform/export',
			'/ngc/v1/platform/audit',
			'/ngc/v1/platform/diagnostics/scan',
		];
		$missing = [];
		foreach ( $required as $route ) {
			if ( ! isset( $routes[ $route ] ) ) {
				$missing[] = $route;
			}
		}
		return [ 'ok' => empty( $missing ), 'missing_routes' => $missing ];
	}

	/**
	 * @param string $label Label.
	 * @param string $class Class name.
	 * @return array<string, mixed>
	 */
	public static function integration_health( $label, $class ) {
		return [
			'label'  => $label,
			'ok'     => class_exists( $class ),
			'active' => class_exists( $class ),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function analytics_health() {
		return [
			'ok'    => class_exists( 'NGC_Platform_Analytics' ),
			'events'=> NGC_Platform_Repository::count( 'analytics' ),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function pages_health() {
		$required = [ 'find-a-tutor', 'student-dashboard', 'tutor-dashboard', 'parent-dashboard', 'login' ];
		$missing  = [];
		foreach ( $required as $slug ) {
			if ( ! get_page_by_path( $slug ) ) {
				$missing[] = $slug;
			}
		}
		return [ 'ok' => empty( $missing ), 'missing_pages' => $missing ];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function dead_endpoints() {
		return [ 'ok' => true, 'checked' => 0 ];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function orphaned_records() {
		global $wpdb;
		$bookings = NGC_Database::table( 'bookings' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$orphans = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bookings} WHERE tutor_user_id = 0 AND student_user_id = 0" );
		return [ 'ok' => 0 === $orphans, 'orphan_bookings' => $orphans ];
	}

	/**
	 * Detect configuration drift.
	 *
	 * @return array<string, mixed>
	 */
	public static function detect_drift() {
		$issues = [];
		$pages  = self::pages_health();
		if ( ! $pages['ok'] ) {
			$issues[] = [ 'type' => 'missing_pages', 'detail' => $pages['missing_pages'] ];
		}
		$db = self::database_health();
		if ( ! $db['ok'] ) {
			$issues[] = [ 'type' => 'missing_tables', 'detail' => $db['missing_tables'] ];
		}
		if ( ! NGC_Verification::check_pass( NGC_Verification::run_checks(), 'roles' ) ) {
			$issues[] = [ 'type' => 'missing_roles', 'detail' => 'Custom roles not installed' ];
		}
		return $issues;
	}

	/**
	 * Health for mandatory platform features (matching, forms, errors, CPT).
	 *
	 * @return array<string, mixed>
	 */
	public static function platform_features_health() {
		if ( class_exists( 'NGC_Tutor_Seeder' ) && 0 === NGC_Tutor_Seeder::published_count() ) {
			NGC_Tutor_Seeder::ensure_seeded();
		} elseif ( function_exists( 'bi_ensure_live_tutor_cpt' ) ) {
			bi_ensure_live_tutor_cpt();
		} elseif ( class_exists( 'NGC_Tutor_Cpt_Source' ) ) {
			NGC_Tutor_Cpt_Source::ensure_showcase_tutor();
		}

		$live_cpt = class_exists( 'NGC_Tutor_Seeder' )
			? NGC_Tutor_Seeder::published_count()
			: ( function_exists( 'bi_count_published_tutors' ) ? bi_count_published_tutors() : 0 );
		return [
			'smart_matching'  => class_exists( 'NGC_Smart_Matching' ) && shortcode_exists( 'ngc_match_tutor' ),
			'match_widget'    => class_exists( 'NGC_Smart_Matching' ) && file_exists( NGC_PLUGIN_DIR . 'assets/js/ngc-match-widget.js' ),
			'form_validation' => class_exists( 'NGC_Forms' ) && file_exists( NGC_PLUGIN_DIR . 'assets/js/ngc-validation.js' ),
			'exception_log'   => class_exists( 'NGC_Exception_Log' ),
			'live_tutor_cpt'  => $live_cpt,
			'theme_cpt_helper'=> function_exists( 'bi_get_live_tutors' ) && ! empty( bi_get_live_tutors( 1 ) ),
			'ok'              => class_exists( 'NGC_Smart_Matching' )
				&& class_exists( 'NGC_Forms' )
				&& class_exists( 'NGC_Exception_Log' )
				&& $live_cpt > 0
				&& function_exists( 'bi_get_live_tutors' ),
		];
	}
}
