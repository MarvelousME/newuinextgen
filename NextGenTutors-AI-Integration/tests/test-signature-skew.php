<?php
/**
 * Timestamp skew enforcement.
 */

$secret = NGTAI_Config::secret();
$path   = '/wp-json/ngtai/v1/callbacks/agent-result';
$body   = '{"subject_id":"MATCH-200"}';
$skew   = NGTAI_Config::skew();

$signed_at = static function ( int $timestamp, string $nonce ) use ( $body, $path, $secret ): array {
	$digest    = NGTAI_Signature::body_sha256( $body );
	$canonical = NGTAI_Signature::canonical( (string) $timestamp, $nonce, 'POST', $path, $digest );
	return [
		'x-ngt-timestamp'   => (string) $timestamp,
		'x-ngt-nonce'       => $nonce,
		'x-ngt-key-id'      => NGTAI_Config::key_id(),
		'x-ngt-body-sha256' => $digest,
		'x-ngt-signature'   => 'v1=' . NGTAI_Signature::sign( $canonical, $secret ),
	];
};

ngtai_assert( 'configured skew within sane bounds', $skew >= 30 && $skew <= 900 );

$result = NGTAI_Signature::verify( 'POST', $path, $body, $signed_at( time(), 'skew-current-000000000000001' ) );
ngtai_assert( 'current timestamp accepted', true === $result );

$result = NGTAI_Signature::verify( 'POST', $path, $body, $signed_at( time() - ( $skew - 10 ), 'skew-within-0000000000000001' ) );
ngtai_assert( 'timestamp just within skew accepted', true === $result );

$result = NGTAI_Signature::verify( 'POST', $path, $body, $signed_at( time() - ( $skew + 5 ), 'skew-stale-00000000000000001' ) );
ngtai_assert( 'stale timestamp rejected', 'ngtai_timestamp_skew' === ngtai_error_code( $result ) );

$result = NGTAI_Signature::verify( 'POST', $path, $body, $signed_at( time() + ( $skew + 5 ), 'skew-future-0000000000000001' ) );
ngtai_assert( 'future timestamp rejected', 'ngtai_timestamp_skew' === ngtai_error_code( $result ) );

$malformed                    = $signed_at( time(), 'skew-malformed-0000000000001' );
$malformed['x-ngt-timestamp'] = 'not-a-number';
$result                       = NGTAI_Signature::verify( 'POST', $path, $body, $malformed );
ngtai_assert( 'non-numeric timestamp rejected', 'ngtai_invalid_timestamp' === ngtai_error_code( $result ) );
