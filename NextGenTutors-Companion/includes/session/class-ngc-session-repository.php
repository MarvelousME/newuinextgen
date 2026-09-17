<?php
/**
 * NGT session persistence — wp_ngc_sessions.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository for the orchestration-truth session table.
 */
class NGC_Session_Repository {

	/**
	 * @return string
	 */
	public static function table() {
		return NGC_Database::table( 'sessions' );
	}

	/**
	 * @param object|array|null $row Row.
	 * @return array<string, mixed>|null
	 */
	public static function to_array( $row ) {
		if ( ! $row ) {
			return null;
		}
		$data = is_array( $row ) ? $row : (array) $row;
		if ( ! empty( $data['meta'] ) && is_string( $data['meta'] ) ) {
			$decoded = json_decode( $data['meta'], true );
			$data['meta'] = is_array( $decoded ) ? $decoded : [];
		} elseif ( ! isset( $data['meta'] ) || ! is_array( $data['meta'] ) ) {
			$data['meta'] = [];
		}
		return $data;
	}

	/**
	 * @param int $id ID.
	 * @return array<string, mixed>|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return self::to_array( $row );
	}

	/**
	 * @param string $uuid UUID.
	 * @return array<string, mixed>|null
	 */
	public static function get_by_uuid( $uuid ) {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE session_uuid = %s", (string) $uuid ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return self::to_array( $row );
	}

	/**
	 * @param string $key Idempotency key.
	 * @return array<string, mixed>|null
	 */
	public static function get_by_idempotency_key( $key ) {
		global $wpdb;
		$key = (string) $key;
		if ( '' === $key ) {
			return null;
		}
		$table = self::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE idempotency_key = %s", $key ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return self::to_array( $row );
	}

	/**
	 * @param int $booking_id Booking ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_by_booking_id( $booking_id ) {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE booking_id = %d ORDER BY id DESC LIMIT 1", (int) $booking_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return self::to_array( $row );
	}

	/**
	 * @param int $order_id Order ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_by_order_id( $order_id ) {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d ORDER BY id DESC LIMIT 1", (int) $order_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return self::to_array( $row );
	}

	/**
	 * @param string $correlation_id Correlation.
	 * @return array<string, mixed>|null
	 */
	public static function get_by_correlation_id( $correlation_id ) {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE correlation_id = %s", (string) $correlation_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return self::to_array( $row );
	}

