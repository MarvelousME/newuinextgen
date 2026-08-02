<?php
/**
 * Immutable audit trail for intelligence platform admin actions.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Audits config changes, exports, and alert acknowledgements.
 */
final class NGC_Intelligence_Audit {

	public const OPTION = 'ngc_intelligence_audit_log';

	/**
	 * @param string               $action Action key.
	 * @param array<string, mixed> $meta   Context.
	 */
	public static function log( $action, array $meta = [] ) {
		$entries = get_option( self::OPTION, [] );
		if ( ! is_array( $entries ) ) {
			$entries = [];
		}
		$entries[] = [
			'id'         => wp_generate_uuid4(),
			'action'     => sanitize_key( $action ),
			'actor_id'   => get_current_user_id(),
			'meta'       => $meta,
			'recorded_at'=> gmdate( 'c' ),
			'ip_hash'    => hash( 'sha256', (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
		];
		if ( count( $entries ) > 500 ) {
			$entries = array_slice( $entries, -500 );
		}
		update_option( self::OPTION, $entries, false );

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'intelligence.' . $action, 'intelligence', 0, $meta );
		}
	}

	/**
	 * @param int $limit Max entries.
	 * @return array<int, array<string, mixed>>
	 */
	public static function recent( $limit = 50 ) {
		$entries = get_option( self::OPTION, [] );
		if ( ! is_array( $entries ) ) {
			return [];
		}
		return array_slice( array_reverse( $entries ), 0, max( 1, (int) $limit ) );
	}
}
