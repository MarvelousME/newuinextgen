<?php
/**
 * Tutor-student matching engine.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Matching {

	public static function register_hooks(): void {
		add_action( 'ngt_find_tutor_matched', [ __CLASS__, 'on_intake' ], 10, 1 );
	}

	/**
	 * @param array<string, mixed> $data Intake data.
	 * @return int|WP_Error Match ID.
	 */
	public static function create_from_intake( array $data ) {
		if ( class_exists( 'NGC_Matching' ) && method_exists( 'NGC_Matching', 'create_from_find_tutor' ) ) {
			return NGC_Matching::create_from_find_tutor( $data );
		}

		if ( class_exists( 'NGT_Hub_Companion_Delegate', false ) && NGT_Hub_Companion_Delegate::domain_writes_blocked() ) {
			NGT_Hub_Companion_Delegate::log(
				'warning',
				'Blocked Hub-local match write — Companion owns matching SoR.',
				[ 'has_parent' => ! empty( $data['parent_user_id'] ) ]
			);
			return new WP_Error(
				'ngt_match_companion_authority',
				__( 'Matching is owned by NextGen Companion. Hub local write blocked.', 'nextgen-automation-hub' )
			);
		}

		global $wpdb;
		$table = NGT_Hub_Database::table( 'matches' );

		$subject = sanitize_text_field( $data['subject'] ?? '' );
		$grade   = sanitize_text_field( $data['grade'] ?? '' );
		$area    = sanitize_text_field( $data['area'] ?? $data['province'] ?? '' );
		$parent  = (int) ( $data['parent_user_id'] ?? get_current_user_id() );

		$candidates = self::score_tutors( $subject, $grade, $area );
		$tutor_id   = ! empty( $candidates[0]['user_id'] ) ? (int) $candidates[0]['user_id'] : 0;
		$score      = ! empty( $candidates[0]['score'] ) ? (float) $candidates[0]['score'] : 0.0;

		$inserted = $wpdb->insert(
			$table,
			[
				'parent_user_id'  => $parent,
				'student_user_id' => (int) ( $data['student_user_id'] ?? 0 ),
				'tutor_user_id'   => $tutor_id,
				'subject'         => $subject,
				'grade'           => $grade,
				'area'            => $area,
				'status'          => $tutor_id ? 'proposed' : 'pending',
				'score'           => $score,
				'notes'           => sanitize_textarea_field( $data['notes'] ?? '' ),
				'meta'            => wp_json_encode( [ 'candidates' => array_slice( $candidates, 0, 5 ) ] ),
				'created_at'      => current_time( 'mysql', true ),
				'updated_at'      => current_time( 'mysql', true ),
			],
			[ '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s' ]
		);

		if ( ! $inserted ) {
			return new WP_Error( 'ngt_match_failed', __( 'Could not create match.', 'nextgen-automation-hub' ) );
		}

		$match_id = (int) $wpdb->insert_id;

		NGT_Hub::fire_event(
			'ngt.match.created',
			'matching',
			$parent,
			$match_id,
			[
				'match_id'      => $match_id,
				'tutor_user_id' => $tutor_id,
				'subject'       => $subject,
				'score'         => $score,
				'status'        => $tutor_id ? 'proposed' : 'pending',
			]
		);

		if ( $tutor_id ) {
			NGT_Hub_Notifications::create(
				$tutor_id,
				'match_proposed',
				__( 'New student match proposed', 'nextgen-automation-hub' ),
				sprintf(
					/* translators: 1: subject, 2: grade */
					__( 'You have been matched for %1$s (%2$s). Review and accept in your dashboard.', 'nextgen-automation-hub' ),
					$subject,
					$grade
				)
			);
		}

		return $match_id;
	}

	/**
	 * @param array<string, mixed> $data Intake data.
	 */
	public static function on_intake( array $data ): void {
		self::create_from_intake( $data );
	}

	/**
	 * @return array<int, array{user_id: int, score: float, name: string}>
	 */
	public static function score_tutors( string $subject, string $grade, string $area ): array {
		$tutors = get_users(
			[
				'role__in' => [ 'ngt_tutor', 'tutor' ],
				'number'   => 50,
			]
		);

		$scored = [];
		foreach ( $tutors as $tutor ) {
			$score = 0.0;
			$subjects = get_user_meta( $tutor->ID, 'ngt_subjects', true );
			if ( is_string( $subjects ) ) {
				$subjects = array_map( 'trim', explode( ',', $subjects ) );
			}
			if ( is_array( $subjects ) && $subject ) {
				foreach ( $subjects as $s ) {
					if ( stripos( $s, $subject ) !== false || stripos( $subject, $s ) !== false ) {
						$score += 40;
						break;
					}
				}
			}

			$tutor_area = (string) get_user_meta( $tutor->ID, 'ngt_area', true );
			if ( $area && $tutor_area && stripos( $tutor_area, $area ) !== false ) {
				$score += 30;
			}

			$rating = (float) get_user_meta( $tutor->ID, 'ngt_rating', true );
			$score += min( 20, $rating * 4 );

			$approved = get_user_meta( $tutor->ID, 'ngt_tutor_approved', true );
			if ( $approved ) {
				$score += 10;
			}

			$scored[] = [
				'user_id' => $tutor->ID,
				'score'   => round( $score, 2 ),
				'name'    => $tutor->display_name,
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

	public static function pending_count(): int {
		if ( class_exists( 'NGC_Database' ) ) {
			global $wpdb;
			return (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM " . NGC_Database::table( 'matches' ) . " WHERE status = 'pending'"
			);
		}
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM " . NGT_Hub_Database::table( 'matches' ) . " WHERE status = 'pending'"
		);
	}
}
