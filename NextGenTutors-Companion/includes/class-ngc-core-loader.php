<?php
/**
 * Eager-loads core classes that are not always triggered by module ::init().
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preloads export, AI, rate limiter, and adapter classes for health checks and REST.
 */
class NGC_Core_Loader {

	/**
	 * @var string[]
	 */
	private static $preload = [
		'NGC_Export_Engine',
		'NGC_Export_Formats',
		'NGC_Export_Scheduler',
		'NGC_Rate_Limiter',
		'NGC_AI_Models',
		'NGC_AI_Agents',
		'NGC_AI_Chat',
		'BIA_Policy',
		'NGC_Amelia_Adapter',
		'NGC_Fluentcrm_Adapter',
		'NGC_FluentSupport_Adapter',
		'NGC_Masterstudy_Adapter',
		'NGC_Gamipress_Adapter',
		'NGC_Tutor_Cpt_Source',
		'NGC_Amelia_Bootstrap',
	];

	/**
	 * Hook registration.
	 */
	public static function init() {
		self::preload_classes();
		add_action( 'init', [ __CLASS__, 'repair_local_stack' ], 99 );
	}

	/**
	 * Force autoload of critical classes (verification uses class_exists without lazy gaps).
	 */
	public static function preload_classes() {
		foreach ( self::$preload as $class ) {
			if ( class_exists( $class ) ) {
				continue;
			}
		}
		if ( class_exists( 'BIA_Policy' ) && method_exists( 'BIA_Policy', 'install' ) ) {
			BIA_Policy::install();
		}
	}

	/**
	 * Idempotent local/Docker repairs for partial verification states.
	 */
	public static function repair_local_stack() {
		if ( ! self::local_stack() ) {
			return;
		}

		if ( class_exists( 'NGC_Tutor_Cpt_Source' ) ) {
			NGC_Tutor_Cpt_Source::ensure_showcase_tutor();
		}

		if ( class_exists( 'NGC_Integrations_Bootstrap' ) ) {
			if ( ! get_option( 'ngc_integrations_bootstrapped' ) ) {
				NGC_Integrations_Bootstrap::configure_local_stack();
			} elseif ( class_exists( 'NGC_Fluentcrm_Adapter' ) ) {
				$crm = new NGC_Fluentcrm_Adapter();
				if ( $crm->is_available() ) {
					$crm->bootstrap_assets();
				}
			}
		}

		if ( class_exists( 'NGC_Amelia_Bootstrap' ) && NGC_Amelia_Bootstrap::is_active() ) {
			NGC_Amelia_Bootstrap::bootstrap( false );
			NGC_Amelia_Bootstrap::ensure_api_key();
		} elseif ( class_exists( 'NGC_Amelia_Bootstrap' ) && ! NGC_Amelia_Bootstrap::is_active() ) {
			NGC_Amelia_Bootstrap::safe_install_and_activate();
			NGC_Amelia_Bootstrap::ensure_api_key();
		}

		if ( class_exists( 'NGC_Platform_Tracking' ) ) {
			NGC_Platform_Tracking::ensure_demo_attribution();
			NGC_Platform_Tracking::seed_local_consent_bootstrap();
		}
	}

	/**
	 * @return bool
	 */
	public static function local_stack() {
		if ( defined( 'NGC_ALLOW_DEMO_SEED' ) && NGC_ALLOW_DEMO_SEED ) {
			return true;
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		return in_array( $host, [ 'localhost', '127.0.0.1' ], true )
			|| ( is_string( $host ) && str_ends_with( $host, '.local' ) );
	}
}
