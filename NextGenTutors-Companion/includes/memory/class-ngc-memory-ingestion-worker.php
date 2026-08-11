<?php
/**
 * Async memory write consumer (durable queue type memory.ingest).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queue handler for memory writes.
 */
final class NGC_Memory_Ingestion_Worker {

	/**
	 * @param mixed                $result  Prior filter result.
	 * @param array<string,mixed>  $payload Queue payload.
	 * @param object|null          $msg     Queue message.
	 * @return true|WP_Error
	 */
	public static function handle( $result, $payload, $msg = null ) {
		unset( $result, $msg );
		if ( ! is_array( $payload ) ) {
			return new WP_Error( 'ngc_memory_ingest', 'Invalid payload.' );
		}
		$inner = isset( $payload['payload'] ) && is_array( $payload['payload'] ) ? $payload['payload'] : $payload;
		$inner['async'] = false;

		$classification = (string) ( $payload['classification'] ?? NGC_Memory_Service::classify( $inner ) );
		$gate           = NGC_Memory_Service::write_policy_gate( $classification, $inner );
		if ( empty( $gate['allow'] ) ) {
			// Ack success without write — policy denial is not a retryable failure.
			return true;
		}

		$out = NGC_Memory_Service::write_now( $inner );
		if ( is_wp_error( $out ) ) {
			return $out;
		}
		return true;
	}
}
