<?php
/**
 * Tutor payout calculation and scheduling.
 *
 * @package NextGenAutomationHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NGT_Hub_Payouts {

	const CRON_HOOK = 'ngt_monthly_payout_calculation';
	const PLATFORM_FEE_PERCENT = 15;

	public static function register_hooks(): void {
		add_action( self::CRON_HOOK, [ __CLASS__, 'run_monthly_calculation' ] );
		add_filter( 'cron_schedules', [ __CLASS__, 'add_monthly_schedule' ] );
	}

	/**
	 * @param array<string, array{interval: int, display: string}> $schedules Schedules.
	 * @return array<string, array{interval: int, display: string}>
	 */
	public static function add_monthly_schedule( array $schedules ): array {
		if ( ! isset( $schedules['monthly'] ) ) {
			$schedules['monthly'] = [
				'interval' => 30 * DAY_IN_SECONDS,
				'display'  => __( 'Once Monthly', 'nextgen-automation-hub' ),
			];
		}
		return $schedules;
	}

	public static function schedule_cron(): void {
		// FIN-001: Companion owns payout scheduling when present — avoid dual monthly calculation.
		if ( class_exists( 'NGT_Hub_Companion_Delegate', false ) && NGT_Hub_Companion_Delegate::companion_active() ) {
			self::unschedule_cron();
			NGT_Hub_Companion_Delegate::log(
				'info',
				'Skipped Hub payout cron — Companion owns payouts.',
				[ 'hook' => self::CRON_HOOK ]
			);
			return;
		}
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( strtotime( 'first day of next month 02:00:00' ), 'monthly', self::CRON_HOOK );
			if ( class_exists( 'NGT_Hub_Companion_Delegate', false ) ) {
				NGT_Hub_Companion_Delegate::log( 'info', 'Scheduled Hub payout cron (standalone mode).', [ 'hook' => self::CRON_HOOK ] );
			}
		}
	}

	public static function unschedule_cron(): void {
		$ts = wp_next_scheduled( self::CRON_HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::CRON_HOOK );
		}
	}

	public static function run_monthly_calculation(): void {
		if ( class_exists( 'NGT_Hub_Companion_Delegate', false ) && NGT_Hub_Companion_Delegate::companion_active() ) {
			NGT_Hub_Companion_Delegate::log( 'warning', 'Blocked Hub payout run — Companion is authoritative.' );
			return;
		}
		$period_end   = gmdate( 'Y-m-d', strtotime( 'last day of previous month' ) );
		$period_start = gmdate( 'Y-m-01', strtotime( 'first day of previous month' ) );

		$tutors = get_users( [ 'role__in' => [ 'ngt_tutor', 'tutor' ], 'fields' => 'ID' ] );
		foreach ( $tutors as $tutor_id ) {
			self::calculate_for_tutor( (int) $tutor_id, $period_start, $period_end );
		}

		NGT_Hub::fire_event( 'ngt.payouts.calculated', 'payouts', 0, 0, [
			'period_start' => $period_start,
			'period_end'   => $period_end,
			'tutor_count'  => count( $tutors ),
		] );
	}

	public static function calculate_for_tutor( int $tutor_user_id, string $period_start, string $period_end ): int {
		$lessons = self::completed_lessons_in_period( $tutor_user_id, $period_start, $period_end );
		$gross   = 0.0;
		$rate    = (float) get_user_meta( $tutor_user_id, 'ngt_hourly_rate', true );
		if ( $rate <= 0 ) {
			$rate = 225.0;
		}

		foreach ( $lessons as $lesson ) {
			$duration = (float) get_post_meta( $lesson->ID, 'ngt_lesson_duration', true );
			if ( $duration <= 0 ) {
				$duration = 60;
			}
			$gross += ( $rate * ( $duration / 60 ) );
		}

		$fee = round( $gross * ( self::PLATFORM_FEE_PERCENT / 100 ), 2 );
		$net = round( $gross - $fee, 2 );

		global $wpdb;
		$wpdb->insert(
			NGT_Hub_Database::table( 'payouts' ),
			[
				'tutor_user_id' => $tutor_user_id,
				'period_start'  => $period_start,
				'period_end'    => $period_end,
				'gross_amount'  => $gross,
				'platform_fee'  => $fee,
				'net_amount'    => $net,
				'lesson_count'  => count( $lessons ),
				'status'        => 'pending',
				'meta'          => wp_json_encode( [ 'hourly_rate' => $rate ] ),
			],
			[ '%d', '%s', '%s', '%f', '%f', '%f', '%d', '%s', '%s' ]
		);

		$payout_id = (int) $wpdb->insert_id;

		wp_insert_post(
			[
				'post_type'   => 'ngt_payout',
				'post_title'  => sprintf( 'Payout %s – %s', $period_start, $period_end ),
				'post_status' => 'publish',
				'meta_input'  => [
					'ngt_tutor_user_id' => $tutor_user_id,
					'ngt_gross'         => $gross,
					'ngt_net'           => $net,
					'ngt_status'        => 'pending',
				],
			]
		);

		NGT_Hub_Notifications::create(
			$tutor_user_id,
			'payout_ready',
			__( 'Monthly payout calculated', 'nextgen-automation-hub' ),
			sprintf(
				/* translators: 1: net amount, 2: period end */
				__( 'Your payout for period ending %2$s is R%1$s (net).', 'nextgen-automation-hub' ),
				number_format( $net, 2 ),
				$period_end
			)
		);

		return $payout_id;
	}

	/**
	 * @return array<int, WP_Post>
	 */
	private static function completed_lessons_in_period( int $tutor_user_id, string $start, string $end ): array {
		$q = new WP_Query(
			[
				'post_type'      => 'ngt_lesson',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'   => 'ngt_tutor_user_id',
						'value' => $tutor_user_id,
					],
					[
						'key'     => 'ngt_lesson_status',
						'value'   => 'completed',
						'compare' => '=',
					],
					[
						'key'     => 'ngt_lesson_date',
						'value'   => [ $start, $end ],
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					],
				],
			]
		);
		return $q->posts;
	}

	/**
	 * @return array<string, float|int>
	 */
	public static function tutor_summary( int $tutor_user_id ): array {
		global $wpdb;
		$table = NGT_Hub_Database::table( 'payouts' );
		return [
			'pending_payouts' => (float) $wpdb->get_var(
				$wpdb->prepare( "SELECT COALESCE(SUM(net_amount), 0) FROM {$table} WHERE tutor_user_id = %d AND status = 'pending'", $tutor_user_id )
			),
			'total_paid' => (float) $wpdb->get_var(
				$wpdb->prepare( "SELECT COALESCE(SUM(net_amount), 0) FROM {$table} WHERE tutor_user_id = %d AND status = 'paid'", $tutor_user_id )
			),
			'lesson_count' => NGT_Hub_Data_Model::count_posts(
				'ngt_lesson',
				[
					[
						'key'   => 'ngt_tutor_user_id',
						'value' => $tutor_user_id,
					],
					[
						'key'   => 'ngt_lesson_status',
						'value' => 'completed',
					],
				]
			),
		];
	}
}
