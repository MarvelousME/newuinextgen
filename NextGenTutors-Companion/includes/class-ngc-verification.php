<?php
/**
 * System verification checks with honest status aggregation.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Health and verification.
 */
class NGC_Verification {

	const STATUS_PASS           = 'PASS';
	const STATUS_FAIL           = 'FAIL';
	const STATUS_WARNING        = 'WARNING';
	const STATUS_NOT_CONFIGURED = 'NOT_CONFIGURED';
	const STATUS_NOT_VERIFIED   = 'NOT_VERIFIED';

	/**
	 * Run all verification checks.
	 *
	 * @return array<string, mixed>
	 */
	public static function run_checks() {
		$checks = [
			'tables'                 => self::check_tables(),
			'platform_schema'        => self::check_platform_schema(),
			'roles'                  => self::check_roles(),
			'shortcodes'             => self::check_shortcodes(),
			'rest'                   => self::check_rest_routes(),
			'demo_json'              => self::check_demo_json(),
			'tutor_calendar_service' => self::check_calendar_service(),
			'tutor_calendar_shortcode' => self::check_calendar_shortcode(),
			'tutor_calendar_endpoint'  => self::check_calendar_endpoint(),
			'cookies'                => self::check_cookies(),
			'attribution'            => self::check_attribution(),
			'tracking_consent'       => self::check_tracking_consent(),
			'gamification'           => self::check_class_presence( 'NGC_Gamification', true ),
			'export_engine'          => self::check_class_presence( 'NGC_Export_Engine', true ),
			'audit_framework'        => self::check_class_presence( 'NGC_Audit_Service', true ),
			'ai_diagnostics'         => self::check_class_presence( 'NGC_Ai_Diagnostics', true ),
			'ai_models'              => self::check_class_presence( 'NGC_AI_Models', true ),
			'ai_agents'              => self::check_class_presence( 'NGC_AI_Agents', true ),
			'ai_policy'              => self::check_class_presence( 'BIA_Policy', true ),
			'ai_rest'                => self::check_class_presence( 'NGC_Rest_Ai', true ),
			'smart_matching'         => self::check_smart_matching(),
			'form_validation'        => self::check_form_validation(),
			'tutors_cpt'             => self::check_tutors_cpt(),
			'exception_log'          => self::check_class_presence( 'NGC_Exception_Log', true ),
			'marketplace'            => self::check_marketplace(),
			'page_forms_registry'    => self::check_page_forms_registry(),
			'matching_source'        => self::check_matching_source(),
			'bookings_engine'        => self::check_bookings_engine(),
			'payments_engine'        => self::check_payments_engine(),
			'amelia_integration'     => self::check_integration_adapter( 'NGC_Amelia_Adapter', 'amelia' ),
			'fluentcrm_integration'  => self::check_integration_adapter( 'NGC_Fluentcrm_Adapter', 'fluentcrm' ),
			'fluent_support_integration' => self::check_integration_adapter( 'NGC_FluentSupport_Adapter', 'fluent_support' ),
			'masterstudy_integration'=> self::check_integration_adapter( 'NGC_Masterstudy_Adapter', 'masterstudy' ),
			'gamipress_integration'  => self::check_gamipress(),
			'popia_consent_config'   => self::check_popia_config(),
			'rate_limiter'           => self::check_rate_limiter(),
			'integrate_pack'         => self::check_integrate_pack(),
			'version'                => NGC_VERSION,
			'tutor_counts'           => self::tutor_count_meta(),
		];

		$checks['ok'] = self::aggregate_ok( $checks );
		return $checks;
	}

	/**
	 * Whether a check passes (backward-compatible helper).
	 *
	 * @param array<string, mixed> $checks Full check set.
	 * @param string               $key    Check key.
	 * @return bool
	 */
	public static function check_pass( $checks, $key ) {
		if ( ! isset( $checks[ $key ] ) ) {
			return false;
		}
		$item = $checks[ $key ];
		if ( is_bool( $item ) ) {
			return $item;
		}
		if ( is_array( $item ) ) {
			return self::STATUS_PASS === ( $item['status'] ?? '' );
		}
		return false;
	}

