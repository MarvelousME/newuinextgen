<?php
/**
 * Personalized drag-and-drop dashboard layouts.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Per-user widget layout persistence.
 */
final class NGC_Intelligence_Layout {

	public const OPTION_PREFIX = 'ngc_intelligence_layout_';

	/**
	 * Default widget order for overview.
	 *
	 * @return array<int, string>
	 */
	public static function defaults() {
		return [
			'brief',
			'kpis',
			'health',
			'chart-bookings',
			'chart-errors',
			'chart-api',
			'chart-workflows',
			'chart-sankey',
			'chart-network',
			'chart-geo',
			'chart-radar',
			'chart-funnel',
			'notifications',
			'ask',
		];
	}

	/**
	 * @param int $user_id User ID.
	 * @return array<int, string>
	 */
	public static function get( $user_id = 0 ) {
		$user_id = $user_id ?: get_current_user_id();
		$key     = self::OPTION_PREFIX . max( 0, (int) $user_id );
		$stored  = get_option( $key, [] );
		if ( ! is_array( $stored ) || ! $stored ) {
			return self::defaults();
		}
		return array_values( array_map( 'sanitize_key', $stored ) );
	}

	/**
	 * @param array<int, string> $widgets Widget IDs.
	 * @param int                $user_id User ID.
	 * @return array<int, string>
	 */
	public static function save( array $widgets, $user_id = 0 ) {
		$user_id = $user_id ?: get_current_user_id();
		$allowed = array_flip( self::defaults() );
		$clean   = array_values(
			array_filter(
				array_map( 'sanitize_key', $widgets ),
				static function ( $w ) use ( $allowed ) {
					return '' !== $w && isset( $allowed[ $w ] );
				}
			)
		);
		if ( ! $clean ) {
			$clean = self::defaults();
		}
		update_option( self::OPTION_PREFIX . max( 0, (int) $user_id ), $clean, false );
		NGC_Intelligence_Audit::log( 'layout.saved', [ 'widgets' => $clean, 'user_id' => $user_id ] );
		return $clean;
	}
}
