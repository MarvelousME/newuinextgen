<?php
/**
 * Tutor matching engine.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Match creation, scoring, accept/reject.
 */
class NGC_Matching {

	/**
	 * @param array<string, mixed> $data Match request data.
	 * @return int|WP_Error Match ID.
	 */
	public static function create_from_find_tutor( $data ) {
		global $wpdb;
		$table = NGC_Database::table( 'matches' );

		$student_id = isset( $data['student_user_id'] ) ? (int) $data['student_user_id'] : 0;
		$parent_id  = isset( $data['parent_user_id'] ) ? (int) $data['parent_user_id'] : get_current_user_id();
		$subject    = sanitize_text_field( $data['subject'] ?? '' );
		$grade      = sanitize_text_field( $data['grade'] ?? '' );
		$province   = sanitize_text_field( $data['province'] ?? $data['area'] ?? '' );
		$notes      = sanitize_textarea_field( $data['notes'] ?? '' );

		$best = self::score_tutors( $subject, $grade, $province );
		$tutor_id = ! empty( $best[0]['user_id'] ) ? (int) $best[0]['user_id'] : 0;
		$score    = ! empty( $best[0]['score'] ) ? (float) $best[0]['score'] : 0.0;

		$inserted = $wpdb->insert(
			$table,
			[
				'uuid'            => class_exists( 'NGC_Uuid' ) ? NGC_Uuid::generate() : wp_generate_uuid4(),
				'student_user_id' => $student_id,
				'parent_user_id'  => $parent_id,
				'tutor_user_id'   => $tutor_id,
				'subject'         => $subject,
				'grade'           => $grade,
				'province'        => $province,
				'status'          => $tutor_id ? 'proposed' : 'pending',
				'score'           => $score,
				'notes'           => $notes,
				'meta'            => wp_json_encode( [ 'candidates' => array_slice( $best, 0, 5 ) ] ),
				'created_at'      => current_time( 'mysql', true ),
				'updated_at'      => current_time( 'mysql', true ),
			]
		);

		if ( ! $inserted ) {
			return new WP_Error( 'ngc_match_create_failed', __( 'Could not create match.', 'nextgencompanion' ) );
		}

		$match_id = (int) $wpdb->insert_id;
		NGC_Audit::log( 'match_created', 'match', $match_id, $data, $parent_id );

		/**
		 * Fires after the authoritative match request is persisted.
		 *
		 * External integrations must subscribe asynchronously and must not
		 * block or alter the deterministic matching transaction.
		 *
		 * @param int                  $match_id Match request ID.
		 * @param array<string, mixed> $context  Persisted request context and eligible candidates.
		 */
		do_action(
			'ngc_match_requested',
			$match_id,
			[
				'match_id'        => $match_id,
				'student_user_id' => $student_id,
				'parent_user_id'  => $parent_id,
				'subject'         => $subject,
				'grade'           => $grade,
				'province'        => $province,
				'candidates'      => array_slice( $best, 0, 5 ),
			]
		);

		NGC_Workflows::dispatch(
			'match.proposed',
			[
				'match_id'        => (string) $match_id,
				'student_user_id' => (string) $student_id,
				'parent_user_id'  => (string) $parent_id,
				'tutor_user_id'   => (string) $tutor_id,
				'subject'         => $subject,
				'score'           => (string) $score,
				'status'          => $tutor_id ? 'proposed' : 'pending',
			]
		);

		self::maybe_auto_accept( $match_id, $parent_id, $score, $tutor_id );

		return $match_id;
	}

	/**
	 * Manually assign a tutor to a match.
	 *
	 * @param int $match_id  Match ID.
	 * @param int $tutor_id  Tutor user ID.
	 * @param int $actor_id  Actor user ID.
	 * @return true|WP_Error
	 */
	public static function manual_assign( $match_id, $tutor_id, $actor_id = 0 ) {
		global $wpdb;
		$match = self::get( $match_id );
		if ( ! $match ) {
			return new WP_Error( 'ngc_match_not_found', __( 'Match not found.', 'nextgencompanion' ) );
		}
		if ( ! user_can( $actor_id ?: get_current_user_id(), 'ngc_manage_matches' ) && ! user_can( $actor_id ?: get_current_user_id(), 'manage_options' ) ) {
			return new WP_Error( 'ngc_forbidden', __( 'Insufficient permissions.', 'nextgencompanion' ), [ 'status' => 403 ] );
		}
		$tutor = get_user_by( 'id', $tutor_id );
		if ( ! $tutor || ! in_array( 'tutor', (array) $tutor->roles, true ) ) {
			return new WP_Error( 'ngc_invalid_tutor', __( 'Invalid tutor.', 'nextgencompanion' ) );
		}

		$table = NGC_Database::table( 'matches' );
		$wpdb->update(
			$table,
			[
				'tutor_user_id' => $tutor_id,
				'status'        => 'proposed',
				'updated_at'    => current_time( 'mysql', true ),
			],
			[ 'id' => $match_id ],
			[ '%d', '%s', '%s' ],
			[ '%d' ]
		);

		NGC_Audit::log( 'match_assigned', 'match', $match_id, [ 'tutor_user_id' => $tutor_id ], $actor_id );
		return true;
	}

