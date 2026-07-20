<?php
/**
 * Booking lifecycle management.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bookings CRUD and status transitions.
 */
class NGC_Bookings {

	/** @var string[] */
	private static $statuses = [ 'requested', 'confirmed', 'cancelled', 'completed' ];

	/**
	 * @param array<string, mixed> $data Booking data.
	 * @return int|WP_Error
	 */
	public static function create( $data ) {
		global $wpdb;
		$table = NGC_Database::table( 'bookings' );

		$row = [
			'uuid'             => class_exists( 'NGC_Uuid' ) ? NGC_Uuid::generate() : wp_generate_uuid4(),
			'match_id'         => (int) ( $data['match_id'] ?? 0 ),
			'student_user_id'  => (int) ( $data['student_user_id'] ?? 0 ),
			'tutor_user_id'    => (int) ( $data['tutor_user_id'] ?? 0 ),
			'subject'          => sanitize_text_field( $data['subject'] ?? '' ),
			'scheduled_at'     => ! empty( $data['scheduled_at'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $data['scheduled_at'] ) ) : null,
			'duration_minutes' => (int) ( $data['duration_minutes'] ?? 60 ),
			'status'           => 'requested',
			'amount'           => (float) ( $data['amount'] ?? 0 ),
			'currency'         => sanitize_text_field( $data['currency'] ?? 'ZAR' ),
			'order_id'         => (int) ( $data['order_id'] ?? 0 ),
			'notes'            => sanitize_textarea_field( $data['notes'] ?? '' ),
			'meta'             => wp_json_encode( $data['meta'] ?? [] ),
			'created_at'       => current_time( 'mysql', true ),
			'updated_at'       => current_time( 'mysql', true ),
		];

		$conflict = self::has_conflict(
			(int) $row['tutor_user_id'],
			(string) $row['scheduled_at'],
			(int) $row['duration_minutes']
		);
		if ( $conflict ) {
			return new WP_Error( 'ngc_booking_conflict', __( 'Selected slot is no longer available.', 'nextgencompanion' ) );
		}

		$inserted = $wpdb->insert( $table, $row );
		if ( ! $inserted ) {
			return new WP_Error( 'ngc_booking_create_failed', __( 'Could not create booking.', 'nextgencompanion' ) );
		}

		$id = (int) $wpdb->insert_id;
		NGC_Audit::log( 'booking_created', 'booking', $id, $data );
		NGC_Platform_Repository::create(
			'conversions',
			[
				'event_key'   => 'booking_created',
				'user_id'     => (int) $row['student_user_id'],
				'visitor_id'  => sanitize_text_field( wp_unslash( $_COOKIE['visitor_id'] ?? '' ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				'object_type' => 'booking',
				'object_id'   => $id,
				'value'       => (float) $row['amount'],
				'currency'    => $row['currency'],
				'attribution' => sanitize_text_field( wp_unslash( $_COOKIE['last_touch_source'] ?? '' ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			]
		);
		NGC_Workflows::dispatch(
			'booking.created',
			[
				'booking_id'      => (string) $id,
				'student_user_id' => (string) $row['student_user_id'],
				'tutor_user_id'   => (string) $row['tutor_user_id'],
				'service_id'      => (string) $row['subject'],
				'employee_id'     => (string) $row['tutor_user_id'],
			]
		);

		return $id;
	}

	/**
	 * Prevent duplicate/overlapping tutor slots.
	 *
	 * @param int    $tutor_user_id Tutor ID.
	 * @param string $scheduled_at  Start datetime.
	 * @param int    $duration      Duration minutes.
	 * @return bool
	 */
	private static function has_conflict( $tutor_user_id, $scheduled_at, $duration ) {
		global $wpdb;
		if ( ! $tutor_user_id || ! $scheduled_at ) {
			return false;
		}
		$table = NGC_Database::table( 'bookings' );
		$end   = gmdate( 'Y-m-d H:i:s', strtotime( $scheduled_at . ' +' . max( 1, (int) $duration ) . ' minutes' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table}
				WHERE tutor_user_id = %d
				AND status IN ('requested','confirmed')
				AND scheduled_at < %s
				AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > %s",
				$tutor_user_id,
				$end,
				$scheduled_at
			)
		);
		return $count > 0;
	}

	/**
	 * @param int    $booking_id Booking ID.
	 * @param string $status     New status.
	 * @param int    $actor_id   Actor.
	 * @return true|WP_Error
	 */
	public static function transition( $booking_id, $status, $actor_id = 0 ) {
		global $wpdb;
		$status = sanitize_key( $status );
		if ( ! in_array( $status, self::$statuses, true ) ) {
			return new WP_Error( 'ngc_invalid_status', __( 'Invalid booking status.', 'nextgencompanion' ) );
		}

		$booking = self::get( $booking_id );
		if ( ! $booking ) {
			return new WP_Error( 'ngc_booking_not_found', __( 'Booking not found.', 'nextgencompanion' ) );
		}

		$table = NGC_Database::table( 'bookings' );
		$wpdb->update(
			$table,
			[ 'status' => $status, 'updated_at' => current_time( 'mysql', true ) ],
			[ 'id' => $booking_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		NGC_Audit::log( 'booking_' . $status, 'booking', $booking_id, [ 'from' => $booking->status ], $actor_id );

		if ( 'completed' === $status ) {
			NGC_Workflows::dispatch(
				'lesson.completed',
				[
					'student_user_id' => (string) $booking->student_user_id,
					'tutor_user_id'   => (string) $booking->tutor_user_id,
					'progress_note'   => $booking->notes,
					'booking_id'      => (string) $booking_id,
				]
			);
			// NGC_Workflows::dispatch already fires ngc_lesson_completed — do not duplicate.
			self::log_session( $booking_id, 'attended' );
			NGC_Reviews::record_earning( $booking );
		}

		if ( 'cancelled' === $status ) {
			NGC_Workflows::dispatch(
				'booking.cancelled',
				[
					'booking_id'      => (string) $booking_id,
					'student_user_id' => (string) $booking->student_user_id,
					'tutor_user_id'   => (string) $booking->tutor_user_id,
				]
			);
		}

		return true;
	}

	/**
	 * @param int    $booking_id Booking ID.
	 * @param string $attendance Attendance slug.
	 */
	public static function log_session( $booking_id, $attendance = 'scheduled' ) {
		global $wpdb;
		$booking = self::get( $booking_id );
		if ( ! $booking ) {
			return;
		}
		$table = NGC_Database::table( 'session_logs' );
		$wpdb->insert(
			$table,
			[
				'booking_id'      => $booking_id,
				'student_user_id' => $booking->student_user_id,
				'tutor_user_id'   => $booking->tutor_user_id,
				'attendance'      => sanitize_key( $attendance ),
				'started_at'      => current_time( 'mysql', true ),
				'created_at'      => current_time( 'mysql', true ),
			],
			[ '%d', '%d', '%d', '%s', '%s', '%s' ]
		);
	}

	/**
	 * @param int $booking_id Booking ID.
	 * @return object|null
	 */
	public static function get( $booking_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'bookings' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $booking_id ) );
	}

	/**
	 * @param int $amelia_id Amelia booking ID.
	 * @return object|null
	 */
	public static function get_by_amelia_id( $amelia_id ) {
		global $wpdb;
		$amelia_id = (int) $amelia_id;
		if ( $amelia_id <= 0 ) {
			return null;
		}
		$table = NGC_Database::table( 'bookings' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE amelia_booking_id = %d", $amelia_id ) );
	}

	/**
	 * Sync or create internal booking from Amelia payload.
	 *
	 * @param array<string, mixed> $data Amelia booking data.
	 * @return int Internal booking ID.
	 */
	public static function sync_from_amelia( $data ) {
		$amelia_id = (int) ( $data['id'] ?? $data['bookingId'] ?? 0 );
		if ( $amelia_id <= 0 ) {
			return 0;
		}
		$existing = self::get_by_amelia_id( $amelia_id );
		if ( $existing ) {
			return (int) $existing->id;
		}

		$employee_id = (int) ( $data['providerId'] ?? $data['employeeId'] ?? 0 );
		$tutor_id    = 0;
		if ( $employee_id ) {
			$users = get_users(
				[
					'meta_key'   => 'ngc_amelia_employee_id',
					'meta_value' => (string) $employee_id,
					'number'     => 1,
					'fields'     => 'ID',
				]
			);
			$tutor_id = $users ? (int) $users[0] : 0;
		}

		$starts = (string) ( $data['bookingStart'] ?? $data['start'] ?? '' );
		$scheduled = $starts ? gmdate( 'Y-m-d H:i:s', strtotime( $starts ) ) : null;
		$email     = sanitize_email( (string) ( $data['customerEmail'] ?? $data['email'] ?? '' ) );
		$student   = $email ? get_user_by( 'email', $email ) : false;

		global $wpdb;
		$table = NGC_Database::table( 'bookings' );
		$wpdb->insert(
			$table,
			[
				'uuid'              => class_exists( 'NGC_Uuid' ) ? NGC_Uuid::generate() : wp_generate_uuid4(),
				'student_user_id'   => $student ? (int) $student->ID : 0,
				'tutor_user_id'     => $tutor_id,
				'subject'           => sanitize_text_field( (string) ( $data['serviceName'] ?? $data['service'] ?? 'Amelia session' ) ),
				'scheduled_at'      => $scheduled,
				'duration_minutes'  => (int) ( $data['duration'] ?? 60 ),
				'status'            => 'confirmed',
				'amelia_booking_id' => $amelia_id,
				'meta'              => wp_json_encode( [ 'source' => 'amelia', 'amelia' => $data ] ),
				'created_at'        => current_time( 'mysql', true ),
				'updated_at'        => current_time( 'mysql', true ),
			]
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * @param int    $amelia_id Amelia booking ID.
	 * @param string $status    Internal status slug.
	 * @return true|WP_Error
	 */
	public static function update_status_by_amelia_id( $amelia_id, $status ) {
		$row = self::get_by_amelia_id( $amelia_id );
		if ( ! $row ) {
			return new WP_Error( 'ngc_booking_not_found', __( 'No internal booking for Amelia ID.', 'nextgencompanion' ) );
		}
		return self::transition( (int) $row->id, $status );
	}

	/**
	 * @param array<string, mixed> $args Query args.
	 * @return array<int, object>
	 */
	public static function query( $args = [] ) {
		global $wpdb;
		$table  = NGC_Database::table( 'bookings' );
		$where  = [ '1=1' ];
		$values = [];

		if ( ! empty( $args['student_user_id'] ) ) {
			$where[]  = 'student_user_id = %d';
			$values[] = (int) $args['student_user_id'];
		}
		if ( ! empty( $args['tutor_user_id'] ) ) {
			$where[]  = 'tutor_user_id = %d';
			$values[] = (int) $args['tutor_user_id'];
		}
		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = sanitize_key( $args['status'] );
		}

		$limit = isset( $args['limit'] ) ? (int) $args['limit'] : 20;
		$sql   = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY scheduled_at DESC, id DESC LIMIT %d';
		$values[] = $limit;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
	}

	/**
	 * @param int                  $booking_id Booking ID.
	 * @param array<string, mixed> $data       Update data.
	 * @return true|WP_Error
	 */
	public static function update( $booking_id, $data ) {
		global $wpdb;
		$booking = self::get( $booking_id );
		if ( ! $booking ) {
			return new WP_Error( 'ngc_booking_not_found', __( 'Booking not found.', 'nextgencompanion' ) );
		}

		$fields = [];
		$formats = [];
		if ( isset( $data['scheduled_at'] ) ) {
			$fields['scheduled_at'] = gmdate( 'Y-m-d H:i:s', strtotime( $data['scheduled_at'] ) );
			$formats[] = '%s';
		}
		if ( isset( $data['notes'] ) ) {
			$fields['notes'] = sanitize_textarea_field( $data['notes'] );
			$formats[] = '%s';
		}
		if ( isset( $data['amount'] ) ) {
			$fields['amount'] = (float) $data['amount'];
			$formats[] = '%f';
		}
		if ( isset( $data['status'] ) ) {
			$fields['status'] = sanitize_key( (string) $data['status'] );
			$formats[] = '%s';
		}
		if ( empty( $fields ) ) {
			return true;
		}
		$fields['updated_at'] = current_time( 'mysql', true );
		$formats[] = '%s';

		$table = NGC_Database::table( 'bookings' );
		$wpdb->update( $table, $fields, [ 'id' => $booking_id ], $formats, [ '%d' ] );
		NGC_Audit::log( 'booking_updated', 'booking', $booking_id, $data );
		return true;
	}

	/**
	 * @param int    $booking_id Booking ID.
	 * @param string $status     New status slug.
	 * @return true|WP_Error
	 */
	public static function update_status( $booking_id, $status ) {
		return self::update( $booking_id, [ 'status' => $status ] );
	}

	/**
	 * @param int $booking_id Booking ID.
	 * @return true|WP_Error
	 */
	public static function delete( $booking_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'bookings' );
		$wpdb->delete( $table, [ 'id' => $booking_id ], [ '%d' ] );
		NGC_Audit::log( 'booking_deleted', 'booking', $booking_id );
		return true;
	}

	/**
	 * @param int $parent_id Parent user ID.
	 * @param int $limit     Limit.
	 * @return array<int, object>
	 */
	public static function query_for_parent( $parent_id, $limit = 20 ) {
		global $wpdb;
		$matches_table  = NGC_Database::table( 'matches' );
		$bookings_table = NGC_Database::table( 'bookings' );

		$linked = get_user_meta( $parent_id, 'ngc_linked_students', true );
		if ( ! is_array( $linked ) ) {
			$linked = [];
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$match_student_ids = $wpdb->get_col(
			$wpdb->prepare( "SELECT DISTINCT student_user_id FROM {$matches_table} WHERE parent_user_id = %d AND student_user_id > 0", $parent_id )
		);
		$student_ids = array_unique( array_filter( array_map( 'intval', array_merge( $linked, $match_student_ids ) ) ) );

		if ( empty( $student_ids ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT b.* FROM {$bookings_table} b
					INNER JOIN {$matches_table} m ON m.id = b.match_id
					WHERE m.parent_user_id = %d
					ORDER BY b.scheduled_at DESC, b.id DESC
					LIMIT %d",
					$parent_id,
					$limit
				)
			);
		}

		$placeholders = implode( ',', array_fill( 0, count( $student_ids ), '%d' ) );
		$values       = array_merge( $student_ids, [ $limit ] );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPlaceholder
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$bookings_table} WHERE student_user_id IN ({$placeholders}) ORDER BY scheduled_at DESC, id DESC LIMIT %d",
				$values
			)
		);
	}

	/**
	 * Format booking for dashboard session row.
	 *
	 * @param object $booking Row.
	 * @param int    $viewer  Viewing user ID.
	 * @return array<string, mixed>
	 */
	public static function format_session_row( $booking, $viewer ) {
		$viewer_user = get_user_by( 'id', $viewer );
		$roles       = $viewer_user ? (array) $viewer_user->roles : [];
		if ( in_array( 'parent', $roles, true ) || in_array( 'parent_guardian', $roles, true ) ) {
			$peer_id = (int) $booking->tutor_user_id;
		} elseif ( (int) $booking->student_user_id === $viewer ) {
			$peer_id = (int) $booking->tutor_user_id;
		} else {
			$peer_id = (int) $booking->student_user_id;
		}
		$peer    = get_user_by( 'id', $peer_id );
		$avatar  = $peer ? get_avatar_url( $peer->ID ) : '';

		return [
			'peerName'     => $peer ? $peer->display_name : __( 'Unknown', 'nextgencompanion' ),
			'peerImage'    => $avatar,
			'subject'      => $booking->subject,
			'createdAt'    => $booking->scheduled_at ?: $booking->created_at,
			'status'       => $booking->status,
			'statusLabel'  => ucfirst( $booking->status ),
			'attendance'   => $booking->status,
		];
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		// Called via REST and payments.
	}
}
