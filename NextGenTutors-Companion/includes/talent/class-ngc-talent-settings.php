<?php
/**
 * Talent Intelligence settings — flags default OFF; auto-approve forbidden.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configuration for Talent Intelligence.
 */
final class NGC_Talent_Settings {

	public const OPTION         = 'ngc_talent_settings_v1';
	public const WEIGHTS_OPTION = 'ngc_talent_weights_v1';
	public const MODEL_VERSION  = 'ngt-talent-suitability-v1';
	public const WEIGHTS_VERSION = 'wc-1';

	public const MODE_DISABLED    = 'DISABLED';
	public const MODE_NATIVE      = 'BRIDGE_NATIVE';
	public const MODE_HYBRID      = 'HYBRID';
	public const MODE_DEGRADED    = 'DEGRADED';
	public const MODE_MAINTENANCE = 'MAINTENANCE';

	/**
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return [
			'enabled'                => false,
			'mode'                   => self::MODE_DISABLED,
			'evaluate_applications'  => false,
			'rank_find_tutor'        => false,
			'nlp_sidecar_enabled'    => false,
			'nlp_sidecar_url'        => '',
			'agent_tools_enabled'    => false,
			'auto_approve_forbidden' => true,
			'timeout_ms'             => 2000,
			'completeness_threshold' => 0.4,
		];
	}

	/**
	 * Default tutoring weights (documented in delivery scoring model).
	 *
	 * @return array<string,float>
	 */
	public static function default_weights() {
		return [
			'subject'              => 0.25,
			'grade'                => 0.15,
			'curriculum'           => 0.10,
			'qualification_claim'  => 0.10,
			'teaching_experience'  => 0.10,
			'skill'                => 0.10,
			'language'             => 0.05,
			'availability'         => 0.05,
			'location_delivery'    => 0.05,
			'profile_completeness' => 0.05,
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function get() {
		$stored = get_option( self::OPTION, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}
		$cfg = array_merge( self::defaults(), $stored );
		$cfg['auto_approve_forbidden'] = true;
		return $cfg;
	}

	/**
	 * @param array<string,mixed> $patch Patch.
	 * @return array<string,mixed>
	 */
	public static function update( array $patch ) {
		$cfg = self::get();
		foreach ( $patch as $k => $v ) {
			if ( array_key_exists( $k, self::defaults() ) ) {
				$cfg[ $k ] = $v;
			}
		}
		$cfg['auto_approve_forbidden'] = true;
		update_option( self::OPTION, $cfg, false );
		return $cfg;
	}

	/**
	 * @return array<string,float>
	 */
	public static function weights() {
		$stored = get_option( self::WEIGHTS_OPTION, [] );
		if ( ! is_array( $stored ) || empty( $stored ) ) {
			return self::default_weights();
		}
		$out = self::default_weights();
		foreach ( $stored as $k => $v ) {
			if ( isset( $out[ $k ] ) ) {
				$out[ $k ] = max( 0, (float) $v );
			}
		}
		return $out;
	}

	/**
	 * @param array<string,float> $weights Weights.
	 * @return array<string,float>
	 */
	public static function update_weights( array $weights ) {
		$merged = self::default_weights();
		foreach ( $weights as $k => $v ) {
			if ( isset( $merged[ $k ] ) ) {
				$merged[ $k ] = max( 0, (float) $v );
			}
		}
		update_option( self::WEIGHTS_OPTION, $merged, false );
		return $merged;
	}

	/**
	 * @return bool
	 */
	public static function is_active() {
		$cfg = self::get();
		if ( empty( $cfg['enabled'] ) ) {
			return false;
		}
		$mode = (string) ( $cfg['mode'] ?? self::MODE_DISABLED );
		return ! in_array( $mode, [ self::MODE_DISABLED, self::MODE_MAINTENANCE ], true );
	}

	/**
	 * @return bool
	 */
	public static function evaluate_applications_allowed() {
		return self::is_active() && ! empty( self::get()['evaluate_applications'] );
	}

	/**
	 * @return bool
	 */
	public static function rank_find_tutor_allowed() {
		return self::is_active() && ! empty( self::get()['rank_find_tutor'] );
	}

	/**
	 * @return bool
	 */
	public static function nlp_allowed() {
		$cfg = self::get();
		return self::is_active()
			&& ! empty( $cfg['nlp_sidecar_enabled'] )
			&& '' !== (string) ( $cfg['nlp_sidecar_url'] ?? '' );
	}
}
