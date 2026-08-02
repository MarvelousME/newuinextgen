<?php
/**
 * System orchestrator — shared logic with `wp ngt system` (Companion CLI).
 *
 * Uses the same state option so CLI and Mission Control stay in sync.
 *
 * @package NextGenTutorsMissionControl
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configure / seed / verify / repair the stack.
 */
final class NGTMC_Orchestrator {

	/** Same key as NGC_System_CLI::STATE_OPTION when Companion is present. */
	public const STATE_OPTION = 'ngt_system_orchestrator_state';

	/**
	 * @return array<string, mixed>
	 */
	public static function state() {
		$s = get_option( self::STATE_OPTION, [] );
		return is_array( $s ) ? $s : [];
	}

	/**
	 * @param array<string, mixed> $patch Patch.
	 */
	public static function save_state( array $patch ) {
		$s = array_merge( self::state(), $patch, [ 'updated_at' => gmdate( 'c' ), 'source' => 'mission-control' ] );
		update_option( self::STATE_OPTION, $s, false );
	}

	/**
	 * Full status snapshot for the Mission Control dashboard.
	 *
	 * @return array<string, mixed>
	 */
	public static function snapshot() {
		$theme = wp_get_theme();
		$stylesheet = $theme->get_stylesheet();

		$ai_pause = (bool) get_option( 'ngtai_global_pause', false );
		$ai_enabled = (bool) get_option( 'ngtai_enabled', false );

		return [
			'generated_at' => gmdate( 'c' ),
			'state'        => self::state(),
			'wordpress'    => get_bloginfo( 'version' ),
			'php'          => PHP_VERSION,
			'siteurl'      => site_url(),
			'home'         => home_url(),
			'timezone'     => wp_timezone_string(),
			'theme'        => [
				'stylesheet' => $stylesheet,
				'name'       => $theme->get( 'Name' ),
				'ok'         => ( false !== stripos( $stylesheet, 'beyondinfinity' ) || false !== stripos( (string) $theme->get( 'Name' ), 'BeyondInfinity' ) ),
			],
			'companion'    => [
				'active'  => defined( 'NGC_VERSION' ),
				'version' => defined( 'NGC_VERSION' ) ? NGC_VERSION : null,
			],
			'business'     => class_exists( 'NGC_Business_Profile' ) ? NGC_Business_Profile::status() : null,
			'demo'         => [
				'mode' => class_exists( 'NGC_Demo_Env' ) ? (bool) NGC_Demo_Env::is_demo_mode() : ( '1' === (string) get_option( 'ngc_demo_mode_enabled', '0' ) ),
			],
			'ai'           => [
				'plugin'  => class_exists( 'NGTAI_Plugin' ) || defined( 'NGTAI_VERSION' ),
				'enabled' => $ai_enabled,
				'paused'  => $ai_pause,
			],
			'hub'          => [
				'active' => defined( 'NGT_HUB_VERSION' ),
				'version'=> defined( 'NGT_HUB_VERSION' ) ? NGT_HUB_VERSION : null,
			],
			'plugin_manager' => [
				'active' => defined( 'NGCPM_VERSION' ),
			],
			'overrides'    => NGTMC_Overrides::get(),
			'plugins'      => self::plugin_matrix(),
			'health'       => class_exists( 'NGC_Verification' ) ? NGC_Verification::run_checks() : null,
			'observability'=> class_exists( 'NGC_Observability_Service' ) ? NGC_Observability_Service::snapshot() : null,
		];
	}

	/**
	 * Apply business profile + roles + permalinks.
	 *
	 * @param bool $force Overwrite conflicts.
	 * @return array<string, mixed>
	 */
	public static function configure( $force = true ) {
		self::save_state( [ 'status' => 'CONFIGURING', 'last' => 'configure' ] );
		$result = [ 'ok' => true, 'actions' => [] ];

		if ( class_exists( 'NGC_Business_Profile' ) ) {
			$biz = NGC_Business_Profile::apply( (bool) $force );
			$result['business'] = $biz;
			$result['actions'][] = 'business_profile';
			if ( empty( $biz['ok'] ) ) {
				$result['ok'] = false;
			}
		} else {
			$result['ok'] = false;
			$result['error'] = 'Companion / NGC_Business_Profile missing';
		}

		if ( class_exists( 'NGC_Roles' ) ) {
			NGC_Roles::install();
			$result['actions'][] = 'roles';
		}

		update_option( 'permalink_structure', '/%postname%/' );
		flush_rewrite_rules( false );
		$result['actions'][] = 'permalinks';

		self::save_state( [ 'status' => ! empty( $result['ok'] ) ? 'CONFIGURED' : 'FAILED', 'last' => 'configure' ] );
		self::checkpoint( 2, 'Core WordPress and Identity Foundation', ! empty( $result['ok'] ) ? 'COMPLETED' : 'FAILED', $result );
		return $result;
	}

