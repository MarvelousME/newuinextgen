<?php
/**
 * Session / review milestone badges — aligned to System Triggers GamiPress map.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Evaluates cumulative milestones after scoring events.
 */
final class NGC_Gamification_Milestones {

	/**
	 * @param int                  $user_id   User.
	 * @param string               $event_key Event.
	 * @param array<string, mixed> $context   Context.
	 */
	public static function evaluate( $user_id, $event_key, $context = [] ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return;
		}

		if ( in_array( $event_key, [ 'session_completed', 'lesson_completion', 'booking_completion' ], true ) ) {
			self::student_session_milestones( $user_id );
		}

		if ( 'session_completed_tutor' === $event_key ) {
			self::tutor_session_milestones( $user_id, is_array( $context ) ? $context : [] );
		}

		if ( 'review_submission' === $event_key || 'tutor_rating' === $event_key ) {
			$tutor_id = (int) ( $context['tutor_user_id'] ?? $context['tutor_id'] ?? $user_id );
			self::tutor_rating_milestones( $tutor_id );
		}
	}

	/**
	 * Student: 5 / 25 / 100 completed sessions.
	 *
	 * @param int $user_id Student.
	 */
	private static function student_session_milestones( $user_id ) {
		$count = (int) get_user_meta( $user_id, 'ngt_completed_sessions', true );
		++$count;
		update_user_meta( $user_id, 'ngt_completed_sessions', $count );

		if ( $count >= 5 ) {
			NGC_Achievement_Engine::award( $user_id, 'quick_learner', [ 'sessions' => $count ] );
		}
		if ( $count >= 25 ) {
			NGC_Achievement_Engine::award( $user_id, 'scholar', [ 'sessions' => $count ] );
		}
		if ( $count >= 100 ) {
			NGC_Achievement_Engine::award( $user_id, 'master', [ 'sessions' => $count ] );
		}
	}

	/**
	 * Tutor session counts + specialist (30+ in one subject).
	 *
	 * @param int                  $tutor_id Tutor.
	 * @param array<string, mixed> $context  Context.
	 */
	private static function tutor_session_milestones( $tutor_id, $context ) {
		$count = (int) get_user_meta( $tutor_id, 'ngt_tutor_completed_sessions', true );
		++$count;
		update_user_meta( $tutor_id, 'ngt_tutor_completed_sessions', $count );

		if ( $count >= 10 ) {
			NGC_Achievement_Engine::award( $tutor_id, 'tutor_10_sessions', [ 'sessions' => $count ] );
		}
		if ( $count >= 50 ) {
			NGC_Achievement_Engine::award( $tutor_id, 'tutor_50_sessions', [ 'sessions' => $count ] );
		}

		$subject = sanitize_title( (string) ( $context['subject'] ?? '' ) );
		if ( $subject ) {
			$key   = 'ngt_tutor_subject_sessions_' . $subject;
			$sc    = (int) get_user_meta( $tutor_id, $key, true );
			++$sc;
			update_user_meta( $tutor_id, $key, $sc );
			if ( $sc >= 30 ) {
				NGC_Achievement_Engine::award( $tutor_id, 'specialist', [ 'subject' => $subject, 'sessions' => $sc ] );
			}
		}

		$earnings = (float) get_user_meta( $tutor_id, 'ngt_lifetime_earnings', true );
		if ( $earnings >= 100000 ) {
			NGC_Achievement_Engine::award( $tutor_id, 'elite_earner', [ 'earnings' => $earnings ] );
		}
	}

	/**
	 * Popular = 50+ reviews (RESOLVED RC-01). Highly Rated = avg ≥ 4.8 and 20+ reviews.
	 *
	 * @param int $tutor_id Tutor.
	 */
	public static function tutor_rating_milestones( $tutor_id ) {
		$tutor_id = (int) $tutor_id;
		if ( $tutor_id <= 0 ) {
			return;
		}

		$avg   = (float) get_user_meta( $tutor_id, 'tutor_average_rating', true );
		$count = (int) get_user_meta( $tutor_id, 'tutor_review_count', true );
		if ( $count <= 0 && class_exists( 'NGC_Reviews' ) && method_exists( 'NGC_Reviews', 'stats_for_tutor' ) ) {
			$stats = NGC_Reviews::stats_for_tutor( $tutor_id );
			$avg   = (float) ( $stats['average'] ?? $avg );
			$count = (int) ( $stats['count'] ?? $count );
		}

		$popular_threshold = class_exists( 'NGC_Business_Rules' )
			? (int) NGC_Business_Rules::get( 'ngt.tutor.popular_review_threshold' )
			: 50;
		$min_rating = class_exists( 'NGC_Business_Rules' )
			? (float) NGC_Business_Rules::get( 'ngt.tutor.minimum_rating' )
			: 4.0;

		if ( $count >= $popular_threshold ) {
			NGC_Achievement_Engine::award( $tutor_id, 'popular', [ 'reviews' => $count ] );
			update_user_meta( $tutor_id, 'ngt_popular_tutor', 1 );
		}

		if ( $avg >= 4.8 && $count >= 20 ) {
			NGC_Achievement_Engine::award( $tutor_id, 'highly_rated', [ 'average' => $avg, 'reviews' => $count ] );
			update_user_meta( $tutor_id, 'ngt_highly_rated', 1 );
			update_user_meta( $tutor_id, 'ngt_featured_search_eligible', 1 );
		}

		if ( $avg > 0 && $avg < $min_rating && $count >= 5 ) {
			do_action(
				'ngc_tutor_quality_review_required',
				$tutor_id,
				[
					'average' => $avg,
					'count'   => $count,
					'minimum' => $min_rating,
				]
			);
		}
	}
}
