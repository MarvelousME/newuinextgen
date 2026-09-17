<?php
/**
 * Server-authoritative join window policy.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Join window: frontend countdown is cosmetic; this class is authoritative.
 */
class NGC_Session_Join_Policy {

	public const OPTION_BEFORE = 'ngc_session_join_before_minutes';
	public const OPTION_AFTER  = 'ngc_session_join_after_minutes';

	/**
	 * @return int
	 */
	public static function before_minutes() {
		$default = 5;
		if ( function_exists( 'get_option' ) ) {
			$default = (int) get_option( self::OPTION_BEFORE, 5 );
		}
		return max( 0, $default );
	}

	/**
	 * @return int
	 */
	public static function after_minutes() {
		$default = 15;
		if ( function_exists( 'get_option' ) ) {
			$default = (int) get_option( self::OPTION_AFTER, 15 );
		}
		return max( 0, $default );
	}

	/**
	 * Evaluate join eligibility.
	 *
	 * @param array<string, mixed> $session Session array.
	 * @param int                  $now     Unix timestamp (injectable).
	 * @return array{allowed:bool,reason:string,opens_at:?int,closes_at:?int,seconds_until_open:int,label:string,status:string}
	 */
	public static function evaluate( array $session, $now = 0 ) {
		$now    = $now > 0 ? (int) $now : time();
		$status = (string) ( $session['status'] ?? '' );
		$pay    = (string) ( $session['payment_status'] ?? '' );

		if ( in_array( $status, [ NGC_Session_States::CANCELLED, NGC_Session_States::REFUNDED, NGC_Session_States::FAILED, NGC_Session_States::COMPLETED ], true ) ) {
			return self::result( false, 'session_closed', $status, null, null, 0, 'SESSION CLOSED' );
		}

		if ( 'paid' !== $pay && ! in_array( $status, NGC_Session_States::joinable(), true ) && NGC_Session_States::PAID !== $status && NGC_Session_States::BOOKING_CONFIRMED !== $status && NGC_Session_States::PROVISIONING !== $status ) {
			if ( in_array( $status, [ NGC_Session_States::DRAFT, NGC_Session_States::AWAITING_PAYMENT ], true ) || 'unpaid' === $pay || 'failed' === $pay ) {
				return self::result( false, 'payment_required', $status, null, null, 0, 'PAYMENT REQUIRED' );
			}
		}

		if ( NGC_Session_States::PROVISIONING === $status || NGC_Session_States::FAILED === $status ) {
			return self::result( false, 'not_ready', $status, null, null, 0, 'LESSON NOT READY' );
		}

		if ( ! in_array( $status, array_merge( NGC_Session_States::joinable(), [ NGC_Session_States::PAID, NGC_Session_States::BOOKING_CONFIRMED ] ), true ) ) {
			return self::result( false, 'status_' . $status, $status, null, null, 0, 'LESSON NOT READY' );
		}

		$start = self::parse_ts( $session['scheduled_start'] ?? '' );
		$end   = self::parse_ts( $session['scheduled_end'] ?? '' );
		if ( ! $start ) {
			return self::result( false, 'missing_schedule', $status, null, null, 0, 'LESSON NOT READY' );
		}
		if ( ! $end ) {
			$duration = (int) ( $session['duration_minutes'] ?? 60 );
			$end      = $start + ( max( 1, $duration ) * 60 );
		}

		$opens  = $start - ( self::before_minutes() * 60 );
		$closes = $end + ( self::after_minutes() * 60 );

		if ( $now < $opens ) {
			$until = $opens - $now;
			$label = sprintf( 'Lesson starts in %s', self::format_countdown( $start - $now ) );
			return self::result( false, 'too_early', $status, $opens, $closes, $until, $label );
		}

		if ( $now > $closes ) {
			return self::result( false, 'too_late', $status, $opens, $closes, 0, 'SESSION CLOSED' );
		}

		return self::result( true, 'open', $status, $opens, $closes, 0, 'JOIN LESSON' );
	}

	/**
	 * @param mixed $value Datetime string or timestamp.
	 * @return int
	 */
	public static function parse_ts( $value ) {
		if ( is_numeric( $value ) && (int) $value > 0 ) {
			return (int) $value;
		}
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return 0;
		}
		$ts = strtotime( $value . ( false === strpos( $value, 'T' ) && false === strpos( $value, 'Z' ) ? ' UTC' : '' ) );
		return $ts ? (int) $ts : 0;
	}

	/**
	 * @param int $seconds Seconds.
	 * @return string
	 */
	public static function format_countdown( $seconds ) {
		$seconds = max( 0, (int) $seconds );
		$h       = (int) floor( $seconds / 3600 );
		$m       = (int) floor( ( $seconds % 3600 ) / 60 );
		$s       = $seconds % 60;
		if ( $h > 0 ) {
			return sprintf( '%02d:%02d:%02d', $h, $m, $s );
		}
		return sprintf( '%02d:%02d', $m, $s );
	}

	/**
	 * @param bool     $allowed Allowed.
	 * @param string   $reason  Reason.
	 * @param string   $status  Status.
	 * @param int|null $opens   Opens at.
	 * @param int|null $closes  Closes at.
	 * @param int      $until   Seconds until open.
	 * @param string   $label   Label.
	 * @return array<string, mixed>
	 */
	private static function result( $allowed, $reason, $status, $opens, $closes, $until, $label ) {
		return [
			'allowed'            => (bool) $allowed,
			'reason'             => (string) $reason,
			'opens_at'           => $opens,
			'closes_at'          => $closes,
			'seconds_until_open' => (int) $until,
			'label'              => (string) $label,
			'status'             => (string) $status,
			'can_join'           => (bool) $allowed,
		];
	}
}