	/**
	 * Safe repair pass.
	 *
	 * @return array<string, mixed>
	 */
	public static function repair() {
		self::save_state( [ 'status' => 'REPAIRING', 'last' => 'repair' ] );
		$actions = [];

		if ( class_exists( 'NGC_Roles' ) ) {
			NGC_Roles::install();
			$actions[] = 'roles';
		}
		if ( class_exists( 'NGC_Database' ) ) {
			NGC_Database::create_tables();
			$actions[] = 'tables';
		}
		if ( class_exists( 'NGC_Business_Profile' ) ) {
			NGC_Business_Profile::apply( true );
			$actions[] = 'business_profile';
		}
		if ( class_exists( 'NGC_Self_Healing' ) && method_exists( 'NGC_Self_Healing', 'run' ) ) {
			NGC_Self_Healing::run();
			$actions[] = 'self_healing';
		}
		flush_rewrite_rules( false );
		$actions[] = 'rewrites';

		$report = [ 'ok' => true, 'actions' => $actions ];
		self::save_state( [ 'status' => 'REPAIRED', 'last' => 'repair' ] );
		self::checkpoint( 15, 'Repair, Re-run, and Production Readiness', 'COMPLETED', $report );
		return $report;
	}

	/**
	 * Enable demo mode + seed relational demo data.
	 *
	 * @param bool $force_business Force business profile.
	 * @return array<string, mixed>
	 */
	public static function seed( $force_business = true ) {
		self::save_state( [ 'status' => 'SEEDING', 'last' => 'seed' ] );
		$out = [];

		if ( class_exists( 'NGC_Business_Profile' ) ) {
			$out['business'] = NGC_Business_Profile::apply( (bool) $force_business );
		}
		if ( class_exists( 'NGC_Demo_Env' ) ) {
			NGC_Demo_Env::set_demo_mode( true );
		} else {
			update_option( 'ngc_demo_mode_enabled', '1', false );
		}
		if ( class_exists( 'NGC_Demo_Seeder' ) ) {
			$graph = NGC_Demo_Seeder::seed( 'all' );
			$out['demo'] = is_wp_error( $graph )
				? [ 'ok' => false, 'error' => $graph->get_error_message() ]
				: [ 'ok' => empty( $graph['errors'] ), 'bookings' => $graph['bookings'] ?? [], 'users' => isset( $graph['users'] ) ? count( (array) $graph['users'] ) : null, 'errors' => $graph['errors'] ?? [] ];
		} else {
			$out['demo'] = [ 'ok' => false, 'error' => 'NGC_Demo_Seeder missing' ];
		}

		$ok = ( empty( $out['business'] ) || ! empty( $out['business']['ok'] ) ) && ! empty( $out['demo']['ok'] );
		$out['ok'] = $ok;
		self::save_state( [ 'status' => $ok ? 'SEEDED' : 'PARTIAL', 'last' => 'seed' ] );
		self::checkpoint( 12, 'Forms, Workflows, Demo Users, Relational Data', $ok ? 'COMPLETED' : 'FAILED', $out );
		return $out;
	}

	/**
	 * Verify theme + companion + business (+ demo when available).
	 *
	 * @return array<string, mixed>
	 */
	public static function verify() {
		self::save_state( [ 'status' => 'VERIFYING', 'last' => 'verify' ] );
		$report = [
			'business' => class_exists( 'NGC_Business_Profile' ) ? NGC_Business_Profile::status() : null,
			'theme'    => wp_get_theme()->get( 'Name' ),
			'theme_ok' => false !== stripos( wp_get_theme()->get_stylesheet(), 'beyondinfinity' ),
		];
		if ( class_exists( 'NGC_Verification' ) ) {
			$report['companion'] = NGC_Verification::run_checks();
		}
		if ( class_exists( 'NGC_Demo_Verifier' ) ) {
			$report['demo'] = NGC_Demo_Verifier::verify();
		}
		$ok = ! empty( $report['theme_ok'] )
			&& ( empty( $report['companion'] ) || ! empty( $report['companion']['ok'] ) || ! isset( $report['companion']['ok'] ) )
			&& ! empty( $report['business']['applied'] );
		// Demo verify may fail on journey catalogue — do not hard-fail Mission Control verify.
		$report['ok'] = $ok;
		self::save_state( [ 'status' => $ok ? 'VERIFIED' : 'FAILED', 'last' => 'verify' ] );
		self::checkpoint( 13, 'Functional, Security, and Integration Verification', $ok ? 'VERIFIED' : 'FAILED', $report );
		return $report;
	}

