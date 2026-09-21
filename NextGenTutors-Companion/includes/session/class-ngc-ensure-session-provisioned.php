<?php
/**
 * Idempotent command: EnsureSessionProvisioned(orderId, bookingId).
 *
 * All payment/booking hooks must converge here.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provisions exactly one session, LMS relationship, meeting, invoice, CRM, and audit trail.
 */
class NGC_Ensure_Session_Provisioned {

	/** @var NGC_Booking_Provider_Interface */
	private $booking;

	/** @var NGC_Commerce_Provider_Interface */
	private $commerce;

	/** @var NGC_Learning_Provider_Interface */
	private $learning;

	/** @var NGC_Meeting_Provider_Interface */
	private $meeting;

	/** @var NGC_Notification_Provider_Interface */
	private $notify;

	/** @var NGC_Crm_Provider_Interface */
	private $crm;

	/** @var NGC_Audit_Provider_Interface */
	private $audit;

	/**
	 * @param array<string, object> $adapters Optional injected adapters.
	 */
	public function __construct( array $adapters = [] ) {
		$this->booking  = $adapters['booking'] ?? new NGC_Session_Booking_Adapter();
		$this->commerce = $adapters['commerce'] ?? new NGC_Session_Commerce_Adapter();
		$this->learning = $adapters['learning'] ?? new NGC_Session_Learning_Adapter();
		$this->meeting  = $adapters['meeting'] ?? new NGC_Session_Meeting_Adapter();
		$this->notify   = $adapters['notify'] ?? new NGC_Session_Notification_Adapter();
		$this->crm      = $adapters['crm'] ?? new NGC_Session_Crm_Adapter();
		$this->audit    = $adapters['audit'] ?? new NGC_Session_Audit_Adapter();
	}

	/**
	 * WordPress façade.
	 *
	 * @param int                  $order_id   Order ID.
	 * @param int                  $booking_id Booking ID.
	 * @param array<string, mixed> $context    Context.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function run( $order_id, $booking_id = 0, array $context = [] ) {
		$cmd = new self();
		return $cmd->execute( (int) $order_id, (int) $booking_id, $context );
	}

	/**
	 * @param int                  $order_id   Order ID.
	 * @param int                  $booking_id Booking ID.
	 * @param array<string, mixed> $context    Context.
	 * @return array<string, mixed>|WP_Error
	 */
	public function execute( $order_id, $booking_id = 0, array $context = [] ) {
		$started = microtime( true );
		$order_id   = (int) $order_id;
		$booking_id = (int) $booking_id;

		if ( $order_id <= 0 && $booking_id <= 0 ) {
			return new WP_Error( 'ngc_session_args', __( 'Order or booking is required.', 'nextgencompanion' ) );
		}

		$idem    = NGC_Session_Correlation::idempotency_key( $order_id, $booking_id );
		$idem_op = 'provision:' . $idem;
		if ( class_exists( 'NGC_Idempotency' ) ) {
			$begun    = NGC_Idempotency::begin( $idem_op, $idem, 'session_provision' );
			$existing = NGC_Session_Repository::get_by_idempotency_key( $idem )
				?: ( $order_id ? NGC_Session_Repository::get_by_order_id( $order_id ) : null )
				?: ( $booking_id ? NGC_Session_Repository::get_by_booking_id( $booking_id ) : null );
			if ( is_wp_error( $begun ) ) {
				NGC_Session_Observability::success( 'duplicate_event_suppressed_total', [ 'order_id' => $order_id, 'booking_id' => $booking_id, 'operation' => 'provision' ] );
				return $existing ?: $begun;
			}
			if ( 'replay' === ( $begun['status'] ?? '' ) && $existing && self::is_terminal_provision( $existing ) ) {
				NGC_Session_Observability::success( 'duplicate_event_suppressed_total', [ 'order_id' => $order_id, 'booking_id' => $booking_id, 'session_id' => (int) $existing['id'] ] );
				return $existing;
			}
		}

		try {
			$session = $this->provision( $order_id, $booking_id, $context, $idem );
			if ( is_wp_error( $session ) ) {
				if ( class_exists( 'NGC_Idempotency' ) ) {
					NGC_Idempotency::reject( $idem_op, $session->get_error_message() );
				}
				NGC_Session_Observability::failure(
					'session_provision_failure_total',
					[ 'order_id' => $order_id, 'booking_id' => $booking_id, 'operation' => 'provision' ]
				);
				return $session;
			}
			if ( class_exists( 'NGC_Idempotency' ) ) {
				if ( self::is_terminal_provision( $session ) ) {
					NGC_Idempotency::commit( $idem_op, [ 'session_id' => (int) $session['id'], 'status' => $session['status'] ] );
				} else {
					NGC_Idempotency::reject( $idem_op, 'awaiting_further_provision:' . ( $session['status'] ?? '' ) );
				}
			}
			$ms = (int) round( ( microtime( true ) - $started ) * 1000 );
			NGC_Session_Observability::event(
				'session_provision_success_total',
				$session + [ 'operation' => 'provision' ],
				'success',
				$ms
			);
			return $session;
		} catch ( NGC_Session_Exception $e ) {
			if ( class_exists( 'NGC_Idempotency' ) ) {
				NGC_Idempotency::reject( 'provision:' . $idem, $e->getMessage() );
			}
			NGC_Session_Observability::failure(
				'session_provision_failure_total',
				[ 'order_id' => $order_id, 'booking_id' => $booking_id, 'error_code' => $e->get_error_code() ]
			);
			return $e->to_wp_error();
		}
	}

