<?php
/**
 * Nonce replay protection: claim() returns true once, 'duplicate' after.
 */

global $wpdb, $ngtai_test_transients;

$nonce = 'replay-nonce-' . str_repeat( 'a', 24 );

$first = NGTAI_Nonce_Store::claim( $nonce, 'req-1', '/wp-json/ngtai/v1/callbacks/agent-result' );
ngtai_assert( 'first nonce claim returns true', true === $first );

$second = NGTAI_Nonce_Store::claim( $nonce, 'req-2', '/wp-json/ngtai/v1/callbacks/agent-result' );
ngtai_assert( 'replayed nonce returns duplicate', 'duplicate' === $second );

// Clear the transient cache layer; the durable unique row must still block replay.
unset( $ngtai_test_transients[ 'ngtai_nonce_' . md5( $nonce ) ] );
$third = NGTAI_Nonce_Store::claim( $nonce, 'req-3', '/wp-json/ngtai/v1/callbacks/agent-result' );
ngtai_assert( 'replay blocked by durable store after cache flush', 'duplicate' === $third );

$other = NGTAI_Nonce_Store::claim( 'replay-nonce-' . str_repeat( 'b', 24 ), 'req-4', '/wp-json/ngtai/v1/callbacks/agent-result' );
ngtai_assert( 'distinct nonce claims independently', true === $other );

// Expired durable rows are purged; live ones survive.
$wpdb->insert(
	NGTAI_Database::table( 'callback_nonces' ),
	[
		'nonce'      => 'replay-nonce-expired-000000001',
		'expires_at' => '2000-01-01 00:00:00',
		'created_at' => '2000-01-01 00:00:00',
	]
);
$purged = NGTAI_Nonce_Store::purge_expired();
ngtai_assert( 'purge removes only expired nonce rows', 1 === $purged );
ngtai_assert( 'live nonce still blocked after purge', 'duplicate' === NGTAI_Nonce_Store::claim( $nonce, 'req-5', '/x' ) );
