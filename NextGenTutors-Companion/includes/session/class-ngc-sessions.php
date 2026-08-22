<?php
/**
 * NGT orchestration sessions repository.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persistence for wp_ngc_sessions.
 */
class NGC_Sessions {

	/**
	 * Ensure schema exists.
	 */
	public static function ensure_schema() {
		if ( ! class_exists( 'NGC_Database' ) ) {
			return;
		}
		global $wpdb;
		$table = NGC_Database::table( 'sessions' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
		if ( ! $exists ) {
			NGC_Database::create_tables();
		}
	}

	/**
	 * @param int $id Session ID.
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = NGC_Database::table( 'sessions' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", (int) $id ) );
		return $row ?: null;
	}

	/**
	 * @param string $uuid Session UUID.
	 * @return object|null
	 */
	public static function get_by_uuid( $uuid ) {
		global $wpdb;
		$table = NGC_Database::table( 'sessions' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE session_uuid = %s LIMIT 1", sanitize_text_field( $uuid ) ) );
		return $row ?: null;
	}

	/**
	 * @param int $booking_id Booking ID.
	 * @return object|null
	 */
	public static function get_by_booking( $booking_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'sessions' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE booking_id = %d ORDER BY id DESC LIMIT 1",
				(int) $booking_id
			)
		);
		return $row ?: null;
	}

