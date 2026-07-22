<?php
/**
 * Callback nonce replay protection.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Claims callback nonces in cache and durable storage.
 */
final class NGTAI_Nonce_Store {

	/**
	 * Claim a nonce exactly once.
	 *
	 * @param string $nonce       Nonce.
	 * @param string $request_id  Request identifier.
	 * @param string $path        Callback request path.
	 * @param string $remote_hash Optional remote-address hash.
	 * @return true|'duplicate'|WP_Error
	 */
	public static function claim( $nonce, $request_id, $path, $remote_hash = '' ) {
		global $wpdb;

		if ( ! isset( $wpdb ) ) {
			return new WP_Error( 'ngtai_database_unavailable', 'Nonce storage is unavailable.' );
		}

		$nonce         = self::sanitize( $nonce, 191 );
		$request_id    = self::sanitize( $request_id, 191 );
		$path          = self::sanitize( $path, 255 );
		$remote_hash   = self::sanitize( $remote_hash, 64 );
		$transient_key = 'ngtai_nonce_' . md5( $nonce );

		if ( function_exists( 'get_transient' ) && false !== get_transient( $transient_key ) ) {
			return 'duplicate';
		}

		$now       = time();
		$created   = gmdate( 'Y-m-d H:i:s', $now );
		$expires   = gmdate( 'Y-m-d H:i:s', $now + ( NGTAI_Config::nonce_retention_days() * 86400 ) );
		$inserted  = $wpdb->insert(
			NGTAI_Database::table( 'callback_nonces' ),
			[
				'nonce'        => $nonce,
				'request_id'   => '' === $request_id ? null : $request_id,
				'request_path' => $path,
				'received_at'  => $created,
				'expires_at'   => $expires,
				'remote_hash'  => '' === $remote_hash ? null : $remote_hash,
				'created_at'   => $created,
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);
		if ( false === $inserted ) {
			return 'duplicate';
		}

		if ( function_exists( 'set_transient' ) ) {
			$minute = defined( 'MINUTE_IN_SECONDS' ) ? MINUTE_IN_SECONDS : 60;
			set_transient( $transient_key, 1, 30 * $minute );
		}

		return true;
	}

	/**
	 * Delete expired durable nonce claims.
	 *
	 * @return int
	 */
	public static function purge_expired() {
		global $wpdb;

		if ( ! isset( $wpdb ) ) {
			return 0;
		}

		$table = NGTAI_Database::table( 'callback_nonces' );
		$sql   = $wpdb->prepare( "DELETE FROM {$table} WHERE expires_at < %s", gmdate( 'Y-m-d H:i:s' ) );
		if ( ! is_callable( [ $wpdb, 'query' ] ) ) {
			return 0;
		}
		$rows = call_user_func( [ $wpdb, 'query' ], $sql );
		return false === $rows ? 0 : (int) $rows;
	}

	/**
	 * Sanitize and bound a stored identifier.
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