	/**
	 * @param int                  $order_id   Order.
	 * @param int                  $booking_id Booking.
	 * @param array<string, mixed> $context    Context.
	 * @param string               $idem       Idempotency key.
	 * @return array<string, mixed>|WP_Error
	 */
	private function provision( $order_id, $booking_id, array $context, $idem ) {
		$order_snap = null;
		if ( $order_id ) {
			$order_snap = $this->commerce->get_order_snapshot( $order_id );
			if ( is_wp_error( $order_snap ) ) {
				return $order_snap;
			}
			if ( ! $booking_id ) {
				$booking_id = (int) ( $order_snap['booking_id'] ?? 0 );
				if ( ! $booking_id && ! empty( $order_snap['items'][0]['meta']['_ngt_booking_id'] ) ) {
					$booking_id = (int) $order_snap['items'][0]['meta']['_ngt_booking_id'];
				}
			}
		}

		$booking = null;
		if ( $booking_id ) {
			$booking = $this->booking->get_normalized( $booking_id );
			if ( is_wp_error( $booking ) ) {
				return $booking;
			}
			if ( ! $order_id && ! empty( $booking['order_id'] ) ) {
				$order_id = (int) $booking['order_id'];
			}
		}

		$item     = $order_snap['items'][0] ?? [];
		$item_meta = $item['meta'] ?? [];
		$student  = (int) ( $booking['student_user_id'] ?? $item_meta['_ngt_student_id'] ?? $context['student_user_id'] ?? 0 );
		$tutor    = (int) ( $booking['tutor_user_id'] ?? $item_meta['_ngt_tutor_id'] ?? $context['tutor_user_id'] ?? 0 );
		$parent   = (int) ( $item_meta['_ngt_parent_id'] ?? $context['parent_user_id'] ?? 0 );
		$subject  = (string) ( $booking['subject_id'] ?? $item_meta['_ngt_subject_id'] ?? $context['subject_id'] ?? '' );
		$subject_name = (string) ( $booking['subject'] ?? $item_meta['_ngt_subject_name'] ?? $subject );
		$start    = (string) ( $booking['start'] ?? $item_meta['_ngt_scheduled_start'] ?? $context['scheduled_start'] ?? '' );
		$end      = (string) ( $booking['end'] ?? $item_meta['_ngt_scheduled_end'] ?? $context['scheduled_end'] ?? '' );
		$tz       = (string) ( $booking['timezone'] ?? $item_meta['_ngt_timezone'] ?? 'Africa/Johannesburg' );

		if ( ! $parent && $student ) {
			$parties = NGC_Session_Identity::resolve_parties( (int) ( $order_snap['customer_id'] ?? 0 ), $student );
			$parent  = (int) $parties['parent_user_id'];
		}

		$session = NGC_Session_Repository::get_by_idempotency_key( $idem )
			?: ( $order_id ? NGC_Session_Repository::get_by_order_id( $order_id ) : null )
			?: ( $booking_id ? NGC_Session_Repository::get_by_booking_id( $booking_id ) : null );

		if ( ! $session ) {
			$session = NGC_Session_Repository::create(
				[
					'idempotency_key'  => $idem,
					'booking_provider' => (string) ( $booking['booking_provider'] ?? 'ngc' ),
					'booking_id'       => $booking_id,
					'order_id'         => $order_id,
					'order_item_id'    => (int) ( $item['order_item_id'] ?? 0 ),
					'product_id'       => (int) ( $item['product_id'] ?? 0 ),
					'student_user_id'  => $student,
					'parent_user_id'   => $parent,
					'tutor_user_id'    => $tutor,
					'subject_id'       => $subject,
					'subject_name'     => $subject_name,
					'scheduled_start'  => $start ?: null,
					'scheduled_end'    => $end ?: null,
					'timezone'         => $tz,
					'status'           => NGC_Session_States::DRAFT,
					'payment_status'   => 'unpaid',
					'booking_status'   => (string) ( $booking['status'] ?? '' ),
				]
			);
			if ( is_wp_error( $session ) ) {
				return $session;
			}
			$this->audit->record( 'session_created', 'session', (int) $session['id'], 'success', [ 'correlation_id' => $session['correlation_id'] ] );
			$this->bind_order_item_uuid( $order_id, (int) ( $item['order_item_id'] ?? 0 ), $session );
		}

		$paid = $order_id ? $this->commerce->is_paid( $order_id ) : false;
		if ( ! $paid ) {
			$session = $this->maybe_status( $session, NGC_Session_States::AWAITING_PAYMENT, [ 'payment_status' => 'unpaid' ] );
			$this->audit->record( 'payment_pending', 'session', (int) $session['id'], 'success', [ 'correlation_id' => $session['correlation_id'] ] );
			return is_wp_error( $session ) ? $session : $session;
		}

		$this->audit->record( 'payment_confirmed', 'order', $order_id, 'success', [ 'correlation_id' => $session['correlation_id'] ] );
		NGC_Session_Observability::success( 'payment_success_total', $session );

		$invoice = $this->commerce->ensure_invoice( $order_id );
		if ( ! is_wp_error( $invoice ) ) {
			$this->audit->record( 'invoice_created', 'invoice', (int) $invoice, 'success', [ 'correlation_id' => $session['correlation_id'], 'order_id' => $order_id ] );
		}

		$session = $this->maybe_status( $session, NGC_Session_States::PAID, [ 'payment_status' => 'paid' ] );
		if ( is_wp_error( $session ) ) {
			return $session;
		}

		if ( $booking_id ) {
			$confirmed = $this->booking->confirm( $booking_id, 'confirmed' );
			if ( is_wp_error( $confirmed ) ) {
				return $confirmed;
			}
			$this->audit->record( 'booking_confirmed', 'booking', $booking_id, 'success', [ 'correlation_id' => $session['correlation_id'] ] );
			NGC_Session_Observability::success( 'booking_confirmation_total', $session );
			$session = $this->maybe_status( $session, NGC_Session_States::BOOKING_CONFIRMED, [ 'booking_status' => 'confirmed' ] );
			if ( is_wp_error( $session ) ) {
				return $session;
			}
		}

		$session = $this->maybe_status( $session, NGC_Session_States::PROVISIONING );
		if ( is_wp_error( $session ) ) {
			return $session;
		}

		$lms_error = null;
		if ( $this->learning->is_available() ) {
			$course = $this->learning->ensure_subject_course( $subject, $subject_name, $tutor );
			if ( is_wp_error( $course ) ) {
				$lms_error = $course;
			} else {
				$enroll = $this->learning->ensure_enrollment( $student, (int) $course['course_id'] );
				if ( is_wp_error( $enroll ) ) {
					$lms_error = $enroll;
				} else {
					$lesson = $this->learning->ensure_session_lesson(
						(int) $course['course_id'],
						$tutor,
						(string) $session['session_uuid'],
						sprintf( '%s — %s', $subject_name, $start ?: $session['session_uuid'] )
					);
					if ( is_wp_error( $lesson ) ) {
						$lms_error = $lesson;
					} else {
						$session = NGC_Session_Repository::update(
							(int) $session['id'],
							[
								'masterstudy_course_id' => (int) $course['course_id'],
								'masterstudy_lesson_id' => (int) $lesson['lesson_id'],
								'lesson_status'         => 'ready',
							]
						);
						$this->audit->record( 'masterstudy_enrolled', 'session', (int) $session['id'], 'success', [ 'correlation_id' => $session['correlation_id'] ] );
						$this->audit->record( 'lesson_resolved', 'session', (int) $session['id'], 'success', [ 'correlation_id' => $session['correlation_id'], 'lesson_id' => (int) $lesson['lesson_id'] ] );
						NGC_Session_Observability::success( 'masterstudy_provision_total', $session );
					}
				}
			}
		} else {
			$session = NGC_Session_Repository::update( (int) $session['id'], [ 'lesson_status' => 'lms_unavailable' ] );
		}

		if ( $lms_error ) {
			$this->audit->record(
				'masterstudy_enrolled',
				'session',
				(int) $session['id'],
				'error',
				[
					'correlation_id' => $session['correlation_id'],
					'error_code'     => $lms_error->get_error_code(),
				]
			);
			NGC_Session_Observability::failure( 'session_provision_failure_total', $session + [ 'operation' => 'masterstudy' ] );
			return NGC_Session_Repository::transition( (int) $session['id'], NGC_Session_States::FAILED, [ 'lesson_status' => 'failed' ] );
		}

		$meeting = $this->meeting->ensure_meeting( is_array( $session ) ? $session : [], $context );
		if ( is_wp_error( $meeting ) ) {
			$this->audit->record( 'meeting_created', 'session', (int) $session['id'], 'error', [ 'correlation_id' => $session['correlation_id'], 'error_code' => $meeting->get_error_code() ] );
			NGC_Session_Observability::failure( 'session_provision_failure_total', $session + [ 'operation' => 'meeting' ] );
			return NGC_Session_Repository::transition( (int) $session['id'], NGC_Session_States::FAILED, [ 'meeting_status' => 'failed' ] );
		}

		$session = NGC_Session_Repository::update(
			(int) $session['id'],
			[
				'meeting_provider'      => $meeting['provider'],
				'meeting_id'            => $meeting['meeting_id'],
				'meeting_url_reference' => $meeting['reference'],
				'meeting_status'        => 'ready',
			]
		);
		$this->audit->record( 'meeting_created', 'session', (int) $session['id'], 'success', [ 'correlation_id' => $session['correlation_id'], 'meeting_id' => $meeting['meeting_id'] ] );
		NGC_Session_Observability::success( 'meeting_provision_total', $session );

		$session = $this->maybe_status( $session, NGC_Session_States::READY );
		if ( is_wp_error( $session ) ) {
			return $session;
		}

		$this->crm->sync_session( $session, $context );
		$this->notify->notify( 'session.ready', $session, $context );
		$this->notify->notify( 'booking.confirmed', $session, $context );

		$window = NGC_Session_Join_Policy::evaluate( $session );
		if ( ! empty( $window['allowed'] ) && NGC_Session_States::READY === $session['status'] ) {
			$opened = NGC_Session_Repository::transition( (int) $session['id'], NGC_Session_States::JOIN_WINDOW_OPEN );
			if ( ! is_wp_error( $opened ) ) {
				$session = $opened;
			}
		}

		return $session;
	}

