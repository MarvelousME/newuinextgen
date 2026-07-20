<?php
/**
 * PayFast ITN validation helpers (signature, amount, replay, idempotency).
 *
 * Pure logic extracted for unit testing without WooCommerce bootstrap.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security checks for PayFast Instant Transaction Notifications.
 */
final class NGC_PayFast_Itn {

	/**
	 * Build PayFast MD5 signature string.
	 *
	 * @param array<string, string> $data Payload without signature.
	 * @param string                $passphrase Optional passphrase.
	 * @return string
	 */
	public static function generate_signature( array $data, $passphrase = '' ) {
		$parts = [];
		foreach ( $data as $key => $val ) {
			if ( 'signature' === $key || '' === (string) $val ) {
				continue;
			}
			$parts[] = $key . '=' . rawurlencode( trim( (string) $val ) );
		}
		$string = implode( '&', $parts );
		if ( '' !== (string) $passphrase ) {
			$string .= '&passphrase=' . rawurlencode( trim( (string) $passphrase ) );
		}
		return md5( $string );
	}

	/**
	 * @param array<string, mixed> $posted ITN POST body.
	 * @param string               $passphrase Passphrase.
	 * @return bool
	 */
	public static function signature_valid( array $posted, $passphrase = '' ) {
		$received = isset( $posted['signature'] ) ? (string) $posted['signature'] : '';
		if ( '' === $received ) {
			return false;
		}
		$payload = [];
		foreach ( $posted as $key => $val ) {
			if ( 'signature' === $key ) {
				continue;
			}
			$payload[ $key ] = is_scalar( $val ) ? (string) $val : '';
		}
		return hash_equals( $received, self::generate_signature( $payload, $passphrase ) );
	}

	/**
	 * Validate amount matches order total (2 decimal places).
	 *
	 * @param string $posted_amount Amount from ITN.
	 * @param float  $order_total   Order total.
	 * @return bool
	 */
	public static function amount_matches( $posted_amount, $order_total ) {
		$a = number_format( (float) $posted_amount, 2, '.', '' );
		$b = number_format( (float) $order_total, 2, '.', '' );
		return hash_equals( $a, $b );
	}

	/**
	 * @param string $posted_merchant Merchant ID from ITN.
	 * @param string $configured      Gateway merchant ID.
	 * @return bool
	 */
	public static function merchant_matches( $posted_merchant, $configured ) {
		if ( '' === (string) $configured ) {
			return false;
		}
		return hash_equals( (string) $configured, (string) $posted_merchant );
	}

	/**
	 * Production (non-sandbox) must use a passphrase.
	 *
	 * @param bool   $sandbox    Sandbox mode.
	 * @param string $passphrase Passphrase.
	 * @return bool
	 */
	public static function passphrase_policy_ok( $sandbox, $passphrase ) {
		if ( $sandbox ) {
			return true;
		}
		return '' !== trim( (string) $passphrase );
	}

	/**
	 * Replay key from pf_payment_id (PayFast unique txn id).
	 *
	 * @param string $pf_payment_id PayFast payment id.
	 * @return string
	 */
	public static function replay_transient_key( $pf_payment_id ) {
		return 'ngc_pf_itn_' . md5( (string) $pf_payment_id );
	}

	/**
	 * Whether this pf_payment_id was already processed.
	 *
	 * @param string $pf_payment_id PayFast id.
	 * @return bool
	 */
	public static function is_replay( $pf_payment_id ) {
		$pf_payment_id = trim( (string) $pf_payment_id );
		if ( '' === $pf_payment_id ) {
			return false;
		}
		return (bool) get_transient( self::replay_transient_key( $pf_payment_id ) );
	}

	/**
	 * Mark pf_payment_id as processed (30 day retention).
	 *
	 * @param string $pf_payment_id PayFast id.
	 * @param int    $order_id      Order id.
	 */
	public static function mark_processed( $pf_payment_id, $order_id = 0 ) {
		$pf_payment_id = trim( (string) $pf_payment_id );
		if ( '' === $pf_payment_id ) {
			return;
		}
		set_transient(
			self::replay_transient_key( $pf_payment_id ),
			[
				'order_id' => (int) $order_id,
				'at'       => time(),
			],
			30 * DAY_IN_SECONDS
		);
	}

	/**
	 * Full ITN acceptance gate (returns WP_Error on failure or true).
	 *
	 * @param array<string, mixed> $posted      POST body.
	 * @param array<string, mixed> $gateway_cfg merchant_id, passphrase, sandbox.
	 * @param float                $order_total Order total.
	 * @param string|null          $prior_pf_id Previously stored pf_payment_id on order.
	 * @return true|WP_Error
	 */
	public static function validate_notification( array $posted, array $gateway_cfg, $order_total, $prior_pf_id = null ) {
		$sandbox    = ! empty( $gateway_cfg['sandbox'] );
		$passphrase = (string) ( $gateway_cfg['passphrase'] ?? '' );
		$merchant   = (string) ( $gateway_cfg['merchant_id'] ?? '' );

		if ( ! self::passphrase_policy_ok( $sandbox, $passphrase ) ) {
			return new WP_Error( 'ngc_pf_passphrase', 'Passphrase required outside sandbox.', [ 'status' => 403 ] );
		}

		if ( ! self::signature_valid( $posted, $passphrase ) ) {
			return new WP_Error( 'ngc_pf_signature', 'Invalid ITN signature.', [ 'status' => 400 ] );
		}

		if ( ! self::merchant_matches( (string) ( $posted['merchant_id'] ?? '' ), $merchant ) ) {
			return new WP_Error( 'ngc_pf_merchant', 'Merchant ID mismatch.', [ 'status' => 400 ] );
		}

		if ( ! self::amount_matches( (string) ( $posted['amount_gross'] ?? $posted['amount'] ?? '' ), $order_total ) ) {
			return new WP_Error( 'ngc_pf_amount', 'Amount mismatch.', [ 'status' => 400 ] );
		}

		$pf_id = (string) ( $posted['pf_payment_id'] ?? '' );
		if ( $pf_id && self::is_replay( $pf_id ) ) {
			return new WP_Error( 'ngc_pf_replay', 'Duplicate ITN (replay).', [ 'status' => 200, 'idempotent' => true ] );
		}

		if ( $pf_id && $prior_pf_id && hash_equals( (string) $prior_pf_id, $pf_id ) ) {
			return new WP_Error( 'ngc_pf_replay', 'Duplicate ITN (order meta).', [ 'status' => 200, 'idempotent' => true ] );
		}

		return true;
	}
}