	/**
	 * Insert a session. Idempotent on idempotency_key.
	 *
	 * @param array<string, mixed> $data Data.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create( array $data ) {
		global $wpdb;
		$key = (string) ( $data['idempotency_key'] ?? '' );
		if ( $key ) {
			$existing = self::get_by_idempotency_key( $key );
			if ( $existing ) {
				return $existing;
			}
		}

		$now  = function_exists( 'current_time' ) ? current_time( 'mysql', true ) : gmdate( 'Y-m-d H:i:s' );
		$uuid = (string) ( $data['session_uuid'] ?? '' );
		if ( '' === $uuid ) {
			$uuid = class_exists( 'NGC_Uuid' ) ? NGC_Uuid::generate() : wp_generate_uuid4();
		}
		$corr = (string) ( $data['correlation_id'] ?? '' );
		if ( '' === $corr ) {
			$corr = NGC_Session_Correlation::generate();
		}
		if ( '' === $key ) {
			$key = NGC_Session_Correlation::idempotency_key( (int) ( $data['order_id'] ?? 0 ), (int) ( $data['booking_id'] ?? 0 ) );
		}

		$meta = $data['meta'] ?? [];
		if ( is_array( $meta ) ) {
			$meta = wp_json_encode( $meta );
		}

		$row = [
			'uuid'                    => $uuid,
			'session_uuid'            => $uuid,
			'correlation_id'          => $corr,
			'idempotency_key'         => $key,
			'booking_provider'        => sanitize_key( (string) ( $data['booking_provider'] ?? 'ngc' ) ),
			'booking_id'              => (int) ( $data['booking_id'] ?? 0 ),
			'order_id'                => (int) ( $data['order_id'] ?? 0 ),
			'order_item_id'           => (int) ( $data['order_item_id'] ?? 0 ),
			'product_id'              => (int) ( $data['product_id'] ?? 0 ),
			'student_user_id'         => (int) ( $data['student_user_id'] ?? 0 ),
			'parent_user_id'          => (int) ( $data['parent_user_id'] ?? 0 ),
			'tutor_user_id'           => (int) ( $data['tutor_user_id'] ?? 0 ),
			'subject_id'              => sanitize_title( (string) ( $data['subject_id'] ?? '' ) ),
			'subject_name'            => sanitize_text_field( (string) ( $data['subject_name'] ?? '' ) ),
			'masterstudy_course_id'   => (int) ( $data['masterstudy_course_id'] ?? 0 ),
			'masterstudy_lesson_id'   => (int) ( $data['masterstudy_lesson_id'] ?? 0 ),
			'meeting_provider'        => sanitize_key( (string) ( $data['meeting_provider'] ?? '' ) ),
			'meeting_id'              => sanitize_text_field( (string) ( $data['meeting_id'] ?? '' ) ),
			'meeting_url_reference'   => sanitize_text_field( (string) ( $data['meeting_url_reference'] ?? '' ) ),
			'scheduled_start'         => $data['scheduled_start'] ?? null,
			'scheduled_end'           => $data['scheduled_end'] ?? null,
			'timezone'                => sanitize_text_field( (string) ( $data['timezone'] ?? 'Africa/Johannesburg' ) ),
			'status'                  => sanitize_key( (string) ( $data['status'] ?? NGC_Session_States::DRAFT ) ),
			'payment_status'          => sanitize_key( (string) ( $data['payment_status'] ?? 'unpaid' ) ),
			'booking_status'          => sanitize_key( (string) ( $data['booking_status'] ?? '' ) ),
			'lesson_status'           => sanitize_key( (string) ( $data['lesson_status'] ?? '' ) ),
			'meeting_status'          => sanitize_key( (string) ( $data['meeting_status'] ?? '' ) ),
			'version'                 => 1,
			'meta'                    => $meta,
			'created_at'              => $now,
			'updated_at'              => $now,
		];

		$inserted = $wpdb->insert( self::table(), $row );
		if ( ! $inserted ) {
			$existing = self::get_by_idempotency_key( $key );
			if ( $existing ) {
				return $existing;
			}
			return new WP_Error( 'ngc_session_create_failed', __( 'Could not create session.', 'nextgencompanion' ), [ 'db' => $wpdb->last_error ] );
		}

		return self::get( (int) $wpdb->insert_id );
	}

	/**
	 * Optimistic update with version bump.
	 *
	 * @param int                  $id      ID.
	 * @param array<string, mixed> $patch   Patch.
	 * @param int                  $version Expected version (0 = skip).
	 * @return array<string, mixed>|WP_Error
	 */
	public static function update( $id, array $patch, $version = 0 ) {
		global $wpdb;
		$current = self::get( $id );
		if ( ! $current ) {
			return new WP_Error( 'ngc_session_not_found', __( 'Session not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
		}
		if ( $version > 0 && (int) $current['version'] !== (int) $version ) {
			return new WP_Error( 'ngc_session_conflict', __( 'Session was updated concurrently.', 'nextgencompanion' ), [ 'status' => 409 ] );
		}

		if ( isset( $patch['meta'] ) && is_array( $patch['meta'] ) ) {
			$patch['meta'] = wp_json_encode( array_merge( (array) $current['meta'], $patch['meta'] ) );
		}

		$patch['version']    = (int) $current['version'] + 1;
		$patch['updated_at'] = function_exists( 'current_time' ) ? current_time( 'mysql', true ) : gmdate( 'Y-m-d H:i:s' );

		$where = [ 'id' => (int) $id ];
		$ok    = $wpdb->update( self::table(), $patch, $where );
		if ( false === $ok ) {
			return new WP_Error( 'ngc_session_update_failed', __( 'Could not update session.', 'nextgencompanion' ) );
		}
		return self::get( (int) $id );
	}

	/**
	 * Validated status transition.
	 *
	 * @param int    $id     Session ID.
	 * @param string $to     Target status.
	 * @param array  $extra  Extra columns.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function transition( $id, $to, array $extra = [] ) {
		$current = self::get( $id );
		if ( ! $current ) {
			return new WP_Error( 'ngc_session_not_found', __( 'Session not found.', 'nextgencompanion' ), [ 'status' => 404 ] );
		}
		$from = (string) $current['status'];
		try {
			NGC_Session_State_Machine::assert( $from, $to );
		} catch ( NGC_Session_Transition_Exception $e ) {
			if ( class_exists( 'NGC_Audit' ) ) {
				NGC_Audit::log(
					'session_transition_rejected',
					'session',
					(int) $id,
					[ 'from' => $from, 'to' => $to ],
					0,
					[
						'result'         => 'error',
						'correlation_id' => (string) ( $current['correlation_id'] ?? '' ),
						'error_code'     => $e->get_error_code(),
					]
				);
			}
			return $e->to_wp_error();
		}

		$patch = array_merge( $extra, [ 'status' => $to ] );
		$now   = function_exists( 'current_time' ) ? current_time( 'mysql', true ) : gmdate( 'Y-m-d H:i:s' );
		if ( NGC_Session_States::IN_PROGRESS === $to && empty( $current['started_at'] ) ) {
			$patch['started_at'] = $now;
		}
		if ( NGC_Session_States::COMPLETED === $to ) {
			$patch['completed_at'] = $now;
			$patch['lesson_status'] = 'completed';
		}
		if ( NGC_Session_States::CANCELLED === $to ) {
			$patch['cancelled_at'] = $now;
		}
		return self::update( $id, $patch, (int) $current['version'] );
	}

	/**
	 * @param array<string, mixed> $args Query args.
	 * @return array<int, array<string, mixed>>
	 */
	public static function query( array $args = [] ) {
		global $wpdb;
		$table  = self::table();
		$where  = [ '1=1' ];
		$values = [];
		foreach ( [ 'student_user_id', 'parent_user_id', 'tutor_user_id', 'booking_id', 'order_id' ] as $col ) {
			if ( ! empty( $args[ $col ] ) ) {
				$where[]  = "{$col} = %d";
				$values[] = (int) $args[ $col ];
			}
		}
		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = sanitize_key( $args['status'] );
		}
		$limit = max( 1, min( 200, (int) ( $args['limit'] ?? 20 ) ) );
		$sql   = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . " ORDER BY scheduled_start DESC, id DESC LIMIT {$limit}";
		if ( $values ) {
			$sql = $wpdb->prepare( $sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$out  = [];
		foreach ( (array) $rows as $row ) {
			$out[] = self::to_array( $row );
		}
		return $out;
	}
}
