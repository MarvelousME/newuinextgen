<?php
/**
 * Gamification points and badges engine.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Gamification {

	/** @var array<string, array{points: int, badge?: string, label?: string}> */
	private static $rules = [
		'ngt.lesson.completed'     => [ 'points' => 25, 'badge' => 'lesson_streak', 'label' => 'Lesson Champion' ],
		'ngt.tutor.approved'       => [ 'points' => 100, 'badge' => 'verified_tutor', 'label' => 'Verified Tutor' ],
		'ngt.find_tutor.submitted' => [ 'points' => 10 ],
		'ngt.tutor_application.submitted' => [ 'points' => 15 ],
		'ngt.match.created'        => [ 'points' => 20, 'badge' => 'first_match', 'label' => 'First Match' ],
		'wp.user_registered'       => [ 'points' => 5, 'badge' => 'welcome', 'label' => 'Welcome Aboard' ],
	];

	public static function register_hooks(): void {
		add_action( 'ngt_automation_event_fired', [ __CLASS__, 'on_event' ], 15, 2 );
	}

	/**
	 * @param array<string, mixed> $payload Event payload.
	 */
	public static function on_event( string $event_key, array $payload ): void {
		if ( class_exists( 'NGC_Gamification' ) && method_exists( 'NGC_Gamification', 'award_for_event' ) ) {
			NGC_Gamification::award_for_event( $event_key, $payload );
			return;
		}

		if ( ! isset( self::$rules[ $event_key ] ) ) {
			return;
		}

		$user_id = (int) ( $payload['user_id'] ?? $payload['student_user_id'] ?? $payload['parent_user_id'] ?? $payload['tutor_user_id'] ?? 0 );
		if ( ! $user_id ) {
			return;
		}

		$rule = self::$rules[ $event_key ];
		self::award_points( $user_id, (int) $rule['points'], $event_key );

		if ( ! empty( $rule['badge'] ) ) {
			self::award_badge( $user_id, $rule['badge'], $rule['label'] ?? $rule['badge'] );
		}
	}

	public static function award_points( int $user_id, int $points, string $reason ): void {
		global $wpdb;
		$wpdb->insert(
			NGT_Hub_Database::table( 'gamification' ),
			[
				'user_id'   => $user_id,
				'points'    => $points,
				'reason'    => sanitize_text_field( $reason ),
				'event_key' => sanitize_key( $reason ),
			],
			[ '%d', '%d', '%s', '%s' ]
		);
		update_user_meta( $user_id, 'ngt_total_points', self::total_points( $user_id ) );
	}

	public static function award_badge( int $user_id, string $badge_key, string $label ): void {
		global $wpdb;
		$table = NGT_Hub_Database::table( 'badges' );
		$exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND badge_key = %s LIMIT 1",
				$user_id,
				$badge_key
			)
		);
		if ( $exists ) {
			return;
		}
		$wpdb->insert(
			$table,
			[
				'user_id'   => $user_id,
				'badge_key' => sanitize_key( $badge_key ),
				'label'     => sanitize_text_field( $label ),
			],
			[ '%d', '%s', '%s' ]
		);
		NGT_Hub_Notifications::create(
			$user_id,
			'badge_earned',
			__( 'Badge earned!', 'nextgen-automation-hub' ),
			sprintf( __( 'You earned the "%s" badge.', 'nextgen-automation-hub' ), $label )
		);
	}

	public static function total_points( int $user_id ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(points), 0) FROM " . NGT_Hub_Database::table( 'gamification' ) . ' WHERE user_id = %d',
				$user_id
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function user_summary( int $user_id ): array {
		if ( class_exists( 'NGC_Gamification' ) && method_exists( 'NGC_Gamification', 'user_summary' ) ) {
			return NGC_Gamification::user_summary( $user_id );
		}

		global $wpdb;
		$badges = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT badge_key, label, earned_at FROM " . NGT_Hub_Database::table( 'badges' ) . ' WHERE user_id = %d ORDER BY earned_at DESC',
				$user_id
			),
			ARRAY_A
		);

		return [
			'points'  => self::total_points( $user_id ),
			'badges'  => is_array( $badges ) ? $badges : [],
			'level'   => (int) floor( self::total_points( $user_id ) / 100 ) + 1,
		];
	}
}
