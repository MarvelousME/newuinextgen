<?php
/**
 * Human approval REST routes.
 *
 * @package NextGenTutorsAIIntegration
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
final class NGTAI_Rest_Approvals extends NGTAI_Rest {
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register' ] );
	}
	public static function register() {
		register_rest_route(
			'ngtai/v1',
			'/approvals/(?P<id>[A-Za-z0-9._-]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'get' ],
					'permission_callback' => [ __CLASS__, 'admin_guard' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'decide' ],
					'permission_callback' => [ __CLASS__, 'admin_guard' ],
				],
			]
		);
	}
	public static function get( WP_REST_Request $request ) {
		global $wpdb;
		$table = NGTAI_Database::table( 'approvals' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE approval_id=%s", sanitize_text_field( $request['id'] ) ), ARRAY_A );
		if ( ! $row ) {
			return new WP_Error( 'ngtai_not_found', __( 'Approval not found.', 'nextgentutors-ai-integration' ), [ 'status' => 404 ] );
		}
		return rest_ensure_response( NGTAI_Logger::scrub( $row ) );
	}
	public static function decide( WP_REST_Request $request ) {
		$decision = sanitize_key( (string) $request->get_param( 'decision' ) );
		$reason   = sanitize_textarea_field( (string) $request->get_param( 'reason' ) );
		if ( ! in_array( $decision, [ 'approve', 'deny' ], true ) ) {
			return new WP_Error( 'ngtai_bad_decision', __( 'Decision must be approve or deny.', 'nextgentutors-ai-integration' ), [ 'status' => 422 ] );
		}
		return rest_ensure_response( NGTAI_Callback_Controller::decide_approval( sanitize_text_field( $request['id'] ), 'approve' === $decision, $reason, get_current_user_id() ) );
	}
}
