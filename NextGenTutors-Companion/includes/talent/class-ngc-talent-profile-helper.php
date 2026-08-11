<?php
/**
 * Shared list/normalize helpers for talent scoring.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pure helpers — no I/O.
 */
final class NGC_Talent_Profile_Helper {

	/**
	 * @param array<string,mixed> $arr Array.
	 * @param string              $key Key.
	 * @return string[]
	 */
	public static function list_field( array $arr, $key ) {
		$v = $arr[ $key ] ?? [];
		if ( is_string( $v ) ) {
			$v = array_filter( array_map( 'trim', explode( ',', $v ) ) );
		}
		return is_array( $v ) ? array_values( array_map( 'strval', $v ) ) : [];
	}

	/**
	 * @param string[] $list List.
	 * @return string[]
	 */
	public static function normalize_list( array $list ) {
		$out = [];
		foreach ( $list as $i ) {
			$i = strtolower( trim( (string) $i ) );
			if ( '' !== $i ) {
				$out[] = $i;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * @param string[] $hay Haystack.
	 * @param string   $needle Needle.
	 * @return bool
	 */
	public static function list_has( array $hay, $needle ) {
		$needle = strtolower( (string) $needle );
		foreach ( $hay as $h ) {
			$h = strtolower( (string) $h );
			if ( $h === $needle || false !== strpos( $h, $needle ) || false !== strpos( $needle, $h ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param array<string,mixed> $candidate Candidate.
	 * @return string[]
	 */
	public static function skills_of( array $candidate ) {
		$skills = self::list_field( $candidate, 'skills' );
		foreach ( self::list_field( $candidate, 'subjects' ) as $s ) {
			$skills[] = $s;
		}
		return self::normalize_list( $skills );
	}

	/**
	 * @param string $text Text.
	 * @return float|null
	 */
	public static function extract_years( $text ) {
		if ( preg_match( '/(\d+)\s*\+?\s*(years|yrs)/i', (string) $text, $m ) ) {
			return (float) $m[1];
		}
		return null;
	}

	/**
	 * Whether a requirement profile has any ranking signal.
	 *
	 * @param array<string,mixed> $requirements Requirements.
	 * @return bool
	 */
	public static function has_ranking_signal( array $requirements ) {
		foreach ( [ 'subjects', 'grades', 'curricula', 'skills', 'languages', 'deliveryModes', 'delivery_modes' ] as $k ) {
			if ( ! empty( self::list_field( $requirements, $k ) ) ) {
				return true;
			}
		}
		return '' !== (string) ( $requirements['location'] ?? $requirements['province'] ?? '' )
			|| isset( $requirements['experience_years_min'] )
			|| ! empty( $requirements['availability'] );
	}
}
