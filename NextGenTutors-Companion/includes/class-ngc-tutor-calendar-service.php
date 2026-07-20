<?php
/**
 * Tutor calendar service.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Produces public-safe tutor calendar slots.
 */
class NGC_Tutor_Calendar_Service {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'wp_ajax_ngc_calendar_slot_selected', [ __CLASS__, 'slot_selected' ] );
		add_action( 'wp_ajax_nopriv_ngc_calendar_slot_selected', [ __CLASS__, 'slot_selected' ] );
	}

	/**
	 * Handle booking CTA slot selection event.
	 */
	public static function slot_selected() {
		check_ajax_referer( 'ngc_calendar_slot', 'nonce' );
		$tutor_id      = (int) ( $_POST['tutor_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$date          = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$start         = sanitize_text_field( wp_unslash( $_POST['start_time'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$delivery_mode = sanitize_text_field( wp_unslash( $_POST['delivery_mode'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$subject       = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		NGC_Audit::log(
			'calendar_slot_selected',
			'tutor',
			$tutor_id,
			[
				'event'         => 'CALENDAR_SLOT_SELECTED',
				'date'          => $date,
				'start_time'    => $start,
				'delivery_mode' => $delivery_mode,
				'subject'       => $subject,
				'visitor_id'    => sanitize_text_field( wp_unslash( $_COOKIE['visitor_id'] ?? '' ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			]
		);
		wp_send_json_success( [ 'ok' => true ] );
	}

	/**
	 * @param int                  $tutor_id Tutor post/user id.
	 * @param array<string, mixed> $args     Query args.
	 * @return array<string, mixed>
	 */
	public static function get_calendar( $tutor_id, $args = [] ) {
		$context = NGC_Tutor_Availability_Repository::resolve_tutor_context( (int) $tutor_id );
		if ( ! NGC_Tutor_Availability_Repository::is_publicly_bookable( $context ) ) {
			return [
				'success' => false,
				'error'   => 'tutor_not_public',
				'data'    => [
					'tutor_id' => (int) $tutor_id,
					'timezone' => $context['timezone'],
					'slots'    => [],
				],
				'meta'    => [
					'source'       => 'fallback',
					'retrieved_at' => gmdate( 'c' ),
				],
			];
		}

		$timezone = NGC_Tutor_Availability_Repository::sanitize_timezone( (string) ( $args['timezone'] ?? $context['timezone'] ) );
		$from     = self::sanitize_date( (string) ( $args['from'] ?? gmdate( 'Y-m-d' ) ) );
		$to       = self::sanitize_date( (string) ( $args['to'] ?? gmdate( 'Y-m-d', strtotime( '+21 days' ) ) ) );
		$subject  = sanitize_text_field( (string) ( $args['subject'] ?? '' ) );
		$delivery = NGC_Tutor_Availability_Repository::normalize_delivery_mode( (string) ( $args['delivery_mode'] ?? $context['delivery_mode'] ) );
		$demo     = ! empty( $args['demo'] ) && class_exists( 'NGC_Platform_Demo' ) && NGC_Platform_Demo::is_enabled();

		if ( $demo ) {
			return self::demo_calendar( $context, $timezone );
		}

		$base_slots = self::generate_available_slots( $context, $from, $to, $delivery, $timezone, $subject );
		$busy_slots = [];
		$source     = 'internal';

		$amelia = new NGC_Amelia_Availability_Adapter();
		if ( $amelia->is_available() && ! empty( $context['amelia_employee_id'] ) ) {
			$busy_slots = $amelia->get_busy_slots( (int) $context['amelia_employee_id'], $from, $to );
			$source     = 'amelia';
		}

		if ( empty( $busy_slots ) ) {
			$internal  = new NGC_Internal_Booking_Adapter();
			$busy_slots = $internal->get_busy_slots( (int) $context['user_id'], $from, $to );
			$source     = 'internal';
		}

		$slots = self::merge_slots( $base_slots, $busy_slots, $delivery, $timezone, $subject );
		return [
			'success' => true,
			'data'    => [
				'tutor_id'           => (int) $context['tutor_id'],
				'user_id'            => (int) $context['user_id'],
				'amelia_employee_id' => (int) $context['amelia_employee_id'],
				'timezone'           => $timezone,
				'delivery_mode'      => $delivery,
				'slots'              => $slots,
			],
			'meta' => [
				'source'       => $source,
				'retrieved_at' => gmdate( 'c' ),
			],
		];
	}

	/**
	 * @param array<string, mixed> $context Tutor context.
	 * @param string               $timezone Timezone.
	 * @return array<string, mixed>
	 */
	private static function demo_calendar( $context, $timezone ) {
		$data = [];
		if ( class_exists( 'NGC_Platform_Demo' ) ) {
			$data = NGC_Platform_Demo::get_payload( 'demo_tutor_calendar' );
		}
		$slots = is_array( $data['slots'] ?? null ) ? $data['slots'] : [];
		return [
			'success' => true,
			'data'    => [
				'tutor_id'           => (int) $context['tutor_id'],
				'user_id'            => (int) $context['user_id'],
				'amelia_employee_id' => (int) $context['amelia_employee_id'],
				'timezone'           => $timezone,
				'delivery_mode'      => NGC_Tutor_Availability_Repository::normalize_delivery_mode( (string) ( $context['delivery_mode'] ?? 'hybrid' ) ),
				'slots'              => $slots,
			],
			'meta' => [
				'source'       => 'demo',
				'retrieved_at' => gmdate( 'c' ),
			],
		];
	}

	/**
	 * @param array<string, mixed> $context  Tutor context.
	 * @param string               $from     Date from.
	 * @param string               $to       Date to.
	 * @param string               $delivery Delivery mode.
	 * @param string               $timezone Timezone.
	 * @param string               $subject  Subject.
	 * @return array<int, array<string, mixed>>
	 */
	private static function generate_available_slots( $context, $from, $to, $delivery, $timezone, $subject ) {
		$hours      = NGC_Tutor_Availability_Repository::normalize_working_hours( (array) ( $context['working_hours'] ?? [] ) );
		$period_from = new DateTimeImmutable( $from, new DateTimeZone( $timezone ) );
		$period_to   = new DateTimeImmutable( $to, new DateTimeZone( $timezone ) );
		$slots      = [];
		for ( $day = $period_from; $day <= $period_to; $day = $day->modify( '+1 day' ) ) {
			$key = strtolower( $day->format( 'l' ) );
			if ( empty( $hours[ $key ]['start'] ) || empty( $hours[ $key ]['end'] ) ) {
				continue;
			}
			$start = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $day->format( 'Y-m-d' ) . ' ' . $hours[ $key ]['start'], new DateTimeZone( $timezone ) );
			$end   = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $day->format( 'Y-m-d' ) . ' ' . $hours[ $key ]['end'], new DateTimeZone( $timezone ) );
			if ( ! $start || ! $end || $start >= $end ) {
				continue;
			}
			for ( $cursor = $start; $cursor < $end; $cursor = $cursor->modify( '+1 hour' ) ) {
				$slot_end = $cursor->modify( '+1 hour' );
				if ( $slot_end > $end ) {
					break;
				}
				$slots[] = [
					'date'          => $cursor->format( 'Y-m-d' ),
					'start_time'    => $cursor->format( 'H:i' ),
					'end_time'      => $slot_end->format( 'H:i' ),
					'slot_status'   => 'available',
					'delivery_mode' => $delivery,
					'location_type' => 'in-person' === $delivery ? 'in_person' : ( 'online' === $delivery ? 'online' : 'hybrid' ),
					'subject'       => $subject,
					'timezone'      => $timezone,
					'booking_id'    => 0,
					'is_available'  => true,
					'is_booked'     => false,
					'is_blocked'    => false,
					'source'        => 'internal',
				];
			}
		}
		return $slots;
	}

	/**
	 * @param array<int, array<string, mixed>> $base      Base slots.
	 * @param array<int, array<string, mixed>> $busy      Busy slots.
	 * @param string                           $delivery  Delivery mode.
	 * @param string                           $timezone  Timezone.
	 * @param string                           $subject   Subject.
	 * @return array<int, array<string, mixed>>
	 */
	private static function merge_slots( $base, $busy, $delivery, $timezone, $subject ) {
		$map = [];
		foreach ( $base as $slot ) {
			$key = $slot['date'] . '|' . $slot['start_time'] . '|' . $slot['end_time'];
			$map[ $key ] = $slot;
		}
		foreach ( $busy as $slot ) {
			$key = $slot['date'] . '|' . $slot['start_time'] . '|' . $slot['end_time'];
			$status = self::normalize_slot_status( (string) ( $slot['slot_status'] ?? 'unavailable' ) );
			$entry  = [
				'date'          => sanitize_text_field( (string) $slot['date'] ),
				'start_time'    => sanitize_text_field( (string) $slot['start_time'] ),
				'end_time'      => sanitize_text_field( (string) $slot['end_time'] ),
				'slot_status'   => $status,
				'delivery_mode' => $delivery,
				'location_type' => 'in-person' === $delivery ? 'in_person' : ( 'online' === $delivery ? 'online' : 'hybrid' ),
				'subject'       => $subject,
				'timezone'      => $timezone,
				'booking_id'    => self::public_safe_booking_id( (int) ( $slot['booking_id'] ?? 0 ) ),
				'is_available'  => 'available' === $status,
				'is_booked'     => in_array( $status, [ 'booked', 'pending', 'completed' ], true ),
				'is_blocked'    => in_array( $status, [ 'blocked', 'unavailable' ], true ),
				'source'        => sanitize_key( (string) ( $slot['source'] ?? 'internal' ) ),
			];

			if ( isset( $map[ $key ] ) ) {
				$map[ $key ] = array_merge( $map[ $key ], $entry );
			} else {
				$map[ $key ] = $entry;
			}
		}

		$slots = array_values( $map );
		usort(
			$slots,
			static function ( $a, $b ) {
				$left  = $a['date'] . ' ' . $a['start_time'];
				$right = $b['date'] . ' ' . $b['start_time'];
				return strcmp( $left, $right );
			}
		);
		foreach ( $slots as &$slot ) {
			$slot['status']       = $slot['slot_status'];
			$slot['public_label'] = self::public_label( (string) $slot['slot_status'] );
			unset( $slot['slot_status'] );
		}
		unset( $slot );

		return $slots;
	}

	/**
	 * @param int $booking_id Booking ID.
	 * @return int
	 */
	private static function public_safe_booking_id( $booking_id ) {
		return $booking_id > 0 ? (int) $booking_id : 0;
	}

	/**
	 * @param string $status Status.
	 * @return string
	 */
	private static function public_label( $status ) {
		$map = [
			'available'   => 'Available',
			'booked'      => 'Booked',
			'blocked'     => 'Unavailable',
			'unavailable' => 'Not available',
			'pending'     => 'Pending confirmation',
			'completed'   => 'Unavailable',
			'cancelled'   => 'Unavailable',
		];
		return $map[ $status ] ?? 'Not available';
	}

	/**
	 * @param string $status Status.
	 * @return string
	 */
	private static function normalize_slot_status( $status ) {
		$status = sanitize_key( $status );
		return in_array( $status, [ 'available', 'booked', 'blocked', 'unavailable', 'pending', 'completed', 'cancelled' ], true ) ? $status : 'unavailable';
	}

	/**
	 * @param string $date Date.
	 * @return string
	 */
	private static function sanitize_date( $date ) {
		$date = sanitize_text_field( $date );
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : gmdate( 'Y-m-d' );
	}
}

