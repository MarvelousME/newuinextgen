<?php
/**
 * Callback idempotency storage.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remembers idempotency keys with a unique database constraint.
 */
final class NGTAI_Idempotency_Store {

	/**
	 * Check whether a key was seen, remembering it atomically if new.
	 *
	 * @param string $key  Idempotency key.
	 * @param string $hash Optional result hash.
	 * @return bool True for a duplicate, false for a newly remembered key.
	 */
	public static function seen_or_remember( $key, $hash = '' ) {
		global $wpdb;

		if ( ! isset( $wpdb ) ) {
			return false;
		}

		$key  = self::sanitize( $key, 191 );
		$hash = self::sanitize( $hash, 64 );
		$result = $wpdb->insert(
			$wpdb->prefix . 'ngtai_idempotency',
			[
				'idempotency_key' => $key,
				'result_hash'     => '' === $hash ? null : $hash,
				'created_at'      => gmdate( 'Y-m-d H:i:s' ),
			],
			[ '%s', '%s', '%s' ]
		);

		return false === $result;
	}

	/**
	 * Sanitize and bound a stored value.
	 *
	 * @param mixed $value  Value.
	 * @param int   $length Maximum length.
	 * @return string
	 */
	private static function sanitize( $value, $length ) {
		$value = (string) $value;
		$value = function_exists( 'sanitize_text_field' ) ? sanitize_text_field( $value ) : trim( strip_tags( $value ) );
		return substr( $value, 0, $length );
	}
}
