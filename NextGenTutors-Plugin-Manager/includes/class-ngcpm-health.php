<?php
/**
 * System readiness calculations.
 *
 * @package NextGenCorePluginManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Health and readiness scoring.
 */
class NGCPM_Health {

	/**
	 * @param array<string, array<string, mixed>>|null $scan Scan results.
	 * @return array<string, mixed>
	 */
	public static function calculate( $scan = null ) {
		$scan = $scan ?: NGCPM_Scanner::scan( true );

		$counts = [
			'total'           => count( $scan ),
			'installed'       => 0,
			'active'          => 0,
			'missing'         => 0,
			'inactive'        => 0,
			'outdated'        => 0,
			'manual_required' => 0,
			'required_ready'  => 0,
			'optional_ready'  => 0,
			'failed'          => 0,
		];

		$required_total = 0;
		$required_ready = 0;

		foreach ( $scan as $row ) {
			$health   = (string) ( $row['health_status'] ?? '' );
			$required = ! empty( $row['required'] );

			if ( ! empty( $row['installed'] ) ) {
				++$counts['installed'];
			}
			if ( ! empty( $row['active'] ) ) {
				++$counts['active'];
			}

			switch ( $health ) {
				case 'MISSING':
					++$counts['missing'];
					break;
				case 'INACTIVE':
					++$counts['inactive'];
					break;
				case 'VERSION_OUTDATED':
					++$counts['outdated'];
					break;
				case 'MANUAL_REQUIRED':
					++$counts['manual_required'];
					break;
				case 'READY':
					if ( $required ) {
						++$counts['required_ready'];
					} else {
						++$counts['optional_ready'];
					}
					break;
			}

			if ( $required ) {
				++$required_total;
				if ( 'READY' === $health ) {
					++$required_ready;
				}
			}
		}

		$readiness_pct = $required_total > 0 ? (int) round( ( $required_ready / $required_total ) * 100 ) : 100;
		$overall       = self::overall_status( $scan );
		$counts['failed'] = self::count_recent_failures();

		return array_merge(
			$counts,
			[
				'required_total'      => $required_total,
				'required_ready'      => $required_ready,
				'readiness_percent'   => $readiness_pct,
				'overall_status'      => $overall,
				'is_ready'            => 'READY' === $overall,
			]
		);
	}

	/**
	 * @param array<string, array<string, mixed>> $scan Scan.
	 * @return string READY|NOT_READY|WARNING
	 */
	public static function overall_status( $scan ) {
		foreach ( $scan as $row ) {
			if ( empty( $row['required'] ) ) {
				continue;
			}
			$health = (string) ( $row['health_status'] ?? '' );
			if ( in_array( $health, [ 'MISSING', 'INACTIVE', 'MANUAL_REQUIRED' ], true ) ) {
				return 'NOT_READY';
			}
		}
		foreach ( $scan as $row ) {
			if ( empty( $row['required'] ) ) {
				continue;
			}
			if ( 'VERSION_OUTDATED' === ( $row['health_status'] ?? '' ) ) {
				return 'WARNING';
			}
		}
		return 'READY';
	}

