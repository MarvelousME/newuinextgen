<?php
declare(strict_types=1);

namespace NGTBM\Infrastructure\Integrations;

use NGTBM\Domain\Subsystem\SubsystemDefinition;
use NGTBM\Domain\Subsystem\SubsystemRegistry;

/**
 * Load architecture manifests into the Control Plane registry.
 */
final class ArchitectureLoader {

	public static function architecture_root(): string {
		$filtered = apply_filters( 'ngc_rad_architecture_root', '' );
		if ( is_string( $filtered ) && $filtered !== '' && is_dir( $filtered ) ) {
			return rtrim( $filtered, '/\\' );
		}
		// Monorepo: plugin is NextGenTutors-BeyondMeasure under repo root.
		$candidate = dirname( NGTBM_PLUGIN_DIR, 1 );
		$arch      = $candidate . DIRECTORY_SEPARATOR . 'architecture';
		if ( is_dir( $arch ) ) {
			return $arch;
		}
		$candidate2 = dirname( NGTBM_PLUGIN_DIR, 2 ) . DIRECTORY_SEPARATOR . 'architecture';
		return is_dir( $candidate2 ) ? $candidate2 : $arch;
	}

	public static function hydrate( SubsystemRegistry $registry ): void {
		$root = self::architecture_root();
		$dir  = $root . DIRECTORY_SEPARATOR . 'manifests';
		if ( ! is_dir( $dir ) ) {
			self::seed_builtins( $registry );
			return;
		}
		$enabled = get_option( 'ngtbm_subsystem_enabled', [] );
		if ( ! is_array( $enabled ) ) {
			$enabled = [];
		}
		foreach ( glob( $dir . DIRECTORY_SEPARATOR . '*.json' ) ?: [] as $file ) {
			$raw = file_get_contents( $file );
			if ( ! is_string( $raw ) ) {
				continue;
			}
			$data = json_decode( $raw, true );
			if ( ! is_array( $data ) ) {
				continue;
			}
			$system = (array) ( $data['system'] ?? [] );
			$id     = (string) ( $system['id'] ?? pathinfo( $file, PATHINFO_FILENAME ) );
			$name   = (string) ( $system['name'] ?? $id );
			$provides = array_values( (array) ( $data['capabilities']['provides'] ?? [] ) );
			$consumes = array_values( (array) ( $data['capabilities']['consumes'] ?? [] ) );
			$is_on    = array_key_exists( $id, $enabled ) ? (bool) $enabled[ $id ] : true;
			$registry->register(
				new SubsystemDefinition(
					$id,
					$name,
					self::icon_for( $id ),
					self::category_for( $id ),
					$provides,
					[],
					[],
					[],
					$consumes,
					$provides,
					$is_on ? 'healthy' : 'offline',
					'',
					$is_on
				)
			);
		}
		self::seed_builtins( $registry );
	}

	/**
	 * Ensure Control Plane itself and known admin modules exist.
	 */
	private static function seed_builtins( SubsystemRegistry $registry ): void {
		if ( ! $registry->get( 'beyond-measure' ) ) {
			$registry->register(
				[
					'id'           => 'beyond-measure',
					'name'         => 'Beyond Measure Control Plane',
					'icon'         => 'gauge',
					'category'     => 'Platform',
					'capabilities' => [ 'control.plane.admin' ],
					'status'       => 'healthy',
					'enabled'      => true,
				]
			);
		}
	}

	private static function icon_for( string $id ): string {
		$map = [
			'bridge-talent-intelligence' => 'brain',
			'bridge-memory-tencentdb'    => 'database',
			'companion'                  => 'layers',
			'beyond-measure'             => 'gauge',
			'beyondinfinity'             => 'layout',
		];
		return $map[ $id ] ?? 'cube';
	}

	private static function category_for( string $id ): string {
		if ( str_contains( $id, 'talent' ) || str_contains( $id, 'memory' ) || str_contains( $id, 'ai' ) ) {
			return 'AI & Intelligence';
		}
		if ( in_array( $id, [ 'companion', 'beyond-measure' ], true ) ) {
			return 'Platform';
		}
		return 'Ecosystem';
	}

	/**
	 * @return array{nodes:list<array<string,mixed>>,edges:list<array<string,mixed>>}
	 */
	public static function dependency_graph( SubsystemRegistry $registry ): array {
		$nodes = [];
		$edges = [];
		foreach ( $registry->all() as $sub ) {
			$nodes[] = [
				'id'       => $sub->id,
				'label'    => $sub->name,
				'status'   => $sub->enabled ? $sub->status : 'offline',
				'category' => $sub->category,
				'provides' => $sub->provides,
				'depends'  => $sub->depends_on,
			];
			foreach ( $sub->depends_on as $dep ) {
				$edges[] = [
					'id'     => $sub->id . '->' . $dep,
					'source' => $sub->id,
					'target' => $dep,
					'kind'   => 'consumes',
				];
			}
		}

		$edges_file = self::architecture_root() . DIRECTORY_SEPARATOR . 'dependency-rules' . DIRECTORY_SEPARATOR . 'edges.json';
		if ( is_readable( $edges_file ) ) {
			$raw = json_decode( (string) file_get_contents( $edges_file ), true );
			if ( is_array( $raw ) ) {
				foreach ( (array) ( $raw['allow'] ?? [] ) as $i => $rule ) {
					if ( ! is_array( $rule ) ) {
						continue;
					}
					$from = (string) ( $rule['from'] ?? '' );
					$to   = (string) ( $rule['to'] ?? '' );
					if ( $from === '' || $to === '' ) {
						continue;
					}
					$edges[] = [
						'id'         => 'arch-' . $i . '-' . $from . '-' . $to,
						'source'     => $from,
						'target'     => $to,
						'kind'       => (string) ( $rule['kind'] ?? 'contract' ),
						'capability' => (string) ( $rule['capability'] ?? '' ),
					];
				}
			}
		}

		return [ 'nodes' => $nodes, 'edges' => $edges ];
	}
}
