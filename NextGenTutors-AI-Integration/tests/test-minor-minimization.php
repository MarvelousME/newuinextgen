<?php
/**
 * Minor data minimization: never-send removal and learner pseudonymization.
 */

$payload = [
	'learner_id'     => 'raw-learner-77',
	'student_id'     => 'raw-student-12',
	'sa_id'          => '9901015800088',
	'id_number'      => '9901015800088',
	'address'        => '1 Main Rd',
	'guardian_phone' => '0821111111',
	'grade'          => 8,
	'subjects'       => [ 'maths', 'science' ],
	'nested'         => [ 'child_id' => 'raw-child-5', 'sa_id' => 'nested-said' ],
];

$minimized = NGTAI_Redactor::minimize_minor( $payload );

foreach ( [ 'sa_id', 'id_number', 'address', 'guardian_phone' ] as $forbidden ) {
	ngtai_assert( "never-send key {$forbidden} removed", ! array_key_exists( $forbidden, $minimized ) );
}
ngtai_assert( 'nested never-send key removed', ! array_key_exists( 'sa_id', $minimized['nested'] ) );

ngtai_assert( 'learner_id pseudonymized', 0 === strpos( (string) $minimized['learner_id'], 'learner_' ) && 'raw-learner-77' !== $minimized['learner_id'] );
ngtai_assert( 'student_id pseudonymized', 0 === strpos( (string) $minimized['student_id'], 'learner_' ) );
ngtai_assert( 'nested child_id pseudonymized', 0 === strpos( (string) $minimized['nested']['child_id'], 'learner_' ) );

ngtai_assert( 'hash is deterministic', NGTAI_Redactor::minimize_minor( $payload )['learner_id'] === $minimized['learner_id'] );
ngtai_assert( 'already-pseudonymized id kept stable', $minimized['learner_id'] === NGTAI_Redactor::minimize_minor( [ 'learner_id' => $minimized['learner_id'] ] )['learner_id'] );

ngtai_assert( 'non-sensitive grade preserved', 8 === $minimized['grade'] );
ngtai_assert( 'subjects preserved', [ 'maths', 'science' ] === $minimized['subjects'] );

// Full minor profile through redact(): blocked keys and never-send both apply.
$via_profile = NGTAI_Redactor::redact(
	[ 'learner_id' => 'raw-learner-77', 'password' => 'x', 'address' => 'somewhere' ],
	'minor'
);
ngtai_assert( 'minor profile redacts blocked keys', ! array_key_exists( 'address', $via_profile ) );
ngtai_assert( 'minor profile pseudonymizes learner ids', 0 === strpos( (string) $via_profile['learner_id'], 'learner_' ) );
ngtai_assert( 'minor profile reported as last used', 'minor' === NGTAI_Redactor::last_profile() );
