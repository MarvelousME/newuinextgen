<?php
/**
 * PayFast / Woo / ledger reconciliation.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Daily reconciliation + drift report.
 */
final class NGC_Reconciliation {

	public const CRON_HOOK = 'ngc_reconciliation_daily';

	/**
	 * Init cron.
	 */
	public static function init() {
		add_action( self::CRON_HOOK, [ __CLASS__, 'cron_run' ] );
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Cron entry.
	 */
	public static function cron_run() {
		self::run( [] );
	}

	/**
	 * Run reconciliation.
	 *
	 * @param array $payload Optional filters.
	 * @return array|WP_Error|true
	 */
	public static function run( array $payload = [] ) {
		global $wpdb;
		$tenant = NGC_Tenant_Context::id();

		$ledger_cash = NGC_Ledger::balance( 'cash' );

		$woo_total = 0.0;
		if ( function_exists( 'wc_get_orders' ) ) {
			$orders = wc_get_orders(
				[
					'status'       => [ 'completed', 'processing' ],
					'limit'        => 500,
					'date_created' => '>' . ( time() - WEEK_IN_SECONDS ),
					'return'       => 'ids',
				]
			);
			foreach ( (array) $orders as $oid ) {
				$o = wc_get_order( $oid );
				if ( $o ) {
					$woo_total += (float) $o->get_total();
				}
			}
		} else {
			// Fallback: sum payment settlement journals.
			$woo_total = (float) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COALESCE(SUM(e.debit),0) FROM ' . NGC_Platform_Schema::table( 'gl_entries' ) . ' e
					INNER JOIN ' . NGC_Platform_Schema::table( 'gl_journals' ) . ' j ON j.id = e.journal_id
					WHERE e.tenant_id = %d AND e.account_code = %s AND j.source = %s',
					$tenant,
					'cash',
					'payment'
				)
			);
		}

		$drift  = round( $woo_total - $ledger_cash, 2 );
		$status = abs( $drift ) < 0.01 ? 'ok' : ( abs( $drift ) < 100 ? 'warn' : 'drift' );
		$report = [
			'woo_total'    => $woo_total,
			'ledger_cash'  => $ledger_cash,
			'drift'        => $drift,
			'status'       => $status,
			'suggestions'  => [],
			'tenant_id'    => $tenant,
			'ran_at'       => gmdate( 'c' ),
		];
		if ( 'ok' !== $status ) {
			$report['suggestions'][] = 'Review unsettled Woo orders vs ledger payment journals.';
			$report['suggestions'][] = 'Check PayFast ITN failures and wallet credits without journals.';
		}

		$uuid = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'recon_', true );
		$wpdb->insert(
			NGC_Platform_Schema::table( 'recon_runs' ),
			[
				'tenant_id'    => $tenant,
				'run_uuid'     => $uuid,
				'status'       => $status,
				'woo_total'    => $woo_total,
				'ledger_total' => $ledger_cash,
				'drift'        => $drift,
				'report_json'  => wp_json_encode( $report ),
				'created_at'   => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s' ]
		);

		if ( class_exists( 'NGC_Metrics' ) ) {
			NGC_Metrics::set_gauge( 'ledger_recon_drift', abs( $drift ) );
		}
		if ( 'drift' === $status && class_exists( 'NGC_Platform_Observability' ) ) {
			NGC_Platform_Observability::alert( 'reconciliation_drift', $report );
		}

		update_option( 'ngc_last_recon_report', $report, false );
		return $report;
	}

	/**
	 * Latest reports.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public static function recent( $limit = 10 ) {
		global $wpdb;
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . NGC_Platform_Schema::table( 'recon_runs' ) . ' WHERE tenant_id = %d ORDER BY id DESC LIMIT %d',
				NGC_Tenant_Context::id(),
				max( 1, min( 50, (int) $limit ) )
			)
		);
	}
}
