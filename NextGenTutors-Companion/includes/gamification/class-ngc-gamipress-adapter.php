<?php
/**
 * GamiPress integration adapter.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bridges internal gamification to GamiPress when active.
 */
class NGC_Gamipress_Adapter {

	/**
	 * @return bool
	 */
	public static function is_active() {
		return function_exists( 'gamipress_award_points_to_user' );
	}

	/**
	 * Map internal point types to GamiPress point types.
	 *
	 * @return array<string, string>
	 */
	public static function point_type_map() {
		return apply_filters(
			'ngc_gamipress_point_map',
			[
				'xp'                => 'xp',
				'tutor_points'      => 'tutor-points',
				'student_points'    => 'student-points',
				'parent_points'     => 'parent-points',
				'reputation_points' => 'reputation',
				'referral_points'   => 'referrals',
				'loyalty_points'    => 'loyalty',
			]
		);
	}

	/**
	 * Sync points to GamiPress.
	 *
	 * @param int    $user_id    User ID.
	 * @param string $point_type Internal point type.
	 * @param float  $amount     Amount.
	 * @param string $reason     Reason.
	 */
	public static function award_points( $user_id, $point_type, $amount, $reason = '' ) {
		if ( ! self::is_active() || $amount <= 0 ) {
			return;
		}
		$map  = self::point_type_map();
		$gp   = $map[ $point_type ] ?? $point_type;
		gamipress_award_points_to_user( (int) $user_id, (int) $amount, $gp, [ 'reason' => $reason ] );
	}

	/**
	 * Award GamiPress achievement by slug.
	 *
	 * @param int    $user_id User ID.
	 * @param string $slug    Achievement post slug.
	 */
	public static function award_achievement( $user_id, $slug ) {
		if ( ! self::is_active() || ! function_exists( 'gamipress_award_achievement_to_user' ) ) {
			return;
		}

		$type = self::achievement_post_type();
		$post = $type ? get_page_by_path( sanitize_title( $slug ), OBJECT, $type ) : null;
		if ( ! $post ) {
			self::ensure_achievements();
			$post = $type ? get_page_by_path( sanitize_title( $slug ), OBJECT, $type ) : null;
		}
		if ( $post ) {
			gamipress_award_achievement_to_user( (int) $user_id, (int) $post->ID );
		}
	}

	/**
	 * @param int    $user_id    User ID.
	 * @param string $point_type Point type.
	 * @return float
	 */
	public static function get_points( $user_id, $point_type = 'xp' ) {
		if ( ! self::is_active() || ! function_exists( 'gamipress_get_user_points' ) ) {
			return NGC_Scoring_Engine::get_balance( $user_id, $point_type );
		}
		$map = self::point_type_map();
		$gp  = $map[ $point_type ] ?? $point_type;
		return (float) gamipress_get_user_points( (int) $user_id, $gp );
	}

	/**
	 * Ensure GamiPress point types exist on admin init.
	 */
	public static function ensure_point_types() {
		if ( ! self::is_active() || ! post_type_exists( 'points-type' ) ) {
			return;
		}
		foreach ( self::point_type_map() as $internal => $slug ) {
			if ( get_page_by_path( $slug, OBJECT, 'points-type' ) ) {
				continue;
			}
			wp_insert_post(
				[
					'post_title'  => ucwords( str_replace( '-', ' ', $slug ) ),
					'post_name'   => $slug,
					'post_status' => 'publish',
					'post_type'   => 'points-type',
				]
			);
		}
	}

	/**
	 * Seed GamiPress badge achievements from the internal catalog.
	 *
	 * @return array{created:int,existing:int}
	 */
	public static function ensure_achievements() {
		$counts = [ 'created' => 0, 'existing' => 0 ];
		if ( ! self::is_active() || ! class_exists( 'NGC_Achievement_Engine' ) ) {
			return $counts;
		}

		$type = self::achievement_post_type();
		if ( ! $type ) {
			return $counts;
		}

		foreach ( NGC_Achievement_Engine::catalog() as $key => $def ) {
			$slug = sanitize_title( $key );
			if ( get_page_by_path( $slug, OBJECT, $type ) ) {
				++$counts['existing'];
				continue;
			}

			$post_id = wp_insert_post(
				[
					'post_title'  => sanitize_text_field( (string) ( $def['title'] ?? $key ) ),
					'post_name'   => $slug,
					'post_status' => 'publish',
					'post_type'   => $type,
					'meta_input'  => [
						'_gamipress_points' => (int) ( $def['points'] ?? 0 ),
					],
				]
			);

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				++$counts['created'];
			}
		}

		return $counts;
	}

	/**
	 * GamiPress achievement post type (badge by default).
	 *
	 * @return string
	 */
	public static function achievement_post_type() {
		if ( post_type_exists( 'badge' ) ) {
			return 'badge';
		}
		if ( post_type_exists( 'achievement-type' ) ) {
			return 'achievement-type';
		}
		foreach ( [ 'step', 'rank' ] as $candidate ) {
			if ( post_type_exists( $candidate ) ) {
				return $candidate;
			}
		}
		return '';
	}
}
