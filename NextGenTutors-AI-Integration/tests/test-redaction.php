<?php
/**
 * Redaction: blocked keys, minimized PII, and payload allowlists.
 */

$payload = [
	'email'     => 'parent@example.com',
	'phone'     => '0821234567',
	'password'  => 'super-secret',
	'api_key'   => 'sk-123',
	'note'      => 'plain value',
	'nested'    => [
		'token'      => 'abc',
		'first_name' => 'Thandi',
	],
];

$redacted = NGTAI_Redactor::redact( $payload, 'default' );
ngtai_assert( 'password key redacted', '[REDACTED]' === $redacted['password'] );
ngtai_assert( 'api_key key redacted', '[REDACTED]' === $redacted['api_key'] );
ngtai_assert( 'email minimized not raw', 'parent@example.com' !== $redacted['email'] && false !== strpos( (string) $redacted['email'], '@example.com' ) );
ngtai_assert( 'phone minimized', '0821234567' !== $redacted['phone'] );
ngtai_assert( 'non-sensitive value untouched', 'plain value' === $redacted['note'] );
ngtai_assert( 'nested token redacted', '[REDACTED]' === $redacted['nested']['token'] );
ngtai_assert( 'nested first_name minimized', 'Thandi' !== $redacted['nested']['first_name'] );

ngtai_assert( 'unknown profile falls back to default', is_array( NGTAI_Redactor::redact( [ 'password' => 'x' ], 'no-such-profile' ) ) );
ngtai_assert( 'scalar input passes through', 'hello' === NGTAI_Redactor::redact( 'hello' ) );

$allowlisted = NGTAI_Redactor::apply_allowlist(
	'match.proposed',
	[
		'match_id'    => 'M-1',
		'tutor_id'    => 42,
		'score'       => 0.91,
		'explanation' => 'strong subject overlap',
		'status'      => 'proposed',
		'internal_db' => 'must-not-leak',
		'email'       => 'x@y.z',
	]
);
ngtai_assert( 'allowlisted fields kept', isset( $allowlisted['match_id'], $allowlisted['tutor_id'], $allowlisted['score'] ) );
ngtai_assert( 'non-allowlisted field dropped', ! array_key_exists( 'internal_db', $allowlisted ) );
ngtai_assert( 'non-allowlisted email dropped', ! array_key_exists( 'email', $allowlisted ) );

$match = NGTAI_Redactor::apply_allowlist(
	'match.requested',
	[
		'match_id'   => 'M-2',
		'candidates' => [
			[ 'tutor_id' => 1, 'display_name' => 'A', 'verified' => true, 'eligible' => true, 'score' => 0.8, 'secret_notes' => 'x' ],
			[ 'tutor_id' => 2, 'display_name' => 'B', 'verified' => false, 'eligible' => true ],
			[ 'tutor_id' => 3, 'display_name' => 'C', 'verified' => true, 'eligible' => false ],
			'not-an-array',
		],
	]
);
ngtai_assert( 'only verified+eligible candidates kept', 1 === count( $match['candidates'] ) );
ngtai_assert( 'kept candidate is the eligible one', 1 === ( $match['candidates'][0]['tutor_id'] ?? 0 ) );
ngtai_assert( 'candidate fields limited to allowlist', ! array_key_exists( 'secret_notes', $match['candidates'][0] ) );
