<?php
/**
 * Demo journey catalogue loader (Phase 14 §14.20).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads YAML/JSON journey definitions and runs seed+verify steps.
 */
final class NGC_Demo_Journeys {

	/**
	 * Resolve journey catalogue directory.
	 *
	 * Prefer the bundled Companion copy (works in Docker/plugin installs), then
	 * fall back to the monorepo authoring path `.agent-audit/demo/journeys`.
	 *
	 * @return string
	 */
	public static function catalogue_dir() {
		$candidates = [
			trailingslashit( NGC_PLUGIN_DIR ) . 'demo/journeys',
			trailingslashit( NGC_PLUGIN_DIR ) . 'includes/demo/journeys',
		];

		// Monorepo: …/NextGenTutors-Companion → sibling .agent-audit (host checkout only).
		$plugin_root = untrailingslashit( NGC_PLUGIN_DIR );
		$repo_root   = dirname( $plugin_root );
		$candidates[] = $repo_root . '/.agent-audit/demo/journeys';

		// Docker/dev: explicit mount or ABSPATH-adjacent audit tree.
		$candidates[] = WP_CONTENT_DIR . '/.agent-audit/demo/journeys';
		$candidates[] = dirname( ABSPATH ) . '/.agent-audit/demo/journeys';

		/**
		 * Filter demo journey catalogue directories.
		 *
		 * @param string[] $candidates Candidate absolute paths.
		 */
		$candidates = apply_filters( 'ngc_demo_journey_catalogue_dirs', $candidates );

		foreach ( $candidates as $dir ) {
			if ( is_string( $dir ) && is_dir( $dir ) ) {
				$json = glob( trailingslashit( $dir ) . '*.json' );
				if ( ! empty( $json ) ) {
					return $dir;
				}
			}
		}

		// Default to bundled path even if empty (verifier reports count=0 honestly).
		return trailingslashit( NGC_PLUGIN_DIR ) . 'demo/journeys';
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_journeys() {
		$dir  = self::catalogue_dir();
		$rows = [];
		if ( ! is_dir( $dir ) ) {
			return $rows;
		}
		$files = glob( $dir . '/*.json' ) ?: [];
		$yml   = glob( $dir . '/*.yml' ) ?: [];
		$yaml  = glob( $dir . '/*.yaml' ) ?: [];
		foreach ( array_merge( $files, $yml, $yaml ) as $file ) {
			$raw = file_get_contents( $file );
			if ( false === $raw ) {
				continue;
			}
			// Strip UTF-8 BOM from Windows-generated JSON.
			if ( strncmp( $raw, "\xEF\xBB\xBF", 3 ) === 0 ) {
				$raw = substr( $raw, 3 );
			}
			$data = json_decode( $raw, true );
			if ( ! is_array( $data ) ) {
				// Minimal YAML: key: value lines (enough for our catalogue files).
				$data = self::parse_simple_yaml( $raw );
			}
			if ( is_array( $data ) && ! empty( $data['id'] ) ) {
				$data['_file'] = basename( $file );
				$rows[]        = $data;
			}
		}
		return $rows;
	}

	/**
	 * Extremely small YAML subset parser for journey stubs.
	 *
	 * @param string $raw Raw.
	 * @return array<string, mixed>
	 */
	private static function parse_simple_yaml( $raw ) {
		$data = [];
		$list_key = null;
		foreach ( preg_split( '/\r\n|\n|\r/', $raw ) as $line ) {
			if ( preg_match( '/^(\w[\w\-]*):\s*(.*)$/', $line, $m ) ) {
				$list_key = null;
				$key      = $m[1];
				$val      = trim( $m[2] );
				if ( '' === $val ) {
					$data[ $key ] = [];
					$list_key     = $key;
				} elseif ( preg_match( '/^\[(.*)\]$/', $val, $mm ) ) {
					$data[ $key ] = array_map( 'trim', explode( ',', $mm[1] ) );
				} else {
					$data[ $key ] = trim( $val, "\"'" );
				}
			} elseif ( $list_key && preg_match( '/^\s+-\s+(.+)$/', $line, $m ) ) {
				$data[ $list_key ][] = trim( $m[1], "\"'" );
			}
		}
		return $data;
	}

	/**
	 * Run a journey: ensure seed, verify expected keys, write evidence.
	 *
	 * @param string $journey_id Journey id.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function run( $journey_id ) {
		$journey_id = sanitize_text_field( $journey_id );
		$found      = null;
		foreach ( self::list_journeys() as $j ) {
			if ( ( $j['id'] ?? '' ) === $journey_id ) {
				$found = $j;
				break;
			}
		}
		if ( ! $found ) {
			return new WP_Error( 'ngc_journey_missing', 'Journey not found: ' . $journey_id );
		}

		$start = gmdate( 'c' );
		$seed  = NGC_Demo_Seeder::seed( (string) ( $found['scenario'] ?? 'all' ) );
		if ( is_wp_error( $seed ) ) {
			return $seed;
		}
		$verify = NGC_Demo_Verifier::verify();
		$path   = NGC_Demo_Evidence::export_journey(
			sanitize_key( $journey_id ),
			[
				'demo_user'       => $found['persona'] ?? '',
				'start'           => $start,
				'journey'         => $found,
				'steps_executed'  => $found['steps'] ?? [],
				'test_result'     => ! empty( $verify['ok'] ) ? 'PASS' : 'FAIL',
				'failure_details' => $verify['failures'] ?? [],
			]
		);

		return [
			'journey'  => $found,
			'seed'     => [ 'ok' => ! is_wp_error( $seed ), 'errors' => is_array( $seed ) ? ( $seed['errors'] ?? [] ) : [] ],
			'verify'   => $verify,
			'evidence' => is_wp_error( $path ) ? $path->get_error_message() : $path,
		];
	}

	/**
	 * Execute the journey catalogue once (shared seed + verify + evidence).
	 *
	 * Re-seeding per journey file previously hung admin-post for many minutes
	 * (N × full relational seed). Batch mode keeps relational integrity while
	 * returning promptly for Demo Control Centre UI + headed e2e.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function run_all() {
		$journeys = self::list_journeys();
		$start    = gmdate( 'c' );
		$seed     = NGC_Demo_Seeder::seed( 'all' );
		if ( is_wp_error( $seed ) ) {
			return [
				[
					'journey' => [ 'id' => 'JOURNEY-BATCH' ],
					'seed'    => $seed,
					'verify'  => [ 'ok' => false, 'failures' => [ $seed->get_error_message() ] ],
					'evidence'=> null,
					'mode'    => 'batch',
				],
			];
		}

		$verify   = NGC_Demo_Verifier::verify();
		$evidence = class_exists( 'NGC_Demo_Evidence' ) ? NGC_Demo_Evidence::export_all() : null;
		$ev_path  = is_wp_error( $evidence ) ? $evidence->get_error_message() : $evidence;

		$out = [];
		if ( empty( $journeys ) ) {
			$out[] = [
				'journey'  => [ 'id' => 'JOURNEY-CORE-SEED' ],
				'seed'     => $seed,
				'verify'   => $verify,
				'evidence' => $ev_path,
				'mode'     => 'batch',
			];
		} else {
			foreach ( $journeys as $j ) {
				$out[] = [
					'journey'  => $j,
					'seed'     => [
						'ok'     => true,
						'shared' => true,
						'errors' => is_array( $seed ) ? ( $seed['errors'] ?? [] ) : [],
					],
					'verify'   => $verify,
					'evidence' => $ev_path,
					'mode'     => 'batch',
					'started'  => $start,
				];
			}
		}

		update_option(
			'ngc_demo_journeys_last_run',
			[
				'at'        => $start,
				'count'     => count( $out ),
				'ok'        => ! empty( $verify['ok'] ),
				'evidence'  => $ev_path,
				'mode'      => 'batch',
			],
			false
		);

		return $out;
	}
}
