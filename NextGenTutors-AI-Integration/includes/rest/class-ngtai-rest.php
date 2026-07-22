<?php
/**
 * REST security helpers.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class NGTAI_Rest {
	/**
	 * Return the exact bytes signed by the caller.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return string
	 */
	protected static function read_raw_body( WP_REST_Request $request ) {
		return (string) $request->get_body();
	}

	/**
	 * Verify signature then atomically claim replay nonce.
	 *
	 * The guard runs first inside each callback because WP REST permission
	 * callbacks are not a reliable place to coordinate raw-body idempotency.
	 *
	 * @param WP_REST_Request $request Request.
	 * @param string          $path    Canonical path.
	 * @return true|'duplicate'|WP_Error
	 */
	protected static function machine_guard( WP_REST_Request $request, $path ) {
		$raw     = self::read_raw_body( $request );
		$headers = NGTAI_Signature::normalize_headers( $request->get_headers() );
		$valid   = NGTAI_Signature::verify( 'POST', $path, $raw, $headers );
		if ( is_wp_error( $valid ) ) {
			NGTAI_Logger::bump( 'ngtai_signature_failure_total' );
			NGTAI_Audit::log( 'ngtai_signature_failed', [ 'error_code' => $valid->get_error_code(), 'path' => $path ], (string) ( $headers['x-ngt-correlation-id'] ?? '' ) );
			return $valid;
		}
		$claim = NGTAI_Nonce_Store::claim(
			(string) ( $headers['x-ngt-nonce'] ?? '' ),
			(string) ( $headers['x-ngt-request-id'] ?? '' ),
			$path
		);
		if ( 'duplicate' === $claim ) {
			NGTAI_Logger::bump( 'ngtai_duplicate_event_total' );
			NGTAI_Audit::log( 'ngtai_callback_replayed', [ 'path' => $path ], (string) ( $headers['x-ngt-correlation-id'] ?? '' ) );
		}
		return $claim;
	}

	/**
	 * Cookie-authenticated admin authorization.
	 *
	 * @return true|WP_Error
	 */
	public static function admin_guard() {
		return NGTAI_Access::rest_can_manage() ? true : new WP_Error( 'ngtai_forbidden', __( 'Insufficient permission.', 'nextgentutors-ai-integration' ), [ 'status' => 403 ] );
	}
}
