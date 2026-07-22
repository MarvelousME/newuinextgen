<?php
/**
 * Durable audit adapter.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGTAI_Audit {
	/**
	 * Record an integration audit event locally and in Companion.
	 *
	 * @param string               $action      Action (with or without ngtai_ prefix).
	 * @param array<string,mixed>  $detail      Detail.
	 * @param string               $correlation Correlation ID.
	 * @return void
	 */
	public static function log( $action, array $detail = [], $correlation = '' ) {
		$action = sanitize_key( $action );
		if ( 0 === strpos( $action, 'ngtai_' ) ) {
			$action = substr( $action, 6 );
		}
		$safe        = class_exists( 'NGTAI_Logger' ) ? NGTAI_Logger::scrub( $detail ) : self::scrub( $detail );
		$correlation = sanitize_text_field( $correlation );
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'ngtai_' . $action, 'ngtai', 0, $safe + [ 'correlation_id' => $correlation ], get_current_user_id() );
		}
		global $wpdb;
		$table = $wpdb->prefix . 'ngtai_audit';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			[
				'action'         => 'ngtai_' . $action,
				'detail'         => wp_json_encode( $safe ),
				'correlation_id' => $correlation,
				'created_at'     => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Fallback scrubber.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	private static function scrub( $value ) {
		if ( ! is_array( $value ) ) {
			return is_scalar( $value ) || null === $value ? $value : '[unsupported]';
		}
		$output = [];
		foreach ( $value as $key => $child ) {
			$output[ $key ] = preg_match( '/secret|token|password|authorization|cookie|email|phone|id_number/i', (string) $key ) ? '[redacted]' : self::scrub( $child );
		}
		return $output;
	}
}
