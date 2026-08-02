<?php
/**
 * Centralized platform version provider for admin branding.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product identity — marketing version is filterable; package versions come from constants.
 */
final class NGC_Platform_Version {

	/**
	 * @return string
	 */
	public static function product_name() {
		return (string) apply_filters( 'ngt_platform_product_name', 'NEXT GEN TUTORS' );
	}

	/**
	 * Marketing / platform major.minor (not Companion package version).
	 *
	 * @return string
	 */
	public static function marketing_version() {
		return (string) apply_filters( 'ngt_platform_marketing_version', '1.0' );
	}

	/**
	 * Primary admin identity string.
	 *
	 * @return string
	 */
	public static function display_title() {
		return (string) apply_filters(
			'ngt_platform_display_title',
			self::product_name() . ' v' . self::marketing_version()
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function bundle() {
		$theme = defined( 'BI_VERSION' ) ? (string) BI_VERSION : '';
		$mc    = defined( 'NGTMC_VERSION' ) ? (string) NGTMC_VERSION : '';
		return [
			'product'         => self::product_name(),
			'marketing'       => self::marketing_version(),
			'display'         => self::display_title(),
			'companion'       => defined( 'NGC_VERSION' ) ? (string) NGC_VERSION : '',
			'theme'           => $theme,
			'mission_control' => $mc,
		];
	}
}
