<?php
/**
 * WF-05 — scheduled monthly and bi-weekly tutor payouts.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Monthly + bi-weekly payout batch processor.
 */
class NGC_Payout_Scheduler {

	const CRON_HOOK         = 'ngc_monthly_payout_batch';
	const CRON_HOOK_BIWEEKLY = 'ngc_biweekly_payout_batch';

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_filter( 'cron_schedules', [ __CLASS__, 'add_cron_schedules' ] );
		add_action( 'init', [ __CLASS__, 'ensure_cron' ] );
		add_action( self::CRON_HOOK, [ __CLASS__, 'run_batch' ] );
		add_action( self::CRON_HOOK_BIWEEKLY, [ __CLASS__, 'run_batch' ] );
	}

	/**
	 * @param array<string, mixed> $schedules Schedules.
	 * @return array<string, mixed>
	 */
	public static function add_cron_schedules( $schedules ) {
		if ( empty( $schedules['ngc_biweekly'] ) ) {
			$schedules['ngc_biweekly'] = [
				'interval' => 14 * DAY_IN_SECONDS,
				'display'  => __( 'Every two weeks (NextGen payouts)', 'nextgencompanion' ),
			];
		}
		return $schedules;
	}

	/**
	 * Schedule payout crons if not already scheduled.
	 */
	public static function ensure_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			$first = strtotime( 'first day of next month 02:00:00' );
			wp_schedule_event( $first ?: time() + DAY_IN_SECONDS, 'ngc_monthly', self::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( self::CRON_HOOK_BIWEEKLY ) ) {
			$next = strtotime( 'next Monday 03:00:00' );
			wp_schedule_event( $next ?: time() + WEEK_IN_SECONDS, 'ngc_biweekly', self::CRON_HOOK_BIWEEKLY );
		}
	}

	/**
	 * Process pending earnings for all tutors.
	 *
	 * @return array{processed:int,total:float,errors:int}
	 */
	public static function run_batch() {
		global $wpdb;
		$table = NGC_Database::table( 'earnings' );
		if ( ! $table || ! class_exists( 'NGC_Reviews' ) ) {
			return [ 'processed' => 0, 'total' => 0.0, 'errors' => 0 ];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$tutor_ids = $wpdb->get_col( "SELECT DISTINCT tutor_user_id FROM {$table} WHERE status = 'pending' AND tutor_user_id > 0" );
		$processed = 0;
		$total     = 0.0;
		$errors    = 0;

		foreach ( array_map( 'intval', (array) $tutor_ids ) as $tutor_id ) {
			$pending = NGC_Reviews::pending_payout_for_tutor( $tutor_id );
			if ( $pending <= 0 ) {
				continue;
			}
			$result = NGC_Reviews::process_payout( $tutor_id, $pending, false );
			if ( is_wp_error( $result ) ) {
				++$errors;
				continue;
			}
			++$processed;
			$total += $pending;
		}

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log( 'payout_batch', 'payout', 0, compact( 'processed', 'total', 'errors' ), 0 );
		}

		return [
			'processed' => $processed,
			'total'     => $total,
			'errors'    => $errors,
		];
	}
}
