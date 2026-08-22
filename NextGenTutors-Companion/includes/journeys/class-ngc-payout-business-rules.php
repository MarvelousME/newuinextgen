<?php
/**
 * Payout business rules — fee, minimum, timezone (no silent EFT).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies configurable payout thresholds before confirm.
 */
final class NGC_Payout_Business_Rules {

	/**
	 * Init.
	 */
	public static function init() {
		add_filter( 'ngc_payout_create_amount', [ __CLASS__, 'filter_amount' ], 10, 2 );
		add_action( 'ngc_monthly_payout_batch', [ __CLASS__, 'emit_cycle_started' ], 5 );
	}

	/**
	 * Emit canonical payout cycle event.
	 */
	public static function emit_cycle_started() {
		if ( class_exists( 'NGC_Journey_Events' ) ) {
			NGC_Journey_Events::emit(
				NGC_Journey_Events::TUTOR_PAYOUT_CYCLE_STARTED,
				[
					'timezone' => (string) NGC_Business_Rules::get( 'ngt.payout.timezone' ),
				],
				false
			);
		}
	}

	/**
	 * Classify payout eligibility.
	 *
	 * @param float $amount   Proposed amount.
	 * @param int   $tutor_id Tutor.
	 * @return float|WP_Error Filtered amount or error to skip.
	 */
	public static function filter_amount( $amount, $tutor_id = 0 ) {
		$min = (float) NGC_Business_Rules::get( 'ngt.payout.minimum_amount' );
		$amt = (float) $amount;
		if ( $amt < $min ) {
			if ( class_exists( 'NGC_Audit' ) ) {
				NGC_Audit::log(
					'payout_carry_forward',
					'tutor',
					(int) $tutor_id,
					[
						'amount'  => $amt,
						'minimum' => $min,
						'status'  => 'PAYOUT_CARRY_FORWARD',
					]
				);
			}
			return new WP_Error( 'ngc_payout_below_minimum', 'PAYOUT_CARRY_FORWARD' );
		}
		return $amt;
	}

	/**
	 * Net after platform fee (decimal string).
	 *
	 * @param float|string $gross Gross.
	 * @return string
	 */
	public static function net_after_fee( $gross ) {
		return NGC_Business_Rules::apply_platform_fee( (string) $gross );
	}
}
