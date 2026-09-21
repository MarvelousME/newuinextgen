<?php
/**
 * PayFast credential resolution (sandbox defaults; production via admin / wp-config).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Official PayFast sandbox merchant (public test credentials).
 *
 * Swap to production in WooCommerce → Settings → Payments → PayFast (NextGen):
 * uncheck Sandbox, then set Merchant ID, Merchant Key, and Passphrase.
 * Optional wp-config overrides (win over admin fields):
 *   NGC_PAYFAST_SANDBOX, NGC_PAYFAST_MERCHANT_ID, NGC_PAYFAST_MERCHANT_KEY, NGC_PAYFAST_PASSPHRASE
 */
final class NGC_PayFast_Credentials {

	public const OPTION_KEY = 'woocommerce_ngc_payfast_settings';

	/** Public PayFast sandbox merchant ID. */
	public const SANDBOX_MERCHANT_ID = '10000100';

	/** Public PayFast sandbox merchant key. */
	public const SANDBOX_MERCHANT_KEY = '46f0cd694581a';

	/** Public PayFast sandbox passphrase (WooCommerce PayFast plugin). */
	public const SANDBOX_PASSPHRASE = 'jt7NOE43FZPn';

	public const SANDBOX_PROCESS_URL   = 'https://sandbox.payfast.co.za/eng/process';
	public const LIVE_PROCESS_URL      = 'https://www.payfast.co.za/eng/process';
	public const SANDBOX_VALIDATE_URL  = 'https://sandbox.payfast.co.za/eng/query/validate';
	public const LIVE_VALIDATE_URL     = 'https://www.payfast.co.za/eng/query/validate';

	/**
	 * WooCommerce option payload for sandbox.
	 *
	 * @return array<string, string>
	 */
	public static function sandbox_settings() {
		return [
			'enabled'      => 'yes',
			'title'        => 'PayFast',
			'description'  => 'Pay securely with PayFast (sandbox). Switch off Sandbox and paste live merchant credentials before production traffic.',
			'merchant_id'  => self::SANDBOX_MERCHANT_ID,
			'merchant_key' => self::SANDBOX_MERCHANT_KEY,
			'passphrase'   => self::SANDBOX_PASSPHRASE,
			'sandbox'      => 'yes',
		];
	}

	/**
	 * Persist sandbox credentials. Never overwrites a non-sandbox merchant ID.
	 *
	 * @param bool $force Re-apply official sandbox even if already enabled.
	 * @return array<string, mixed>
	 */
	public static function persist_sandbox( $force = false ) {
		$existing = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $existing ) ) {
			$existing = [];
		}
		$merchant = (string) ( $existing['merchant_id'] ?? '' );
		$sandbox  = 'yes' === ( $existing['sandbox'] ?? 'yes' );
		$is_live  = '' !== $merchant && self::SANDBOX_MERCHANT_ID !== $merchant && ! $sandbox;

		if ( $is_live && ! $force ) {
			return [ 'ok' => true, 'status' => 'live_credentials_preserved', 'merchant_id' => $merchant ];
		}

		$next = array_merge( $existing, self::sandbox_settings() );
		update_option( self::OPTION_KEY, $next, false );
		update_option( 'woocommerce_default_gateway', 'ngc_payfast', false );

		return [ 'ok' => true, 'status' => 'sandbox_configured', 'merchant_id' => self::SANDBOX_MERCHANT_ID ];
	}

	/**
	 * Resolve effective credentials: sandbox defaults ← admin ← wp-config constants.
	 *
	 * @param array<string, mixed> $settings Gateway settings.
	 * @return array{enabled:string,merchant_id:string,merchant_key:string,passphrase:string,sandbox:bool,process_url:string,validate_url:string}
	 */
	public static function resolve( array $settings ) {
		$sandbox = true;
		if ( defined( 'NGC_PAYFAST_SANDBOX' ) ) {
			$sandbox = (bool) NGC_PAYFAST_SANDBOX;
		} elseif ( isset( $settings['sandbox'] ) ) {
			$sandbox = 'yes' === (string) $settings['sandbox'];
		}

		$defaults = $sandbox ? self::sandbox_settings() : [
			'merchant_id'  => '',
			'merchant_key' => '',
			'passphrase'   => '',
		];

		$merchant_id  = (string) ( $settings['merchant_id'] ?? '' );
		$merchant_key = (string) ( $settings['merchant_key'] ?? '' );
		$passphrase   = (string) ( $settings['passphrase'] ?? '' );

		if ( $sandbox ) {
			if ( '' === $merchant_id || self::SANDBOX_MERCHANT_ID === $merchant_id ) {
				$merchant_id  = self::SANDBOX_MERCHANT_ID;
				$merchant_key = $merchant_key ?: self::SANDBOX_MERCHANT_KEY;
				$passphrase   = ( '' === $passphrase || 'payfast' === $passphrase ) ? self::SANDBOX_PASSPHRASE : $passphrase;
			}
		}

		if ( defined( 'NGC_PAYFAST_MERCHANT_ID' ) && '' !== (string) NGC_PAYFAST_MERCHANT_ID ) {
			$merchant_id = (string) NGC_PAYFAST_MERCHANT_ID;
		}
		if ( defined( 'NGC_PAYFAST_MERCHANT_KEY' ) && '' !== (string) NGC_PAYFAST_MERCHANT_KEY ) {
			$merchant_key = (string) NGC_PAYFAST_MERCHANT_KEY;
		}
		if ( defined( 'NGC_PAYFAST_PASSPHRASE' ) && '' !== (string) NGC_PAYFAST_PASSPHRASE ) {
			$passphrase = (string) NGC_PAYFAST_PASSPHRASE;
		}

		return [
			'enabled'      => (string) ( $settings['enabled'] ?? 'no' ),
			'merchant_id'  => $merchant_id,
			'merchant_key' => $merchant_key,
			'passphrase'   => $passphrase,
			'sandbox'      => $sandbox,
			'process_url'  => $sandbox ? self::SANDBOX_PROCESS_URL : self::LIVE_PROCESS_URL,
			'validate_url' => $sandbox ? self::SANDBOX_VALIDATE_URL : self::LIVE_VALIDATE_URL,
		];
	}
}
