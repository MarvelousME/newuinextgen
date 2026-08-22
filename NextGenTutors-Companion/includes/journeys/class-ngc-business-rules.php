<?php
/**
 * Journey business rules — configurable options (never hard-code fees/SLAs in callers).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Option-backed rules for learner / tutor / safety journeys.
 */
final class NGC_Business_Rules {

	public const OPT_PREFIX = 'ngt_rule_';

	/**
	 * Default rule catalog.
	 *
	 * @return array<string, array{default:mixed,type:string,label:string}>
	 */
	public static function catalog() {
		return [
			'ngt.booking.first_booking_reward'       => [
				'default' => 100,
				'type'    => 'int',
				'label'   => 'First booking NGT points',
			],
			'ngt.tutor.verification_sla_hours'       => [
				'default' => 48,
				'type'    => 'int',
				'label'   => 'Tutor verification SLA (hours)',
			],
			'ngt.tutor.minimum_listing_badges'       => [
				'default' => 3,
				'type'    => 'int',
				'label'   => 'Legacy badge count (unused; mandatory checks gate listing)',
			],
			'ngt.tutor.minimum_rating'               => [
				'default' => 4.0,
				'type'    => 'float',
				'label'   => 'Minimum tutor rating before quality review',
			],
			'ngt.tutor.popular_review_threshold'     => [
				'default' => 50,
				'type'    => 'int',
				'label'   => 'Popular tutor = review count (Match: reviews not bookings)',
			],
			'ngt.payout.platform_fee_percent'        => [
				'default' => '15.00',
				'type'    => 'decimal',
				'label'   => 'Platform fee percent',
			],
			'ngt.payout.minimum_amount'              => [
				'default' => '100.00',
				'type'    => 'decimal',
				'label'   => 'Minimum payout amount (ZAR)',
			],
			'ngt.payout.timezone'                    => [
				'default' => 'Africa/Johannesburg',
				'type'    => 'string',
				'label'   => 'Payout business timezone',
			],
			'ngt.no_show.compensation_percent'       => [
				'default' => '50.00',
				'type'    => 'decimal',
				'label'   => 'No-show compensation percent (Match default; path UNVERIFIED)',
			],
			'ngt.referral.reward_amount'             => [
				'default' => '50.00',
				'type'    => 'decimal',
				'label'   => 'Referral reward amount',
			],
			'ngt.safety.high_priority_response_sla'  => [
				'default' => 2,
				'type'    => 'int',
				'label'   => 'Critical safety response SLA (hours)',
			],
			'ngt.safety.minimum_listing_checks'      => [
				'default' => 3,
				'type'    => 'int',
				'label'   => 'Mandatory listing checks: ID + Background + Training',
			],
			'ngt.journey.learner_enabled'            => [
				'default' => '1',
				'type'    => 'bool',
				'label'   => 'Learner journey workflows enabled',
			],
			'ngt.journey.tutor_enabled'              => [
				'default' => '1',
				'type'    => 'bool',
				'label'   => 'Tutor journey workflows enabled',
			],
			'ngt.journey.safety_enabled'             => [
				'default' => '1',
				'type'    => 'bool',
				'label'   => 'Safety journey workflows enabled',
			],
			'ngt.journey.disable_automatorwp_core'   => [
				'default' => '1',
				'type'    => 'bool',
				'label'   => 'Block AutomatorWP core side effects when authority on',
			],
			'ngt.session.recording_enabled'          => [
				'default' => '0',
				'type'    => 'bool',
				'label'   => 'Session recording (Match: OFF — not offered)',
			],
		];
	}

	/**
	 * @param string $key Rule key e.g. ngt.payout.platform_fee_percent.
	 * @return mixed
	 */
	public static function get( $key ) {
		$catalog = self::catalog();
		$key     = (string) $key;
		$def     = $catalog[ $key ]['default'] ?? null;
		$opt     = self::OPT_PREFIX . md5( $key );
		$stored  = get_option( $opt, null );
		if ( null === $stored || false === $stored ) {
			$value = $def;
		} else {
			$value = $stored;
		}
		$type = $catalog[ $key ]['type'] ?? 'string';
		return self::cast( $value, $type );
	}

	/**
	 * @param string $key   Rule key.
	 * @param mixed  $value Value.
	 * @return bool
	 */
	public static function set( $key, $value ) {
		$catalog = self::catalog();
		if ( ! isset( $catalog[ $key ] ) ) {
			return false;
		}
		$opt = self::OPT_PREFIX . md5( $key );
		return update_option( $opt, $value, false );
	}

	/**
	 * Seed missing options once.
	 */
	public static function maybe_seed() {
		if ( '1' === (string) get_option( 'ngt_business_rules_seeded_v1', '' ) ) {
			return;
		}
		foreach ( self::catalog() as $key => $meta ) {
			$opt = self::OPT_PREFIX . md5( $key );
			if ( false === get_option( $opt, false ) ) {
				add_option( $opt, $meta['default'], '', false );
			}
		}
		update_option( 'ngt_business_rules_seeded_v1', '1', false );
	}

	/**
	 * Journey feature flag.
	 *
	 * @param string $journey learner|tutor|safety.
	 * @return bool
	 */
	public static function journey_enabled( $journey ) {
		$map = [
			'learner' => 'ngt.journey.learner_enabled',
			'tutor'   => 'ngt.journey.tutor_enabled',
			'safety'  => 'ngt.journey.safety_enabled',
		];
		$key = $map[ sanitize_key( $journey ) ] ?? '';
		return $key ? (bool) self::get( $key ) : false;
	}

	/**
	 * Decimal fee as string for bcmath-safe math (fallback to float).
	 *
	 * @param string $gross_minor Gross amount as decimal string.
	 * @return string Net after platform fee (decimal string).
	 */
	public static function apply_platform_fee( $gross_minor ) {
		$gross = (string) $gross_minor;
		$pct   = (string) self::get( 'ngt.payout.platform_fee_percent' );
		if ( function_exists( 'bcmul' ) && function_exists( 'bcsub' ) && function_exists( 'bcdiv' ) ) {
			$fee = bcmul( $gross, bcdiv( $pct, '100', 6 ), 4 );
			return bcsub( $gross, $fee, 2 );
		}
		$g = (float) $gross;
		$p = (float) $pct;
		return number_format( $g - ( $g * ( $p / 100.0 ) ), 2, '.', '' );
	}

	/**
	 * @param mixed  $value Value.
	 * @param string $type  Type.
	 * @return mixed
	 */
	private static function cast( $value, $type ) {
		switch ( $type ) {
			case 'int':
				return (int) $value;
			case 'float':
				return (float) $value;
			case 'bool':
				return in_array( (string) $value, [ '1', 'true', 'yes', 'on' ], true );
			case 'decimal':
				return number_format( (float) $value, 2, '.', '' );
			default:
				return (string) $value;
		}
	}
}
