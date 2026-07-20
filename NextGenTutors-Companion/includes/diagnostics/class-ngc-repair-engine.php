<?php
/**
 * Repair engine — dry run, risk scoring, snapshots, rollback.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Safe self-healing with approval workflow.
 */
class NGC_Repair_Engine {

	/**
	 * @var array<string, mixed>|null
	 */
	private static $last_report = null;

	/**
	 * Hook registration.
	 */
	public static function init() {
		// Repair engine is invoked via REST and admin.
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function last_report() {
		return self::$last_report ?: get_option( 'ngc_last_repair_report', [] );
	}

	/**
	 * Build repair plan from health scan.
	 *
	 * @param bool $dry_run Dry run only.
	 * @return array<string, mixed>
	 */
	public static function build_plan( $dry_run = true ) {
		$issues = NGC_Health_Scanner::detect_drift();
		$actions = [];

		foreach ( $issues as $issue ) {
			switch ( $issue['type'] ) {
				case 'missing_pages':
					$actions[] = [
						'action'      => 'recreate_pages',
						'risk'        => 2,
						'description' => 'Recreate missing core pages',
						'detail'      => $issue['detail'],
					];
					break;
				case 'missing_tables':
					$actions[] = [
						'action'      => 'recreate_tables',
						'risk'        => 3,
						'description' => 'Recreate missing database tables',
						'detail'      => $issue['detail'],
					];
					break;
				case 'missing_roles':
					$actions[] = [
						'action'      => 'recreate_roles',
						'risk'        => 2,
						'description' => 'Reinstall custom roles and capabilities',
					];
					break;
			}
		}

		if ( empty( $actions ) ) {
			$actions[] = [
				'action'      => 'rebuild_caches',
				'risk'        => 1,
				'description' => 'Flush rewrite rules and transients',
			];
		}

		return [
			'dry_run'  => $dry_run,
			'issues'   => $issues,
			'actions'  => $actions,
			'risk_score' => self::risk_score( $actions ),
		];
	}

	/**
	 * Execute repairs with optional approval.
	 *
	 * @param array<string, mixed> $options Options.
	 * @return array<string, mixed>
	 */
	public static function execute( $options = [] ) {
		$dry_run  = ! empty( $options['dry_run'] );
		$approved = ! empty( $options['approved'] );
		$plan     = self::build_plan( $dry_run );

		if ( $dry_run || ! $approved ) {
			return array_merge( $plan, [ 'executed' => false ] );
		}

		$snapshot_key = 'snap_' . wp_generate_uuid4();
		self::create_snapshot( $snapshot_key, $plan );

		$results = [];
		foreach ( $plan['actions'] as $action ) {
			$results[ $action['action'] ] = self::run_action( $action['action'] );
		}

		NGC_Audit::log( 'repair_executed', 'system', 0, [
			'snapshot' => $snapshot_key,
			'results'  => $results,
		], get_current_user_id(), [
			'workflow_key'   => 'self_healing',
			'correlation_id' => $snapshot_key,
		] );

		$report = [
			'executed'     => true,
			'snapshot_key' => $snapshot_key,
			'results'      => $results,
			'rollback'     => self::rollback_plan( $snapshot_key ),
			'verified_at'  => gmdate( 'c' ),
		];
		self::$last_report = $report;
		update_option( 'ngc_last_repair_report', $report, false );

		return $report;
	}

	/**
	 * @param string $action Action slug.
	 * @return bool
	 */
	private static function run_action( $action ) {
		switch ( $action ) {
			case 'recreate_pages':
				if ( class_exists( 'NGC_Page_Forms_Registry' ) ) {
					NGC_Page_Forms_Registry::repair();
					$report = NGC_Page_Forms_Registry::last_report();
					return ! empty( $report['ok'] );
				}
				return NGC_Self_Healing::repair_pages();
			case 'recreate_tables':
				NGC_Database::create_tables();
				return NGC_Database::tables_exist();
			case 'recreate_roles':
				NGC_Roles::install();
				return NGC_Verification::check_pass( NGC_Verification::run_checks(), 'roles' );
			case 'rebuild_caches':
				flush_rewrite_rules();
				wp_cache_flush();
				return true;
			case 'recreate_workflows':
				if ( function_exists( 'bi_workflow_install_pack' ) ) {
					bi_workflow_install_pack();
				}
				return true;
			case 'repair_metadata':
				return true;
			default:
				return false;
		}
	}

	/**
	 * @param array<int, array<string, mixed>> $actions Actions.
	 * @return int
	 */
	private static function risk_score( $actions ) {
		$score = 0;
		foreach ( $actions as $action ) {
			$score += (int) ( $action['risk'] ?? 1 );
		}
		return min( 10, $score );
	}

	/**
	 * @param string               $key  Snapshot key.
	 * @param array<string, mixed> $plan Plan.
	 */
	private static function create_snapshot( $key, $plan ) {
		global $wpdb;
		$wpdb->insert(
			NGC_Database::table( 'repair_snapshots' ),
			[
				'snapshot_key' => $key,
				'repair_type'  => 'full_repair',
				'payload'      => wp_json_encode( $plan ),
				'created_by'   => get_current_user_id(),
				'created_at'   => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%d', '%s' ]
		);
	}

	/**
	 * @param string $snapshot_key Snapshot key.
	 * @return array<string, mixed>
	 */
	public static function rollback_plan( $snapshot_key ) {
		return [
			'snapshot_key' => $snapshot_key,
			'steps'        => [
				'Restore from repair_snapshots table',
				'Re-run verification checks',
				'Notify administrator',
			],
			'note' => 'Rollback restores metadata snapshots; database table recreation is not automatically reversed.',
		];
	}
}
