<?php
/**
 * NGT session lifecycle state machine.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates session status transitions.
 */
class NGC_Session_States {

	public const DRAFT              = 'draft';
	public const AWAITING_PAYMENT   = 'awaiting_payment';
	public const PAID               = 'paid';
	public const BOOKING_CONFIRMED  = 'booking_confirmed';
	public const PROVISIONING       = 'provisioning';
	public const READY              = 'ready';
	public const JOIN_WINDOW_OPEN   = 'join_window_open';
	public const IN_PROGRESS        = 'in_progress';
	public const COMPLETED          = 'completed';
	public const CANCELLED          = 'cancelled';
	public const REFUNDED           = 'refunded';
	public const FAILED             = 'failed';

	/**
	 * @return array<string, string[]>
	 */
	public static function map() {
		return [
			self::DRAFT             => [ self::AWAITING_PAYMENT, self::PAID, self::BOOKING_CONFIRMED, self::CANCELLED ],
			self::AWAITING_PAYMENT  => [ self::PAID, self::CANCELLED, self::FAILED ],
			self::PAID              => [ self::BOOKING_CONFIRMED, self::PROVISIONING, self::REFUNDED, self::CANCELLED ],
			self::BOOKING_CONFIRMED => [ self::PROVISIONING, self::READY, self::CANCELLED, self::REFUNDED ],
			self::PROVISIONING      => [ self::READY, self::FAILED, self::CANCELLED ],
			self::READY             => [ self::JOIN_WINDOW_OPEN, self::IN_PROGRESS, self::CANCELLED, self::REFUNDED ],
			self::JOIN_WINDOW_OPEN  => [ self::IN_PROGRESS, self::CANCELLED, self::READY ],
			self::IN_PROGRESS       => [ self::COMPLETED, self::CANCELLED ],
			self::COMPLETED         => [],
			self::CANCELLED         => [],
			self::REFUNDED          => [],
			self::FAILED            => [ self::AWAITING_PAYMENT, self::PROVISIONING, self::CANCELLED ],
		];
	}

	/**
	 * @param string $from From status.
	 * @param string $to   To status.
	 * @return bool
	 */
	public static function can_transition( $from, $to ) {
		$from = sanitize_key( (string) $from );
		$to   = sanitize_key( (string) $to );
		if ( $from === $to ) {
			return true;
		}
		$map = self::map();
		if ( ! isset( $map[ $from ] ) ) {
			return false;
		}
		return in_array( $to, $map[ $from ], true );
	}

	/**
	 * Statuses that may authorize join (subject to time window).
	 *
	 * @param string $status Status.
	 * @return bool
	 */
	public static function is_joinable( $status ) {
		return in_array(
			sanitize_key( (string) $status ),
			[ self::READY, self::JOIN_WINDOW_OPEN, self::IN_PROGRESS, self::BOOKING_CONFIRMED, self::PROVISIONING ],
			true
		);
	}
}
