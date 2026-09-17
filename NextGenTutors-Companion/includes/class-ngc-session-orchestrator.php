<?php
/**
 * Session orchestrator — single authoritative NGT session lifecycle.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates booking, commerce, learning, and meeting adapters.
 * Business rules live here, not scattered across WordPress hooks.
 */
class NGC_Session_Orchestrator {

	/** @var bool */
	public static $provisioning = false;

	/**
	 * @return void
	 */
	public static function init() {
		add_action( 'ngc_booking_confirmed', [ __CLASS__, 'on_booking_confirmed' ], 5, 2 );
		add_action( 'ngc_booking_status_changed', [ __CLASS__, 'on_status_changed' ], 5, 3 );
		add_action( 'ngc_payment_settled', [ __CLASS__, 'on_payment_settled' ], 5, 2 );
		add_action( 'ngc_payment_refunded', [ __CLASS__, 'on_payment_refunded' ], 5, 2 );
		add_action( 'woocommerce_order_status_processing', [ __CLASS__, 'on_order_paid_status' ], 40 );
		add_action( 'woocommerce_order_status_cancelled', [ __CLASS__, 'on_order_cancelled' ], 40 );
		add_action( 'init', [ __CLASS__, 'maybe_open_join_windows' ], 30 );
		add_filter( 'ngc_session_join_url', [ __CLASS__, 'filter_join_url' ], 10, 2 );
	}

	/**
	 * @param int                  $booking_id Booking ID.
	 * @param array<string, mixed> $context    Context.
	 * @return array<string, mixed>|WP_Error|null
	 */
	public static function on_booking_confirmed( $booking_id, $context = [] ) {
		if ( self::$provisioning ) {
			return null;
		}
		$order_id = (int) ( $context['order_id'] ?? 0 );
		if ( ! $order_id && class_exists( 'NGC_Bookings' ) ) {
			$booking = NGC_Bookings::get( (int) $booking_id );
			$order_id = $booking ? (int) ( $booking->order_id ?? 0 ) : 0;
		}
		return self::ensure( $order_id, (int) $booking_id, is_array( $context ) ? $context : [] );
	}

	/**
	 * @param int    $booking_id Booking.
	 * @param string $from       From.
	 * @param string $to         To.
	 * @return void
	 */
	public static function on_status_changed( $booking_id, $from, $to ) {
		if ( 'confirmed' === $to ) {
			self::on_booking_confirmed( $booking_id, [ 'from_status' => $from ] );
		}
		if ( 'cancelled' === $to ) {
			self::cancel_session_for_booking( (int) $booking_id, 'cancelled' );
		}
		if ( 'completed' === $to ) {
			$session = NGC_Session_Repository::get_by_booking_id( (int) $booking_id );
			if ( $session && NGC_Session_State_Machine::can_transition( (string) $session['status'], NGC_Session_States::COMPLETED ) ) {
				NGC_Session_Repository::transition( (int) $session['id'], NGC_Session_States::COMPLETED );
			}
		}
	}

	/**
	 * @param int                  $order_id Order.
	 * @param array<string, mixed> $context  Context.
	 * @return void
	 */
	public static function on_payment_settled( $order_id, $context = [] ) {
		$booking_id = (int) ( $context['booking_id'] ?? 0 );
		if ( ! $booking_id && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( (int) $order_id );
			$booking_id = $order ? (int) $order->get_meta( 'ngc_booking_id' ) : 0;
		}
		self::ensure( (int) $order_id, $booking_id, is_array( $context ) ? $context : [] );
	}

	/**
	 * @param int $order_id Order.
	 * @return void
	 */
	public static function on_order_paid_status( $order_id ) {
		self::on_payment_settled( (int) $order_id, [] );
	}

	/**
	 * @param int                  $order_id Order.
	 * @param array<string, mixed> $context  Context.
	 * @return void
	 */
	public static function on_payment_refunded( $order_id, $context = [] ) {
		$session = NGC_Session_Repository::get_by_order_id( (int) $order_id );
		if ( ! $session ) {
			return;
		}
		self::cancel_session( $session, NGC_Session_States::REFUNDED );
		unset( $context );
	}

	/**
	 * @param int $order_id Order.
	 * @return void
	 */
	public static function on_order_cancelled( $order_id ) {
		$session = NGC_Session_Repository::get_by_order_id( (int) $order_id );
		if ( $session ) {
			self::cancel_session( $session, NGC_Session_States::CANCELLED );
		}
	}

	/**
	 * @param int                  $order_id   Order.
	 * @param int                  $booking_id Booking.
	 * @param array<string, mixed> $context    Context.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function ensure( $order_id, $booking_id = 0, array $context = [] ) {
		self::$provisioning = true;
		try {
			return NGC_Ensure_Session_Provisioned::run( (int) $order_id, (int) $booking_id, $context );
		} finally {
			self::$provisioning = false;
		}
	}

	/**
	 * Open join windows for ready sessions whose start is within policy.
	 *
	 * @return void
	 */
	public static function maybe_open_join_windows() {
		if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_cron() ) {
			return;
		}
		if ( get_transient( 'ngc_session_join_window_tick' ) ) {
			return;
		}
		set_transient( 'ngc_session_join_window_tick', 1, 30 );
		if ( ! class_exists( 'NGC_Session_Repository' ) ) {
			return;
		}
		$ready = NGC_Session_Repository::query( [ 'status' => NGC_Session_States::READY, 'limit' => 50 ] );
		foreach ( $ready as $session ) {
			$window = NGC_Session_Join_Policy::evaluate( $session );
			if ( ! empty( $window['allowed'] ) ) {
				NGC_Session_Repository::transition( (int) $session['id'], NGC_Session_States::JOIN_WINDOW_OPEN );
			}
		}
	}

	/**
	 * Never expose a raw meeting URL through the old filter.
	 *
	 * @param string $url        Incoming.
	 * @param int    $booking_id Booking.
	 * @return string
	 */
	public static function filter_join_url( $url, $booking_id ) {
		unset( $url, $booking_id );
		return '';
	}

	/**
	 * @param int    $booking_id Booking.
	 * @param string $to         Session status.
	 * @return void
	 */
	public static function cancel_session_for_booking( $booking_id, $to ) {
		$session = NGC_Session_Repository::get_by_booking_id( (int) $booking_id );
		if ( $session ) {
			self::cancel_session( $session, $to );
		}
	}

	/**
	 * @param array<string, mixed> $session Session.
	 * @param string               $to      cancelled|refunded.
	 * @return void
	 */
	private static function cancel_session( array $session, $to ) {
		if ( NGC_Session_State_Machine::can_transition( (string) $session['status'], $to ) ) {
			NGC_Session_Repository::transition( (int) $session['id'], $to, [ 'meeting_status' => 'revoked' ] );
		}
		$adapter = new NGC_Session_Meeting_Adapter();
		$adapter->revoke( $session );
		( new NGC_Session_Audit_Adapter() )->record(
			$to === NGC_Session_States::REFUNDED ? 'refund_created' : 'session_cancelled',
			'session',
			(int) $session['id'],
			'success',
			[ 'correlation_id' => $session['correlation_id'] ]
		);
	}
}
