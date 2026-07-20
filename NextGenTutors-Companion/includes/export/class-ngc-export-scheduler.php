<?php
/**
 * Scheduled and background export jobs.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export job scheduler.
 */
class NGC_Export_Scheduler {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'ngc_process_export_job', [ __CLASS__, 'process_job' ], 10, 1 );
		add_action( 'ngc_run_scheduled_exports', [ __CLASS__, 'run_scheduled' ] );
		if ( ! wp_next_scheduled( 'ngc_run_scheduled_exports' ) ) {
			wp_schedule_event( time(), 'hourly', 'ngc_run_scheduled_exports' );
		}
	}

	/**
	 * Queue an export job.
	 *
	 * @param array<string, mixed> $config Job config.
	 * @return int Job ID.
	 */
	public static function queue( $config ) {
		global $wpdb;
		$scheduled = ! empty( $config['scheduled_at'] ) ? sanitize_text_field( $config['scheduled_at'] ) : null;

		$wpdb->insert(
			NGC_Database::table( 'export_jobs' ),
			[
				'dataset'      => sanitize_key( $config['dataset'] ?? '' ),
				'format'       => sanitize_key( $config['format'] ?? 'csv' ),
				'status'       => $scheduled ? 'scheduled' : 'pending',
				'filters'      => wp_json_encode( $config['filters'] ?? [] ),
				'columns'      => wp_json_encode( $config['columns'] ?? [] ),
				'created_by'   => get_current_user_id(),
				'scheduled_at' => $scheduled,
				'created_at'   => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ]
		);

		$job_id = (int) $wpdb->insert_id;
		if ( ! $scheduled ) {
			wp_schedule_single_event( time() + 5, 'ngc_process_export_job', [ $job_id ] );
		}
		return $job_id;
	}

	/**
	 * @param int $job_id Job ID.
	 */
	public static function process_job( $job_id ) {
		global $wpdb;
		$table = NGC_Database::table( 'export_jobs' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$job = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $job_id ), ARRAY_A );
		if ( ! $job ) {
			return;
		}

		$wpdb->update( $table, [ 'status' => 'processing' ], [ 'id' => (int) $job_id ], [ '%s' ], [ '%d' ] );

		$result = NGC_Export_Engine::run_export( [
			'dataset' => $job['dataset'],
			'format'  => $job['format'],
			'filters' => json_decode( (string) ( $job['filters'] ?? '{}' ), true ) ?: [],
			'columns' => json_decode( (string) ( $job['columns'] ?? '[]' ), true ) ?: [],
			'compress'=> true,
		] );

		if ( ! empty( $result['success'] ) ) {
			$wpdb->update(
				$table,
				[
					'status'       => 'completed',
					'file_path'    => $result['path'] ?? '',
					'completed_at' => current_time( 'mysql', true ),
				],
				[ 'id' => (int) $job_id ],
				[ '%s', '%s', '%s' ],
				[ '%d' ]
			);
			if ( ! empty( $job['created_by'] ) ) {
				self::email_delivery( (int) $job['created_by'], $result );
			}
		} else {
			$wpdb->update(
				$table,
				[
					'status'        => 'failed',
					'error_message' => $result['error'] ?? 'Unknown error',
					'completed_at'  => current_time( 'mysql', true ),
				],
				[ 'id' => (int) $job_id ],
				[ '%s', '%s', '%s' ],
				[ '%d' ]
			);
		}
	}

	/**
	 * Process due scheduled exports.
	 */
	public static function run_scheduled() {
		global $wpdb;
		$table = NGC_Database::table( 'export_jobs' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$jobs = $wpdb->get_col(
			"SELECT id FROM {$table} WHERE status = 'scheduled' AND scheduled_at <= UTC_TIMESTAMP() LIMIT 10"
		);
		foreach ( (array) $jobs as $job_id ) {
			self::process_job( (int) $job_id );
		}
	}

	/**
	 * @param int                  $user_id User ID.
	 * @param array<string, mixed> $result  Export result.
	 */
	private static function email_delivery( $user_id, $result ) {
		$user = get_userdata( $user_id );
		if ( ! $user || empty( $result['url'] ) ) {
			return;
		}
		wp_mail(
			$user->user_email,
			__( 'Your NextGen export is ready', 'nextgencompanion' ),
			sprintf(
				/* translators: %s: download URL */
				__( 'Your export has completed. Download: %s', 'nextgencompanion' ),
				$result['url']
			)
		);
	}
}
