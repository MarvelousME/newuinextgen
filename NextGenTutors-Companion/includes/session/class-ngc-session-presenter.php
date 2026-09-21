<?php
/**
 * Dashboard presenter — no meeting URL leakage.
 *
 * Join URLs are issued only by NGC_Session_Launch after payment + window checks.
 * Dashboard and booking REST payloads must use this class so secrets never leak.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Formats sessions/bookings for dashboards from persisted data.
 */
class NGC_Session_Presenter {

	public const DEFAULT_TIMEZONE = 'Africa/Johannesburg';

	/**
	 * Keys that must never appear in REST/dashboard booking payloads.
	 *
	 * @var string[]
	 */
	private static $launch_secret_keys = [
		'join_url',
		'joinUrl',
		'meeting_url',
		'meetingUrl',
		'player_url',
		'launch_url',
		'classroom_url',
		'password',
		'token',
		'secret',
		'access_token',
		'room',
	];

	/**
	 * @param object|array $booking Booking row.
	 * @param int          $viewer  Viewer.
	 * @return array<string, mixed>
	 */
	public static function format_session_row( $booking, $viewer ) {
		$booking_id = (int) self::booking_field( $booking, 'id', 0 );
		$session    = $booking_id ? NGC_Session_Repository::get_by_booking_id( $booking_id ) : null;
		if ( $session ) {
			return self::format_session( $session, $viewer );
		}

		$base = [];
		if ( class_exists( 'NGC_Bookings' ) ) {
			$base = NGC_Bookings::format_session_row_legacy( $booking, $viewer );
		}
		$window = NGC_Session_Join_Policy::evaluate(
			[
				'status'           => NGC_Session_States::AWAITING_PAYMENT,
				'payment_status'   => 'unpaid',
				'scheduled_start'  => self::booking_field( $booking, 'scheduled_at', '' ),
				'scheduled_end'    => '',
				'duration_minutes' => self::booking_field( $booking, 'duration_minutes', 60 ),
			]
		);

		return array_merge(
			$base,
			self::blank_join_fields(),
			[
				'sessionId'     => 0,
				'canJoin'       => false,
				'joinReason'    => $window['reason'],
				'joinLabel'     => $window['label'],
				'paymentStatus' => 'unpaid',
				'timezone'      => self::DEFAULT_TIMEZONE,
			]
		);
	}

	/**
	 * @param array<string, mixed> $session Session.
	 * @param int                  $viewer  Viewer.
	 * @return array<string, mixed>
	 */
	public static function format_session( array $session, $viewer ) {
		$viewer  = (int) $viewer;
		$peer_id = (int) $session['tutor_user_id'] === $viewer
			? (int) $session['student_user_id']
			: (int) $session['tutor_user_id'];
		$peer    = function_exists( 'get_user_by' ) ? get_user_by( 'id', $peer_id ) : null;
		$avatar  = ( $peer && function_exists( 'get_avatar_url' ) ) ? get_avatar_url( $peer->ID ) : '';
		$window  = NGC_Session_Join_Policy::evaluate( $session );
		$invoice = null;
		if ( ! empty( $session['order_id'] ) && class_exists( 'NGC_Invoices' ) ) {
			$invoice = self::invoice_for_order( (int) $session['order_id'] );
		}

		return array_merge(
			self::blank_join_fields(),
			[
				'id'               => (int) $session['booking_id'],
				'bookingId'        => (int) $session['booking_id'],
				'sessionId'        => (int) $session['id'],
				'sessionUuid'      => (string) $session['session_uuid'],
				'correlationId'    => (string) $session['correlation_id'],
				'peerName'         => $peer ? $peer->display_name : __( 'Unknown', 'nextgencompanion' ),
				'peerImage'        => $avatar,
				'subject'          => (string) $session['subject_name'],
				'createdAt'        => (string) $session['scheduled_start'],
				'scheduledStart'   => (string) $session['scheduled_start'],
				'scheduledEnd'     => (string) $session['scheduled_end'],
				'timezone'         => (string) $session['timezone'],
				'status'           => (string) $session['status'],
				'statusLabel'      => self::status_label( (string) $session['status'], $window ),
				'attendance'       => (string) ( $session['meta']['attendance'] ?? $session['status'] ),
				'paymentStatus'    => (string) $session['payment_status'],
				'bookingStatus'    => (string) $session['booking_status'],
				'lessonStatus'     => (string) $session['lesson_status'],
				'meetingStatus'    => (string) $session['meeting_status'],
				'canJoin'          => ! empty( $window['allowed'] ),
				'joinReason'       => (string) $window['reason'],
				'joinLabel'        => (string) $window['label'],
				'countdown'        => (string) $window['label'],
				'secondsUntilOpen' => (int) $window['seconds_until_open'],
				'invoice'          => $invoice,
				'orderId'          => (int) $session['order_id'],
				'materials'        => (array) ( $session['meta']['materials'] ?? [] ),
				'studentJoinedAt'  => $session['student_joined_at'],
				'tutorJoinedAt'    => $session['tutor_joined_at'],
				'completedAt'      => $session['completed_at'],
			]
		);
	}

