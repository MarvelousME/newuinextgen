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
	 * @return string[]
	 */
	public static function statuses() {
		return self::$statuses;
	}

	/**
	 * @param string $status Status slug.
	 * @return string|WP_Error
	 */
	public static function normalize_status( $status ) {
		$status = sanitize_key( $status );
		if ( ! in_array( $status, self::$statuses, true ) ) {
			return new WP_Error( 'ngc_invalid_status', __( 'Invalid booking status.', 'nextgencompanion' ) );
		}
		return $status;
	}

	/**
	 * @param array<string, mixed> $data Booking data.
	 * @return int|WP_Error
	 */
	public static function create( $data ) {
		global $wpdb;
		$table = NGC_Database::table( 'bookings' );

		$idem = self::begin_create_idempotency( $data );
		if ( 'error' === $idem['status'] ) {
			return $idem['error'];
		}
		if ( 'replay' === $idem['status'] ) {
			return (int) $idem['booking_id'];
		}
		$idem_key = ( 'begun' === $idem['status'] ) ? (string) $idem['key'] : '';

		$row = self::build_create_row( $data );
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
		self::after_create( $id, $data, $row );

		if ( $idem_key !== '' && class_exists( 'NGC_Idempotency' ) ) {
			NGC_Idempotency::commit( $idem_key, [ 'booking_id' => $id ] );
		}

		return $id;
	}

	/**
	 * @internal
	 * @param array<string, mixed> $data Booking data.
	 * @return array{status:string,key?:string,booking_id?:int,error?:WP_Error}
	 */
	private static function begin_create_idempotency( $data ) {
		if ( empty( $data['idempotency_key'] ) || ! class_exists( 'NGC_Idempotency' ) ) {
			return [ 'status' => 'skip' ];
		}
		$idem_key = (string) $data['idempotency_key'];
		$fp       = NGC_Idempotency::fingerprint(
			[
				'student' => (int) ( $data['student_user_id'] ?? 0 ),
				'tutor'   => (int) ( $data['tutor_user_id'] ?? 0 ),
				'sched'   => (string) ( $data['scheduled_at'] ?? '' ),
				'subject' => (string) ( $data['subject'] ?? '' ),
			]
		);
		$begun = NGC_Idempotency::begin( $idem_key, $fp, 'bookings' );
		if ( is_wp_error( $begun ) ) {
			return [
				'status' => 'error',
				'error'  => $begun,
			];
		}
		if ( 'replay' === ( $begun['status'] ?? '' ) ) {
			$replay = self::idempotency_replay_id( $begun );
			if ( is_wp_error( $replay ) ) {
				return [
					'status' => 'error',
					'error'  => $replay,
				];
			}
			return [
				'status'     => 'replay',
				'booking_id' => $replay,
			];
		}
		return [
			'status' => 'begun',
			'key'    => $idem_key,
		];
	}

	/**
	 * Replay id 0 must not look like a successful create.
	 *
	 * @internal
	 * @param array<string, mixed> $begun Idempotency begin() result.
	 * @return int|WP_Error
	 */
	private static function idempotency_replay_id( $begun ) {
		$id = (int) ( $begun['result']['booking_id'] ?? 0 );
		if ( $id <= 0 ) {
			return new WP_Error(
				'ngc_booking_idempotency_replay',
				__( 'Idempotent replay missing booking id.', 'nextgencompanion' )
			);
		}
		return $id;
	}

	/**
	 * @internal
	 * @param array<string, mixed> $data Booking data.
	 * @return array<string, mixed>
	 */
	private static function build_create_row( $data ) {
		return [
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
	}

	/**
	 * @param int                  $id   Booking ID.
	 * @param array<string, mixed> $data Original payload.
	 * @param array<string, mixed> $row  Inserted row.
	 */
	private static function after_create( $id, $data, $row ) {
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
		$status = self::normalize_status( $status );
		if ( is_wp_error( $status ) ) {
			return $status;
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
		self::dispatch_transition( $booking_id, $status, $booking, $actor_id );
		return true;
	}

	/**
	 * @param int    $booking_id Booking ID.
	 * @param string $status     New status.
	 * @param object $booking    Prior row.
	 * @param int    $actor_id   Actor.
	 */
	private static function dispatch_transition( $booking_id, $status, $booking, $actor_id ) {
		if ( 'confirmed' === $status ) {
			$meeting = class_exists( 'NGC_Meetings' )
				? NGC_Meetings::ensure_for_booking( $booking_id, [ 'user_id' => (int) $actor_id ] )
				: null;
			$join_url = ( ! is_wp_error( $meeting ) && is_array( $meeting ) ) ? (string) ( $meeting['join_url'] ?? '' ) : '';
			$ctx      = [
				'booking_id'      => (string) $booking_id,
				'student_user_id' => (string) $booking->student_user_id,
				'tutor_user_id'   => (string) $booking->tutor_user_id,
				'join_url'        => $join_url,
				'session_start'   => (string) ( $booking->scheduled_at ?? '' ),
				'subject'         => (string) ( $booking->subject ?? '' ),
			];
			NGC_Workflows::dispatch( 'booking.confirmed', $ctx );
			do_action( 'ngc_booking_confirmed', $booking_id, $ctx );
		}

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
			do_action( 'ngc_booking_completed', $booking_id, [ 'booking' => $booking ] );
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
			do_action( 'ngc_booking_cancelled', $booking_id, [ 'booking' => $booking ] );
		}
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

		// Commerce truth: Amelia schedules only. Do not mark paid/join-ready without Woo settlement.
		// Filter may return 'confirmed' only for explicitly approved non-commerce Amelia flows.
		$initial_status = apply_filters( 'ngc_amelia_sync_initial_status', 'requested', $data );
		if ( ! in_array( $initial_status, self::$statuses, true ) ) {
			$initial_status = 'requested';
		}

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
				'status'            => $initial_status,
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
	 * Recent bookings as associative rows for admin listings.
	 *
	 * @param int $limit Max rows.
	 * @return array<int, array<string, mixed>>
	 */
	public static function recent( $limit = 25 ) {
		$rows = self::query( [ 'limit' => max( 1, (int) $limit ) ] );
		$out  = [];
		foreach ( (array) $rows as $row ) {
			$out[] = [
				'id'         => (int) ( $row->id ?? 0 ),
				'booking_id' => (int) ( $row->id ?? 0 ),
				'status'     => (string) ( $row->status ?? '' ),
				'subject'    => (string) ( $row->subject ?? '' ),
				'starts_at'  => (string) ( $row->scheduled_at ?? '' ),
				'created_at' => (string) ( $row->created_at ?? '' ),
				'student_user_id' => (int) ( $row->student_user_id ?? 0 ),
				'tutor_user_id'   => (int) ( $row->tutor_user_id ?? 0 ),
				'duration_minutes' => (int) ( $row->duration_minutes ?? 0 ),
			];
		}
		return $out;
	}

	/**
	 * List bookings (alias of query with admin-friendly defaults).
	 *
	 * @param array<string, mixed> $args Query args (limit, status, …).
	 * @return array<int, array<string, mixed>>
	 */
	public static function list( $args = [] ) {
		$limit = isset( $args['limit'] ) ? (int) $args['limit'] : 25;
		return self::recent( $limit > 0 ? $limit : 25 );
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
	 * Decode booking meta JSON.
	 *
	 * @param object|int $booking Booking row or ID.
	 * @return array<string, mixed>
	 */
	public static function get_meta( $booking ) {
		if ( is_numeric( $booking ) ) {
			$booking = self::get( (int) $booking );
		}
		if ( ! $booking ) {
			return [];
		}
		$meta = json_decode( (string) ( $booking->meta ?? '' ), true );
		return is_array( $meta ) ? $meta : [];
	}

	/**
	 * Merge-patch booking meta.
	 *
	 * @param int                  $booking_id Booking ID.
	 * @param array<string, mixed> $patch      Meta patch (shallow merge).
	 * @return bool
	 */
	public static function update_meta( $booking_id, $patch ) {
		global $wpdb;
		$booking_id = (int) $booking_id;
		$booking    = self::get( $booking_id );
		if ( ! $booking ) {
			return false;
		}
		$meta = array_merge( self::get_meta( $booking ), is_array( $patch ) ? $patch : [] );
		$table = NGC_Database::table( 'bookings' );
		return false !== $wpdb->update(
			$table,
			[
				'meta'       => wp_json_encode( $meta ),
				'updated_at' => current_time( 'mysql', true ),
			],
			[ 'id' => $booking_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * @param int $booking_id Booking ID.
	 * @return array<string, mixed>
	 */
	public static function get_meeting_meta( $booking_id ) {
		$meta = self::get_meta( $booking_id );
		$m    = $meta['meeting'] ?? [];
		return is_array( $m ) ? $m : [];
	}

	/**
	 * @param int                  $booking_id Booking ID.
	 * @param array<string, mixed> $meeting    Meeting payload.
	 * @return bool
	 */
	public static function set_meeting_meta( $booking_id, $meeting ) {
		return self::update_meta( $booking_id, [ 'meeting' => $meeting ] );
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
		$peer   = get_user_by( 'id', $peer_id );
		$avatar = $peer ? get_avatar_url( $peer->ID ) : '';

		$session_id   = 0;
		$can_join     = false;
		$join_reason  = 'not_ready';
		$provider     = '';
		$meeting      = self::get_meeting_meta( (int) $booking->id );
		if ( is_array( $meeting ) ) {
			$provider = (string) ( $meeting['provider'] ?? '' );
		}

		// Prefer orchestrated session launch — never embed meeting URLs in dashboard HTML.
		if ( class_exists( 'NGC_Session_Orchestrator' ) && class_exists( 'NGC_Sessions' ) ) {
			$session = NGC_Sessions::get_by_booking( (int) $booking->id );
			if ( ! $session && class_exists( 'NGC_Meetings' ) && NGC_Meetings::can_join_status( $booking ) ) {
				$ensured = NGC_Session_Orchestrator::ensure_provisioned(
					[
						'booking_id' => (int) $booking->id,
						'source'     => 'dashboard_format',
					]
				);
				if ( ! is_wp_error( $ensured ) && is_array( $ensured ) ) {
					$session_id = (int) ( $ensured['session_id'] ?? 0 );
					$session    = $session_id ? NGC_Sessions::get( $session_id ) : null;
				}
			}
			if ( $session ) {
				$session_id = (int) $session->id;
				$window     = NGC_Session_Orchestrator::join_window_status( $session );
				$can_join   = ! empty( $window['allowed'] );
				$join_reason = (string) ( $window['reason'] ?? '' );
				$provider    = $provider ?: (string) ( $session->meeting_provider ?? '' );
			}
		} elseif ( class_exists( 'NGC_Meetings' ) && NGC_Meetings::can_join_status( $booking ) ) {
			// Legacy fallback when session module absent — status gate only; URL via REST join.
			$can_join    = true;
			$join_reason = 'legacy_status';
		}

		return [
			'id'              => (int) $booking->id,
			'bookingId'       => (int) $booking->id,
			'sessionId'       => $session_id,
			'peerName'        => $peer ? $peer->display_name : __( 'Unknown', 'nextgencompanion' ),
			'peerImage'       => $avatar,
			'subject'         => $booking->subject,
			'createdAt'       => $booking->scheduled_at ?: $booking->created_at,
			'status'          => $booking->status,
			'statusLabel'     => ucfirst( $booking->status ),
			'attendance'      => $booking->status,
			// Intentionally empty: clients must POST /sessions/{id}/launch (or bookings/{id}/join).
			'joinUrl'         => '',
			'join_url'        => '',
			'meetingUrl'      => '',
			'canJoin'         => $can_join && $session_id > 0,
			'joinReason'      => $join_reason,
			'joinVia'         => $session_id > 0 ? 'session_launch' : 'booking_join',
			'meetingProvider' => $provider,
		];
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		// Called via REST and payments.
	}
}
