<?php
/**
 * Staging-only Agent Gateway bridge constants for local Docker.
 * Loaded as mu-plugin — no secrets committed; reads from getenv.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'NGT_AGENT_GATEWAY_URL' ) ) {
	$url = getenv( 'NGT_AGENT_GATEWAY_URL' );
	if ( ! $url ) {
		// Prefer Compose service DNS; fall back to Docker Desktop host bridge.
		$url = 'http://agent-gateway:8787';
	}
	define( 'NGT_AGENT_GATEWAY_URL', $url );
}
if ( ! defined( 'NGT_GATEWAY_SHARED_SECRET' ) ) {
	$secret = getenv( 'NGT_GATEWAY_SHARED_SECRET' );
	if ( ! $secret ) {
		$secret = 'staging-local-secret';
	}
	define( 'NGT_GATEWAY_SHARED_SECRET', $secret );
}