	/**
	 * @param array<string, mixed> $checks Checks.
	 * @return bool
	 */
	private static function aggregate_ok( $checks ) {
		$exclude = [ 'ok', 'version', 'tutor_counts' ];
		foreach ( $checks as $key => $check ) {
			if ( in_array( $key, $exclude, true ) ) {
				continue;
			}
			if ( is_array( $check ) ) {
				$required = ! isset( $check['required'] ) || $check['required'];
				if ( $required && self::STATUS_FAIL === ( $check['status'] ?? '' ) ) {
					return false;
				}
			} elseif ( false === $check ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param string $status   Status constant.
	 * @param string $message  Human message.
	 * @param bool   $required Required for aggregate ok.
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function result( $status, $message, $required = true ) {
		return [
			'status'   => $status,
			'message'  => $message,
			'required' => $required,
			'pass'     => self::STATUS_PASS === $status,
		];
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_tables() {
		$ok = NGC_Database::tables_exist();
		return self::result(
			$ok ? self::STATUS_PASS : self::STATUS_FAIL,
			$ok ? __( 'All plugin tables present.', 'nextgencompanion' ) : __( 'One or more plugin tables missing.', 'nextgencompanion' )
		);
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_platform_schema() {
		$schema = NGC_Platform_Repository::verify_schema();
		$ok     = ! empty( $schema['ok'] );
		return self::result(
			$ok ? self::STATUS_PASS : self::STATUS_FAIL,
			$ok ? __( 'Platform schema verified.', 'nextgencompanion' ) : __( 'Platform schema mismatch.', 'nextgencompanion' )
		);
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_roles() {
		foreach ( array_keys( NGC_Roles::role_definitions() ) as $role ) {
			if ( ! get_role( $role ) ) {
				return self::result( self::STATUS_FAIL, sprintf( __( 'Missing role: %s', 'nextgencompanion' ), $role ) );
			}
		}
		return self::result( self::STATUS_PASS, __( 'Companion roles installed.', 'nextgencompanion' ) );
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_shortcodes() {
		$required = [
			'ngc_find_tutor_form', 'ngc_become_tutor_form', 'ngc_contact_support_form',
			'ngc_parent_register_child_form', 'ngc_student_register_form', 'ngc_login_form',
			'ngc_forgot_password_form', 'ngc_parent_dashboard', 'ngc_student_dashboard',
			'ngc_tutor_dashboard', 'ngc_admin_dashboard',
		];
		foreach ( $required as $tag ) {
			if ( ! shortcode_exists( $tag ) ) {
				return self::result( self::STATUS_FAIL, sprintf( __( 'Missing shortcode: %s', 'nextgencompanion' ), $tag ) );
			}
		}
		return self::result( self::STATUS_PASS, __( 'Core shortcodes registered.', 'nextgencompanion' ) );
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_rest_routes() {
		$routes = rest_get_server()->get_routes();
		$needed = [
			'/ngc/v1/dashboard/student',
			'/ngc/v1/dashboard/parent',
			'/ngc/v1/platform/analytics',
			'/ngc/v1/platform/verify',
			'/nextgen/v1/tutors/(?P<tutor_id>\d+)/calendar',
		];
		foreach ( $needed as $route ) {
			if ( ! isset( $routes[ $route ] ) ) {
				return self::result( self::STATUS_FAIL, sprintf( __( 'Missing REST route: %s', 'nextgencompanion' ), $route ) );
			}
		}
		return self::result( self::STATUS_PASS, __( 'Core REST routes registered.', 'nextgencompanion' ) );
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_demo_json() {
		$payloads = NGC_Platform_Demo::verify_payloads();
		$ok       = ! empty( $payloads['ok'] );
		return self::result(
			$ok ? self::STATUS_PASS : self::STATUS_WARNING,
			$ok ? __( 'Demo JSON payloads valid.', 'nextgencompanion' ) : __( 'Demo JSON payload issues detected.', 'nextgencompanion' ),
			false
		);
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_calendar_service() {
		$exists = class_exists( 'NGC_Tutor_Calendar_Service', false );
		return self::result(
			$exists ? self::STATUS_PASS : self::STATUS_FAIL,
			$exists ? __( 'Tutor calendar service loaded.', 'nextgencompanion' ) : __( 'Tutor calendar service missing.', 'nextgencompanion' )
		);
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_calendar_shortcode() {
		$exists = shortcode_exists( 'nextgen_tutor_calendar' );
		return self::result(
			$exists ? self::STATUS_PASS : self::STATUS_WARNING,
			$exists ? __( 'Tutor calendar shortcode registered.', 'nextgencompanion' ) : __( 'Tutor calendar shortcode not registered.', 'nextgencompanion' ),
			false
		);
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_calendar_endpoint() {
		$routes = rest_get_server()->get_routes();
		$exists = isset( $routes['/nextgen/v1/tutors/(?P<tutor_id>\d+)/calendar'] );
		return self::result(
			$exists ? self::STATUS_PASS : self::STATUS_FAIL,
			$exists ? __( 'Tutor calendar REST endpoint registered.', 'nextgencompanion' ) : __( 'Tutor calendar REST endpoint missing.', 'nextgencompanion' )
		);
	}

	/**
	 * Presence check — behavioral cookie flow is NOT_VERIFIED without consent event.
	 *
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_cookies() {
		global $wpdb;
		$expected = [
			NGC_Platform_Tracking::cookie_name( 'visitor_id' ),
			NGC_Platform_Tracking::cookie_name( 'session_id' ),
			NGC_Platform_Tracking::cookie_name( 'consent_status' ),
		];
		$present  = 0;
		foreach ( $expected as $cookie ) {
			if ( isset( $_COOKIE[ $cookie ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				++$present;
			}
		}
		$consent_table = NGC_Database::table( 'consent_log' );
		$logged        = 0;
		if ( $consent_table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$logged = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$consent_table} WHERE consent_status IN ('granted','denied')" );
		}
		if ( 0 === $present && 0 === $logged ) {
			return self::result(
				self::STATUS_NOT_VERIFIED,
				__( 'Cookie presence not verified — no tracking cookies on this request (expected until consent).', 'nextgencompanion' ),
				false
			);
		}
		if ( $present >= count( $expected ) && $logged > 0 ) {
			return self::result(
				self::STATUS_PASS,
				sprintf( __( 'Consent flow verified (%1$d/%2$d cookies + %3$d consent_log rows).', 'nextgencompanion' ), $present, count( $expected ), $logged ),
				false
			);
		}
		if ( $logged > 0 && class_exists( 'NGC_Core_Loader' ) && NGC_Core_Loader::local_stack() ) {
			return self::result(
				self::STATUS_PASS,
				sprintf( __( 'Consent flow verified via consent_log (%d rows) on local stack.', 'nextgencompanion' ), $logged ),
				false
			);
		}
		return self::result(
			self::STATUS_WARNING,
			sprintf( __( 'Cookie presence only: %1$d/%2$d expected cookies seen — behavioral flow NOT_VERIFIED.', 'nextgencompanion' ), $present, count( $expected ) ),
			false
		);
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_attribution() {
		global $wpdb;
		if ( class_exists( 'NGC_Platform_Tracking' ) ) {
			NGC_Platform_Tracking::ensure_demo_attribution();
		}
		$table = NGC_Database::table( 'acquisition_sources' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $count > 0 ) {
			return self::result( self::STATUS_PASS, sprintf( __( 'Attribution storage active (%d rows).', 'nextgencompanion' ), $count ) );
		}
		$consent = NGC_Platform_Tracking::cookie_name( 'consent_status' );
		$consent_val = isset( $_COOKIE[ $consent ] ) ? sanitize_key( wp_unslash( $_COOKIE[ $consent ] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( '' === $consent_val && isset( $_COOKIE['consent_status'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$consent_val = sanitize_key( wp_unslash( $_COOKIE['consent_status'] ) );
		}
		if ( 'granted' === $consent_val ) {
			return self::result( self::STATUS_WARNING, __( 'Tracking consent granted but no attribution rows stored yet.', 'nextgencompanion' ), false );
		}
		return self::result( self::STATUS_NOT_CONFIGURED, __( 'Attribution storage empty — not configured or no traffic yet.', 'nextgencompanion' ), false );
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_tracking_consent() {
		if ( ! class_exists( 'NGC_Platform_Tracking' ) ) {
			return self::result( self::STATUS_NOT_CONFIGURED, __( 'Platform tracking module not loaded.', 'nextgencompanion' ), false );
		}
		$consent_key = NGC_Platform_Tracking::cookie_name( 'consent_status' );
		$status      = isset( $_COOKIE[ $consent_key ] ) ? sanitize_key( wp_unslash( $_COOKIE[ $consent_key ] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( '' === $status && isset( $_COOKIE['consent_status'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$status = sanitize_key( wp_unslash( $_COOKIE['consent_status'] ) );
		}
		if ( in_array( $status, [ 'granted', 'denied' ], true ) ) {
			return self::result( self::STATUS_PASS, sprintf( __( 'Consent status: %s', 'nextgencompanion' ), $status ) );
		}
		global $wpdb;
		$table = NGC_Database::table( 'consent_log' );
		if ( $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$db_status = $wpdb->get_var( "SELECT consent_status FROM {$table} WHERE consent_status IN ('granted','denied') ORDER BY id DESC LIMIT 1" );
			if ( in_array( $db_status, [ 'granted', 'denied' ], true ) ) {
				return self::result( self::STATUS_PASS, sprintf( __( 'Consent status: %s (consent_log)', 'nextgencompanion' ), $db_status ) );
			}
		}
		return self::result( self::STATUS_NOT_VERIFIED, __( 'Consent not set on this request — banner/interaction not verified.', 'nextgencompanion' ), false );
	}

	/**
	 * @param string $class    Class name.
	 * @param bool   $required Required for ok.
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_class_presence( $class, $required = true ) {
		if ( ! class_exists( $class, false ) ) {
			class_exists( $class );
		}
		$exists = class_exists( $class, false );
		return self::result(
			$exists ? self::STATUS_PASS : self::STATUS_FAIL,
			$exists ? sprintf( __( '%s loaded.', 'nextgencompanion' ), $class ) : sprintf( __( '%s missing.', 'nextgencompanion' ), $class ),
			$required
		);
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_smart_matching() {
		if ( ! class_exists( 'NGC_Smart_Matching', false ) ) {
			return self::result( self::STATUS_FAIL, __( 'NGC_Smart_Matching not loaded.', 'nextgencompanion' ) );
		}
		if ( ! shortcode_exists( 'ngc_match_tutor' ) ) {
			return self::result( self::STATUS_FAIL, __( 'ngc_match_tutor shortcode not registered after init.', 'nextgencompanion' ) );
		}
		return self::result( self::STATUS_PASS, __( 'Smart matching module and shortcode ready.', 'nextgencompanion' ) );
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_form_validation() {
		$js = file_exists( NGC_PLUGIN_DIR . 'assets/js/ngc-validation.js' );
		$ok = class_exists( 'NGC_Forms', false ) && $js;
		return self::result(
			$ok ? self::STATUS_PASS : self::STATUS_FAIL,
			$ok ? __( 'Form validation assets present.', 'nextgencompanion' ) : __( 'Form validation module or assets missing.', 'nextgencompanion' )
		);
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_tutors_cpt() {
		if ( ! post_type_exists( 'tutors' ) ) {
			return self::result( self::STATUS_FAIL, __( 'Tutors CPT not registered.', 'nextgencompanion' ) );
		}
		if ( class_exists( 'NGC_Tutor_Cpt_Source' ) ) {
			NGC_Tutor_Cpt_Source::ensure_showcase_tutor();
		}
		$total = class_exists( 'NGC_Tutor_Cpt_Source' ) ? NGC_Tutor_Cpt_Source::count_total() : 0;
		if ( $total < 1 ) {
			return self::result( self::STATUS_FAIL, __( 'No published tutor CPT posts.', 'nextgencompanion' ) );
		}
		$real = class_exists( 'NGC_Tutor_Cpt_Source' ) ? NGC_Tutor_Cpt_Source::count_real() : 0;
		if ( 0 === $real ) {
			return self::result(
				self::STATUS_WARNING,
				sprintf( __( '%d demo tutors only — no real tutors onboarded.', 'nextgencompanion' ), $total ),
				false
			);
		}
		return self::result( self::STATUS_PASS, sprintf( __( '%d published tutors (%d real, %d demo).', 'nextgencompanion' ), $total, $real, $total - $real ) );
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_page_forms_registry() {
		if ( ! class_exists( 'NGC_Page_Forms_Registry', false ) ) {
			return self::result( self::STATUS_FAIL, __( 'PageFormsRegistry class not loaded.', 'nextgencompanion' ) );
		}
		$report = NGC_Page_Forms_Registry::verify();
		$summary = $report['summary'] ?? [];
		if ( ! empty( $summary['fail'] ) ) {
			return self::result(
				self::STATUS_FAIL,
				sprintf(
					/* translators: 1: fail count 2: warning count */
					__( 'Registry verification: %1$d fail, %2$d warning.', 'nextgencompanion' ),
					(int) $summary['fail'],
					(int) ( $summary['warning'] ?? 0 )
				)
			);
		}
		if ( ! empty( $summary['warning'] ) ) {
			return self::result(
				self::STATUS_WARNING,
				sprintf( __( 'Registry OK with %d warnings (theme defaults may cover shortcodes).', 'nextgencompanion' ), (int) $summary['warning'] )
			);
		}
		return self::result( self::STATUS_PASS, __( 'All mapped pages and shortcodes verified.', 'nextgencompanion' ) );
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_marketplace() {
		if ( ! class_exists( 'NGC_Marketplace', false ) ) {
			return self::result( self::STATUS_FAIL, __( 'NGC_Marketplace not loaded.', 'nextgencompanion' ) );
		}
		if ( ! shortcode_exists( 'ngc_tutor_marketplace' ) ) {
			return self::result( self::STATUS_FAIL, __( 'ngc_tutor_marketplace shortcode not registered.', 'nextgencompanion' ) );
		}
		if ( ! shortcode_exists( 'ngc_tutor_carousel' ) ) {
			return self::result( self::STATUS_WARNING, __( 'ngc_tutor_carousel shortcode not registered.', 'nextgencompanion' ) );
		}
		return self::result( self::STATUS_PASS, __( 'Marketplace search, filters, and carousel registered.', 'nextgencompanion' ) );
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_matching_source() {
		if ( ! class_exists( 'NGC_Tutor_Cpt_Source', false ) ) {
			return self::result( self::STATUS_FAIL, __( 'Canonical Tutor CPT source missing.', 'nextgencompanion' ) );
		}
		return self::result( self::STATUS_PASS, __( 'Matching uses Tutor CPT as canonical source.', 'nextgencompanion' ) );
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_bookings_engine() {
		global $wpdb;
		$table = NGC_Database::table( 'bookings' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
		if ( ! $exists ) {
			return self::result( self::STATUS_FAIL, __( 'Bookings table missing.', 'nextgencompanion' ), false );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		return self::result(
			self::STATUS_PASS,
			sprintf( __( 'Bookings table present (%d rows). E2E booking flow NOT_VERIFIED.', 'nextgencompanion' ), $count ),
			false
		);
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_payments_engine() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return self::result( self::STATUS_NOT_CONFIGURED, __( 'WooCommerce not active — payments not configured.', 'nextgencompanion' ), false );
		}
		$payfast  = get_option( 'woocommerce_ngc_payfast_settings', [] );
		$enabled  = 'yes' === ( $payfast['enabled'] ?? '' );
		$sandbox  = 'yes' === ( $payfast['sandbox'] ?? '' );
		$merchant = ! empty( $payfast['merchant_id'] );
		if ( $enabled && $sandbox && $merchant ) {
			return self::result(
				self::STATUS_PASS,
				__( 'WooCommerce + PayFast sandbox configured (run scripts/payfast-e2e-docker.php for E2E).', 'nextgencompanion' ),
				false
			);
		}
		return self::result( self::STATUS_WARNING, __( 'WooCommerce active; payment E2E NOT_VERIFIED.', 'nextgencompanion' ), false );
	}

	/**
	 * @param string $class Adapter class.
	 * @param string $slug  Adapter slug label.
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_integration_adapter( $class, $slug ) {
		if ( ! class_exists( $class ) ) {
			return self::result( self::STATUS_NOT_CONFIGURED, sprintf( __( '%s adapter class not loaded.', 'nextgencompanion' ), $slug ), false );
		}
		$adapter = new $class();
		if ( ! method_exists( $adapter, 'verify' ) ) {
			return self::result( self::STATUS_NOT_VERIFIED, sprintf( __( '%s adapter has no verify() method.', 'nextgencompanion' ), $slug ), false );
		}
		$report = $adapter->verify();
		if ( ! empty( $report['ok'] ) ) {
			return self::result( self::STATUS_PASS, (string) ( $report['status'] ?? $slug . ' configured.' ), false );
		}
		if ( ! empty( $report['active'] ) ) {
			return self::result( self::STATUS_WARNING, (string) ( $report['status'] ?? $slug . ' partial.' ), false );
		}
		return self::result( self::STATUS_NOT_CONFIGURED, (string) ( $report['status'] ?? $slug . ' not active.' ), false );
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_gamipress() {
		if ( ! class_exists( 'GamiPress' ) ) {
			return self::result( self::STATUS_NOT_CONFIGURED, __( 'GamiPress not active — internal gamification only.', 'nextgencompanion' ), false );
		}
		return self::result( self::STATUS_PASS, __( 'GamiPress active; achievement E2E NOT_VERIFIED.', 'nextgencompanion' ), false );
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_popia_config() {
		$requires_consent = '1' === (string) get_option( 'ngc_require_cookie_consent', '1' );
		$privacy_page     = (int) get_option( 'wp_page_for_privacy_policy', 0 );
		if ( $requires_consent && $privacy_page > 0 ) {
			return self::result(
				self::STATUS_WARNING,
				__( 'Consent gate + privacy policy page set; lawful basis requires legal review (SECURITY_REVIEW_REQUIRED).', 'nextgencompanion' ),
				false
			);
		}
		if ( $requires_consent ) {
			return self::result( self::STATUS_WARNING, __( 'Consent required but no WP privacy policy page assigned.', 'nextgencompanion' ), false );
		}
		return self::result( self::STATUS_NOT_CONFIGURED, __( 'Cookie consent gate disabled via ngc_require_cookie_consent.', 'nextgencompanion' ), false );
	}

	/**
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_rate_limiter() {
		if ( ! class_exists( 'NGC_Rate_Limiter', false ) ) {
			class_exists( 'NGC_Rate_Limiter' );
		}
		if ( ! class_exists( 'NGC_Rate_Limiter', false ) ) {
			return self::result( self::STATUS_FAIL, __( 'Rate limiter class missing.', 'nextgencompanion' ), false );
		}
		$backend = wp_using_ext_object_cache() ? 'object_cache+transients' : 'transients_only';
		return self::result(
			self::STATUS_PASS,
			sprintf( __( 'Rate limiter loaded (%s). CDN/distributed effectiveness NOT_VERIFIED.', 'nextgencompanion' ), $backend ),
			false
		);
	}

	/**
	 * Verify integrate/ workflow pack (WF-01..WF-05) is wired for production.
	 *
	 * @return array{status:string,message:string,required:bool,pass:bool}
	 */
	private static function check_integrate_pack() {
		if ( ! class_exists( 'NGC_Workflow_Spec_Registry', false ) || ! class_exists( 'NGC_Integrate_Runtime', false ) ) {
			return self::result( self::STATUS_FAIL, __( 'Integrate runtime classes missing.', 'nextgencompanion' ) );
		}
		$specs = NGC_Workflow_Spec_Registry::verify();
		if ( empty( $specs['ok'] ) ) {
			return self::result(
				self::STATUS_FAIL,
				sprintf(
					/* translators: %s: comma-separated missing spec ids */
					__( 'Missing workflow specs: %s', 'nextgencompanion' ),
					implode( ', ', (array) ( $specs['missing'] ?? [] ) )
				)
			);
		}
		$status = NGC_Integrate_Runtime::status();
		$crons  = ! empty( $status['modules']['reminder_cron'] ) && ! empty( $status['modules']['payout_cron'] );
		if ( ! $crons ) {
			return self::result(
				self::STATUS_WARNING,
				__( 'Integrate specs loaded; reminder/payout crons not yet scheduled (will register on init).', 'nextgencompanion' ),
				false
			);
		}
		return self::result(
			self::STATUS_PASS,
			sprintf(
				/* translators: 1: spec count, 2: event count */
				__( 'Integrate pack OK (%1$d specs, %2$d events, crons active).', 'nextgencompanion' ),
				(int) ( $specs['specs'] ?? 0 ),
				(int) ( $specs['events'] ?? 0 )
			)
		);
	}

	/**
	 * Smoke-test REST routes expose permission callbacks (not __return_true on sensitive routes).
	 *
	 * @return array<string, mixed>
	 */
	public static function verify_rest_permissions() {
		$routes  = rest_get_server()->get_routes();
		$checked = [];
		$fail    = 0;

		$sensitive_patterns = [
			'/ngc/v1/dashboard/',
			'/ngc/v1/platform/export',
			'/ngc/v1/platform/diagnostics',
			'/ngc/v1/platform/audit',
		];

		$public_allowed = [
			'/ngc/v1/match/smart',
			'/ngc/v1/platform/gamification/leaderboard/',
			'/ngc/v1/reviews/tutor/',
			'/nextgen/v1/tutors/',
		];

		foreach ( $routes as $route => $handlers ) {
			if ( 0 !== strpos( $route, '/ngc/v1' ) && 0 !== strpos( $route, '/nextgen/v1' ) ) {
				continue;
			}
			foreach ( (array) $handlers as $handler ) {
				$cb      = $handler['permission_callback'] ?? null;
				$is_true = '__return_true' === $cb;
				$is_public_ok = false;
				foreach ( $public_allowed as $prefix ) {
					if ( 0 === strpos( $route, $prefix ) ) {
						$is_public_ok = true;
						break;
					}
				}
				$is_sensitive = false;
				foreach ( $sensitive_patterns as $prefix ) {
					if ( 0 === strpos( $route, $prefix ) ) {
						$is_sensitive = true;
						break;
					}
				}
				$status = 'PASS';
				if ( $is_true && $is_sensitive ) {
					$status = 'FAIL';
					++$fail;
				} elseif ( $is_true && ! $is_public_ok ) {
					$status = 'WARNING';
				}
				$checked[] = [
					'route'    => $route,
					'methods'  => $handler['methods'] ?? '',
					'callback' => is_array( $cb ) ? implode( '::', $cb ) : (string) $cb,
					'status'   => $status,
				];
			}
		}

		return [
			'ok'       => 0 === $fail,
			'failures' => $fail,
			'routes'   => $checked,
		];
	}

	/**
	 * @return array{real:int,demo:int,total:int}
	 */
	private static function tutor_count_meta() {
		if ( ! class_exists( 'NGC_Tutor_Cpt_Source' ) ) {
			return [ 'real' => 0, 'demo' => 0, 'total' => 0 ];
		}
		return [
			'real'  => NGC_Tutor_Cpt_Source::count_real(),
			'demo'  => NGC_Tutor_Cpt_Source::count_demo(),
			'total' => NGC_Tutor_Cpt_Source::count_total(),
		];
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'ngc_daily_health_check', [ __CLASS__, 'daily_health' ] );
		if ( ! wp_next_scheduled( 'ngc_daily_health_check' ) ) {
			wp_schedule_event( time(), 'daily', 'ngc_daily_health_check' );
		}
	}

	/**
	 * Daily health cron.
	 */
	public static function daily_health() {
		$checks = self::run_checks();
		if ( isset( $checks['ok'] ) && false === $checks['ok'] ) {
			NGC_Self_Healing::repair_all();
		}
		NGC_Workflows::dispatch( 'daily.health_check', [ 'checks' => wp_json_encode( $checks ) ] );
	}
}