	/**
	 * @param array<string, mixed>|WP_Error $session Session.
	 * @param string                        $to      Target.
	 * @param array<string, mixed>          $extra   Extra.
	 * @return array<string, mixed>|WP_Error
	 */
	private function maybe_status( $session, $to, array $extra = [] ) {
		if ( is_wp_error( $session ) ) {
			return $session;
		}
		if ( (string) $session['status'] === $to ) {
			if ( $extra ) {
				$updated = NGC_Session_Repository::update( (int) $session['id'], $extra );
				return is_wp_error( $updated ) ? $session : $updated;
			}
			return $session;
		}
		if ( ! NGC_Session_State_Machine::can_transition( (string) $session['status'], $to ) ) {
			return $session;
		}
		return NGC_Session_Repository::transition( (int) $session['id'], $to, $extra );
	}

	/**
	 * Persist session UUID onto the order item when possible.
	 *
	 * @param int                  $order_id Order.
	 * @param int                  $item_id  Item.
	 * @param array<string, mixed> $session  Session.
	 * @return void
	 */
	private function bind_order_item_uuid( $order_id, $item_id, array $session ) {
		if ( ! $order_id || ! $item_id || ! function_exists( 'wc_get_order' ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$item = $order->get_item( $item_id );
		if ( ! $item ) {
			return;
		}
		$item->update_meta_data( '_ngt_session_uuid', $session['session_uuid'] );
		$item->update_meta_data( '_ngt_correlation_id', $session['correlation_id'] );
		$item->save();
	}

	/**
	 * Whether further EnsureSessionProvisioned work is unnecessary.
	 *
	 * @param array<string, mixed> $session Session.
	 * @return bool
	 */
	private static function is_terminal_provision( array $session ) {
		$status = (string) ( $session['status'] ?? '' );
		return in_array(
			$status,
			[
				NGC_Session_States::READY,
				NGC_Session_States::JOIN_WINDOW_OPEN,
				NGC_Session_States::IN_PROGRESS,
				NGC_Session_States::COMPLETED,
				NGC_Session_States::CANCELLED,
				NGC_Session_States::REFUNDED,
			],
			true
		);
	}
}
