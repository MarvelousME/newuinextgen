<?php
/**
 * NGT session lifecycle states.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical session statuses. Pure constants — no WordPress I/O.
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
	 * @return string[]
	 */
	public static function all() {
		return [
			self::DRAFT,
			self::AWAITING_PAYMENT,
			self::PAID,
			self::BOOKING_CONFIRMED,
			self::PROVISIONING,
			self::READY,
			self::JOIN_WINDOW_OPEN,
			self::IN_PROGRESS,
			self::COMPLETED,
			self::CANCELLED,
			self::REFUNDED,
			self::FAILED,
		];
	}

	/**
	 * Terminal states (no further happy-path transitions).
	 *
	 * @return string[]
	 */
	public static function terminal() {
		return [ self::COMPLETED, self::CANCELLED, self::REFUNDED ];
	}

	/**
	 * States that may expose JOIN (policy still applies).
	 *
	 * @return string[]
	 */
	public static function joinable() {
		return [ self::READY, self::JOIN_WINDOW_OPEN, self::IN_PROGRESS ];
	}

	/**
	 * @param string $status Status.
	 * @return bool
	 */
	public static function is_valid( $status ) {
		return in_array( (string) $status, self::all(), true );
	}
}
