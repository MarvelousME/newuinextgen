<?php
/**
 * Secret encryption helpers.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encrypts credentials without a plaintext fallback.
 */
final class NGTAI_Crypto {

	/**
	 * Determine whether a supported encryption backend is available.
	 *
	 * @return bool
	 */
	public static function available() {
		if ( class_exists( 'NGC_Crypto' ) && is_callable( [ 'NGC_Crypto', 'encrypt' ] ) && is_callable( [ 'NGC_Crypto', 'decrypt' ] ) ) {
			return true;
		}

		$sodium_ready = function_exists( 'sodium_crypto_secretbox' ) && function_exists( 'sodium_crypto_secretbox_open' ) && self::sodium_key();
		$openssl_ready = function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' ) && function_exists( 'wp_salt' );
		return (bool) ( $sodium_ready || $openssl_ready );
	}

	/**
	 * Encrypt plaintext.
	 *
	 * @param string $plain Plaintext.
	 * @return string|WP_Error
	 */
	public static function encrypt( $plain ) {
		if ( class_exists( 'NGC_Crypto' ) && is_callable( [ 'NGC_Crypto', 'encrypt' ] ) && is_callable( [ 'NGC_Crypto', 'decrypt' ] ) ) {
			return NGC_Crypto::encrypt( (string) $plain );
		}

		$key = self::sodium_key();
		if ( false !== $key && function_exists( 'sodium_crypto_secretbox' ) ) {
			try {
				$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
				$cipher = sodium_crypto_secretbox( (string) $plain, $nonce, $key );
				return 'sodium:' . base64_encode( $nonce . $cipher );
			} catch ( Throwable $error ) {
				unset( $error );
				return new WP_Error( 'ngtai_crypto_encrypt_failed', 'Secret encryption failed.' );
			}
		}

		if ( function_exists( 'openssl_encrypt' ) && function_exists( 'wp_salt' ) ) {
			try {
				$iv = random_bytes( 12 );
			} catch ( Throwable $error ) {
				unset( $error );
				return new WP_Error( 'ngtai_crypto_encrypt_failed', 'Secret encryption failed.' );
			}
			$tag    = '';
			$key    = hash( 'sha256', (string) wp_salt( 'auth' ), true );
			$cipher = openssl_encrypt( (string) $plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
			if ( false === $cipher || 16 !== strlen( $tag ) ) {
				return new WP_Error( 'ngtai_crypto_encrypt_failed', 'Secret encryption failed.' );
			}
			return 'aesgcm:' . base64_encode( $iv . $tag . $cipher );
		}

		return new WP_Error( 'ngtai_crypto_unavailable', 'No secure encryption backend is available; plaintext storage is refused.' );
	}

	/**
	 * Decrypt ciphertext.
	 *
	 * @param string $cipher Ciphertext.
	 * @return string|false
	 */
	public static function decrypt( $cipher ) {
		$cipher = (string) $cipher;

		if ( 0 === strpos( $cipher, 'sodium:' ) ) {
			return self::decrypt_sodium( substr( $cipher, 7 ) );
		}
		if ( 0 === strpos( $cipher, 'aesgcm:' ) ) {
			return self::decrypt_aesgcm( substr( $cipher, 7 ) );
		}

		if ( class_exists( 'NGC_Crypto' ) && is_callable( [ 'NGC_Crypto', 'encrypt' ] ) && is_callable( [ 'NGC_Crypto', 'decrypt' ] ) ) {
			$plain = NGC_Crypto::decrypt( $cipher );
			return self::is_error( $plain ) ? false : (string) $plain;
		}

		return false;
	}

	/**
	 * Build the sodium key.
	 *
	 * @return string|false
	 */
	private static function sodium_key() {
		$material = ( defined( 'AUTH_KEY' ) ? (string) AUTH_KEY : '' ) . ( defined( 'SECURE_AUTH_KEY' ) ? (string) SECURE_AUTH_KEY : '' );
		return '' === $material ? false : hash( 'sha256', $material, true );
	}

	/**
	 * Decrypt a sodium payload.
	 *
	 * @param string $encoded Base64 payload.
	 * @return string|false
	 */
	private static function decrypt_sodium( $encoded ) {
		if ( ! function_exists( 'sodium_crypto_secretbox_open' ) ) {
			return false;
		}
		$key  = self::sodium_key();
		$data = base64_decode( $encoded, true );
		if ( false === $key || false === $data || strlen( $data ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return false;
		}
		$nonce  = substr( $data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$payload = substr( $data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$plain   = sodium_crypto_secretbox_open( $payload, $nonce, $key );
		return false === $plain ? false : $plain;
	}

	/**
	 * Decrypt an AES-GCM payload.
	 *
	 * @param string $encoded Base64 payload.
	 * @return string|false
	 */
	private static function decrypt_aesgcm( $encoded ) {
		if ( ! function_exists( 'openssl_decrypt' ) || ! function_exists( 'wp_salt' ) ) {
			return false;
		}
		$data = base64_decode( $encoded, true );
		if ( false === $data || strlen( $data ) < 28 ) {
			return false;
		}
		$iv      = substr( $data, 0, 12 );
		$tag     = substr( $data, 12, 16 );
		$payload = substr( $data, 28 );
		$key     = hash( 'sha256', (string) wp_salt( 'auth' ), true );
		$plain   = openssl_decrypt( $payload, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
		return false === $plain ? false : $plain;
	}

	/**
	 * Detect a WordPress error without requiring helper functions.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private static function is_error( $value ) {
		return class_exists( 'WP_Error' ) && $value instanceof WP_Error;
	}
}
