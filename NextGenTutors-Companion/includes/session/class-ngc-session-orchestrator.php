<?php
/**
 * NGT Session Orchestrator — single EnsureSessionProvisioned command.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates booking + commerce + MasterStudy + meeting into one session.
 */
class NGC_Session_Orchestrator {

	public const OPT_JOIN_BEFORE = 'ngc_session_join_before_minutes';
	public const OPT_JOIN_AFTER  = 'ngc_session_join_after_minutes';

	/**
	 * Hook registration.
	 */
	public static function init() {
		NGC_Sessions::ensure_schema();
		add_action( 'ngc_booking_confirmed', [ __CLASS__, 'on_booking_confirmed' ], 20, 2 );
		add_action( 'ngc_payment_settled', [ __CLASS__, 'on_payment_settled' ], 20, 2 );
		add_action( 'ngc_payment_refunded', [ __CLASS__, 'on_payment_refunded' ], 20, 2 );
		add_action( 'woocommerce_order_status_failed', [ __CLASS__, 'on_order_failed' ], 30 );
		add_action( 'ngc_booking_cancelled', [ __CLASS__, 'on_booking_cancelled' ], 20, 2 );
		add_action( 'ngc_booking_completed', [ __CLASS__, 'on_booking_completed' ], 20, 2 );
		add_action( 'init', [ __CLASS__, 'maybe_migrate_schema' ], 5 );
	}

	/**
	 * Ensure sessions table after upgrades.
	 */
	public static function maybe_migrate_schema() {
		NGC_Sessions::ensure_schema();
	}

	/**
	 * @param int                  $booking_id Booking ID.
	 * @param array<string, mixed> $context    Context.
	 */
	public static function on_booking_confirmed( $booking_id, $context = [] ) {
		self::ensure_provisioned(
			[
				'booking_id' => (int) $booking_id,
				'order_id'   => (int) ( $context['order_id'] ?? 0 ),
				'source'     => 'booking_confirmed',
			]
		);
	}

	/**
	 * @param int                  $order_id Order ID.
	 * @param array<string, mixed> $context  Context.
	 */
	public static function on_payment_settled( $order_id, $context = [] ) {
		$booking_id = (int) ( $context['booking_id'] ?? 0 );
		if ( ! $booking_id && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( (int) $order_id );
			if ( $order ) {
				$booking_id = (int) $order->get_meta( 'ngc_booking_id' );
			}
		}
		self::ensure_provisioned(
			[
				'order_id'   => (int) $order_id,
				'booking_id' => $booking_id,
				'source'     => 'payment_settled',
			]
		);
	}

	/**
	 * @param int                  $order_id Order ID.
	 * @param array<string, mixed> $context  Context.
	 */
	public static function on_payment_refunded( $order_id, $context = [] ) {
		$session = NGC_Sessions::get_by_order( (int) $order_id );
		if ( ! $session && ! empty( $context['booking_id'] ) ) {
			$session = NGC_Sessions::get_by_booking( (int) $context['booking_id'] );
		}
		if ( ! $session && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( (int) $order_id );
			if ( $order ) {
				$booking_id = (int) $order->get_meta( 'ngc_booking_id' );
				if ( $booking_id ) {
					$session = NGC_Sessions::get_by_booking( $booking_id );
					if ( $booking_id && class_exists( 'NGC_Bookings' ) ) {
						$booking = NGC_Bookings::get( $booking_id );
						if ( $booking && 'cancelled' !== $booking->status && 'completed' !== $booking->status ) {
							NGC_Bookings::transition( $booking_id, 'cancelled' );
						}
					}
				}
			}
		}
		if ( $session ) {
			self::mark_terminal( (int) $session->id, NGC_Session_States::REFUNDED, [ 'order_id' => (int) $order_id ] );
		}
		self::metric( 'payment_refund_total' );
	}

