<?php
/**
 * Authenticated encryption for secrets at rest.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encrypt/decrypt BYOK API keys using site salts (sodium or AES-256-GCM).
 */
final class NGC_Crypto {

	/**
	 * @return string 32-byte key material.
	 */
	private static function key() {
		$material = ( defined( 'AUTH_KEY' ) ? AUTH_KEY : '' ) . ( defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '' ) . ( defined( 'LOGGED_IN_KEY' ) ? LOGGED_IN_KEY : '' );
		if ( '' === $material ) {
			$material = (string) get_option( 'ngc_crypto_fallback_salt' );
			if ( '' === $material ) {
				$material = wp_generate_password( 64, true, true );
				update_option( 'ngc_crypto_fallback_salt', $material, false );
			}
		}
		return hash( 'sha256', $material, true );
	}

	/**
	 * @param string $plain Plaintext secret.
	 * @return string|WP_Error Opaque ciphertext blob.
	 */
	public static function encrypt( $plain ) {
		$key = self::key();

		if ( function_exists( 'sodium_crypto_secretbox' ) ) {
			$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = sodium_crypto_secretbox( $plain, $nonce, $key );
			return 's:' . base64_encode( $nonce . $cipher );
		}

		if ( function_exists( 'openssl_encrypt' ) ) {
			$iv     = random_bytes( 12 );
			$tag    = '';
			$cipher = openssl_encrypt( $plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
			if ( false === $cipher ) {
				return new WP_Error( 'ngc_crypto', __( 'Encryption failed.', 'nextgencompanion' ) );
			}
			return 'o:' . base64_encode( $iv . $tag . $cipher );
		}

		return new WP_Error( 'ngc_crypto', __( 'No encryption backend available; refusing to store the secret in plaintext.', 'nextgencompanion' ) );
	}

	/**
	 * @param string $blob Ciphertext blob.
	 * @return string|WP_Error
	 */
	public static function decrypt( $blob ) {
		$key    = self::key();
		$prefix = substr( $blob, 0, 2 );
		$data   = base64_decode( substr( $blob, 2 ), true );
		if ( false === $data ) {
			return new WP_Error( 'ngc_crypto', __( 'Corrupt ciphertext.', 'nextgencompanion' ) );
		}

		if ( 's:' === $prefix && function_exists( 'sodium_crypto_secretbox_open' ) ) {
			$nonce  = substr( $data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = substr( $data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$plain  = sodium_crypto_secretbox_open( $cipher, $nonce, $key );
			return false === $plain ? new WP_Error( 'ngc_crypto', __( 'Decryption failed.', 'nextgencompanion' ) ) : $plain;
		}

		if ( 'o:' === $prefix && function_exists( 'openssl_decrypt' ) ) {
			$iv     = substr( $data, 0, 12 );
			$tag    = substr( $data, 12, 16 );
			$cipher = substr( $data, 28 );
			$plain  = openssl_decrypt( $cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
			return false === $plain ? new WP_Error( 'ngc_crypto', __( 'Decryption failed.', 'nextgencompanion' ) ) : $plain;
		}

		return new WP_Error( 'ngc_crypto', __( 'Unsupported ciphertext or missing backend.', 'nextgencompanion' ) );
	}

	/**
	 * @return bool
	 */
	public static function available() {
		return function_exists( 'sodium_crypto_secretbox' ) || function_exists( 'openssl_encrypt' );
	}
}
