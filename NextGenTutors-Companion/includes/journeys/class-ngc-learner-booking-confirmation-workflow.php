<?php
/**
 * Learner booking confirmation workflow — PaymentCaptured side effects via authority.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single confirmation path: CRM projection, first-booking reward, learning provision, notify.
 */
final class NGC_Learner_Booking_Confirmation_Workflow {

	public const ACTION = 'learner_booking_confirmation';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'ngc_payment_settled', [ __CLASS__, 'on_payment_settled' ], 30, 2 );
		add_action( 'ngc_workflow_authority_execute_' . self::ACTION, [ __CLASS__, 'execute' ], 10, 1 );
	}

	/**
	 * @param int                  $order_id Order ID.
	 * @param array<string, mixed> $context  Context.
	 */
	public static function on_payment_settled( $order_id, $context = [] ) {
		if ( ! class_exists( 'NGC_Business_Rules' ) || ! NGC_Business_Rules::journey_enabled( 'learner' ) ) {
			return;
		}
		$order_id = (int) $order_id;
		$payload  = array_merge(
			is_array( $context ) ? $context : [],
			[
				'order_id'        => $order_id,
				'idempotency_key' => 'learner_confirm:' . $order_id,
			]
		);

		if ( class_exists( 'NGC_Workflow_Authority' ) && NGC_Platform::authority_enabled() ) {
			NGC_Workflow_Authority::from_producer( 'journey', self::ACTION, $payload );
			return;
		}
		self::execute( $payload );
	}

	/**
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	public static function execute( $payload ) {
		$order_id   = (int) ( $payload['order_id'] ?? 0 );
		$user_id    = (int) ( $payload['user_id'] ?? 0 );
		$booking_id = (int) ( $payload['booking_id'] ?? 0 );
		$amount     = isset( $payload['amount'] ) ? (float) $payload['amount'] : 0.0;

		if ( $order_id && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				if ( ! $user_id ) {
					$user_id = (int) $order->get_user_id();
				}
				if ( ! $booking_id ) {
					$booking_id = (int) $order->get_meta( 'ngc_booking_id' );
				}
				if ( $amount <= 0 ) {
					$amount = (float) $order->get_total();
				}
			}
		}

		$results = [
			'ok'         => true,
			'order_id'   => $order_id,
			'user_id'    => $user_id,
			'booking_id' => $booking_id,
			'steps'      => [],
		];

		if ( class_exists( 'NGC_Journey_Events' ) ) {
			NGC_Journey_Events::emit(
				NGC_Journey_Events::PAYMENT_CAPTURED,
				[
					'order_id'   => $order_id,
					'user_id'    => $user_id,
					'booking_id' => $booking_id,
					'amount'     => $amount,
				],
				false
			);
			if ( $booking_id ) {
				NGC_Journey_Events::emit(
					NGC_Journey_Events::BOOKING_CONFIRMED,
					[
						'booking_id' => $booking_id,
						'order_id'   => $order_id,
						'user_id'    => $user_id,
					],
					false
				);
			}
			$results['steps'][] = 'events_emitted';
		}

		// CRM projection (not booking authority).
		if ( $user_id && class_exists( 'NGC_Crm_Projection_Port' ) ) {
			$results['steps'][] = NGC_Crm_Projection_Port::project_active_customer( $user_id, $order_id );
		}

		// First-booking reward (idempotent).
		if ( $user_id && class_exists( 'NGC_First_Booking_Rule' ) ) {
			$results['steps'][] = NGC_First_Booking_Rule::maybe_award( $user_id, $order_id, $booking_id );
		}

		// Learning identity / enrollment projection.
		if ( $user_id && class_exists( 'NGC_Learning_Provider_Port' ) ) {
			$results['steps'][] = NGC_Learning_Provider_Port::ensure_student( $user_id );
		}

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log(
				'learner_booking_confirmation',
				'order',
				$order_id,
				[
					'user_id'    => $user_id,
					'booking_id' => $booking_id,
					'steps'      => $results['steps'],
				]
			);
		}

		return $results;
	}
}
