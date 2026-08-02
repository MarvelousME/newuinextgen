<?php
/**
 * User wallet ledger.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wallet credit/debit operations.
 */
class NGC_Wallet {

	/**
	 * @param int    $user_id User ID.
	 * @return float
	 */
	public static function balance( $user_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'wallet_ledger' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$balance = $wpdb->get_var( $wpdb->prepare( "SELECT balance_after FROM {$table} WHERE user_id = %d ORDER BY id DESC LIMIT 1", $user_id ) );
		return null === $balance ? 0.0 : (float) $balance;
	}

	/**
	 * @param int    $user_id     User ID.
	 * @param float  $amount      Amount.
	 * @param string $reference   Reference.
	 * @param string $description Description.
	 * @return int|WP_Error Ledger entry ID.
	 */
	public static function credit( $user_id, $amount, $reference = '', $description = '' ) {
		return self::entry( $user_id, 'credit', $amount, $reference, $description );
	}

	/**
	 * @param int    $user_id     User ID.
	 * @param float  $amount      Amount.
	 * @param string $reference   Reference.
	 * @param string $description Description.
	 * @return int|WP_Error
	 */
	public static function debit( $user_id, $amount, $reference = '', $description = '' ) {
		$balance = self::balance( $user_id );
		if ( $amount > $balance ) {
			return new WP_Error( 'ngc_insufficient_balance', __( 'Insufficient wallet balance.', 'nextgencompanion' ) );
		}
		return self::entry( $user_id, 'debit', $amount, $reference, $description );
	}

	/**
	 * @param int    $user_id     User ID.
	 * @param string $type        credit|debit.
	 * @param float  $amount      Amount.
	 * @param string $reference   Reference.
	 * @param string $description Description.
	 * @return int|WP_Error
	 */
	private static function entry( $user_id, $type, $amount, $reference, $description ) {
		global $wpdb;
		$amount  = abs( (float) $amount );
		$reference = sanitize_text_field( $reference );
		$table = NGC_Database::table( 'wallet_ledger' );

		if ( $reference !== '' ) {
			$existing = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE reference = %s LIMIT 1",
					$reference
				)
			);
			if ( $existing ) {
				return $existing;
			}
		}

		$balance = self::balance( $user_id );
		if ( 'credit' === $type ) {
			$balance += $amount;
		} else {
			$balance -= $amount;
		}

		$inserted = $wpdb->insert(
			$table,
			[
				'user_id'        => (int) $user_id,
				'entry_type'     => sanitize_key( $type ),
				'amount'         => $amount,
				'currency'       => 'ZAR',
				'balance_after'  => $balance,
				'reference'      => $reference,
				'description'    => sanitize_text_field( $description ),
				'created_at'     => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%f', '%s', '%f', '%s', '%s', '%s' ]
		);

		if ( ! $inserted ) {
			return new WP_Error( 'ngc_wallet_failed', __( 'Wallet entry failed.', 'nextgencompanion' ) );
		}

		update_user_meta( $user_id, 'ngc_wallet_balance', $balance );
		NGC_Audit::log( 'wallet_' . $type, 'wallet', (int) $wpdb->insert_id, [ 'amount' => $amount, 'user_id' => $user_id ] );
		do_action(
			'ngc_wallet_' . ( 'credit' === $type ? 'credited' : 'debited' ),
			(int) $user_id,
			[
				'amount'    => $amount,
				'reference' => $reference,
				'entry_id'  => (int) $wpdb->insert_id,
			]
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * @param int $user_id User ID.
	 * @param int $limit   Max rows.
	 * @return array<int, object>
	 */
	public static function ledger( $user_id, $limit = 20 ) {
		global $wpdb;
		$table = NGC_Database::table( 'wallet_ledger' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY id DESC LIMIT %d",
				(int) $user_id,
				(int) $limit
			)
		);
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		// Static API.
	}
}
