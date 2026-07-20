<?php
/**
 * Demo evidence pack writer (Phase 14 §14.24).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writes journey evidence under .agent-audit/evidence/demo/.
 */
final class NGC_Demo_Evidence {

	/**
	 * @return string Absolute evidence root.
	 */
	public static function root() {
		$root = dirname( NGC_PLUGIN_DIR ) . '/.agent-audit/evidence/demo';
		if ( ! is_dir( $root ) ) {
			wp_mkdir_p( $root );
		}
		return $root;
	}

	/**
	 * Ensure per-journey directories exist for the catalogue (scaffolding).
	 *
	 * @return array<int, string> Created/ensured directory paths.
	 */
	public static function scaffold_catalogue_dirs() {
		$created = [];
		$root    = self::root();
		$ids     = [ 'all-journeys' ];
		if ( class_exists( 'NGC_Demo_Journeys' ) ) {
			foreach ( NGC_Demo_Journeys::list_journeys() as $j ) {
				if ( ! empty( $j['id'] ) ) {
					$ids[] = sanitize_key( (string) $j['id'] );
				}
			}
		}
		foreach ( array_unique( $ids ) as $id ) {
			$dir = trailingslashit( $root ) . $id;
			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}
			$readme = $dir . '/README.md';
			if ( ! file_exists( $readme ) ) {
				file_put_contents(
					$readme,
					"# Evidence: {$id}\n\nPopulate via `wp ngc demo_run_journey --id={$id}` or `wp ngc demo_export_evidence`.\n"
				);
			}
			$created[] = $dir;
		}
		return $created;
	}

	/**
	 * Export evidence for a journey id (§14.24 fields).
	 *
	 * @param string               $journey_id Journey.
	 * @param array<string, mixed> $extra Extra fields.
	 * @return string|WP_Error Path to evidence JSON.
	 */
	public static function export_journey( $journey_id, $extra = [] ) {
		$journey_id = sanitize_key( $journey_id );
		$dir        = trailingslashit( self::root() ) . $journey_id;
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$verify = NGC_Demo_Verifier::verify();
		$graph  = get_option( NGC_Demo_Seeder::OPTION_GRAPH, [] );
		$journey = $extra['journey'] ?? [];

		$pack = array_merge(
			[
				'journey_id'             => $journey_id,
				'journey_version'        => NGC_Demo_Env::SEED_VERSION,
				'demo_user'              => $extra['demo_user'] ?? ( $journey['persona'] ?? '' ),
				'start_timestamp'        => $extra['start'] ?? gmdate( 'c' ),
				'end_timestamp'          => gmdate( 'c' ),
				'initial_state'          => $extra['initial_state'] ?? [ 'demo_mode' => NGC_Demo_Env::is_demo_mode() ],
				'steps_executed'         => $extra['steps_executed'] ?? ( $journey['steps'] ?? [] ),
				'screens_visited'        => $extra['screens_visited'] ?? [],
				'commands_or_api_calls'  => $extra['commands_or_api_calls'] ?? [
					'wp ngc demo_seed',
					'wp ngc demo_verify',
					'wp ngc demo_run_journey --id=' . $journey_id,
				],
				'records_created'        => $extra['records_created'] ?? ( $graph['created'] ?? $graph ),
				'records_updated'        => $extra['records_updated'] ?? [],
				'events_emitted'         => $extra['events_emitted'] ?? ( $graph['events'] ?? [] ),
				'handlers_executed'      => $extra['handlers_executed'] ?? [],
				'notifications_sent'     => NGC_Demo_Notifications::all(),
				'integrations_called'    => $verify['integrations'] ?? [],
				'audit_entries'          => $extra['audit_entries'] ?? ( $journey['expected_audit_events'] ?? [] ),
				'financial_calculations' => $extra['financial_calculations'] ?? ( $graph['finance'] ?? [] ),
				'reconciliation_result'  => $extra['reconciliation_result'] ?? null,
				'agent_actions'          => $extra['agent_actions'] ?? ( $graph['agents'] ?? [] ),
				'policy_decisions'       => $extra['policy_decisions'] ?? [],
				'test_result'            => $extra['test_result'] ?? ( ! empty( $verify['ok'] ) ? 'PASS' : 'FAIL' ),
				'failure_details'        => $extra['failure_details'] ?? ( $verify['failures'] ?? [] ),
				'final_state'            => [
					'seed_status' => get_option( NGC_Demo_Seeder::OPTION_STATUS, [] ),
					'verify_ok'   => ! empty( $verify['ok'] ),
					'clock'       => NGC_Demo_Clock::status(),
				],
				'seed_status'            => get_option( NGC_Demo_Seeder::OPTION_STATUS, [] ),
				'seed_graph'             => $graph,
				'verify'                 => $verify,
				'personas'               => NGC_Demo_Registry::directory_for_admin(),
			],
			$extra
		);

		$path = $dir . '/evidence.json';
		$ok   = (bool) file_put_contents( $path, wp_json_encode( $pack, JSON_PRETTY_PRINT ) );
		if ( ! $ok ) {
			return new WP_Error( 'ngc_evidence_write', __( 'Could not write evidence pack.', 'nextgencompanion' ) );
		}

		$manifest = trailingslashit( self::root() ) . 'INDEX.md';
		$line     = sprintf( "| `%s` | %s | %s |\n", $journey_id, $pack['test_result'], $pack['end_timestamp'] );
		if ( ! file_exists( $manifest ) ) {
			file_put_contents(
				$manifest,
				"# Demo evidence index\n\n| Journey | Result | Exported |\n|---------|--------|----------|\n" . $line
			);
		} else {
			file_put_contents( $manifest, $line, FILE_APPEND );
		}

		return $path;
	}

	/**
	 * Export all evidence summary + scaffold dirs.
	 *
	 * @return string|WP_Error
	 */
	public static function export_all() {
		self::scaffold_catalogue_dirs();
		return self::export_journey(
			'all-journeys',
			[
				'demo_user' => 'system',
				'steps_executed' => [ 'scaffold', 'seed', 'verify', 'export' ],
				'journey'   => [
					'id'      => 'all-journeys',
					'persona' => 'system',
					'steps'   => [ [ 'action' => 'export-all' ] ],
				],
			]
		);
	}
}