	/**
	 * @param string               $status Status.
	 * @param array<string, mixed> $window Window.
	 * @return string
	 */
	public static function status_label( $status, array $window ) {
		if ( ! empty( $window['allowed'] ) ) {
			return __( 'JOIN LESSON', 'nextgencompanion' );
		}
		if ( 'too_early' === ( $window['reason'] ?? '' ) ) {
			return (string) $window['label'];
		}
		$map = [
			NGC_Session_States::AWAITING_PAYMENT => __( 'Awaiting payment', 'nextgencompanion' ),
			NGC_Session_States::COMPLETED        => __( 'Completed', 'nextgencompanion' ),
			NGC_Session_States::CANCELLED        => __( 'Cancelled', 'nextgencompanion' ),
			NGC_Session_States::REFUNDED         => __( 'Refunded', 'nextgencompanion' ),
			NGC_Session_States::FAILED           => __( 'Failed', 'nextgencompanion' ),
		];
		return $map[ $status ] ?? ucfirst( str_replace( '_', ' ', $status ) );
	}

	/**
	 * Recursively strip launch URLs and meeting secrets from an array.
	 *
	 * @param mixed $data Nested payload.
	 * @return mixed
	 */
	public static function redact_launch_secrets( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}
		foreach ( self::$launch_secret_keys as $key ) {
			unset( $data[ $key ] );
		}
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = self::redact_launch_secrets( $value );
			}
		}
		return $data;
	}

	/**
	 * Booking REST/list payload without meeting join URLs.
	 *
	 * @param object|array|null $booking Booking row.
	 * @return object|array|null
	 */
	public static function sanitize_booking_for_rest( $booking ) {
		if ( null === $booking || false === $booking ) {
			return $booking;
		}

		$is_array = is_array( $booking );
		$row      = $is_array ? $booking : clone $booking;
		$meta     = self::redact_launch_secrets( self::decode_meta( $is_array ? ( $row['meta'] ?? null ) : ( $row->meta ?? null ) ) );

		if ( $is_array ) {
			$row['meta'] = $meta;
			foreach ( self::$launch_secret_keys as $key ) {
				unset( $row[ $key ] );
			}
			return $row;
		}

		$row->meta = $meta;
		foreach ( self::$launch_secret_keys as $key ) {
			if ( isset( $row->{$key} ) ) {
				unset( $row->{$key} );
			}
		}
		return $row;
	}

	/**
	 * @param array<int, object|array> $bookings Rows.
	 * @return array<int, object|array>
	 */
	public static function sanitize_bookings_for_rest( array $bookings ) {
		$out = [];
		foreach ( $bookings as $booking ) {
			$out[] = self::sanitize_booking_for_rest( $booking );
		}
		return $out;
	}

	/**
	 * @param int $order_id Order.
	 * @return array<string, mixed>|null
	 */
	public static function invoice_for_order( $order_id ) {
		global $wpdb;
		if ( ! class_exists( 'NGC_Database' ) ) {
			return null;
		}
		$table = NGC_Database::table( 'invoices' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d ORDER BY id DESC LIMIT 1", (int) $order_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $row ) {
			return null;
		}
		return [
			'id'     => (int) $row->id,
			'number' => (string) $row->invoice_number,
			'amount' => (float) $row->amount,
			'status' => (string) $row->status,
		];
	}

	/**
	 * @return array<string, string>
	 */
	private static function blank_join_fields() {
		return [
			'joinUrl'    => '',
			'join_url'   => '',
			'meetingUrl' => '',
		];
	}

	/**
	 * @param object|array $booking Booking.
	 * @param string       $key     Field.
	 * @param mixed        $default Default.
	 * @return mixed
	 */
	private static function booking_field( $booking, $key, $default = '' ) {
		if ( is_array( $booking ) ) {
			return $booking[ $key ] ?? $default;
		}
		return $booking->{$key} ?? $default;
	}

	/**
	 * @param mixed $raw Meta JSON, array, or empty.
	 * @return array<string, mixed>
	 */
	private static function decode_meta( $raw ) {
		if ( is_array( $raw ) ) {
			return $raw;
		}
		if ( ! is_string( $raw ) || '' === $raw ) {
			return [];
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : [];
	}
}
