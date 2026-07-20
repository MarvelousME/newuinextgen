<?php
/**
 * Referral capture and conversion (integrate WF-01/WF-02 extension).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * POPIA-aware referral ledger using ngc_referrals table.
 */
class NGC_Referrals {

	const REWARD_DEFAULT = 50.0;

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'user_register', [ __CLASS__, 'on_user_register' ], 20, 1 );
		add_action( 'ngc_workflow_dispatched', [ __CLASS__, 'on_payment_completed' ], 10, 2 );
	}

	/**
	 * @param int $user_id New user ID.
	 */
	public static function on_user_register( $user_id ) {
		if ( class_exists( 'NGC_Platform_Tracking' ) && ! NGC_Platform_Tracking::marketing_capture_allowed() ) {
			return;
		}
		$ref = isset( $_COOKIE['ngc_ref'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			? absint( wp_unslash( $_COOKIE['ngc_ref'] ) )
			: 0;
		if ( ! $ref && isset( $_REQUEST['ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$ref = absint( wp_unslash( $_REQUEST['ref'] ) );
		}
		if ( ! $ref || $ref === (int) $user_id ) {
			return;
		}
		$referrer = get_userdata( $ref );
		if ( ! $referrer ) {
			return;
		}
		self::create(
			[
				'referrer_id'   => $ref,
				'referred_id'   => (int) $user_id,
				'reward_amount' => (float) apply_filters( 'ngc_referral_reward_amount', self::REWARD_DEFAULT ),
				'status'        => 'pending',
			]
		);
		update_user_meta( (int) $user_id, 'ngc_referred_by', $ref );
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'referral_registered', 'referral', (int) $user_id, [ 'referrer_id' => $ref ], $ref );
		}
	}

	/**
	 * @param string               $full Event key.
	 * @param array<string, mixed> $vars Context.
	 */
	public static function on_payment_completed( $full, $vars ) {
		if ( 'ngt.payment.received' !== $full && 'woocommerce.order.completed' !== $full ) {
			return;
		}
		$user_id = (int) ( $vars['user_id'] ?? $vars['customer_id'] ?? 0 );
		if ( ! $user_id ) {
			return;
		}
		self::mark_converted_for_user( $user_id );
	}

	/**
	 * @param int $referred_id Referred user.
	 */
	public static function mark_converted_for_user( $referred_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'referrals' );
		if ( ! $table ) {
			return;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE referred_id = %d AND status = %s LIMIT 1",
				$referred_id,
				'pending'
			)
		);
		if ( ! $row ) {
			return;
		}
		$wpdb->update(
			$table,
			[
				'status'       => 'converted',
				'converted_at' => current_time( 'mysql', true ),
			],
			[ 'id' => (int) $row->id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
		if ( class_exists( 'NGC_Gamification' ) ) {
			do_action( 'ngc_referral_converted', (int) $row->referrer_id, $referred_id );
		}
		NGC_Workflows::dispatch(
			'referral.converted',
			[
				'referrer_id' => (string) $row->referrer_id,
				'referred_id' => (string) $referred_id,
				'reward'      => (string) $row->reward_amount,
			]
		);
	}

	/**
	 * @param array<string, mixed> $data Row data.
	 * @return int Insert id or 0.
	 */
	public static function create( $data ) {
		global $wpdb;
		$table = NGC_Database::table( 'referrals' );
		if ( ! $table ) {
			return 0;
		}
		$wpdb->insert(
			$table,
			[
				'referrer_id'   => (int) ( $data['referrer_id'] ?? 0 ),
				'referred_id'   => (int) ( $data['referred_id'] ?? 0 ),
				'reward_amount' => (float) ( $data['reward_amount'] ?? self::REWARD_DEFAULT ),
				'status'        => sanitize_key( (string) ( $data['status'] ?? 'pending' ) ),
				'created_at'    => current_time( 'mysql', true ),
			],
			[ '%d', '%d', '%f', '%s', '%s' ]
		);
		return (int) $wpdb->insert_id;
	}
}
