<?php
/**
 * Explicit NGT session state machine.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates session status transitions. Pure logic.
 */
class NGC_Session_State_Machine {

	/**
	 * Allowed from → to map.
	 *
	 * @return array<string, string[]>
	 */
	public static function map() {
		return [
			NGC_Session_States::DRAFT             => [
				NGC_Session_States::AWAITING_PAYMENT,
				NGC_Session_States::PAID,
				NGC_Session_States::CANCELLED,
				NGC_Session_States::FAILED,
			],
			NGC_Session_States::AWAITING_PAYMENT  => [
				NGC_Session_States::PAID,
				NGC_Session_States::CANCELLED,
				NGC_Session_States::FAILED,
			],
			NGC_Session_States::PAID              => [
				NGC_Session_States::BOOKING_CONFIRMED,
				NGC_Session_States::PROVISIONING,
				NGC_Session_States::REFUNDED,
				NGC_Session_States::CANCELLED,
				NGC_Session_States::FAILED,
			],
			NGC_Session_States::BOOKING_CONFIRMED => [
				NGC_Session_States::PROVISIONING,
				NGC_Session_States::CANCELLED,
				NGC_Session_States::REFUNDED,
				NGC_Session_States::FAILED,
			],
			NGC_Session_States::PROVISIONING      => [
				NGC_Session_States::READY,
				NGC_Session_States::FAILED,
				NGC_Session_States::CANCELLED,
			],
			NGC_Session_States::READY             => [
				NGC_Session_States::JOIN_WINDOW_OPEN,
				NGC_Session_States::IN_PROGRESS,
				NGC_Session_States::CANCELLED,
				NGC_Session_States::REFUNDED,
				NGC_Session_States::FAILED,
			],
			NGC_Session_States::JOIN_WINDOW_OPEN  => [
				NGC_Session_States::IN_PROGRESS,
				NGC_Session_States::READY,
				NGC_Session_States::CANCELLED,
				NGC_Session_States::REFUNDED,
				NGC_Session_States::FAILED,
			],
			NGC_Session_States::IN_PROGRESS       => [
				NGC_Session_States::COMPLETED,
				NGC_Session_States::FAILED,
				NGC_Session_States::CANCELLED,
				NGC_Session_States::REFUNDED,
			],
			NGC_Session_States::FAILED            => [
				NGC_Session_States::PROVISIONING,
				NGC_Session_States::AWAITING_PAYMENT,
				NGC_Session_States::CANCELLED,
				NGC_Session_States::REFUNDED,
			],
			NGC_Session_States::COMPLETED         => [],
			NGC_Session_States::CANCELLED         => [],
			NGC_Session_States::REFUNDED          => [],
		];
	}

	/**
	 * @param string $from From.
	 * @param string $to   To.
	 * @return bool
	 */
	public static function can_transition( $from, $to ) {
		$from = (string) $from;
		$to   = (string) $to;
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
	 * @param string $from From.
	 * @param string $to   To.
	 * @return string Target status.
	 * @throws NGC_Session_Transition_Exception Invalid transition.
	 */
	public static function assert( $from, $to ) {
		$from = (string) $from;
		$to   = (string) $to;
		if ( ! NGC_Session_States::is_valid( $to ) ) {
			throw new NGC_Session_Transition_Exception( $from, $to );
		}
		if ( ! self::can_transition( $from, $to ) ) {
			throw new NGC_Session_Transition_Exception( $from, $to );
		}
		return $to;
	}

	/**
	 * Happy-path next status after payment confirmation.
	 *
	 * @param string $from Current.
	 * @return string|null
	 */
	public static function next_after_payment( $from ) {
		$from = (string) $from;
		if ( in_array( $from, [ NGC_Session_States::DRAFT, NGC_Session_States::AWAITING_PAYMENT, NGC_Session_States::FAILED ], true ) ) {
			return NGC_Session_States::PAID;
		}
		if ( NGC_Session_States::PAID === $from ) {
			return NGC_Session_States::BOOKING_CONFIRMED;
		}
		return null;
	}
}
