<?php
/**
 * Database table helpers.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves integration table names.
 */
final class NGTAI_Database {

	/**
	 * Get a prefixed integration table name.
	 *
	 * @param string $name Logical table name.
	 * @return string
	 */
	public static function table( $name ) {
		global $wpdb;

		if ( ! isset( $wpdb ) ) {
			return '';
		}

		return $wpdb->prefix . 'ngtai_' . (string) $name;
	}

	/**
	 * Get the primary logical table names.
	 *
	 * @return string[]
	 */
	public static function tables() {
		return [
			'callback_nonces',
			'deliveries',
			'agent_results',
			'approvals',
		];
	}
}
