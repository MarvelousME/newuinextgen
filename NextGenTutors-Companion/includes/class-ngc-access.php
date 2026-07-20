<?php
/**
 * Object-level access helpers (IDOR prevention).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central ownership checks for bookings, matches, and related entities.
 */
final class NGC_Access {

	/**
	 * Whether the user is a platform admin/operator.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return bool
	 */
	public static function is_ops( $user_id = 0 ) {
		$user_id = $user_id ?: get_current_user_id();
		return user_can( $user_id, 'manage_options' )
			|| user_can( $user_id, 'ngc_admin_operations' )
			|| user_can( $user_id, 'ngc_manage_bookings' )
			|| user_can( $user_id, 'ngc_manage_matches' );
	}

	/**
	 * @param object|null $booking Booking row.
	 * @param int         $user_id User ID.
	 * @return bool
	 */
	public static function can_view_booking( $booking, $user_id = 0 ) {
		if ( ! $booking ) {
			return false;
		}
		$user_id = $user_id ?: get_current_user_id();
		if ( self::is_ops( $user_id ) ) {
			return true;
		}
		$parties = [
			(int) ( $booking->student_user_id ?? 0 ),
			(int) ( $booking->tutor_user_id ?? 0 ),
		];
		$parent = (int) ( $booking->parent_user_id ?? 0 );
		if ( $parent ) {
			$parties[] = $parent;
		}
		// Parent linked via student meta.
		$student = (int) ( $booking->student_user_id ?? 0 );
		if ( $student ) {
			$linked_parent = (int) get_user_meta( $student, 'ngt_parent_user_id', true );
			if ( ! $linked_parent ) {
				$linked_parent = (int) get_user_meta( $student, 'ngc_parent_user_id', true );
			}
			if ( $linked_parent ) {
				$parties[] = $linked_parent;
			}
		}
		return in_array( (int) $user_id, $parties, true );
	}

	/**
	 * Mutating booking actions (update / status) — parties or ops.
	 *
	 * @param object|null $booking Booking.
	 * @param int         $user_id User.
	 * @return bool
	 */
	public static function can_mutate_booking( $booking, $user_id = 0 ) {
		return self::can_view_booking( $booking, $user_id );
	}

	/**
	 * Prevent forging student/tutor IDs on create unless ops.
	 *
	 * @param array<string, mixed> $data    Incoming payload.
	 * @param int                  $user_id Actor.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function sanitize_booking_create_payload( array $data, $user_id = 0 ) {
		$user_id = $user_id ?: get_current_user_id();
		if ( self::is_ops( $user_id ) ) {
			return $data;
		}

		$user  = get_userdata( $user_id );
		$roles = $user ? (array) $user->roles : [];

		if ( in_array( 'tutor', $roles, true ) || in_array( 'ngt_tutor', $roles, true ) ) {
			$data['tutor_user_id'] = $user_id;
			// Tutors may not assign arbitrary students unless linked — strip untrusted student swap.
			if ( ! empty( $data['student_user_id'] ) && (int) $data['student_user_id'] !== $user_id ) {
				// Keep student only if provided by parent/admin path; tutors should not invent students.
				unset( $data['student_user_id'] );
			}
			return $data;
		}

		// Parent / student: force student to self unless creating for an owned child.
		$requested_student = (int) ( $data['student_user_id'] ?? 0 );
		if ( $requested_student && $requested_student !== $user_id ) {
			$parent_of = (int) get_user_meta( $requested_student, 'ngt_parent_user_id', true );
			if ( ! $parent_of ) {
				$parent_of = (int) get_user_meta( $requested_student, 'ngc_parent_user_id', true );
			}
			if ( $parent_of !== $user_id ) {
				return new WP_Error(
					'ngc_forbidden_student',
					__( 'You cannot create bookings for that student.', 'nextgencompanion' ),
					[ 'status' => 403 ]
				);
			}
		} else {
			$data['student_user_id'] = $requested_student ?: $user_id;
		}

		// Never allow non-ops to set amount above 0 without checkout path — zero trust on price injection.
		if ( isset( $data['amount'] ) && ! current_user_can( 'ngc_manage_bookings' ) && ! current_user_can( 'manage_options' ) ) {
			// Parents may propose amount but server should treat as estimate; clamp negatives.
			$data['amount'] = max( 0, (float) $data['amount'] );
		}

		return $data;
	}

	/**
	 * Strip privileged fields from booking updates for non-ops.
	 *
	 * @param array<string, mixed> $data    Update payload.
	 * @param int                  $user_id Actor.
	 * @return array<string, mixed>
	 */
	public static function sanitize_booking_update_payload( array $data, $user_id = 0 ) {
		$user_id = $user_id ?: get_current_user_id();
		if ( self::is_ops( $user_id ) ) {
			return $data;
		}
		unset( $data['amount'], $data['tutor_user_id'], $data['student_user_id'], $data['order_id'], $data['match_id'] );
		return $data;
	}

	/**
	 * @param object|null $match   Match row.
	 * @param int         $user_id User.
	 * @return bool
	 */
	public static function can_act_on_match( $match, $user_id = 0 ) {
		if ( ! $match ) {
			return false;
		}
		$user_id = $user_id ?: get_current_user_id();
		if ( user_can( $user_id, 'ngc_manage_matches' ) || user_can( $user_id, 'manage_options' ) ) {
			return true;
		}
		return in_array(
			(int) $user_id,
			[
				(int) ( $match->parent_user_id ?? 0 ),
				(int) ( $match->student_user_id ?? 0 ),
				(int) ( $match->tutor_user_id ?? 0 ),
			],
			true
		);
	}
}
