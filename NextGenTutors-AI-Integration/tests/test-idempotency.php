<?php
/**
 * Idempotency store: unique-key insert semantics.
 */

$key = 'idem-key-' . hash( 'sha256', 'test-idempotency' );

ngtai_assert( 'new idempotency key is remembered (not seen)', false === NGTAI_Idempotency_Store::seen_or_remember( $key, 'hash-1' ) );
ngtai_assert( 'same idempotency key reported as duplicate', true === NGTAI_Idempotency_Store::seen_or_remember( $key, 'hash-1' ) );
ngtai_assert( 'duplicate regardless of result hash', true === NGTAI_Idempotency_Store::seen_or_remember( $key, 'hash-2' ) );
ngtai_assert( 'different key is remembered independently', false === NGTAI_Idempotency_Store::seen_or_remember( $key . '-b' ) );

// Keys longer than the 191-char column are truncated consistently, so both
// variants land on the same stored key.
$long = str_repeat( 'k', 250 );
ngtai_assert( 'long key remembered after truncation', false === NGTAI_Idempotency_Store::seen_or_remember( $long ) );
ngtai_assert( 'truncation collision detected as duplicate', true === NGTAI_Idempotency_Store::seen_or_remember( substr( $long, 0, 191 ) . 'different-tail' ) );
