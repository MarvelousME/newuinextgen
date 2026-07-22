<?php
/**
 * Agent result versioning: unique (agent_run_id, result_version) semantics.
 */

$contract = static function ( string $run_id, int $version ): NGTAI_Agent_Result {
	return new NGTAI_Agent_Result(
		[
			'agent_run_id'   => $run_id,
			'result_version' => $version,
			'event_id'       => 'evt-version-0001',
			'correlation_id' => 'version-corr-0001',
			'agent_name'     => 'match-agent',
			'action_name'    => 'match.recommendation',
			'status'         => 'succeeded',
			'result'         => [ 'ranking' => [], 'explanation' => 'version ' . $version ],
			'completed_at'   => gmdate( 'c' ),
		]
	);
};

$id_v1 = NGTAI_Result_Repository::insert( $contract( 'run-ver-1', 1 ) );
ngtai_assert( 'first version stored', is_int( $id_v1 ) && $id_v1 > 0 );
ngtai_assert( 'same run and version is duplicate', 'duplicate' === NGTAI_Result_Repository::insert( $contract( 'run-ver-1', 1 ) ) );

$id_v2 = NGTAI_Result_Repository::insert( $contract( 'run-ver-1', 2 ) );
ngtai_assert( 'new version of same run stored', is_int( $id_v2 ) && $id_v2 > 0 && $id_v2 !== $id_v1 );

$row = NGTAI_Result_Repository::find_version( 'run-ver-1', 2 );
ngtai_assert( 'find_version returns matching row', is_array( $row ) && 2 === (int) $row['result_version'] );
ngtai_assert( 'find_version hydrates result json', 'version 2' === ( $row['result']['explanation'] ?? '' ) );
ngtai_assert( 'find_version misses unknown version', null === NGTAI_Result_Repository::find_version( 'run-ver-1', 9 ) );

// Array-shaped store() path preserves dotted action names.
$id_arr = NGTAI_Result_Repository::store(
	[
		'agent_run_id'   => 'run-ver-arr-1',
		'result_version' => 1,
		'event_id'       => 'evt-version-0002',
		'correlation_id' => 'version-corr-0002',
		'agent_name'     => 'match-agent',
		'action_name'    => 'match.recommendation',
		'status'         => 'received',
		'result'         => [ 'note' => 'array path' ],
	]
);
ngtai_assert( 'array store path inserts', is_int( $id_arr ) && $id_arr > 0 );
$arr_row = NGTAI_Result_Repository::find_version( 'run-ver-arr-1', 1 );
ngtai_assert( 'array store preserves dotted action name', 'match.recommendation' === ( $arr_row['action_name'] ?? '' ) );

ngtai_assert( 'mark_applied stamps row', true === NGTAI_Result_Repository::mark_applied( (int) $id_v2 ) );
$applied = NGTAI_Result_Repository::find_version( 'run-ver-1', 2 );
ngtai_assert( 'applied_at recorded', ! empty( $applied['applied_at'] ) );