	/**
	 * @param int $order_id Order ID.
	 * @return object|null
	 */
	public static function get_by_order( $order_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'sessions' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE order_id = %d ORDER BY id DESC LIMIT 1",
				(int) $order_id
			)
		);
		return $row ?: null;
	}

	/**
	 * @param array<string, mixed> $data Insert data.
	 * @return int|WP_Error
	 */
	public static function create( $data ) {
		global $wpdb;
		self::ensure_schema();
		$table = NGC_Database::table( 'sessions' );

		$uuid = sanitize_text_field( (string) ( $data['session_uuid'] ?? '' ) );
		if ( '' === $uuid ) {
			$uuid = self::generate_uuid();
		}
		$correlation = sanitize_text_field( (string) ( $data['correlation_id'] ?? '' ) );
		if ( '' === $correlation ) {
			$correlation = 'NGT-SES-' . gmdate( 'Ymd' ) . '-' . strtoupper( substr( md5( $uuid ), 0, 8 ) );
		}
		$idem = sanitize_text_field( (string) ( $data['idempotency_key'] ?? '' ) );
		if ( '' === $idem ) {
			$idem = 'session:' . (int) ( $data['booking_id'] ?? 0 ) . ':' . (int) ( $data['order_id'] ?? 0 );
		}

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id FROM {$table} WHERE idempotency_key = %s LIMIT 1",
				$idem
			)
		);
		if ( $existing ) {
			return (int) $existing;
		}

		$now = current_time( 'mysql', true );
		$row = [
			'session_uuid'           => $uuid,
			'correlation_id'         => $correlation,
			'idempotency_key'        => $idem,
			'booking_provider'       => sanitize_key( (string) ( $data['booking_provider'] ?? 'ngc' ) ),
			'booking_id'             => (int) ( $data['booking_id'] ?? 0 ),
			'order_id'               => (int) ( $data['order_id'] ?? 0 ),
			'order_item_id'          => (int) ( $data['order_item_id'] ?? 0 ),
			'product_id'             => (int) ( $data['product_id'] ?? 0 ),
			'student_user_id'        => (int) ( $data['student_user_id'] ?? 0 ),
			'parent_user_id'         => (int) ( $data['parent_user_id'] ?? 0 ),
			'tutor_user_id'          => (int) ( $data['tutor_user_id'] ?? 0 ),
			'subject_id'             => sanitize_text_field( (string) ( $data['subject_id'] ?? '' ) ),
			'subject_name'           => sanitize_text_field( (string) ( $data['subject_name'] ?? '' ) ),
			'masterstudy_course_id'  => (int) ( $data['masterstudy_course_id'] ?? 0 ),
			'masterstudy_lesson_id'  => (int) ( $data['masterstudy_lesson_id'] ?? 0 ),
			'meeting_provider'       => sanitize_key( (string) ( $data['meeting_provider'] ?? '' ) ),
			'meeting_id'             => sanitize_text_field( (string) ( $data['meeting_id'] ?? '' ) ),
			'meeting_url_reference'  => esc_url_raw( (string) ( $data['meeting_url_reference'] ?? '' ) ),
			'scheduled_start'        => $data['scheduled_start'] ?? null,
			'scheduled_end'          => $data['scheduled_end'] ?? null,
			'timezone'               => sanitize_text_field( (string) ( $data['timezone'] ?? 'Africa/Johannesburg' ) ),
			'status'                 => sanitize_key( (string) ( $data['status'] ?? NGC_Session_States::DRAFT ) ),
			'payment_status'         => sanitize_key( (string) ( $data['payment_status'] ?? 'unpaid' ) ),
			'booking_status'         => sanitize_key( (string) ( $data['booking_status'] ?? '' ) ),
			'lesson_status'          => sanitize_key( (string) ( $data['lesson_status'] ?? '' ) ),
			'meeting_status'         => sanitize_key( (string) ( $data['meeting_status'] ?? '' ) ),
			'version'                => 1,
			'meta'                   => wp_json_encode( is_array( $data['meta'] ?? null ) ? $data['meta'] : [] ),
			'created_at'             => $now,
			'updated_at'             => $now,
		];

		// Platform-wide uuid unique column (ensure_uuid_columns) — never insert blank.
		if ( method_exists( 'NGC_Database', 'ensure_row_uuid' ) ) {
			$row = NGC_Database::ensure_row_uuid( $table, $row );
		} elseif ( ! isset( $row['uuid'] ) || '' === (string) $row['uuid'] ) {
			$row['uuid'] = $uuid;
		}

		$ok = $wpdb->insert( $table, $row );
		if ( ! $ok ) {
			return new WP_Error( 'ngc_session_create_failed', __( 'Could not create session.', 'nextgencompanion' ), [ 'db' => $wpdb->last_error ] );
		}
		$id = (int) $wpdb->insert_id;
		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'session_created', 'session', $id, [ 'correlation_id' => $correlation, 'booking_id' => $row['booking_id'] ] );
		}
		return $id;
	}

	/**
	 * @param int                  $id   Session ID.
	 * @param array<string, mixed> $data Patch.
	 * @return true|WP_Error
	 */
	public static function update( $id, $data ) {
		global $wpdb;
		$session = self::get( $id );
		if ( ! $session ) {
			return new WP_Error( 'ngc_session_not_found', __( 'Session not found.', 'nextgencompanion' ) );
		}

		if ( isset( $data['status'] ) ) {
			$to = sanitize_key( (string) $data['status'] );
			if ( ! NGC_Session_States::can_transition( $session->status, $to ) ) {
				return new WP_Error(
					'ngc_session_invalid_transition',
					sprintf(
						/* translators: 1: from status 2: to status */
						__( 'Invalid session transition %1$s → %2$s.', 'nextgencompanion' ),
						$session->status,
						$to
					),
					[ 'status' => 409 ]
				);
			}
		}

		$allowed = [
			'order_id', 'order_item_id', 'product_id', 'student_user_id', 'parent_user_id', 'tutor_user_id',
			'subject_id', 'subject_name', 'masterstudy_course_id', 'masterstudy_lesson_id',
			'meeting_provider', 'meeting_id', 'meeting_url_reference',
			'scheduled_start', 'scheduled_end', 'timezone',
			'status', 'payment_status', 'booking_status', 'lesson_status', 'meeting_status',
			'student_joined_at', 'tutor_joined_at', 'started_at', 'completed_at', 'cancelled_at',
			'version', 'meta', 'correlation_id',
		];
		$fields = [];
		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}
			$val = $data[ $key ];
			if ( 'meta' === $key && is_array( $val ) ) {
				$val = wp_json_encode( $val );
			}
			$fields[ $key ] = $val;
		}
		if ( empty( $fields ) ) {
			return true;
		}
		$fields['updated_at'] = current_time( 'mysql', true );
		$fields['version']    = (int) $session->version + 1;

		$table = NGC_Database::table( 'sessions' );
		$wpdb->update( $table, $fields, [ 'id' => (int) $id ] );
		if ( class_exists( 'NGC_Audit' ) && isset( $fields['status'] ) ) {
			NGC_Audit::log(
				'session_status',
				'session',
				(int) $id,
				[
					'from'           => $session->status,
					'to'             => $fields['status'],
					'correlation_id' => $session->correlation_id,
				]
			);
		}
		return true;
	}

	/**
	 * @return string
	 */
	public static function generate_uuid() {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return wp_generate_uuid4();
		}
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0x0fff ) | 0x4000,
			wp_rand( 0, 0x3fff ) | 0x8000,
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff )
		);
	}
}