	/**
	 * Accept a proposed match.
	 *
	 * @param int $match_id Match ID.
	 * @param int $user_id  Accepting user.
	 * @return true|WP_Error
	 */
	public static function accept( $match_id, $user_id = 0 ) {
		global $wpdb;
		$user_id = $user_id ?: get_current_user_id();
		$match   = self::get( $match_id );
		if ( ! $match ) {
			return new WP_Error( 'ngc_match_not_found', __( 'Match not found.', 'nextgencompanion' ) );
		}
		if ( ! self::user_can_act_on_match( $match, $user_id ) ) {
			return new WP_Error( 'ngc_forbidden', __( 'Cannot accept this match.', 'nextgencompanion' ), [ 'status' => 403 ] );
		}

		$table = NGC_Database::table( 'matches' );
		$wpdb->update(
			$table,
			[ 'status' => 'accepted', 'updated_at' => current_time( 'mysql', true ) ],
			[ 'id' => $match_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		NGC_Audit::log( 'match_accepted', 'match', $match_id, [], $user_id );
		do_action( 'ngc_match_accepted', (int) $match_id );
		NGC_Workflows::dispatch(
			'match.accepted',
			[
				'match_id'        => (string) $match_id,
				'student_user_id' => (string) $match->student_user_id,
				'tutor_user_id'   => (string) $match->tutor_user_id,
				'subject'         => $match->subject,
			]
		);

		NGC_Bookings::create(
			[
				'match_id'        => $match_id,
				'student_user_id' => (int) $match->student_user_id,
				'tutor_user_id'   => (int) $match->tutor_user_id,
				'subject'         => $match->subject,
				'notes'           => __( 'Auto-created from accepted match.', 'nextgencompanion' ),
			]
		);

		return true;
	}

	/**
	 * Reject a match.
	 *
	 * @param int    $match_id Match ID.
	 * @param int    $user_id  User ID.
	 * @param string $reason   Optional reason.
	 * @return true|WP_Error
	 */
	public static function reject( $match_id, $user_id = 0, $reason = '' ) {
		global $wpdb;
		$user_id = $user_id ?: get_current_user_id();
		$match   = self::get( $match_id );
		if ( ! $match ) {
			return new WP_Error( 'ngc_match_not_found', __( 'Match not found.', 'nextgencompanion' ) );
		}
		if ( ! self::user_can_act_on_match( $match, $user_id ) ) {
			return new WP_Error( 'ngc_forbidden', __( 'Cannot reject this match.', 'nextgencompanion' ), [ 'status' => 403 ] );
		}

		$table = NGC_Database::table( 'matches' );
		$wpdb->update(
			$table,
			[
				'status'     => 'rejected',
				'notes'      => $reason ? $reason : $match->notes,
				'updated_at' => current_time( 'mysql', true ),
			],
			[ 'id' => $match_id ],
			[ '%s', '%s', '%s' ],
			[ '%d' ]
		);

		NGC_Audit::log( 'match_rejected', 'match', $match_id, [ 'reason' => $reason ], $user_id );
		return true;
	}

	/**
	 * Score available tutors for a subject/grade/province.
	 *
	 * @param string $subject  Subject.
	 * @param string $grade    Grade.
	 * @param string $province Province.
	 * @return array<int, array<string, mixed>>
	 */
	public static function score_tutors( $subject, $grade, $province ) {
		if ( class_exists( 'NGC_Tutor_Cpt_Source' ) ) {
			return NGC_Tutor_Cpt_Source::score_for_legacy_matching( $subject, $grade, $province );
		}

		$tutors = get_users(
			[
				'role'   => 'tutor',
				'number' => 50,
				'fields' => 'all',
			]
		);

		$scored = [];
		foreach ( $tutors as $tutor ) {
			if ( ! get_user_meta( $tutor->ID, 'ngc_tutor_verified', true ) && ! get_user_meta( $tutor->ID, 'ngt_tutor_verified', true ) ) {
				continue;
			}
			$score = 0.0;
			$subjects = array_map( 'strtolower', (array) get_user_meta( $tutor->ID, 'ngc_subjects', true ) );
			if ( empty( $subjects ) ) {
				$subjects = array_map( 'trim', explode( ',', strtolower( (string) get_user_meta( $tutor->ID, 'subjects', true ) ) ) );
			}
			if ( $subject && in_array( strtolower( $subject ), $subjects, true ) ) {
				$score += 40;
			} elseif ( $subject && self::fuzzy_contains( $subjects, $subject ) ) {
				$score += 25;
			}
			$tutor_grade = strtolower( (string) get_user_meta( $tutor->ID, 'ngc_grade', true ) );
			if ( $grade && ( $tutor_grade === strtolower( $grade ) || '' === $tutor_grade ) ) {
				$score += 20;
			}
			$tutor_province = strtolower( (string) get_user_meta( $tutor->ID, 'ngc_province', true ) );
			if ( $province && ( $tutor_province === strtolower( $province ) || '' === $tutor_province ) ) {
				$score += 20;
			}
			$avg_rating = NGC_Reviews::average_for_tutor( $tutor->ID );
			if ( $avg_rating > 0 ) {
				$score += min( 20, $avg_rating * 4 );
			}
			$scored[] = [
				'user_id' => $tutor->ID,
				'name'    => $tutor->display_name,
				'score'   => round( $score, 2 ),
			];
		}

		usort(
			$scored,
			static function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		return $scored;
	}

	/**
	 * @param int $match_id Match ID.
	 * @return object|null
	 */
	public static function get( $match_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'matches' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $match_id ) );
	}

	/**
	 * @param array<string, mixed> $args Query args.
	 * @return array<int, object>
	 */
	public static function query( $args = [] ) {
		global $wpdb;
		$table  = NGC_Database::table( 'matches' );
		$where  = [ '1=1' ];
		$values = [];

		if ( ! empty( $args['parent_user_id'] ) ) {
			$where[]  = 'parent_user_id = %d';
			$values[] = (int) $args['parent_user_id'];
		}
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
			$values[] = sanitize_key( (string) $args['status'] );
		}

		$limit = min( 100, max( 1, (int) ( $args['limit'] ?? 20 ) ) );
		$sql   = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT %d';
		$values[] = $limit;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
	}

