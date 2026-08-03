<?php
/**
 * Ethical tutor lead search criteria — job-relevant only.
 *
 * Blocks ethnicity, gender, age, and other protected-trait targeting/inference.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lead criteria validator.
 */
final class NGC_Lead_Criteria {

	/**
	 * Allowed filter keys for discovery queries.
	 *
	 * @return string[]
	 */
	public static function allowed_keys() {
		return [
			'subject',
			'subjects',
			'qualification',
			'experience_years_min',
			'experience_years_max',
			'location',
			'service_area',
			'delivery_mode', // online|in_person|hybrid
			'grade_level',
			'language', // only when required for service delivery
			'speciality',
			'public_contact_channel',
			'availability',
			'source',
		];
	}

	/**
	 * Explicitly forbidden keys (and common aliases).
	 *
	 * @return string[]
	 */
	public static function forbidden_keys() {
		return [
			'ethnicity',
			'race',
			'racial',
			'gender',
			'sex',
			'religion',
			'disability',
			'sexual_orientation',
			'orientation',
			'health',
			'medical',
			'political',
			'politics',
			'age',
			'age_min',
			'age_max',
			'date_of_birth',
			'dob',
			'birth_year',
			'skin_tone',
			'nationality', // often used as ethnicity proxy — reject as targeting filter
			'photo_analysis',
			'inferred_gender',
			'inferred_age',
			'inferred_ethnicity',
		];
	}

	/**
	 * Validate and sanitize a discovery criteria payload.
	 *
	 * @param array<string, mixed> $raw Raw criteria.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function sanitize( array $raw ) {
		$forbidden_hit = [];
		foreach ( array_keys( $raw ) as $key ) {
			$k = strtolower( (string) $key );
			if ( in_array( $k, self::forbidden_keys(), true ) ) {
				$forbidden_hit[] = $k;
			}
			// Nested inference blocks.
			if ( 0 === strpos( $k, 'inferred_' ) || 0 === strpos( $k, 'protect' ) ) {
				$forbidden_hit[] = $k;
			}
		}
		if ( $forbidden_hit ) {
			return new WP_Error(
				'ngc_lead_protected_trait',
				sprintf(
					/* translators: %s: field names */
					__( 'Tutor lead discovery must not target or filter by protected characteristics. Rejected fields: %s', 'nextgencompanion' ),
					implode( ', ', array_unique( $forbidden_hit ) )
				),
				[ 'fields' => array_values( array_unique( $forbidden_hit ) ) ]
			);
		}

		$out = [];
		foreach ( self::allowed_keys() as $key ) {
			if ( ! array_key_exists( $key, $raw ) ) {
				continue;
			}
			$val = $raw[ $key ];
			if ( is_array( $val ) ) {
				$out[ $key ] = array_values( array_map( 'sanitize_text_field', array_map( 'strval', $val ) ) );
			} else {
				$out[ $key ] = sanitize_text_field( (string) $val );
			}
		}

		if ( empty( $out['subject'] ) && empty( $out['subjects'] ) && empty( $out['speciality'] ) ) {
			return new WP_Error(
				'ngc_lead_criteria_empty',
				__( 'At least one job-relevant criterion is required (subject, subjects, or speciality).', 'nextgencompanion' )
			);
		}

		return $out;
	}

	/**
	 * Assert that scoring explanations do not cite protected traits.
	 *
	 * @param string $explanation Free text.
	 * @return true|WP_Error
	 */
	public static function assert_explanation_clean( $explanation ) {
		$text = strtolower( (string) $explanation );
		$patterns = [
			'/\bethnic(?:ity)?\b/',
			'/\brace\b/',
			'/\bgender\b/',
			'/\bsex\b/',
			'/\breli(?:gion|gious)\b/',
			'/\bdisability\b/',
			'/\bage\b/',
			'/\bskin\s*tone\b/',
		];
		foreach ( $patterns as $re ) {
			if ( preg_match( $re, $text ) ) {
				return new WP_Error(
					'ngc_lead_score_protected',
					__( 'Lead scoring explanations must not reference protected characteristics.', 'nextgencompanion' )
				);
			}
		}
		return true;
	}
}
