<?php
/**
 * Canonical string, signing, and round-trip verification (v1.1 contract).
 *
 * Canonical: timestamp \n nonce \n METHOD \n path \n sha256(body)
 */

$secret = NGTAI_Config::secret();
$method = 'POST';
$path   = '/v1/events';
$ts     = '1784584800';
$nonce  = '11111111-2222-3333-4444-555555555555';
$body   = '{"event_type":"match.requested"}';
$hash   = NGTAI_Signature::body_sha256( $body );

ngtai_assert( 'body sha256 is lowercase hex', 1 === preg_match( '/^[a-f0-9]{64}$/', $hash ) );
ngtai_assert( 'body sha256 matches php hash()', hash( 'sha256', $body ) === $hash );

$canonical = NGTAI_Signature::canonical( $ts, $nonce, $method, $path, $hash );
ngtai_assert( 'canonical string deterministic', $canonical === $ts . "\n" . $nonce . "\nPOST\n" . $path . "\n" . $hash );
ngtai_assert( 'canonical uppercases method', NGTAI_Signature::canonical( $ts, $nonce, 'post', $path, $hash ) === $canonical );
ngtai_assert( 'canonical lowercases digest', NGTAI_Signature::canonical( $ts, $nonce, $method, $path, strtoupper( $hash ) ) === $canonical );

$sig = NGTAI_Signature::sign( $canonical, $secret );
ngtai_assert( 'signature is 64 hex chars', 1 === preg_match( '/^[a-f0-9]{64}$/', $sig ) );
ngtai_assert( 'signature equals hash_hmac sha256', hash_hmac( 'sha256', $canonical, $secret ) === $sig );
ngtai_assert( 'signatures_match accepts identical', NGTAI_Signature::signatures_match( $sig, $sig ) );
ngtai_assert( 'signatures_match rejects empty', ! NGTAI_Signature::signatures_match( $sig, '' ) );

$headers = NGTAI_Signature::build_headers(
	'POST',
	'/wp-json/ngtai/v1/callbacks/agent-result',
	$body,
	[ 'idempotency_key' => 'evt:01TESTEVENT', 'correlation_id' => 'corr-1' ]
);
ngtai_assert( 'build_headers includes timestamp', isset( $headers['X-NGT-Timestamp'] ) );
ngtai_assert( 'build_headers includes nonce', isset( $headers['X-NGT-Nonce'] ) );
ngtai_assert( 'build_headers signature has v1 prefix', 0 === strpos( (string) $headers['X-NGT-Signature'], 'v1=' ) );
ngtai_assert( 'build_headers includes key id', ( $headers['X-NGT-Key-Id'] ?? '' ) === NGTAI_Config::key_id() );
ngtai_assert( 'build_headers includes body digest', ( $headers['X-NGT-Body-SHA256'] ?? '' ) === $hash );
ngtai_assert( 'build_headers preserves idempotency key', ( $headers['Idempotency-Key'] ?? '' ) === 'evt:01TESTEVENT' );
ngtai_assert( 'build_headers preserves correlation id', ( $headers['X-NGT-Correlation-ID'] ?? '' ) === 'corr-1' );

$verify_headers = [];
foreach ( $headers as $name => $value ) {
	$verify_headers[ strtolower( $name ) ] = $value;
}
ngtai_assert(
	'round-trip verify accepts built headers',
	true === NGTAI_Signature::verify( 'POST', '/wp-json/ngtai/v1/callbacks/agent-result', $body, $verify_headers )
);

$bare                    = $verify_headers;
$bare['x-ngt-signature'] = substr( (string) $bare['x-ngt-signature'], 3 );
ngtai_assert(
	'verify accepts signature without v1 prefix',
	true === NGTAI_Signature::verify( 'POST', '/wp-json/ngtai/v1/callbacks/agent-result', $body, $bare )
);
