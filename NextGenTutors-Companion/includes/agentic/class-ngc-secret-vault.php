<?php
/**
 * Server-side encrypted secret references (never returned to browsers).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores secrets encrypted at rest. References are opaque option keys.
 */
final class NGC_Secret_Vault {

	const OPTION_PREFIX = 'ngc_vault_';

	/**
	 * @param string $plaintext Secret.
	 * @param string $label     Human label for audits (not the secret).
	 * @return string|WP_Error Opaque reference id.
	 */
	public static function store( $plaintext, $label = '' ) {
		$plaintext = (string) $plaintext;
		if ( '' === $plaintext ) {
			return new WP_Error( 'ngc_vault_empty', __( 'Secret cannot be empty.', 'nextgencompanion' ) );
		}
		if ( ! class_exists( 'NGC_Crypto' ) || ! method_exists( 'NGC_Crypto', 'encrypt' ) ) {
			return new WP_Error( 'ngc_vault_crypto', __( 'Secure crypto unavailable.', 'nextgencompanion' ) );
		}
		$enc = NGC_Crypto::encrypt( $plaintext );
		if ( is_wp_error( $enc ) ) {
			return $enc;
		}
		$ref = 'ref_' . wp_generate_password( 24, false, false );
		update_option(
			self::OPTION_PREFIX . $ref,
			[
				'ciphertext' => $enc,
				'label'      => sanitize_text_field( (string) $label ),
				'created_at' => gmdate( 'c' ),
			],
			false
		);
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'secret_vault_stored', 'vault', 0, [ 'ref' => $ref, 'label' => sanitize_text_field( (string) $label ) ] );
		}
		return $ref;
	}

	/**
	 * Reveal a secret server-side only — never send plaintext or env values to browsers.
	 *
	 * Supported refs:
	 * - `env:NAME` — read from process environment (fail closed if empty)
	 * - `ref_*` / opaque option keys — decrypt from encrypted wp_options vault
	 *
	 * @param string $ref Reference.
	 * @return string|WP_Error Plaintext (server-side only).
	 */
	public static function reveal( $ref ) {
		$raw = trim( (string) $ref );
		if ( 0 === stripos( $raw, 'env:' ) ) {
			return self::reveal_env( substr( $raw, 4 ) );
		}

		$ref = sanitize_key( $raw );
		$row = get_option( self::OPTION_PREFIX . $ref, null );
		if ( ! is_array( $row ) || empty( $row['ciphertext'] ) ) {
			return new WP_Error( 'ngc_vault_missing', __( 'Secret reference not found.', 'nextgencompanion' ) );
		}
		if ( ! class_exists( 'NGC_Crypto' ) || ! method_exists( 'NGC_Crypto', 'decrypt' ) ) {
			return new WP_Error( 'ngc_vault_crypto', __( 'Secure crypto unavailable.', 'nextgencompanion' ) );
		}
		$plain = NGC_Crypto::decrypt( (string) $row['ciphertext'] );
		return false === $plain ? new WP_Error( 'ngc_vault_decrypt', __( 'Secret could not be decrypted.', 'nextgencompanion' ) ) : (string) $plain;
	}

	/**
	 * @param string $name Environment variable name.
	 * @return string|WP_Error
	 */
	private static function reveal_env( $name ) {
		$name = trim( (string) $name );
		if ( ! preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', $name ) ) {
			return new WP_Error( 'ngc_vault_env_invalid', __( 'Invalid environment secret name.', 'nextgencompanion' ) );
		}
		$val = getenv( $name );
		if ( false === $val || '' === $val ) {
			$val = isset( $_ENV[ $name ] ) ? (string) $_ENV[ $name ] : '';
		}
		if ( '' === $val ) {
			return new WP_Error( 'ngc_vault_env_empty', __( 'Environment secret is not configured.', 'nextgencompanion' ) );
		}
		return (string) $val;
	}

	/**
	 * @param string $ref Reference.
	 * @return bool
	 */
	public static function delete( $ref ) {
		$ref = sanitize_key( (string) $ref );
		$ok  = delete_option( self::OPTION_PREFIX . $ref );
		if ( $ok && class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'secret_vault_deleted', 'vault', 0, [ 'ref' => $ref ] );
		}
		return (bool) $ok;
	}

	/**
	 * Public metadata only — never ciphertext or plaintext.
	 *
	 * @param string $ref Reference.
	 * @return array<string,string>|null
	 */
	public static function meta( $ref ) {
		$raw = trim( (string) $ref );
		if ( 0 === stripos( $raw, 'env:' ) ) {
			$name = trim( substr( $raw, 4 ) );
			if ( ! preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', $name ) ) {
				return null;
			}
			$present = ( false !== getenv( $name ) && '' !== (string) getenv( $name ) )
				|| ( isset( $_ENV[ $name ] ) && '' !== (string) $_ENV[ $name ] );
			return [
				'ref'        => 'env:' . $name,
				'label'      => 'env',
				'created_at' => '',
				'present'    => $present ? '1' : '0',
				'backend'    => 'env',
			];
		}

		$ref = sanitize_key( $raw );
		$row = get_option( self::OPTION_PREFIX . $ref, null );
		if ( ! is_array( $row ) ) {
			return null;
		}
		return [
			'ref'        => $ref,
			'label'      => (string) ( $row['label'] ?? '' ),
			'created_at' => (string) ( $row['created_at'] ?? '' ),
			'present'    => ! empty( $row['ciphertext'] ) ? '1' : '0',
			'backend'    => 'option',
		];
	}
}
