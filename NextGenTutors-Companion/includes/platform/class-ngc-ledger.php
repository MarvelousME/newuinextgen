<?php
/**
 * Double-entry ledger — balanced journals only.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post journals; refuse unbalanced.
 */
final class NGC_Ledger {

	/**
	 * Hook payment settlement.
	 */
	public static function init() {
		add_action( 'ngc_payment_settled', [ __CLASS__, 'on_payment_settled' ], 20, 2 );
		add_action( 'ngc_payment_refunded', [ __CLASS__, 'on_payment_refunded' ], 20, 2 );
		add_action( 'ngc_payout_completed', [ __CLASS__, 'on_payout_completed' ], 20, 2 );
		add_action( 'ngc_wallet_credited', [ __CLASS__, 'on_wallet_credited' ], 20, 2 );
	}

	/**
	 * Post a balanced journal.
	 *
	 * @param array  $lines  Lines: [ ['account'=>'cash','debit'=>10,'credit'=>0], ... ].
	 * @param array  $meta   source, source_ref, memo, currency, idempotency_key.
	 * @return int|WP_Error Journal id.
	 */
	public static function post( array $lines, array $meta = [] ) {
		global $wpdb;
		$tenant = NGC_Tenant_Context::id();
		$debit  = 0.0;
		$credit = 0.0;
		$clean  = [];

		foreach ( $lines as $line ) {
			$acct = sanitize_key( (string) ( $line['account'] ?? '' ) );
			$d    = round( (float) ( $line['debit'] ?? 0 ), 2 );
			$c    = round( (float) ( $line['credit'] ?? 0 ), 2 );
			if ( $acct === '' || ( $d <= 0 && $c <= 0 ) || ( $d > 0 && $c > 0 ) ) {
				return new WP_Error( 'ngc_ledger_bad_line', 'Invalid ledger line.' );
			}
			$debit  += $d;
			$credit += $c;
			$clean[] = [ 'account' => $acct, 'debit' => $d, 'credit' => $c ];
		}

		if ( abs( $debit - $credit ) > 0.001 || $debit <= 0 ) {
			return new WP_Error( 'ngc_ledger_unbalanced', 'Journal is unbalanced.', [ 'debit' => $debit, 'credit' => $credit ] );
		}

		$idem = isset( $meta['idempotency_key'] ) ? (string) $meta['idempotency_key'] : '';
		if ( $idem !== '' ) {
			$existing = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT id FROM ' . NGC_Platform_Schema::table( 'gl_journals' ) . ' WHERE tenant_id = %d AND idempotency_key = %s LIMIT 1',
					$tenant,
					$idem
				)
			);
			if ( $existing ) {
				return $existing;
			}
		}

		$uuid = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'j_', true );
		$jtable = NGC_Platform_Schema::table( 'gl_journals' );
		$etable = NGC_Platform_Schema::table( 'gl_entries' );

		$wpdb->query( 'START TRANSACTION' );
		$ok = $wpdb->insert(
			$jtable,
			[
				'tenant_id'       => $tenant,
				'journal_uuid'    => $uuid,
				'idempotency_key' => $idem,
				'source'          => sanitize_key( (string) ( $meta['source'] ?? 'manual' ) ),
				'source_ref'      => substr( (string) ( $meta['source_ref'] ?? '' ), 0, 191 ),
				'memo'            => (string) ( $meta['memo'] ?? '' ),
				'currency'        => sanitize_text_field( (string) ( $meta['currency'] ?? 'ZAR' ) ),
				'posted_at'       => current_time( 'mysql', true ),
				'created_by'      => get_current_user_id(),
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' ]
		);
		if ( ! $ok ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'ngc_ledger_insert_failed', 'Failed to insert journal.' );
		}
		$jid = (int) $wpdb->insert_id;
		foreach ( $clean as $line ) {
			$wpdb->insert(
				$etable,
				[
					'tenant_id'    => $tenant,
					'journal_id'   => $jid,
					'account_code' => $line['account'],
					'debit'        => $line['debit'],
					'credit'       => $line['credit'],
				],
				[ '%d', '%d', '%s', '%f', '%f' ]
			);
		}
		$wpdb->query( 'COMMIT' );

		if ( class_exists( 'NGC_Immutable_Audit' ) ) {
			NGC_Immutable_Audit::append( 'ledger.post', 'gl_journal', $jid, [ 'uuid' => $uuid, 'debit' => $debit ] );
		}
		if ( class_exists( 'NGC_Metrics' ) ) {
			NGC_Metrics::inc( 'ledger_journals_total', 1 );
		}

		return $jid;
	}

	/**
	 * Account balance (debits - credits for debit-normal; inverse for credit-normal).
	 *
	 * @param string $account_code Code.
	 * @return float
	 */
	public static function balance( $account_code ) {
		global $wpdb;
		$tenant = NGC_Tenant_Context::id();
		$row    = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(debit),0) AS d, COALESCE(SUM(credit),0) AS c FROM ' . NGC_Platform_Schema::table( 'gl_entries' ) . ' WHERE tenant_id = %d AND account_code = %s',
				$tenant,
				sanitize_key( (string) $account_code )
			)
		);
		$d = (float) ( $row->d ?? 0 );
		$c = (float) ( $row->c ?? 0 );
		$normal = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT normal_balance FROM ' . NGC_Platform_Schema::table( 'gl_accounts' ) . ' WHERE tenant_id = %d AND code = %s LIMIT 1',
				$tenant,
				sanitize_key( (string) $account_code )
			)
		);
		if ( 'credit' === $normal ) {
			return round( $c - $d, 2 );
		}
		return round( $d - $c, 2 );
	}

	/**
	 * @param int   $order_id Order.
	 * @param array $ctx      Context.
	 */
	public static function on_payment_settled( $order_id, $ctx = [] ) {
		$amount = isset( $ctx['amount'] ) ? (float) $ctx['amount'] : 0.0;
		if ( $amount <= 0 && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$amount = (float) $order->get_total();
			}
		}
		if ( $amount <= 0 ) {
			return;
		}
		$fee   = isset( $ctx['fee'] ) ? (float) $ctx['fee'] : round( $amount * 0.1, 2 );
		$tutor = round( $amount - $fee, 2 );
		self::post(
			[
				[ 'account' => 'cash', 'debit' => $amount, 'credit' => 0 ],
				[ 'account' => 'fees', 'debit' => 0, 'credit' => $fee ],
				[ 'account' => 'tutor_payable', 'debit' => 0, 'credit' => $tutor ],
			],
			[
				'source'          => 'payment',
				'source_ref'      => (string) $order_id,
				'idempotency_key' => 'pay-settle:' . (int) $order_id,
				'memo'            => 'Payment settlement',
				'currency'        => $ctx['currency'] ?? 'ZAR',
			]
		);
	}

	/**
	 * @param int   $order_id Order.
	 * @param array $ctx      Context.
	 */
	public static function on_payment_refunded( $order_id, $ctx = [] ) {
		$amount = isset( $ctx['amount'] ) ? (float) $ctx['amount'] : 0.0;
		if ( $amount <= 0 ) {
			return;
		}
		self::post(
			[
				[ 'account' => 'refunds', 'debit' => $amount, 'credit' => 0 ],
				[ 'account' => 'cash', 'debit' => 0, 'credit' => $amount ],
			],
			[
				'source'          => 'refund',
				'source_ref'      => (string) $order_id,
				'idempotency_key' => 'pay-refund:' . (int) $order_id . ':' . $amount,
				'memo'            => 'Payment refund',
			]
		);
	}

	/**
	 * @param int   $payout_id Payout.
	 * @param array $ctx       Context.
	 */
	public static function on_payout_completed( $payout_id, $ctx = [] ) {
		$amount = isset( $ctx['amount'] ) ? (float) $ctx['amount'] : 0.0;
		if ( $amount <= 0 ) {
			return;
		}
		self::post(
			[
				[ 'account' => 'tutor_payable', 'debit' => $amount, 'credit' => 0 ],
				[ 'account' => 'cash', 'debit' => 0, 'credit' => $amount ],
			],
			[
				'source'          => 'payout',
				'source_ref'      => (string) $payout_id,
				'idempotency_key' => 'payout:' . (int) $payout_id,
				'memo'            => 'Tutor payout',
			]
		);
	}

	/**
	 * @param int   $user_id User.
	 * @param array $ctx     Context with amount, reference.
	 */
	public static function on_wallet_credited( $user_id, $ctx = [] ) {
		$amount = isset( $ctx['amount'] ) ? (float) $ctx['amount'] : 0.0;
		$ref    = isset( $ctx['reference'] ) ? (string) $ctx['reference'] : ( 'u' . (int) $user_id );
		if ( $amount <= 0 ) {
			return;
		}
		self::post(
			[
				[ 'account' => 'cash', 'debit' => $amount, 'credit' => 0 ],
				[ 'account' => 'wallet_liability', 'debit' => 0, 'credit' => $amount ],
			],
			[
				'source'          => 'wallet',
				'source_ref'      => $ref,
				'idempotency_key' => 'wallet-credit:' . $ref,
				'memo'            => 'Wallet credit',
			]
		);
	}
}
