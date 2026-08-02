<?php
/**
 * AJAX endpoints for Mission Control (status refresh).
 *
 * @package NextGenTutorsMissionControl
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin AJAX.
 */
final class NGTMC_Ajax {

	public static function init() {
		add_action( 'wp_ajax_ngtmc_snapshot', [ __CLASS__, 'snapshot' ] );
	}

	public static function snapshot() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		check_ajax_referer( 'ngtmc_ajax', 'nonce' );
		wp_send_json_success( NGTMC_Orchestrator::snapshot() );
	}
}