	/**
	 * @param object $match   Row.
	 * @param int    $user_id User ID.
	 * @return bool
	 */
	private static function user_can_act_on_match( $match, $user_id ) {
		if ( user_can( $user_id, 'ngc_manage_matches' ) || user_can( $user_id, 'manage_options' ) ) {
			return true;
		}
		return in_array( (int) $user_id, [ (int) $match->parent_user_id, (int) $match->student_user_id, (int) $match->tutor_user_id ], true );
	}

	/**
	 * @param string[] $haystack Values.
	 * @param string   $needle   Search.
	 * @return bool
	 */
	private static function fuzzy_contains( $haystack, $needle ) {
		$needle = strtolower( $needle );
		foreach ( $haystack as $item ) {
			if ( false !== strpos( $item, $needle ) || false !== strpos( $needle, $item ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether WF-09 automated accept is enabled.
	 *
	 * @return bool
	 */
	public static function is_auto_assign_enabled() {
		$default = class_exists( 'NGC_Platform_Demo' ) && NGC_Platform_Demo::is_enabled();
		return (bool) apply_filters( 'ngc_auto_assign_enabled', (bool) get_option( 'ngc_auto_assign_enabled', $default ) );
	}

	/**
	 * Minimum match score required for auto-accept.
	 *
	 * @return float
	 */
	public static function min_auto_score() {
		return (float) apply_filters( 'ngc_auto_assign_min_score', 50.0 );
	}

	/**
	 * WF-09 — auto-accept proposed match when policy + score threshold met.
	 *
	 * @param int   $match_id  Match ID.
	 * @param int   $actor_id  Parent or student user ID.
	 * @param float $score     Match score.
	 * @param int   $tutor_id  Proposed tutor user ID.
	 * @return bool True when match was auto-accepted.
	 */
	public static function maybe_auto_accept( $match_id, $actor_id, $score, $tutor_id ) {
		$match_id = (int) $match_id;
		$tutor_id = (int) $tutor_id;
		$actor_id = (int) $actor_id;

		if ( ! $tutor_id || ! self::is_auto_assign_enabled() ) {
			return false;
		}
		if ( $score < self::min_auto_score() ) {
			return false;
		}

		$result = self::accept( $match_id, $actor_id );
		if ( is_wp_error( $result ) ) {
			return false;
		}

		NGC_Workflows::dispatch(
			'match.auto_assigned',
			[
				'match_id'      => (string) $match_id,
				'tutor_user_id' => (string) $tutor_id,
				'score'         => (string) $score,
			]
		);

		return true;
	}

	/**
	 * Hook registration.
	 */
	public static function init() {
		// REST and forms call static methods directly.
	}
}
