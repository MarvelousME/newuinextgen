<?php
/**
 * WP-CLI: wp ngt system * — master setup orchestrator surface.
 *
 * Wraps Companion verification, business profile, roles, demo seed, and
 * checkpoint writing for the 4-phase / 16-step master prompt.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * System orchestration commands.
 */
class NGC_System_CLI {

	public const STATE_OPTION = 'ngt_system_orchestrator_state';
	public const CHECKPOINT_DIR = '.agent-audit/checkpoints/system';

	/**
	 * @return array<string, mixed>
	 */
	private static function state() {
		$s = get_option( self::STATE_OPTION, [] );
		return is_array( $s ) ? $s : [];
	}

	/**
	 * @param array<string, mixed> $patch Patch.
	 */
	private static function save_state( array $patch ) {
		$s = array_merge( self::state(), $patch, [ 'updated_at' => gmdate( 'c' ) ] );
		update_option( self::STATE_OPTION, $s, false );
	}

	/**
	 * Write markdown checkpoint under uploads or ABSPATH sibling when writable.
	 *
	 * @param int                  $step Step number.
	 * @param string               $name Name.
	 * @param string               $status Status.
	 * @param array<string, mixed> $extra Extra.
	 */
	private static function checkpoint( $step, $name, $status, array $extra = [] ) {
		$dir = WP_CONTENT_DIR . '/uploads/ngt-system-checkpoints';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$file = $dir . '/step-' . str_pad( (string) $step, 2, '0', STR_PAD_LEFT ) . '.md';
		$lines = [
			'# Step ' . $step . ' — ' . $name,
			'',
			'## Status',
			$status,
			'',
			'## Started',
			(string) ( $extra['started'] ?? gmdate( 'c' ) ),
			'',
			'## Completed',
			gmdate( 'c' ),
			'',
			'## Evidence',
			'```json',
			wp_json_encode( $extra, JSON_PRETTY_PRINT ),
			'```',
			'',
			'## Theme package',
			'NextGenTutors-BeyondInfinity',
			'',
		];
		file_put_contents( $file, implode( "\n", $lines ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return $file;
	}

	/**
	 * Inspect environment + packages.
	 *
	 * ## OPTIONS
	 *
	 * [--output=<format>]
	 * : json or table
	 * ---
	 * default: json
	 * ---
	 *
	 * @param array<int,string>   $args Args.
	 * @param array<string,mixed> $assoc_args Flags.
	 */
	public function inspect( $args, $assoc_args ) {
		self::save_state( [ 'status' => 'INSPECTING' ] );
		$theme = wp_get_theme();
		$stylesheet = $theme->get_stylesheet();
		$report = [
			'wordpress'     => get_bloginfo( 'version' ),
			'php'           => PHP_VERSION,
			'siteurl'       => site_url(),
			'home'          => home_url(),
			'timezone'      => wp_timezone_string(),
			'theme'         => [
				'stylesheet' => $stylesheet,
				'name'       => $theme->get( 'Name' ),
				'ok'         => ( false !== stripos( $stylesheet, 'beyondinfinity' ) || false !== stripos( (string) $theme->get( 'Name' ), 'BeyondInfinity' ) ),
				'required'   => 'NextGenTutors-BeyondInfinity',
			],
			'companion'     => [
				'active'  => defined( 'NGC_VERSION' ),
				'version' => defined( 'NGC_VERSION' ) ? NGC_VERSION : null,
			],
			'business'      => class_exists( 'NGC_Business_Profile' ) ? NGC_Business_Profile::status() : null,
			'plugins'       => self::plugin_matrix(),
		];
		self::save_state( [ 'status' => 'READY', 'last' => 'inspect' ] );
		self::checkpoint( 0, 'Inspect', 'VERIFIED', $report );
		self::out( $report, $assoc_args );
	}

	/**
	 * Preflight health.
	 *
	 * @param array<int,string>   $args Args.
	 * @param array<string,mixed> $assoc_args Flags.
	 */
	public function preflight( $args, $assoc_args ) {
		$blocking = [];
		$warnings = [];
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			$blocking[] = 'PHP < 8.0';
		}
		if ( ! defined( 'NGC_VERSION' ) ) {
			$blocking[] = 'Companion inactive';
		}
		$theme = wp_get_theme();
		if ( false === stripos( $theme->get_stylesheet(), 'beyondinfinity' ) && false === stripos( (string) $theme->get( 'Name' ), 'BeyondInfinity' ) ) {
			$blocking[] = 'Theme is not NextGenTutors-BeyondInfinity';
		}
		if ( false !== stripos( $theme->get_stylesheet(), 'beyondidentity' ) || false !== stripos( (string) $theme->get( 'Name' ), 'BeyondIdentity' ) ) {
			$blocking[] = 'Invalid theme package BeyondIdentity detected';
		}
		$rest = wp_remote_get( rest_url( 'wp/v2/types' ), [ 'timeout' => 10 ] );
		if ( is_wp_error( $rest ) ) {
			$warnings[] = 'REST loopback: ' . $rest->get_error_message();
		}
		$ok = empty( $blocking );
		$report = [
			'ok'       => $ok,
			'blocking' => $blocking,
			'warnings' => $warnings,
			'status'   => $ok ? 'READY' : 'PREFLIGHT_FAILED',
		];
		self::save_state( [ 'status' => $report['status'], 'last' => 'preflight' ] );
		self::checkpoint( 1, 'Environment, Backup, and Preflight', $ok ? 'VERIFIED' : 'FAILED', $report );
		self::out( $report, $assoc_args );
		if ( ! $ok ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Configure core identity + business profile.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Diff only.
	 *
	 * [--force-safe]
	 * : Overwrite conflicting company fields.
	 *
	 * [--apply]
	 * : Apply changes (default when not dry-run).
	 *
	 * @param array<int,string>   $args Args.
	 * @param array<string,mixed> $assoc_args Flags.
	 */
	public function configure( $args, $assoc_args ) {
		if ( ! class_exists( 'NGC_Business_Profile' ) ) {
			WP_CLI::error( 'NGC_Business_Profile missing' );
		}
		$dry = isset( $assoc_args['dry-run'] );
		$force = isset( $assoc_args['force-safe'] );
		if ( $dry ) {
			$report = [ 'mode' => 'dry-run', 'diff' => NGC_Business_Profile::diff() ];
			self::out( $report, $assoc_args );
			return;
		}
		$result = NGC_Business_Profile::apply( $force );
		if ( class_exists( 'NGC_Roles' ) ) {
			NGC_Roles::install();
			$result['roles'] = 'installed';
		}
		// Permalink structure.
		update_option( 'permalink_structure', '/%postname%/' );
		flush_rewrite_rules( false );
		$result['permalinks'] = '/%postname%/';
		self::save_state( [ 'status' => ! empty( $result['ok'] ) ? 'RUNNING' : 'FAILED', 'last' => 'configure' ] );
		self::checkpoint( 2, 'Core WordPress and Identity Foundation', ! empty( $result['ok'] ) ? 'COMPLETED' : 'FAILED', $result );
		self::out( $result, $assoc_args );
		if ( empty( $result['ok'] ) ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Install/activate awareness (reports only — no invented downloads).
	 *
	 * @param array<int,string>   $args Args.
	 * @param array<string,mixed> $assoc_args Flags.
	 */
	public function install( $args, $assoc_args ) {
		$matrix = self::plugin_matrix();
		$report = [
			'mode'    => 'detect-only',
			'message' => 'Install uses approved local/registry packages only. Missing plugins must be installed via Plugin Manager or approved zips — URLs are never invented.',
			'plugins' => $matrix,
		];
		self::checkpoint( 3, 'Required Plugin Installation and Activation', 'COMPLETED_WITH_WARNINGS', $report );
		self::out( $report, $assoc_args );
	}

	/**
	 * Seed business + Phase 14 demo relational data.
	 *
	 * ## OPTIONS
	 *
	 * [--force-safe]
	 * : Force business profile overwrite.
	 *
	 * @param array<int,string>   $args Args.
	 * @param array<string,mixed> $assoc_args Flags.
	 */
	public function seed( $args, $assoc_args ) {
		$out = [];
		if ( class_exists( 'NGC_Business_Profile' ) ) {
			$out['business'] = NGC_Business_Profile::apply( isset( $assoc_args['force-safe'] ) );
		}
		if ( class_exists( 'NGC_Demo_Env' ) ) {
			NGC_Demo_Env::set_demo_mode( true );
		}
		if ( class_exists( 'NGC_Demo_Seeder' ) ) {
			$graph = NGC_Demo_Seeder::seed( 'all' );
			$out['demo'] = is_wp_error( $graph )
				? [ 'ok' => false, 'error' => $graph->get_error_message() ]
				: [ 'ok' => empty( $graph['errors'] ), 'bookings' => $graph['bookings'] ?? [], 'errors' => $graph['errors'] ?? [] ];
		} else {
			$out['demo'] = [ 'ok' => false, 'error' => 'Seeder missing' ];
		}
		$ok = ! empty( $out['business']['ok'] ) && ! empty( $out['demo']['ok'] );
		self::save_state( [ 'status' => $ok ? 'RUNNING' : 'PARTIAL', 'last' => 'seed' ] );
		self::checkpoint( 12, 'Forms, Workflows, Demo Users, Relational Data', $ok ? 'COMPLETED' : 'FAILED', $out );
		self::out( $out, $assoc_args );
		if ( ! $ok ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Verify platform + demo + business.
	 *
	 * @param array<int,string>   $args Args.
	 * @param array<string,mixed> $assoc_args Flags.
	 */
	public function verify( $args, $assoc_args ) {
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
			&& ( empty( $report['companion'] ) || ! empty( $report['companion']['ok'] ) )
			&& ( empty( $report['demo'] ) || ! empty( $report['demo']['ok'] ) )
			&& ! empty( $report['business']['applied'] );
		$report['ok'] = $ok;
		self::save_state( [ 'status' => $ok ? 'VERIFIED' : 'FAILED', 'last' => 'verify' ] );
		self::checkpoint( 13, 'Functional, Security, and Integration Verification', $ok ? 'VERIFIED' : 'FAILED', $report );
		self::out( $report, $assoc_args );
		if ( ! $ok ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Safe repairs (roles, business apply, rewrite flush, demo re-verify path).
	 *
	 * @param array<int,string>   $args Args.
	 * @param array<string,mixed> $assoc_args Flags.
	 */
	public function repair( $args, $assoc_args ) {
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
		flush_rewrite_rules( false );
		$actions[] = 'rewrites';
		$report = [ 'actions' => $actions, 'ok' => true ];
		self::checkpoint( 15, 'Repair, Re-run, and Production Readiness', 'COMPLETED', $report );
		self::out( $report, $assoc_args );
	}

	/**
	 * Export JSON report to uploads.
	 *
	 * @param array<int,string>   $args Args.
	 * @param array<string,mixed> $assoc_args Flags.
	 */
	public function export_report( $args, $assoc_args ) {
		$theme = wp_get_theme();
		$payload = [
			'generated_at' => gmdate( 'c' ),
			'state'        => self::state(),
			'business'     => class_exists( 'NGC_Business_Profile' ) ? NGC_Business_Profile::status() : null,
			'theme'        => $theme->get( 'Name' ),
			'theme_package'=> 'NextGenTutors-BeyondInfinity',
			'plugins'      => self::plugin_matrix(),
			'checkpoints'  => WP_CONTENT_DIR . '/uploads/ngt-system-checkpoints',
		];
		$dir = WP_CONTENT_DIR . '/uploads/ngt-system-checkpoints';
		wp_mkdir_p( $dir );
		$path = $dir . '/export-report-' . gmdate( 'Ymd-His' ) . '.json';
		file_put_contents( $path, wp_json_encode( $payload, JSON_PRETTY_PRINT ) ); // phpcs:ignore
		WP_CLI::success( 'Wrote ' . $path );
		self::out( [ 'path' => $path ], $assoc_args );
	}

	/**
	 * Reset Phase 14 demo data.
	 *
	 * @param array<int,string>   $args Args.
	 * @param array<string,mixed> $assoc_args Flags.
	 */
	public function reset_demo( $args, $assoc_args ) {
		if ( ! class_exists( 'NGC_Demo_Reset' ) ) {
			WP_CLI::error( 'Demo reset unavailable' );
		}
		$r = NGC_Demo_Reset::reset( 'all' );
		$report = is_wp_error( $r ) ? [ 'ok' => false, 'error' => $r->get_error_message() ] : [ 'ok' => true, 'result' => $r ];
		self::out( $report, $assoc_args );
		if ( empty( $report['ok'] ) ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Run ordered pipeline: preflight → configure → seed → verify.
	 *
	 * ## OPTIONS
	 *
	 * [--force-safe]
	 * : Force business profile overwrite.
	 *
	 * [--dry-run]
	 * : Configure dry-run only then stop.
	 *
	 * @param array<int,string>   $args Args.
	 * @param array<string,mixed> $assoc_args Flags.
	 */
	public function run_all( $args, $assoc_args ) {
		self::save_state( [ 'status' => 'RUNNING' ] );
		$this->preflight( [], $assoc_args );
		if ( isset( $assoc_args['dry-run'] ) ) {
			$this->configure( [], $assoc_args );
			WP_CLI::success( 'Dry-run complete' );
			return;
		}
		$this->configure( [], $assoc_args );
		$this->install( [], $assoc_args );
		$this->seed( [], $assoc_args );
		$this->verify( [], $assoc_args );
		$this->export_report( [], $assoc_args );
		self::save_state( [ 'status' => 'COMPLETED' ] );
		WP_CLI::success( 'ngt system run-all finished' );
	}

	/**
	 * Show orchestrator state.
	 *
	 * @param array<int,string>   $args Args.
	 * @param array<string,mixed> $assoc_args Flags.
	 */
	public function status( $args, $assoc_args ) {
		$report = [
			'state'    => self::state(),
			'business' => class_exists( 'NGC_Business_Profile' ) ? NGC_Business_Profile::status() : null,
			'theme'    => wp_get_theme()->get( 'Name' ),
		];
		self::out( $report, $assoc_args );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function plugin_matrix() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$want = [
			'NextGenTutors-Companion/nextgencompanion.php' => 'NextGenTutors Companion',
			'NextGenTutors-Plugin-Manager/nextgentutors-plugin-manager.php' => 'Plugin Manager',
			'woocommerce/woocommerce.php' => 'WooCommerce',
			'fluent-crm/fluent-crm.php' => 'FluentCRM',
			'fluent-smtp/fluent-smtp.php' => 'FluentSMTP',
			'elementor/elementor.php' => 'Elementor',
		];
		$all = get_plugins();
		$active = (array) get_option( 'active_plugins', [] );
		$rows = [];
		foreach ( $want as $file => $label ) {
			$rows[] = [
				'plugin'    => $label,
				'file'      => $file,
				'installed' => isset( $all[ $file ] ),
				'active'    => in_array( $file, $active, true ),
				'version'   => $all[ $file ]['Version'] ?? '',
			];
		}
		return $rows;
	}

	/**
	 * @param array<string,mixed> $report Report.
	 * @param array<string,mixed> $assoc_args Flags.
	 */
	private static function out( array $report, array $assoc_args ) {
		$fmt = isset( $assoc_args['output'] ) ? (string) $assoc_args['output'] : 'json';
		if ( 'table' === $fmt ) {
			WP_CLI::line( wp_json_encode( $report ) );
			return;
		}
		WP_CLI::line( wp_json_encode( $report, JSON_PRETTY_PRINT ) );
	}
}

/**
 * Thin dispatcher: `wp ngt system <subcommand>`.
 */
class NGC_NGT_CLI {

	/**
	 * System orchestration entry.
	 *
	 * ## OPTIONS
	 *
	 * <command>
	 * : inspect|preflight|install|configure|seed|verify|repair|export-report|reset-demo|run-all|status
	 *
	 * [--dry-run]
	 * [--apply]
	 * [--force-safe]
	 * [--output=<format>]
	 *
	 * @param array<int,string>   $args Args.
	 * @param array<string,mixed> $assoc_args Flags.
	 */
	public function system( $args, $assoc_args ) {
		$cmd = isset( $args[0] ) ? sanitize_key( str_replace( '-', '_', $args[0] ) ) : 'status';
		$map = [
			'inspect'        => 'inspect',
			'preflight'      => 'preflight',
			'install'        => 'install',
			'configure'      => 'configure',
			'seed'           => 'seed',
			'verify'         => 'verify',
			'repair'         => 'repair',
			'export_report'  => 'export_report',
			'export-report'  => 'export_report',
			'reset_demo'     => 'reset_demo',
			'reset-demo'     => 'reset_demo',
			'run_all'        => 'run_all',
			'run-all'        => 'run_all',
			'status'         => 'status',
		];
		// Normalize hyphenated first arg.
		$raw = isset( $args[0] ) ? (string) $args[0] : 'status';
		$key = str_replace( '-', '_', sanitize_key( $raw ) );
		if ( ! isset( $map[ $key ] ) && ! isset( $map[ $raw ] ) ) {
			WP_CLI::error( 'Unknown system command. Use: inspect|preflight|install|configure|seed|verify|repair|export-report|reset-demo|run-all|status' );
		}
		$method = $map[ $key ] ?? $map[ $raw ];
		$cli = new NGC_System_CLI();
		call_user_func( [ $cli, $method ], array_slice( $args, 1 ), $assoc_args );
	}
}

WP_CLI::add_command( 'ngt', 'NGC_NGT_CLI' );
