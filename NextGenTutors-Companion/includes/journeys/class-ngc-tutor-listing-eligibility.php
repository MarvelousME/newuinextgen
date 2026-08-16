<?php
/**
 * Tutor listing eligibility — Match RC-02 (mandatory safety subset).
 *
 * Soft-gated: returns eligibility for callers; does not auto-publish.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mandatory ID + Background + Training before listing go-live.
 */
final class NGC_Tutor_Listing_Eligibility {

	/**
	 * Required verification keys (Match).
	 *
	 * @return string[]
	 */
	public static function mandatory_checks() {
		return apply_filters(
			'ngc_tutor_listing_mandatory_checks',
			[ 'id_verified', 'background_check', 'training_complete' ]
		);
	}

	/**
	 * @param int $tutor_user_id Tutor user ID.
	 * @return array{eligible:bool,missing:string[],checks:array<string,bool>}
	 */
	public static function evaluate( $tutor_user_id ) {
		$tutor_user_id = (int) $tutor_user_id;
		$checks        = [];
		$missing       = [];

		foreach ( self::mandatory_checks() as $key ) {
			$ok = (bool) apply_filters(
				'ngc_tutor_listing_check_' . sanitize_key( $key ),
				(bool) get_user_meta( $tutor_user_id, 'ngt_' . sanitize_key( $key ), true ),
				$tutor_user_id
			);
			$checks[ $key ] = $ok;
			if ( ! $ok ) {
				$missing[] = $key;
			}
		}

		return [
			'eligible' => empty( $missing ),
			'missing'  => $missing,
			'checks'   => $checks,
		];
	}

	/**
	 * Whether auto-publish is allowed (default false — Match conservative).
	 *
	 * @param int $tutor_user_id Tutor.
	 * @return bool
	 */
	public static function may_auto_publish( $tutor_user_id ) {
		$result = self::evaluate( $tutor_user_id );
		$allow  = (bool) apply_filters( 'ngc_tutor_listing_auto_publish', false, $tutor_user_id, $result );
		return $result['eligible'] && $allow;
	}
}