	/**
	 * Count recent install/activation failures from audit log.
	 *
	 * @return int
	 */
	public static function count_recent_failures() {
		$count = 0;
		foreach ( NGCPM_Logger::recent( 100 ) as $entry ) {
			$type = (string) ( $entry['type'] ?? '' );
			if ( in_array( $type, [ 'install_failure', 'activation_failure' ], true ) ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Setup checklist steps.
	 *
	 * @param array<string, array<string, mixed>>|null $scan   Optional scan.
	 * @param array<string, mixed>|null               $health Optional health.
	 * @return array<int, array<string, mixed>>
	 */
	public static function setup_steps( $scan = null, $health = null ) {
		$scan   = $scan ?: NGCPM_Scanner::scan( true );
		$health = $health ?: self::calculate( $scan );

		$steps = [
			[ 'id' => 1, 'label' => __( 'Install & activate required plugins', 'nextgentutors-plugin-manager' ), 'done' => $health['required_ready'] >= $health['required_total'] && $health['required_total'] > 0 ],
			[ 'id' => 2, 'label' => __( 'Configure WooCommerce', 'nextgentutors-plugin-manager' ), 'done' => self::step_plugin_active( $scan, 'woocommerce' ), 'url' => admin_url( 'admin.php?page=wc-settings' ) ],
			[ 'id' => 3, 'label' => __( 'Configure PayFast', 'nextgentutors-plugin-manager' ), 'done' => self::step_plugin_active( $scan, 'payfast-payment-gateway' ), 'url' => admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ],
			[ 'id' => 4, 'label' => __( 'Configure Amelia', 'nextgentutors-plugin-manager' ), 'done' => self::step_plugin_active( $scan, 'ameliabooking' ), 'url' => admin_url( 'admin.php?page=wpamelia' ) ],
			[ 'id' => 5, 'label' => __( 'Configure MasterStudy', 'nextgentutors-plugin-manager' ), 'done' => self::step_plugin_active( $scan, 'masterstudy-lms' ), 'url' => admin_url( 'admin.php?page=stm-lms-settings' ) ],
			[ 'id' => 6, 'label' => __( 'Configure FluentCRM', 'nextgentutors-plugin-manager' ), 'done' => self::step_plugin_active( $scan, 'fluent-crm' ), 'url' => admin_url( 'admin.php?page=fluentcrm-admin' ) ],
			[ 'id' => 7, 'label' => __( 'Configure FluentSMTP', 'nextgentutors-plugin-manager' ), 'done' => self::step_plugin_active( $scan, 'fluent-smtp' ), 'url' => admin_url( 'admin.php?page=fluent-smtp' ) ],
			[ 'id' => 8, 'label' => __( 'Configure AutomatorWP', 'nextgentutors-plugin-manager' ), 'done' => self::step_plugin_active( $scan, 'automatorwp' ), 'url' => admin_url( 'admin.php?page=automatorwp' ) ],
			[ 'id' => 9, 'label' => __( 'Configure GamiPress', 'nextgentutors-plugin-manager' ), 'done' => self::step_plugin_active( $scan, 'gamipress' ), 'url' => admin_url( 'admin.php?page=gamipress_settings' ) ],
			[ 'id' => 10, 'label' => __( 'Verify system health', 'nextgentutors-plugin-manager' ), 'done' => 'READY' === $health['overall_status'], 'url' => admin_url( 'admin.php?page=' . NGCPM_ADMIN_PAGE ) ],
		];

		return $steps;
	}

	/**
	 * @param array<string, array<string, mixed>> $scan Scan.
	 * @param string                              $slug Slug key.
	 * @return bool
	 */
	private static function step_plugin_active( $scan, $slug ) {
		return ! empty( $scan[ $slug ]['active'] );
	}

	/**
	 * Export dependency report.
	 *
	 * @return array<string, mixed>
	 */
	public static function export_report() {
		$scan   = NGCPM_Scanner::scan( false );
		$health = self::calculate( $scan );
		$public = [];
		foreach ( $scan as $slug => $row ) {
			unset( $row['package_path'] );
			$public[ $slug ] = $row;
		}
		return [
			'generated_at'  => gmdate( 'c' ),
			'site_url'      => home_url(),
			'health'        => $health,
			'plugins'       => $public,
			'setup_steps'   => self::setup_steps(),
			'diagnostics'   => NGCPM_Diagnostics::run_all(),
			'cookie_checks' => NGCPM_Cookies::run_checks(),
			'logs_summary'  => [
				'count'  => count( NGCPM_Logger::recent( NGCPM_LOG_LIMIT ) ),
				'recent' => NGCPM_Logger::recent( 10 ),
			],
		];
	}
}
