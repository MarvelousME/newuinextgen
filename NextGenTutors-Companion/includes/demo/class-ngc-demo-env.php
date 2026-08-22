<?php
/**
 * Demo environment safety gates (Phase 14 §14.2).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controls DEMO_MODE equivalents and side-effect blockers.
 */
final class NGC_Demo_Env {

	public const SEED_VERSION = '14.0.0';
	public const OPTION_MODE  = 'ngc_demo_mode_enabled';
	public const OPTION_FLAGS = 'ngc_demo_env_flags';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_filter( 'pre_wp_mail', [ __CLASS__, 'sandbox_mail' ], 5, 2 );
		add_action( 'ngc_payout_before_dispatch', [ __CLASS__, 'block_real_payout' ], 1, 2 );
		add_filter( 'ngc_payfast_live_mode', [ __CLASS__, 'force_payfast_sandbox' ] );
		add_filter( 'ngc_external_side_effects_allowed', [ __CLASS__, 'external_side_effects_allowed' ] );
	}

	/**
	 * WordPress environment type. Fail closed to production on public hosts.
	 *
	 * @return string
	 */
	public static function environment() {
		if ( isset( $GLOBALS['ngc_test_environment'] ) && is_string( $GLOBALS['ngc_test_environment'] ) && '' !== $GLOBALS['ngc_test_environment'] ) {
			return strtolower( $GLOBALS['ngc_test_environment'] );
		}
		if ( defined( 'WP_ENVIRONMENT_TYPE' ) && WP_ENVIRONMENT_TYPE ) {
			return strtolower( (string) WP_ENVIRONMENT_TYPE );
		}
		$from_env = getenv( 'WP_ENVIRONMENT_TYPE' );
		if ( is_string( $from_env ) && '' !== $from_env ) {
			return strtolower( $from_env );
		}
		if ( function_exists( 'wp_get_environment_type' ) ) {
			return strtolower( (string) wp_get_environment_type() );
		}
		$host = function_exists( 'home_url' ) ? wp_parse_url( home_url(), PHP_URL_HOST ) : '';
		if ( in_array( $host, [ 'localhost', '127.0.0.1' ], true ) || ( is_string( $host ) && str_ends_with( $host, '.local' ) ) ) {
			return 'local';
		}
		return 'production';
	}

	/**
	 * @return bool
	 */
	public static function is_production_environment() {
		$env = self::environment();
		if ( in_array( $env, [ 'production', 'prod' ], true ) ) {
			return true;
		}
		$host = function_exists( 'home_url' ) ? wp_parse_url( home_url(), PHP_URL_HOST ) : '';
		return is_string( $host ) && false !== stripos( $host, 'nextgentutors.co.za' );
	}

	/**
	 * Demo CPT/user seed — production cannot enable this via constant or option.
	 *
	 * @return bool
	 */
	public static function seed_allowed() {
		if ( self::is_production_environment() ) {
			return false;
		}
		$env_flag = getenv( 'NGC_ALLOW_DEMO_SEED' );
		if ( is_string( $env_flag ) && in_array( strtolower( trim( $env_flag ) ), [ '0', 'false', 'no', 'off' ], true ) ) {
			return false;
		}
		if ( apply_filters( 'ngc_demo_seed_allowed', null ) === false ) {
			return false;
		}
		if ( defined( 'NGC_ALLOW_DEMO_SEED' ) && NGC_ALLOW_DEMO_SEED ) {
			return (bool) apply_filters( 'ngc_demo_seed_allowed', true );
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public static function is_demo_mode() {
		if ( self::is_production_environment() ) {
			return false;
		}
		if ( defined( 'NGC_DEMO_MODE' ) && NGC_DEMO_MODE ) {
			return true;
		}
		if ( '1' === (string) getenv( 'DEMO_MODE' ) || 'true' === strtolower( (string) getenv( 'DEMO_MODE' ) ) ) {
			return true;
		}
		if ( class_exists( 'NGC_Platform_Demo' ) && NGC_Platform_Demo::is_enabled() ) {
			return true;
		}
		return '1' === (string) get_option( self::OPTION_MODE, '0' );
	}

	/**
	 * Enable demo mode + default sandbox flags.
	 *
	 * @param bool $enabled Enabled.
	 */
	public static function set_demo_mode( $enabled ) {
		update_option( self::OPTION_MODE, $enabled ? '1' : '0', false );
		if ( class_exists( 'NGC_Platform_Demo' ) ) {
			NGC_Platform_Demo::set_enabled( (bool) $enabled );
		}
		if ( $enabled ) {
			self::install_default_flags();
		}
	}

	/**
	 * Install default sandbox flags.
	 */
	public static function install_default_flags() {
		$flags = self::flags();
		$defaults = [
			'email_mode'              => 'sandbox',
			'sms_mode'                => 'sandbox',
			'payment_mode'            => 'sandbox',
			'ai_mode'                 => 'sandbox',
			'allow_reset'             => true,
			'external_side_effects'   => false,
			'seed_version'            => self::SEED_VERSION,
		];
		update_option( self::OPTION_FLAGS, array_merge( $defaults, is_array( $flags ) ? $flags : [] ), false );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function flags() {
		$flags = get_option( self::OPTION_FLAGS, [] );
		return is_array( $flags ) ? $flags : [];
	}

	/**
	 * @return bool
	 */
	public static function allow_reset() {
		if ( ! self::is_demo_mode() ) {
			return false;
		}
		$flags = self::flags();
		return ! empty( $flags['allow_reset'] );
	}

	/**
	 * @return bool
	 */
	public static function external_side_effects_allowed( $allowed = false ) {
		if ( ! self::is_demo_mode() ) {
			return (bool) $allowed;
		}
		$flags = self::flags();
		return ! empty( $flags['external_side_effects'] );
	}

	/**
	 * Production guard — refuse destructive demo ops.
	 *
	 * @return true|WP_Error
	 */
	public static function assert_demo_ops_allowed() {
		if ( self::is_production_environment() ) {
			return new WP_Error( 'ngc_demo_blocked', __( 'Demo operations are forbidden in production.', 'nextgencompanion' ) );
		}
		if ( ! self::seed_allowed() && ! self::is_demo_mode() ) {
			return new WP_Error( 'ngc_demo_disallowed', __( 'Demo operations require NGC_ALLOW_DEMO_SEED on a non-production environment.', 'nextgencompanion' ) );
		}
		return true;
	}

	/**
	 * Sandbox outbound mail — log instead of send in demo mode.
	 *
	 * @param null|mixed           $short_circuit Short-circuit.
	 * @param array<string, mixed> $atts          Mail atts.
	 * @return null|bool
	 */
	public static function sandbox_mail( $short_circuit, $atts ) {
		if ( ! self::is_demo_mode() ) {
			return $short_circuit;
		}
		$flags = self::flags();
		if ( ( $flags['email_mode'] ?? 'sandbox' ) !== 'sandbox' ) {
			return $short_circuit;
		}
		if ( class_exists( 'NGC_Demo_Notifications' ) ) {
			NGC_Demo_Notifications::record(
				[
					'channel'    => 'email',
					'template'   => 'wp_mail',
					'recipient'  => is_array( $atts['to'] ?? null ) ? implode( ',', $atts['to'] ) : (string) ( $atts['to'] ?? '' ),
					'subject'    => (string) ( $atts['subject'] ?? '' ),
					'status'     => 'sandbox_captured',
					'source'     => 'wp_mail',
				]
			);
		}
		return true; // Pretend sent.
	}

	/**
	 * @param mixed $live Live mode flag.
	 * @return bool
	 */
	public static function force_payfast_sandbox( $live ) {
		if ( self::is_demo_mode() ) {
			return false;
		}
		return (bool) $live;
	}

	/**
	 * Block real payout dispatch in demo mode.
	 *
	 * @param mixed                $continue Continue.
	 * @param array<string, mixed> $batch    Batch.
	 * @return mixed|WP_Error
	 */
	public static function block_real_payout( $continue, $batch = [] ) {
		unset( $batch );
		if ( self::is_demo_mode() && ! self::external_side_effects_allowed() ) {
			return new WP_Error( 'ngc_demo_payout_blocked', __( 'Real payouts blocked in demo mode.', 'nextgencompanion' ) );
		}
		return $continue;
	}

	/**
	 * Shared meta applied to demo entities.
	 *
	 * @param string $scenario_id Scenario.
	 * @return array<string, mixed>
	 */
	public static function demo_meta( $scenario_id = 'core' ) {
		return [
			'is_demo'           => true,
			'demo_scenario_id'  => sanitize_key( $scenario_id ),
			'demo_seed_version' => self::SEED_VERSION,
		];
	}

	/**
	 * Demo password (env or option; never for production).
	 *
	 * @return string
	 */
	public static function demo_password() {
		$env = (string) getenv( 'NGC_DEMO_PASSWORD' );
		if ( '' !== $env ) {
			return $env;
		}
		if ( defined( 'NGC_DEMO_PASSWORD' ) && NGC_DEMO_PASSWORD ) {
			return (string) NGC_DEMO_PASSWORD;
		}
		$stored = (string) get_option( 'ngc_demo_password', '' );
		if ( '' !== $stored ) {
			return $stored;
		}
		// Development-only bootstrap secret (rotated via Control Centre).
		$generated = 'NgtDemo!' . substr( md5( home_url() . self::SEED_VERSION ), 0, 8 );
		update_option( 'ngc_demo_password', $generated, false );
		return $generated;
	}
}