	/**
	 * @param int $order_id Order ID.
	 */
	public static function on_order_failed( $order_id ) {
		$order_id = (int) $order_id;
		$session  = NGC_Sessions::get_by_order( $order_id );
		if ( ! $session && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$booking_id = (int) $order->get_meta( 'ngc_booking_id' );
				$session    = $booking_id ? NGC_Sessions::get_by_booking( $booking_id ) : null;
			}
		}
		if ( $session ) {
			NGC_Sessions::update(
				(int) $session->id,
				[
					'payment_status' => 'failed',
					'status'         => NGC_Session_States::can_transition( $session->status, NGC_Session_States::FAILED )
						? NGC_Session_States::FAILED
						: $session->status,
				]
			);
			if ( class_exists( 'NGC_Audit' ) ) {
				NGC_Audit::log( 'payment_failed', 'session', (int) $session->id, [ 'order_id' => $order_id ] );
			}
		}
		self::metric( 'payment_failure_total' );
	}

	/**
	 * @param int                  $booking_id Booking ID.
	 * @param array<string, mixed> $context    Context.
	 */
	public static function on_booking_cancelled( $booking_id, $context = [] ) {
		$session = NGC_Sessions::get_by_booking( (int) $booking_id );
		if ( $session ) {
			self::mark_terminal( (int) $session->id, NGC_Session_States::CANCELLED, [ 'booking_id' => (int) $booking_id ] );
		}
	}

	/**
	 * @param int                  $booking_id Booking ID.
	 * @param array<string, mixed> $context    Context.
	 */
	public static function on_booking_completed( $booking_id, $context = [] ) {
		$session = NGC_Sessions::get_by_booking( (int) $booking_id );
		if ( ! $session ) {
			return;
		}
		$patch = [
			'completed_at' => current_time( 'mysql', true ),
			'lesson_status'=> 'completed',
		];
		if ( NGC_Session_States::can_transition( $session->status, NGC_Session_States::COMPLETED ) ) {
			$patch['status'] = NGC_Session_States::COMPLETED;
		}
		NGC_Sessions::update( (int) $session->id, $patch );
		self::metric( 'session_completion_total' );
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'session_completed', 'session', (int) $session->id, [ 'booking_id' => (int) $booking_id ] );
		}
	}

	/**
	 * Move session to a terminal status when allowed.
	 *
	 * @param int                  $session_id Session.
	 * @param string               $status     Terminal status.
	 * @param array<string, mixed> $meta       Audit meta.
	 */
	public static function mark_terminal( $session_id, $status, $meta = [] ) {
		$session = NGC_Sessions::get( (int) $session_id );
		if ( ! $session ) {
			return;
		}
		$patch = [
			'meeting_status' => 'closed',
			'cancelled_at'   => in_array( $status, [ NGC_Session_States::CANCELLED, NGC_Session_States::REFUNDED ], true )
				? current_time( 'mysql', true )
				: $session->cancelled_at,
		];
		if ( NGC_Session_States::REFUNDED === $status ) {
			$patch['payment_status'] = 'refunded';
		}
		if ( NGC_Session_States::can_transition( $session->status, $status ) ) {
			$patch['status'] = $status;
		}
		NGC_Sessions::update( (int) $session->id, $patch );
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'session_' . $status, 'session', (int) $session->id, $meta );
		}
	}

	/**
	 * @param string $key Metric key.
	 */
	private static function metric( $key ) {
		if ( class_exists( 'NGC_Metrics' ) && method_exists( 'NGC_Metrics', 'inc' ) ) {
			NGC_Metrics::inc( $key );
		}
	}

	/**
	 * Initial status/payment/ready-gate from commerce flags.
	 *
	 * @internal Characterization helper; not a stable plugin API.
	 * @param bool $order_failed      Order failed/cancelled.
	 * @param bool $order_paid        Order paid.
	 * @param bool $legacy_confirmed  Confirmed booking with no order.
	 * @return array{status:string,payment:string,may_ready:bool}
	 */
	public static function initial_lifecycle( $order_failed, $order_paid, $legacy_confirmed ) {
		$status = NGC_Session_States::AWAITING_PAYMENT;
		$pay    = 'unpaid';
		if ( $order_failed ) {
			$status = NGC_Session_States::FAILED;
			$pay    = 'failed';
		} elseif ( $order_paid ) {
			$status = NGC_Session_States::PAID;
			$pay    = 'paid';
		} elseif ( $legacy_confirmed ) {
			$status = NGC_Session_States::BOOKING_CONFIRMED;
		}
		return [
			'status'    => $status,
			'payment'   => $pay,
			'may_ready' => (bool) $order_paid || (bool) $legacy_confirmed,
		];
	}

	/**
	 * @param int $booking_id Booking ID.
	 * @param int $order_id   Order ID.
	 * @return string
	 */
	public static function ensure_idempotency_key( $booking_id, $order_id ) {
		return 'ensure-session:' . (int) $booking_id . ':' . (int) $order_id;
	}

	/**
	 * Idempotent provision: 1 booking → 1 session → 1 meeting → MS links (when available).
	 *
	 * @param array<string, mixed> $args booking_id, order_id, source.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function ensure_provisioned( $args = [] ) {
		$booking_id = (int) ( $args['booking_id'] ?? 0 );
		$order_id   = (int) ( $args['order_id'] ?? 0 );
		$source     = sanitize_key( (string) ( $args['source'] ?? 'manual' ) );

		if ( $booking_id <= 0 && $order_id <= 0 ) {
			return new WP_Error( 'ngc_session_args', __( 'booking_id or order_id required.', 'nextgencompanion' ) );
		}

		$idem_key = self::ensure_idempotency_key( $booking_id, $order_id );
		if ( class_exists( 'NGC_Idempotency' ) ) {
			$begun = NGC_Idempotency::begin( $idem_key, $idem_key, 'sessions' );
			if ( is_wp_error( $begun ) ) {
				return $begun;
			}
			if ( 'replay' === ( $begun['status'] ?? '' ) ) {
				return self::replay_ensure( $booking_id, $order_id, $source );
			}
		}

		$resolved   = self::resolve_booking_context( $booking_id, $order_id );
		$booking_id = $resolved['booking_id'];
		$booking    = $resolved['booking'];
		$session    = $resolved['session'];
		$commerce   = self::read_order_commerce( $order_id );
		$legacy     = ! $order_id && $booking && 'confirmed' === (string) ( $booking->status ?? '' );
		$life       = self::initial_lifecycle( $commerce['failed'], $commerce['paid'], $legacy );

		$session = self::upsert_session_row( $session, $booking, $booking_id, $order_id, $source, $commerce, $life );
		$session = self::require_session_row( $session );
		if ( is_wp_error( $session ) ) {
			return $session;
		}

		$meeting_ok = false;
		if ( $life['may_ready'] ) {
			$meeting_ok = self::provision_when_ready( $session, $booking, $booking_id, $commerce['paid'] );
		} else {
			self::hold_unpaid_session( $session, $booking, $commerce['failed'] );
		}
		$session = self::require_session_row( NGC_Sessions::get( (int) $session->id ) );
		if ( is_wp_error( $session ) ) {
			return $session;
		}

		if ( class_exists( 'NGC_Idempotency' ) ) {
			NGC_Idempotency::commit(
				$idem_key,
				[
					'session_id' => (int) $session->id,
					'meeting_ok' => $meeting_ok,
				]
			);
		}

		self::metric( 'session_provision_success_total' );
		if ( ! empty( $args['replay_suppressed'] ) ) {
			self::metric( 'duplicate_event_suppressed_total' );
		}

		return [
			'ok'         => true,
			'session'    => $session,
			'session_id' => (int) $session->id,
			'meeting_ok' => $meeting_ok,
			'source'     => $source,
		];
	}

	/**
	 * @param object|WP_Error|null $session Session row or error.
	 * @return object|WP_Error
	 */
	private static function require_session_row( $session ) {
		if ( is_wp_error( $session ) ) {
			return $session;
		}
		if ( ! is_object( $session ) || empty( $session->id ) ) {
			self::metric( 'session_provision_failure_total' );
			return new WP_Error(
				'ngc_session_missing',
				__( 'Session row was not available after upsert.', 'nextgencompanion' )
			);
		}
		return $session;
	}

	/**
	 * @param int    $booking_id Booking ID.
	 * @param int    $order_id   Order ID.
	 * @param string $source     Source key.
	 * @return array<string, mixed>
	 */
	private static function replay_ensure( $booking_id, $order_id, $source ) {
		$session = $booking_id ? NGC_Sessions::get_by_booking( $booking_id ) : NGC_Sessions::get_by_order( $order_id );
		// Idempotent create must not freeze payment — sync paid state on replay.
		if ( $session && $order_id && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
			$order_paid = $order && ( $order->is_paid() || in_array( $order->get_status(), [ 'processing', 'completed' ], true ) );
			if ( $order_paid && 'paid' !== (string) $session->payment_status ) {
				$patch = [
					'order_id'       => $order_id,
					'payment_status' => 'paid',
					'parent_user_id' => (int) $order->get_user_id(),
				];
				if ( NGC_Session_States::can_transition( $session->status, NGC_Session_States::PAID ) ) {
					$patch['status'] = NGC_Session_States::PAID;
				}
				NGC_Sessions::update( (int) $session->id, $patch );
				$session = NGC_Sessions::get( (int) $session->id );
			} elseif ( $order_paid && (int) $session->parent_user_id <= 0 && (int) $order->get_user_id() > 0 ) {
				NGC_Sessions::update( (int) $session->id, [ 'parent_user_id' => (int) $order->get_user_id() ] );
				$session = NGC_Sessions::get( (int) $session->id );
			}
		}
		self::metric( 'duplicate_event_suppressed_total' );
		return [
			'ok'         => true,
			'replay'     => true,
			'session'    => $session,
			'session_id' => $session ? (int) $session->id : 0,
			'source'     => $source,
		];
	}

	/**
	 * @param int $booking_id Booking ID.
	 * @param int $order_id   Order ID.
	 * @return array{booking_id:int,booking:?object,session:?object}
	 */
	private static function resolve_booking_context( $booking_id, $order_id ) {
		$booking = null;
		if ( $booking_id && class_exists( 'NGC_Bookings' ) ) {
			$booking = NGC_Bookings::get( $booking_id );
		}
		if ( ! $booking && $order_id && class_exists( 'NGC_Bookings' ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$booking_id = (int) $order->get_meta( 'ngc_booking_id' );
				if ( $booking_id ) {
					$booking = NGC_Bookings::get( $booking_id );
				}
			}
		}
		$session = $booking_id ? NGC_Sessions::get_by_booking( $booking_id ) : null;
		if ( ! $session && $order_id ) {
			$session = NGC_Sessions::get_by_order( $order_id );
		}
		return [
			'booking_id' => (int) $booking_id,
			'booking'    => $booking,
			'session'    => $session,
		];
	}

	/**
	 * @param int $order_id Order ID.
	 * @return array{product_id:int,parent_id:int,paid:bool,failed:bool}
	 */
	private static function read_order_commerce( $order_id ) {
		$out = [
			'product_id' => 0,
			'parent_id'  => 0,
			'paid'       => false,
			'failed'     => false,
		];
		if ( $order_id && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$out['parent_id']    = (int) $order->get_user_id();
				$order_status        = $order->get_status();
				$out['paid']         = $order->is_paid() || in_array( $order_status, [ 'processing', 'completed' ], true );
				$out['failed']       = in_array( $order_status, [ 'failed', 'cancelled' ], true );
				foreach ( $order->get_items() as $item ) {
					$out['product_id'] = (int) $item->get_product_id();
					break;
				}
			}
		}
		return $out;
	}

	/**
	 * @param object|null              $session    Existing session.
	 * @param object|null              $booking    Booking row.
	 * @param int                      $booking_id Booking ID.
	 * @param int                      $order_id   Order ID.
	 * @param string                   $source     Source.
	 * @param array<string, mixed>     $commerce   Commerce flags.
	 * @param array<string, mixed>     $life       Lifecycle flags.
	 * @return object|WP_Error
	 */
	private static function upsert_session_row( $session, $booking, $booking_id, $order_id, $source, $commerce, $life ) {
		$product_id   = (int) $commerce['product_id'];
		$parent_id    = (int) $commerce['parent_id'];
		$order_paid   = (bool) $commerce['paid'];
		$order_failed = (bool) $commerce['failed'];

		if ( ! $session ) {
			$start = $booking && ! empty( $booking->scheduled_at ) ? $booking->scheduled_at : null;
			$dur   = $booking ? max( 15, (int) ( $booking->duration_minutes ?? 60 ) ) : 60;
			$end   = $start ? gmdate( 'Y-m-d H:i:s', strtotime( $start . ' UTC' ) + ( $dur * 60 ) ) : null;

			$create = NGC_Sessions::create(
				[
					'booking_id'      => $booking_id,
					'order_id'        => $order_id,
					'product_id'      => $product_id,
					'student_user_id' => $booking ? (int) $booking->student_user_id : 0,
					'parent_user_id'  => $parent_id,
					'tutor_user_id'   => $booking ? (int) $booking->tutor_user_id : 0,
					'subject_name'    => $booking ? (string) $booking->subject : '',
					'subject_id'      => $booking ? sanitize_title( (string) $booking->subject ) : '',
					'scheduled_start' => $start,
					'scheduled_end'   => $end,
					'status'          => $life['status'],
					'payment_status'  => $life['payment'],
					'booking_status'  => $booking ? (string) $booking->status : '',
					'idempotency_key' => 'session:b' . $booking_id . ':o' . $order_id,
					'meta'            => [ 'source' => $source ],
				]
			);
			if ( is_wp_error( $create ) ) {
				self::metric( 'session_provision_failure_total' );
				return $create;
			}
			return self::require_session_row( NGC_Sessions::get( (int) $create ) );
		}

		$patch = [
			'order_id'   => $order_id ?: (int) $session->order_id,
			'product_id' => $product_id ?: (int) $session->product_id,
		];
		if ( $parent_id > 0 ) {
			$patch['parent_user_id'] = $parent_id;
		}
		if ( $order_failed ) {
			$patch['payment_status'] = 'failed';
			if ( NGC_Session_States::can_transition( $session->status, NGC_Session_States::FAILED ) ) {
				$patch['status'] = NGC_Session_States::FAILED;
			}
		} elseif ( $order_paid ) {
			$patch['payment_status'] = 'paid';
			$from = (string) $session->status;
			if ( NGC_Session_States::FAILED === $from
				&& NGC_Session_States::can_transition( $from, NGC_Session_States::AWAITING_PAYMENT ) ) {
				NGC_Sessions::update(
					(int) $session->id,
					[
						'status'         => NGC_Session_States::AWAITING_PAYMENT,
						'payment_status' => 'unpaid',
						'order_id'       => $order_id ?: (int) $session->order_id,
					]
				);
				$session = NGC_Sessions::get( (int) $session->id );
				$session = self::require_session_row( $session );
				if ( is_wp_error( $session ) ) {
					return $session;
				}
				$from = (string) $session->status;
			}
			if ( NGC_Session_States::can_transition( $from, NGC_Session_States::PAID ) ) {
				$patch['status'] = NGC_Session_States::PAID;
			}
		} elseif ( 'paid' !== (string) $session->payment_status ) {
			$patch['payment_status'] = (string) ( $session->payment_status ?: 'unpaid' );
		}
		$upd = NGC_Sessions::update( (int) $session->id, $patch );
		if ( is_wp_error( $upd ) ) {
			self::metric( 'session_provision_failure_total' );
			return $upd;
		}
		return self::require_session_row( NGC_Sessions::get( (int) $session->id ) );
	}

	/**
	 * @param object      $session    Session row.
	 * @param object|null $booking    Booking row.
	 * @param int         $booking_id Booking ID.
	 * @param bool        $order_paid Paid flag.
	 * @return bool
	 */
	private static function provision_when_ready( $session, $booking, $booking_id, $order_paid ) {
		$meeting_ok = false;
		foreach ( [ NGC_Session_States::BOOKING_CONFIRMED, NGC_Session_States::PROVISIONING ] as $step ) {
			if ( $session && NGC_Session_States::can_transition( $session->status, $step ) && $session->status !== $step ) {
				NGC_Sessions::update( (int) $session->id, [ 'status' => $step ] );
				$session = NGC_Sessions::get( (int) $session->id );
			}
		}

		if ( $booking_id && class_exists( 'NGC_Meetings' ) ) {
			$ensured = NGC_Meetings::ensure_for_booking( $booking_id, [ 'user_id' => (int) ( $session->tutor_user_id ?? 0 ) ] );
			if ( ! is_wp_error( $ensured ) && is_array( $ensured ) ) {
				NGC_Sessions::update(
					(int) $session->id,
					[
						'meeting_provider'      => (string) ( $ensured['provider'] ?? 'jitsi' ),
						'meeting_id'            => (string) ( $ensured['room'] ?? '' ),
						'meeting_url_reference' => (string) ( $ensured['join_url'] ?? '' ),
						'meeting_status'        => 'ready',
					]
				);
				$meeting_ok = true;
				if ( class_exists( 'NGC_Audit' ) ) {
					NGC_Audit::log( 'meeting_created', 'session', (int) $session->id, [ 'room' => $ensured['room'] ?? '' ] );
				}
			}
		}

		$ms = self::provision_masterstudy( $session );
		if ( ! is_wp_error( $ms ) && is_array( $ms ) ) {
			NGC_Sessions::update(
				(int) $session->id,
				[
					'masterstudy_course_id' => (int) ( $ms['course_id'] ?? 0 ),
					'masterstudy_lesson_id' => (int) ( $ms['lesson_id'] ?? 0 ),
					'lesson_status'         => (string) ( $ms['lesson_status'] ?? 'linked' ),
				]
			);
		}

		if ( $session && NGC_Session_States::can_transition( $session->status, NGC_Session_States::READY ) ) {
			NGC_Sessions::update(
				(int) $session->id,
				[
					'status'         => NGC_Session_States::READY,
					'booking_status' => $booking ? (string) $booking->status : (string) $session->booking_status,
					'payment_status' => $order_paid ? 'paid' : (string) $session->payment_status,
				]
			);
		}
		return $meeting_ok;
	}

	/**
	 * @param object      $session      Session row.
	 * @param object|null $booking      Booking row.
	 * @param bool        $order_failed Failed flag.
	 */
	private static function hold_unpaid_session( $session, $booking, $order_failed ) {
		$hold = [
			'booking_status' => $booking ? (string) $booking->status : (string) $session->booking_status,
		];
		if ( $order_failed ) {
			$hold['payment_status'] = 'failed';
			if ( NGC_Session_States::can_transition( $session->status, NGC_Session_States::FAILED ) ) {
				$hold['status'] = NGC_Session_States::FAILED;
			}
		} else {
			$hold['payment_status'] = 'unpaid';
			if ( NGC_Session_States::READY === (string) $session->status
				|| NGC_Session_States::PROVISIONING === (string) $session->status ) {
				if ( NGC_Session_States::can_transition( $session->status, NGC_Session_States::AWAITING_PAYMENT ) ) {
					$hold['status'] = NGC_Session_States::AWAITING_PAYMENT;
				}
			} elseif ( NGC_Session_States::can_transition( $session->status, NGC_Session_States::AWAITING_PAYMENT )
				&& NGC_Session_States::FAILED !== (string) $session->status ) {
				$hold['status'] = NGC_Session_States::AWAITING_PAYMENT;
			}
		}
		NGC_Sessions::update( (int) $session->id, $hold );
	}

	/**
	 * @param object|null $session Session row.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function provision_masterstudy( $session ) {
		if ( ! $session || ! class_exists( 'NGC_Masterstudy_Adapter' ) ) {
			return [ 'course_id' => 0, 'lesson_id' => 0, 'lesson_status' => 'skipped' ];
		}
		$adapter = new NGC_Masterstudy_Adapter();
		if ( ! $adapter->is_available() ) {
			return [ 'course_id' => 0, 'lesson_id' => 0, 'lesson_status' => 'lms_inactive' ];
		}
		if ( method_exists( $adapter, 'ensure_session_learning' ) ) {
			return $adapter->ensure_session_learning(
				[
					'session_id'      => (int) $session->id,
					'student_user_id' => (int) $session->student_user_id,
					'tutor_user_id'   => (int) $session->tutor_user_id,
					'subject'         => (string) $session->subject_name,
					'correlation_id'  => (string) $session->correlation_id,
				]
			);
		}
		// Fallback: ensure student profile only.
		$adapter->create_or_update( 'create_student', [ 'user_id' => (int) $session->student_user_id ] );
		$adapter->create_or_update( 'create_instructor', [ 'user_id' => (int) $session->tutor_user_id ] );
		return [ 'course_id' => 0, 'lesson_id' => 0, 'lesson_status' => 'profiles_only' ];
	}

	/**
	 * Join window policy (server authoritative).
	 *
	 * @param object $session Session.
	 * @return array{allowed:bool,reason:string,opens_at?:string,closes_at?:string}
	 */
	public static function join_window_status( $session ) {
		if ( ! $session ) {
			return [ 'allowed' => false, 'reason' => 'missing_session' ];
		}
		if ( ! NGC_Session_States::is_joinable( $session->status ) ) {
			return [ 'allowed' => false, 'reason' => 'status_' . $session->status ];
		}
		if ( in_array( $session->status, [ NGC_Session_States::CANCELLED, NGC_Session_States::REFUNDED, NGC_Session_States::FAILED ], true ) ) {
			return [ 'allowed' => false, 'reason' => 'session_closed' ];
		}
		if ( (int) $session->order_id > 0 && 'paid' !== $session->payment_status ) {
			return [ 'allowed' => false, 'reason' => 'payment_required' ];
		}

		$before = (int) get_option( self::OPT_JOIN_BEFORE, 5 );
		$after  = (int) get_option( self::OPT_JOIN_AFTER, 30 );
		$start  = $session->scheduled_start ? strtotime( $session->scheduled_start . ' UTC' ) : 0;
		if ( ! $start ) {
			// No schedule → allow when ready (demo/legacy bookings).
			return [ 'allowed' => true, 'reason' => 'no_schedule' ];
		}
		$now   = time();
		$open  = $start - ( max( 0, $before ) * 60 );
		$close = $start + ( max( 0, $after ) * 60 );
		if ( $now < $open ) {
			return [
				'allowed'  => false,
				'reason'   => 'too_early',
				'opens_at' => gmdate( 'c', $open ),
			];
		}
		if ( $now > $close ) {
			return [
				'allowed'   => false,
				'reason'    => 'too_late',
				'closes_at' => gmdate( 'c', $close ),
			];
		}
		return [
			'allowed'   => true,
			'reason'    => 'within_window',
			'opens_at'  => gmdate( 'c', $open ),
			'closes_at' => gmdate( 'c', $close ),
		];
	}

	/**
	 * Authorize launch for a user.
	 *
	 * @param int $session_id Session ID.
	 * @param int $user_id    Actor.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function authorize_launch( $session_id, $user_id ) {
		$session = NGC_Sessions::get( (int) $session_id );
		if ( ! $session ) {
			return new WP_Error( 'ngc_session_not_found', __( 'Session not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
		}
		$user_id = (int) $user_id;
		$roles   = [];
		$user    = get_userdata( $user_id );
		if ( $user ) {
			$roles = (array) $user->roles;
		}
		$is_party = in_array( $user_id, [ (int) $session->student_user_id, (int) $session->tutor_user_id, (int) $session->parent_user_id ], true );
		$is_ops   = user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'ngc_manage_bookings' );
		if ( ! $is_party && ! $is_ops ) {
			self::metric( 'join_denied_total' );
			return new WP_Error( 'ngc_forbidden', __( 'You cannot join this session.', 'nextgencompanion' ), [ 'status' => 403 ] );
		}

		$window = self::join_window_status( $session );
		if ( empty( $window['allowed'] ) ) {
			self::metric( 'join_denied_total' );
			return new WP_Error( 'ngc_join_window', __( 'This lesson is not available to join right now.', 'nextgencompanion' ), [ 'status' => 409, 'window' => $window ] );
		}

		$player_url = '';
		if ( (int) $session->masterstudy_course_id > 0 && class_exists( 'NGC_Masterstudy_Adapter' ) ) {
			$adapter = new NGC_Masterstudy_Adapter();
			if ( method_exists( $adapter, 'course_player_url' ) ) {
				$player_url = (string) $adapter->course_player_url( (int) $session->masterstudy_course_id, (int) $session->masterstudy_lesson_id );
			}
		}

		$meeting_url = '';
		if ( (int) $session->booking_id > 0 && class_exists( 'NGC_Meetings' ) ) {
			$url = NGC_Meetings::join_url_for_user( (int) $session->booking_id, $user_id );
			if ( ! is_wp_error( $url ) ) {
				$meeting_url = (string) $url;
			}
		}
		if ( ! $meeting_url ) {
			$meeting_url = (string) $session->meeting_url_reference;
		}

		$role = 'student';
		if ( $user_id === (int) $session->tutor_user_id || in_array( 'tutor', $roles, true ) ) {
			$role = 'tutor';
			NGC_Sessions::update( (int) $session->id, [ 'tutor_joined_at' => current_time( 'mysql', true ) ] );
		} else {
			NGC_Sessions::update( (int) $session->id, [ 'student_joined_at' => current_time( 'mysql', true ) ] );
		}
		if ( NGC_Session_States::can_transition( $session->status, NGC_Session_States::IN_PROGRESS ) ) {
			NGC_Sessions::update(
				(int) $session->id,
				[
					'status'     => NGC_Session_States::IN_PROGRESS,
					'started_at' => $session->started_at ?: current_time( 'mysql', true ),
				]
			);
		}

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log(
				'join_authorized',
				'session',
				(int) $session->id,
				[
					'user_id'        => $user_id,
					'role'           => $role,
					'correlation_id' => $session->correlation_id,
				]
			);
		}

		self::metric( 'join_authorized_total' );

		$classroom_url = '';
		if ( class_exists( 'NGC_Session_Classroom' ) ) {
			$classroom_url = NGC_Session_Classroom::url( (int) $session->id );
		}

		// Prefer classroom shell (Course Player → live meeting). Fallback: player, then meeting.
		$launch = $classroom_url ?: ( $player_url ?: $meeting_url );

		return [
			'session_id'     => (int) $session->id,
			'session_uuid'   => $session->session_uuid,
			'correlation_id' => $session->correlation_id,
			'role'           => $role,
			'player_url'     => $player_url,
			'meeting_url'    => $meeting_url,
			'classroom_url'  => $classroom_url,
			'launch_url'     => $launch,
			'window'         => $window,
		];
	}
}
