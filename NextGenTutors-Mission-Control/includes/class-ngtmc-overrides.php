<?php
/**
 * System-wide overrides / kill switches applied via Mission Control.
 *
 * @package NextGenTutorsMissionControl
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persist and enforce master overrides.
 */
final class NGTMC_Overrides {

	public const OPTION_KEY = 'ngtmc_system_overrides';

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return [
			'maintenance_mode'       => false,
			'maintenance_message'    => 'NextGenTutors is undergoing scheduled maintenance. Please try again shortly.',
			'demo_mode'              => null, // null = do not force; true/false = force.
			'ai_global_pause'        => null,
			'ai_enabled'             => null,
			'suppress_public_booking'=> false,
			'force_support_email'    => '',
			'force_support_phone'    => '',
			'lock_business_profile'  => false,
			'updated_at'             => '',
			'updated_by'             => 0,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get() {
		$opt = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $opt ) ) {
			$opt = [];
		}
		return array_merge( self::defaults(), $opt );
	}

	/**
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed>
	 */
	public static function save( array $input ) {
		$current = self::get();
		$next    = $current;

		$bool_keys = [ 'maintenance_mode', 'suppress_public_booking', 'lock_business_profile' ];
		foreach ( $bool_keys as $key ) {
			if ( array_key_exists( $key, $input ) ) {
				$next[ $key ] = ! empty( $input[ $key ] );
			}
		}

		// Tri-state: '', '0', '1' → null / false / true.
		foreach ( [ 'demo_mode', 'ai_global_pause', 'ai_enabled' ] as $key ) {
			if ( ! array_key_exists( $key, $input ) ) {
				continue;
			}
			$raw = $input[ $key ];
			if ( null === $raw || '' === $raw || 'inherit' === $raw ) {
				$next[ $key ] = null;
			} else {
				$next[ $key ] = (bool) (int) $raw;
			}
		}

		if ( isset( $input['maintenance_message'] ) ) {
			$next['maintenance_message'] = sanitize_text_field( (string) $input['maintenance_message'] );
		}
		if ( isset( $input['force_support_email'] ) ) {
			$email = sanitize_email( (string) $input['force_support_email'] );
			$next['force_support_email'] = is_email( $email ) ? $email : '';
		}
		if ( isset( $input['force_support_phone'] ) ) {
			$next['force_support_phone'] = preg_replace( '/[^\d+]/', '', (string) $input['force_support_phone'] );
		}

		$next['updated_at'] = gmdate( 'c' );
		$next['updated_by'] = get_current_user_id();

		update_option( self::OPTION_KEY, $next, false );
		self::apply_side_effects( $next );
		return $next;
	}

	/**
	 * Push tri-state overrides into Companion / AI options when set.
	 *
	 * @param array<string, mixed> $o Overrides.
	 */
	public static function apply_side_effects( array $o ) {
		if ( null !== $o['demo_mode'] ) {
			if ( class_exists( 'NGC_Demo_Env' ) && method_exists( 'NGC_Demo_Env', 'set_demo_mode' ) ) {
				NGC_Demo_Env::set_demo_mode( (bool) $o['demo_mode'] );
			} else {
				update_option( 'ngc_demo_mode_enabled', $o['demo_mode'] ? '1' : '0', false );
			}
		}
		if ( null !== $o['ai_global_pause'] && ( defined( 'NGTAI_VERSION' ) || class_exists( 'NGTAI_Config' ) ) ) {
			update_option( 'ngtai_global_pause', $o['ai_global_pause'] ? 1 : 0, false );
		}
		if ( null !== $o['ai_enabled'] && ( defined( 'NGTAI_VERSION' ) || class_exists( 'NGTAI_Config' ) ) ) {
			update_option( 'ngtai_enabled', $o['ai_enabled'] ? 1 : 0, false );
		}
	}

	public static function init() {
		add_action( 'template_redirect', [ __CLASS__, 'maybe_maintenance' ], 0 );
		add_filter( 'bi_theme_option_bi_support_email', [ __CLASS__, 'filter_support_email' ], 20 );
		add_filter( 'bi_theme_option_bi_phone', [ __CLASS__, 'filter_support_phone' ], 20 );
		add_filter( 'body_class', [ __CLASS__, 'body_class' ] );
	}

	/**
	 * @param string[] $classes Classes.
	 * @return string[]
	 */
	public static function body_class( $classes ) {
		$o = self::get();
		if ( ! empty( $o['maintenance_mode'] ) ) {
			$classes[] = 'ngtmc-maintenance';
		}
		if ( ! empty( $o['suppress_public_booking'] ) ) {
			$classes[] = 'ngtmc-booking-suppressed';
		}
		return $classes;
	}

	/**
	 * Front-end maintenance gate (admins always allowed).
	 */
	public static function maybe_maintenance() {
		$o = self::get();
		if ( empty( $o['maintenance_mode'] ) ) {
			return;
		}
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		status_header( 503 );
		nocache_headers();
		$msg = (string) ( $o['maintenance_message'] ?: self::defaults()['maintenance_message'] );
		wp_die(
			esc_html( $msg ),
			esc_html__( 'Maintenance', 'nextgentutors-mission-control' ),
			[ 'response' => 503 ]
		);
	}

	/**
	 * @param mixed $value Value.
	 * @return mixed
	 */
	public static function filter_support_email( $value ) {
		$o = self::get();
		if ( ! empty( $o['force_support_email'] ) ) {
			return $o['force_support_email'];
		}
		return $value;
	}

	/**
	 * @param mixed $value Value.
	 * @return mixed
	 */
	public static function filter_support_phone( $value ) {
		$o = self::get();
		if ( ! empty( $o['force_support_phone'] ) ) {
			return $o['force_support_phone'];
		}
		return $value;
	}
}
