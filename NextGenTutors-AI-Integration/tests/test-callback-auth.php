<?php
/**
 * Callback authentication reject paths and governed result handling.
 */

global $wpdb;

$secret = NGTAI_Config::secret();
$body   = '{"subject_id":"MATCH-001","explanation":"Ranked by subject fit"}';
$path   = '/wp-json/ngtai/v1/callbacks/agent-result';

$signed_headers = static function ( string $nonce, ?int $timestamp = null, ?string $key_id = null ) use ( $body, $path, $secret ): array {
	$timestamp = $timestamp ?? time();
	$digest    = NGTAI_Signature::body_sha256( $body );
	$canonical = NGTAI_Signature::canonical( (string) $timestamp, $nonce, 'POST', $path, $digest );
	return [
		'x-ngt-timestamp'      => (string) $timestamp,
		'x-ngt-nonce'          => $nonce,
		'x-ngt-key-id'         => $key_id ?? NGTAI_Config::key_id(),
		'x-ngt-correlation-id' => 'callback-auth-correlation-0001',
		'idempotency-key'      => 'callback-auth-idem-0001',
		'x-ngt-body-sha256'    => $digest,
		'x-ngt-signature'      => 'v1=' . NGTAI_Signature::sign( $canonical, $secret ),
	];
};

$missing = $signed_headers( 'callback-auth-missing-0001' );
unset( $missing['x-ngt-signature'] );
ngtai_assert( 'missing callback signature rejected', 'ngtai_missing_header' === ngtai_error_code( NGTAI_Signature::verify( 'POST', $path, $body, $missing ) ) );

$stale = $signed_headers( 'callback-auth-stale-0001', time() - NGTAI_Config::skew() - 1 );
ngtai_assert( 'stale callback timestamp rejected', 'ngtai_timestamp_skew' === ngtai_error_code( NGTAI_Signature::verify( 'POST', $path, $body, $stale ) ) );

$digest_headers = $signed_headers( 'callback-auth-digest-0001' );
ngtai_assert( 'callback body digest tamper rejected', 'ngtai_body_digest_mismatch' === ngtai_error_code( NGTAI_Signature::verify( 'POST', $path, $body . ' ', $digest_headers ) ) );

$unknown_key = $signed_headers( 'callback-auth-keyid-0001', null, 'wrong-key-id' );
ngtai_assert( 'callback unknown key id rejected', 'ngtai_unknown_key' === ngtai_error_code( NGTAI_Signature::verify( 'POST', $path, $body, $unknown_key ) ) );

$forged                    = $signed_headers( 'callback-auth-signature-0001' );
$forged['x-ngt-signature'] = 'v1=' . str_repeat( '0', 64 );
ngtai_assert( 'callback invalid HMAC rejected', 'ngtai_signature_mismatch' === ngtai_error_code( NGTAI_Signature::verify( 'POST', $path, $body, $forged ) ) );

/*
 * Governed agent-result handling (post-authentication controller behaviour).
 */
$result_body = static function ( string $run_id, array $overrides = [] ): array {
	return array_merge(
		[
			'agent_run_id'   => $run_id,
			'result_version' => 1,
			'event_id'       => 'evt-callback-0001',
			'correlation_id' => 'callback-corr-0001',
			'agent_name'     => 'match-agent',
			'action_name'    => 'match.recommendation',
			'status'         => 'succeeded',
			'subject_id'     => 'MATCH-001',
			'result'         => [
				'ranking'     => [ [ 'tutor_id' => 7, 'score' => 0.93, 'eligible' => true ] ],
				'explanation' => 'Ranked by subject fit and availability.',
			],
			'completed_at'   => gmdate( 'c' ),
		],
		$overrides
	);
};
$headers = [ 'x-ngt-correlation-id' => 'callback-corr-0001' ];

$applied = NGTAI_Callback_Controller::handle_agent_result( $result_body( 'run-cb-accept-1' ), $headers );
ngtai_assert( 'valid recommendation accepted', is_array( $applied ) && true === $applied['success'] );
ngtai_assert( 'valid recommendation applied', 'applied' === ( $applied['status'] ?? '' ) );

$stored = NGTAI_Result_Repository::find_version( 'run-cb-accept-1', 1 );
ngtai_assert( 'dotted action name preserved in storage', 'match.recommendation' === ( $stored['action_name'] ?? '' ) );

$duplicate = NGTAI_Callback_Controller::handle_agent_result( $result_body( 'run-cb-accept-1' ), $headers );
ngtai_assert( 'replayed result is idempotent', is_array( $duplicate ) && ! empty( $duplicate['idempotent'] ) );

$invalid = NGTAI_Callback_Controller::handle_agent_result( [ 'agent_run_id' => 'run-cb-invalid-1' ], $headers );
ngtai_assert( 'contract-invalid result rejected 422', 'ngtai_invalid_result' === ngtai_error_code( $invalid ) );

$ineligible = NGTAI_Callback_Controller::handle_agent_result(
	$result_body( 'run-cb-inel-1', [ 'result' => [ 'ranking' => [ [ 'tutor_id' => 9, 'eligible' => false ] ], 'explanation' => 'x' ] ] ),
	$headers
);
ngtai_assert( 'ineligible candidate rejected', 'ngtai_ineligible_candidate' === ngtai_error_code( $ineligible ) );

$unexplained = NGTAI_Callback_Controller::handle_agent_result(
	$result_body( 'run-cb-noexp-1', [ 'result' => [ 'ranking' => [ [ 'tutor_id' => 9, 'eligible' => true ] ] ] ] ),
	$headers
);
ngtai_assert( 'ranking without explanation rejected', 'ngtai_explanation_required' === ngtai_error_code( $unexplained ) );

$prohibited = NGTAI_Callback_Controller::handle_agent_result(
	$result_body( 'run-cb-payout-1', [ 'action_name' => 'finance.payout.release', 'result' => [ 'note' => 'attempted execution' ] ] ),
	$headers
);
ngtai_assert( 'prohibited execution action denied by policy', 'ngtai_policy_denied' === ngtai_error_code( $prohibited ) );

$forbidden_mutation = NGTAI_Callback_Controller::handle_agent_result(
	$result_body( 'run-cb-mutate-1', [ 'result' => [ 'approve_tutor' => true, 'explanation' => 'x' ] ] ),
	$headers
);
ngtai_assert( 'forbidden domain mutation rejected as invalid', 'ngtai_invalid_result' === ngtai_error_code( $forbidden_mutation ) );
