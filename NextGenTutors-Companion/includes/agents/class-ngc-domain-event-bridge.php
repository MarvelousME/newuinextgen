<?php
/**
 * Bridges Hub + Companion domain hooks into the agent event envelope.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps operational events to versioned envelopes (idempotent per source key).
 */
final class NGC_Domain_Event_Bridge {

	/**
	 * Hub event_key → envelope event type.
	 *
	 * @var array<string, string>
	 */
	private static $hub_map = [
		'ngt.lesson.completed'              => 'LessonCompleted',
		'ngt.payouts.calculated'            => 'PayoutCalculated',
		'ngt.find_tutor.submitted'          => 'FindTutorSubmitted',
		'ngt.tutor_application.submitted'   => 'TutorApplicationSubmitted',
		'amelia.booking.created'            => 'BookingCreated',
		'ngt.daily.health_check'            => 'HealthCheckCompleted',
		'wp.user_registered'                => 'UserRegistered',
		'woocommerce.order.completed'       => 'OrderCompleted',
		'ngt.match.created'                 => 'MatchCreated',
		'ngt.payment.overdue'               => 'PaymentOverdue',
	];

	/**
	 * Register listeners.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'maybe_install' ], 5 );
		add_action( 'ngt_automation_event_fired', [ __CLASS__, 'on_hub_event' ], 5, 2 );
		add_action( 'ngc_booking_created', [ __CLASS__, 'on_booking_created' ], 5, 1 );
		add_action( 'ngc_lesson_completed', [ __CLASS__, 'on_lesson_completed' ], 5, 1 );
		add_action( 'ngc_form_submitted', [ __CLASS__, 'on_form_submitted' ], 5, 2 );
		add_action( 'ngc_review_submitted', [ __CLASS__, 'on_review_submitted' ], 5, 1 );
		add_action( 'ngc_referral_converted', [ __CLASS__, 'on_referral' ], 5, 2 );
		add_action( 'ngc_workflow_dispatched', [ __CLASS__, 'on_workflow' ], 5, 2 );
		add_action( 'ngc_payment_settled', [ __CLASS__, 'on_payment_settled' ], 5, 1 );
		add_action( 'ngc_match_accepted', [ __CLASS__, 'on_match_accepted' ], 5, 1 );
	}

	public static function maybe_install() {
		$ver = get_option( 'ngc_event_outbox_version', '' );
		if ( version_compare( (string) $ver, '1.0.0', '<' ) ) {
			if ( class_exists( 'NGC_Agent_Event_Envelope' ) ) {
				NGC_Agent_Event_Envelope::maybe_install_outbox();
			}
			update_option( 'ngc_event_outbox_version', '1.0.0', false );
		}
	}

	/**
	 * @param string               $event_key Event.
	 * @param array<string, mixed> $payload   Payload.
	 */
	public static function on_hub_event( $event_key, $payload ) {
		$type = self::$hub_map[ $event_key ] ?? self::key_to_type( (string) $event_key );
		$entity_type = 'hub_event';
		$entity_id   = (string) ( $payload['object_id'] ?? $payload['lesson_id'] ?? $payload['booking_id'] ?? $payload['order_id'] ?? $payload['match_id'] ?? 0 );
		self::emit_once(
			'hub:' . $event_key . ':' . $entity_id . ':' . md5( wp_json_encode( $payload ) ),
			$type,
			$entity_type,
			$entity_id,
			is_array( $payload ) ? $payload : [],
			[ 'source' => 'automation_hub', 'causation_id' => (string) $event_key ]
		);
	}

	/**
	 * @param int|array<string,mixed> $booking Booking.
	 */
	public static function on_booking_created( $booking ) {
		$id = is_array( $booking ) ? (int) ( $booking['booking_id'] ?? $booking['id'] ?? 0 ) : (int) $booking;
		self::emit_once( 'ngc:booking:' . $id, 'BookingCreated', 'booking', (string) $id, is_array( $booking ) ? $booking : [ 'booking_id' => $id ] );
	}

