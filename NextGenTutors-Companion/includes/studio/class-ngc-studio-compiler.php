<?php
/**
 * Compiles visual workflow graphs into executable plans.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates and compiles React-Flow style graphs.
 */
class NGC_Studio_Compiler {

	/**
	 * @param array{nodes:array,edges:array} $graph Workflow graph.
	 * @return array{ok:bool,compiled?:array<string,mixed>,errors?:array<int,string>}
	 */
	public static function compile( $graph ) {
		$nodes = (array) ( $graph['nodes'] ?? [] );
		$edges = (array) ( $graph['edges'] ?? [] );
		$errors = [];

		if ( empty( $nodes ) ) {
			return [ 'ok' => false, 'errors' => [ __( 'Workflow has no nodes.', 'nextgencompanion' ) ] ];
		}

		$by_id = [];
		foreach ( $nodes as $node ) {
			if ( empty( $node['id'] ) ) {
				$errors[] = __( 'Node missing id.', 'nextgencompanion' );
				continue;
			}
			$by_id[ (string) $node['id'] ] = $node;
		}

		$starts = array_filter( $nodes, static fn( $n ) => self::node_type( $n ) === 'START' );
		$ends   = array_filter( $nodes, static fn( $n ) => self::node_type( $n ) === 'END' );
		if ( empty( $starts ) ) {
			$errors[] = __( 'Workflow requires a START node.', 'nextgencompanion' );
		}
		if ( empty( $ends ) ) {
			$errors[] = __( 'Workflow requires an END node.', 'nextgencompanion' );
		}

		$adjacency = [];
		$edge_meta = [];
		foreach ( $edges as $edge ) {
			$src = (string) ( $edge['source'] ?? '' );
			$tgt = (string) ( $edge['target'] ?? '' );
			if ( ! $src || ! $tgt || empty( $by_id[ $src ] ) || empty( $by_id[ $tgt ] ) ) {
				$errors[] = __( 'Invalid edge connection.', 'nextgencompanion' );
				continue;
			}
			$handle = strtolower( (string) ( $edge['sourceHandle'] ?? '' ) );
			$label  = strtolower( (string) ( $edge['label'] ?? ( is_array( $edge['data'] ?? null ) ? ( $edge['data']['label'] ?? '' ) : '' ) ) );
			$adjacency[ $src ][] = $tgt;
			$edge_meta[ $src ][] = [
				'target' => $tgt,
				'handle' => $handle,
				'label'  => $label,
				'id'     => (string) ( $edge['id'] ?? '' ),
			];
		}

		if ( $errors ) {
			return [ 'ok' => false, 'errors' => array_values( array_unique( $errors ) ) ];
		}

		$start_id = (string) ( array_values( $starts )[0]['id'] ?? '' );
		$plan     = [];
		$visited  = [];
		self::walk( $start_id, $by_id, $adjacency, $plan, $visited );

		$triggers = self::extract_triggers( $nodes );

		$compiled = [
			'version'     => 1,
			'start'       => $start_id,
			'plan'        => $plan,
			'triggers'    => $triggers,
			'node_map'    => $by_id,
			'adjacency'   => $adjacency,
			'edge_meta'   => $edge_meta,
			'compiled_at' => current_time( 'mysql', true ),
		];

		$compiled = apply_filters( 'ngc_studio_compiled_workflow', $compiled, $graph );

		return [ 'ok' => true, 'compiled' => $compiled ];
	}

	/**
	 * @param string $node_id Node ID.
	 * @param array<string, array<string, mixed>> $by_id Nodes.
	 * @param array<string, array<int, string>>   $adjacency Edges.
	 * @param array<int, array<string, mixed>>    $plan Output plan.
	 * @param array<string, bool>                 $visited Visited.
	 */
	private static function walk( $node_id, $by_id, $adjacency, &$plan, &$visited ) {
		if ( isset( $visited[ $node_id ] ) ) {
			return;
		}
		$visited[ $node_id ] = true;
		$node = $by_id[ $node_id ] ?? null;
		if ( ! $node ) {
			return;
		}
		$type = self::node_type( $node );
		if ( 'START' !== $type ) {
			$plan[] = [
				'id'     => $node_id,
				'type'   => $type,
				'config' => (array) ( $node['data'] ?? [] ),
			];
		}
		foreach ( (array) ( $adjacency[ $node_id ] ?? [] ) as $next ) {
			self::walk( $next, $by_id, $adjacency, $plan, $visited );
		}
	}

	/**
	 * @param array<int, array<string, mixed>> $nodes Nodes.
	 * @return array<int, array<string, mixed>>
	 */
	private static function extract_triggers( $nodes ) {
		$triggers = [];
		foreach ( $nodes as $node ) {
			$type = self::node_type( $node );
			if ( ! in_array( $type, [ 'EVENT', 'START' ], true ) ) {
				continue;
			}
			$data = (array) ( $node['data'] ?? [] );
			$key  = sanitize_key( (string) ( $data['trigger'] ?? $data['event'] ?? '' ) );
			if ( $key ) {
				$triggers[] = [
					'key'    => $key,
					'type'   => 'event',
					'node'   => (string) $node['id'],
					'config' => $data,
				];
			}
		}
		return $triggers;
	}

	/**
	 * @param array<string, mixed> $node Node.
	 * @return string
	 */
	private static function node_type( $node ) {
		return strtoupper( sanitize_key( (string) ( $node['type'] ?? $node['data']['type'] ?? '' ) ) );
	}
}