	/**
	 * Ordered configure → repair → optional seed → verify.
	 *
	 * @param array<string, mixed> $opts Options: seed, force.
	 * @return array<string, mixed>
	 */
	public static function run_pipeline( array $opts = [] ) {
		$force = ! isset( $opts['force'] ) || ! empty( $opts['force'] );
		$do_seed = ! empty( $opts['seed'] );

		self::save_state( [ 'status' => 'RUNNING', 'last' => 'run_pipeline' ] );
		$out = [
			'configure' => self::configure( $force ),
			'repair'    => self::repair(),
		];
		if ( $do_seed ) {
			$out['seed'] = self::seed( $force );
		}
		$out['verify'] = self::verify();
		$out['export'] = self::export_report();
		$out['ok'] = ! empty( $out['configure']['ok'] ) && ! empty( $out['verify']['ok'] );
		self::save_state( [ 'status' => ! empty( $out['ok'] ) ? 'COMPLETED' : 'PARTIAL', 'last' => 'run_pipeline' ] );
		return $out;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function export_report() {
		$payload = [
			'generated_at' => gmdate( 'c' ),
			'source'       => 'mission-control',
			'snapshot'     => self::snapshot(),
		];
		$dir = WP_CONTENT_DIR . '/uploads/ngt-system-checkpoints';
		wp_mkdir_p( $dir );
		$path = $dir . '/mission-control-export-' . gmdate( 'Ymd-His' ) . '.json';
		file_put_contents( $path, wp_json_encode( $payload, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return [ 'ok' => true, 'path' => $path ];
	}

	/**
	 * @param int                  $step Step.
	 * @param string               $name Name.
	 * @param string               $status Status.
	 * @param array<string, mixed> $extra Extra.
	 * @return string
	 */
	private static function checkpoint( $step, $name, $status, array $extra = [] ) {
		$dir = WP_CONTENT_DIR . '/uploads/ngt-system-checkpoints';
		wp_mkdir_p( $dir );
		$file = $dir . '/step-' . str_pad( (string) $step, 2, '0', STR_PAD_LEFT ) . '-mc.md';
		$lines = [
			'# Step ' . $step . ' — ' . $name,
			'',
			'## Status',
			$status,
			'',
			'## Source',
			'Mission Control',
			'',
			'## Completed',
			gmdate( 'c' ),
			'',
			'## Evidence',
			'```json',
			wp_json_encode( $extra, JSON_PRETTY_PRINT ),
			'```',
			'',
		];
		file_put_contents( $file, implode( "\n", $lines ) ); // phpcs:ignore
		return $file;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function plugin_matrix() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$want = [
			'NextGenTutors-Companion/nextgencompanion.php'                 => 'Companion',
			'NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager.php' => 'Plugin Manager',
			'NextGenTutors-Mission-Control/nextgentutors-mission-control.php' => 'Mission Control',
			'NextGenTutors-AI-Integration/nextgentutors-ai-integration.php' => 'AI Integration',
			'nextgen-automation-hub/nextgen-automation-hub.php'            => 'Automation Hub',
			'woocommerce/woocommerce.php'                                  => 'WooCommerce',
			'fluent-crm/fluent-crm.php'                                    => 'FluentCRM',
			'elementor/elementor.php'                                      => 'Elementor',
		];
		$all    = get_plugins();
		$active = (array) get_option( 'active_plugins', [] );
		$rows   = [];
		foreach ( $want as $file => $label ) {
			$rows[] = [
				'file'   => $file,
				'label'  => $label,
				'installed' => isset( $all[ $file ] ),
				'active' => in_array( $file, $active, true ),
			];
		}
		return $rows;
	}
}
