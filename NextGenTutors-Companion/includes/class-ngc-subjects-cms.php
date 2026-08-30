<?php
/**
 * Subject catalogue CMS — labels and slugs for matching / theme filters.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subjects CMS for tutor marketplace and theme helpers.
 */
class NGC_Subjects_CMS {

	const OPTION_KEY = 'ngc_subjects_catalog';

	/**
	 * @return void
	 */
	public static function init() {
		add_filter( 'bi_subject_label_from_slug', [ __CLASS__, 'label_from_slug' ], 10, 2 );
		add_filter( 'ngc_subject_options', [ __CLASS__, 'subject_options' ] );
	}

	/**
	 * @return array<string, string>
	 */
	public static function defaults() {
		return [
			'mathematics' => __( 'Mathematics', 'nextgencompanion' ),
			'physical-science' => __( 'Physical Science', 'nextgencompanion' ),
			'life-science' => __( 'Life Science', 'nextgencompanion' ),
			'english' => __( 'English', 'nextgencompanion' ),
			'afrikaans' => __( 'Afrikaans', 'nextgencompanion' ),
			'accounting' => __( 'Accounting', 'nextgencompanion' ),
		];
	}

	/**
	 * @return array<string, string>
	 */
	public static function catalog() {
		$stored = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $stored ) || empty( $stored ) ) {
			return self::defaults();
		}
		return array_merge( self::defaults(), $stored );
	}

	/**
	 * @param string $label
	 * @param string $slug
	 * @return string
	 */
	public static function label_from_slug( $label, $slug ) {
		$catalog = self::catalog();
		$key     = sanitize_key( (string) $slug );
		return $catalog[ $key ] ?? $label;
	}

	/**
	 * @return array<string, string>
	 */
	public static function subject_options() {
		return self::catalog();
	}
}
