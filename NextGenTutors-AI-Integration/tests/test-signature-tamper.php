<?php
/**
 * Signature tamper detection: body, path, method, key id, HMAC.
 */

$secret = NGTAI_Config::secret();
$path   = '/wp-json/ngtai/v1/callbacks/agent-result';
$body   = '{"subject_id":"MATCH-100","explanation":"fit"}';

$signed = static function ( string $nonce, ?string $key_id = null, bool $with_digest = true ) use ( $body, $path, $secret ): array {
	$timestamp = (string) time();
	$digest    = NGTAI_Signature::body_sha256( $body );
	$canonical = NGTAI_Signature::canonical( $timestamp, $nonce, 'POST', $path, $digest );
	$headers   = [
		'x-ngt-timestamp' => $timestamp,
		'x-ngt-nonce'     => $nonce,
		'x-ngt-key-id'    => $key_id ?? NGTAI_Config::key_id(),
		'x-ngt-signature' => 'v1=' . NGTAI_Signature::sign( $canonical, $secret ),
	];
	if ( $with_digest ) {
		$headers['x-ngt-body-sha256'] = $digest;
	}
	return $headers;
};

$result = NGTAI_Signature::verify( 'POST', $path, $body, $signed( 'tamper-baseline-000000000001' ) );
ngtai_assert( 'untampered request verifies', true === $result );

$result = NGTAI_Signature::verify( 'POST', $path, $body . 'x', $signed( 'tamper-body-0000000000000001' ) );
ngtai_assert( 'tampered body rejected as digest mismatch', 'ngtai_body_digest_mismatch' === ngtai_error_code( $result ) );

$result = NGTAI_Signature::verify( 'POST', $path, $body . 'x', $signed( 'tamper-body-0000000000000002', null, false ) );
ngtai_assert( 'tampered body without digest header rejected as signature mismatch', 'ngtai_signature_mismatch' === ngtai_error_code( $result ) );

$result = NGTAI_Signature::verify( 'POST', '/wp-json/ngtai/v1/callbacks/approval-request', $body, $signed( 'tamper-path-0000000000000001' ) );
ngtai_assert( 'tampered path rejected', 'ngtai_signature_mismatch' === ngtai_error_code( $result ) );

$result = NGTAI_Signature::verify( 'PUT', $path, $body, $signed( 'tamper-method-00000000000001' ) );
ngtai_assert( 'tampered method rejected', 'ngtai_signature_mismatch' === ngtai_error_code( $result ) );

$result = NGTAI_Signature::verify( 'POST', $path, $body, $signed( 'tamper-keyid-000000000000001', 'wrong-key-id' ) );
ngtai_assert( 'unknown key id rejected', 'ngtai_unknown_key' === ngtai_error_code( $result ) );

$forged                    = $signed( 'tamper-hmac-0000000000000001' );
$forged['x-ngt-signature'] = 'v1=' . str_repeat( '0', 64 );
$result                    = NGTAI_Signature::verify( 'POST', $path, $body, $forged );
ngtai_assert( 'forged HMAC rejected', 'ngtai_signature_mismatch' === ngtai_error_code( $result ) );

$missing = $signed( 'tamper-missing-0000000000001' );
unset( $missing['x-ngt-signature'] );
$result = NGTAI_Signature::verify( 'POST', $path, $body, $missing );
ngtai_assert( 'missing signature header rejected', 'ngtai_missing_header' === ngtai_error_code( $result ) );
