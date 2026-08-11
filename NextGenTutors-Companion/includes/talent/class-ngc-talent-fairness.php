<?php
/**
 * Fairness guards for talent scoring — blocks protected traits.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Strip / reject protected characteristics from scoring payloads.
 */
final class NGC_Talent_Fairness {

	/**
	 * @return string[]
	 */
	public static function forbidden_keys() {
		if ( class_exists( 'NGC_Lead_Criteria' ) ) {
			return NGC_Lead_Criteria::forbidden_keys();
		}
		return [
			'ethnicity', 'race', 'racial', 'gender', 'sex', 'religion', 'disability',
			'sexual_orientation', 'orientation', 'age', 'dob', 'date_of_birth',
			'skin_tone', 'nationality', 'photo_analysis', 'inferred_gender',
			'inferred_age', 'inferred_ethnicity',
		];
	}

	/**
	 * Remove forbidden keys from array (recursive one level of nested maps).
	 *
	 * @param array<string,mixed> $payload Payload.
	 * @return array{clean:array<string,mixed>,stripped:string[]}
	 */
	public static function scrub( array $payload ) {
		$forbidden = array_map( 'strtolower', self::forbidden_keys() );
		$stripped  = [];
		$clean     = [];
		foreach ( $payload as $k => $v ) {
			$key = strtolower( (string) $k );
			if ( in_array( $key, $forbidden, true ) ) {
				$stripped[] = $key;
				continue;
			}
			if ( is_array( $v ) && self::is_assoc( $v ) ) {
				$inner = self::scrub( $v );
				$clean[ $k ] = $inner['clean'];
				$stripped     = array_merge( $stripped, $inner['stripped'] );
			} else {
				$clean[ $k ] = $v;
			}
		}
		return [ 'clean' => $clean, 'stripped' => array_values( array_unique( $stripped ) ) ];
	}

	/**
	 * @param array<string,mixed> $factors Explanation factors.
	 * @return true|WP_Error
	 */
	public static function assert_explanation_safe( array $factors ) {
		$blob = function_exists( 'wp_json_encode' ) ? (string) wp_json_encode( $factors ) : (string) json_encode( $factors );
		$blob_l = strtolower( $blob );
		foreach ( self::forbidden_keys() as $f ) {
			$f = strtolower( (string) $f );
			// Match as JSON key or standalone token — avoid "sex" matching inside "subjects".
			$pattern = '/(?<![a-z0-9_])' . preg_quote( $f, '/' ) . '(?![a-z0-9_])/';
			if ( preg_match( $pattern, $blob_l ) ) {
				return new WP_Error(
					'ngc_talent_protected',
					function_exists( '__' ) ? __( 'Talent explanations must not reference protected characteristics.', 'nextgencompanion' ) : 'Talent explanations must not reference protected characteristics.'
				);
			}
		}
		return true;
	}

	/**
	 * @param array<string,mixed> $arr Array.
	 * @return bool
	 */
	private static function is_assoc( array $arr ) {
		if ( [] === $arr ) {
			return false;
		}
		return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
	}
}