	/**
	 * @param array<string,mixed> $ctx Context.
	 */
	public static function on_lesson_completed( $ctx ) {
		$id = (int) ( $ctx['lesson_id'] ?? $ctx['booking_id'] ?? 0 );
		self::emit_once( 'ngc:lesson:' . $id, 'LessonCompleted', 'lesson', (string) $id, is_array( $ctx ) ? $ctx : [] );
	}

	/**
	 * @param string|int           $form_id Form.
	 * @param array<string,mixed>  $payload Payload.
	 */
	public static function on_form_submitted( $form_id, $payload = [] ) {
		self::emit_once( 'ngc:form:' . $form_id . ':' . get_current_user_id(), 'FormSubmitted', 'form', (string) $form_id, is_array( $payload ) ? $payload : [] );
	}

	/**
	 * @param array<string,mixed>|int $review Review.
	 */
	public static function on_review_submitted( $review ) {
		$id = is_array( $review ) ? (int) ( $review['id'] ?? $review['review_id'] ?? get_current_user_id() ) : (int) $review;
		self::emit_once( 'ngc:review:' . $id, 'ReviewSubmitted', 'review', (string) $id, is_array( $review ) ? $review : [] );
	}

	/**
	 * @param int $referrer Referrer.
	 * @param int $referred Referred.
	 */
	public static function on_referral( $referrer, $referred ) {
		self::emit_once( 'ngc:referral:' . $referrer . ':' . $referred, 'ReferralConverted', 'user', (string) $referrer, [ 'referred_id' => (int) $referred ] );
	}

	/**
	 * @param string               $event Event.
	 * @param array<string,mixed>  $vars  Vars.
	 */
	public static function on_workflow( $event, $vars ) {
		self::emit_once( 'ngc:wf:' . $event . ':' . md5( wp_json_encode( $vars ) ), self::key_to_type( (string) $event ), 'workflow', (string) $event, is_array( $vars ) ? $vars : [] );
	}

	/**
	 * @param int|array<string,mixed> $order Order.
	 */
	public static function on_payment_settled( $order ) {
		$id = is_array( $order ) ? (int) ( $order['order_id'] ?? 0 ) : (int) $order;
		self::emit_once( 'ngc:pay:' . $id, 'PaymentSettled', 'order', (string) $id, is_array( $order ) ? $order : [ 'order_id' => $id ] );
	}

	/**
	 * @param int $match_id Match.
	 */
	public static function on_match_accepted( $match_id ) {
		self::emit_once( 'ngc:match_accept:' . (int) $match_id, 'MatchAccepted', 'match', (string) (int) $match_id, [ 'match_id' => (int) $match_id ] );
	}

	/**
	 * @param string               $dedupe_key Idempotency key.
	 * @param string               $type       Event type.
	 * @param string               $entity_type Entity type.
	 * @param string               $entity_id Entity id.
	 * @param array<string,mixed>  $payload Payload.
	 * @param array<string,mixed>  $meta Meta.
	 */
	private static function emit_once( $dedupe_key, $type, $entity_type, $entity_id, array $payload, array $meta = [] ) {
		if ( ! class_exists( 'NGC_Agent_Event_Envelope' ) ) {
			return;
		}
		$transient = 'ngc_env_' . md5( $dedupe_key );
		if ( get_transient( $transient ) ) {
			return;
		}
		set_transient( $transient, 1, 5 * MINUTE_IN_SECONDS );
		$meta['source'] = $meta['source'] ?? 'companion_bridge';
		NGC_Agent_Event_Envelope::emit( $type, $entity_type, $entity_id, $payload, $meta );
	}

	/**
	 * @param string $key Dot key.
	 * @return string
	 */
	private static function key_to_type( $key ) {
		$parts = preg_split( '/[._\-]+/', $key ) ?: [];
		$out   = '';
		foreach ( $parts as $p ) {
			$out .= ucfirst( strtolower( $p ) );
		}
		return $out ?: 'DomainEvent';
	}
}
