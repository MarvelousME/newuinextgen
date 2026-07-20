<?php
/**
 * REST API for platform services — gamification, export, audit, diagnostics.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Platform service endpoints.
 */
class NGC_Rest_Platform_Services {

	/**
	 * Register routes.
	 */
	public static function register() {
		// Gamification.
		register_rest_route( NGC_Rest::NAMESPACE, '/platform/gamification/scorecard', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ __CLASS__, 'scorecard' ],
			'permission_callback' => [ 'NGC_Rest', 'require_login' ],
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/platform/gamification/leaderboard/(?P<board>[a-z_]+)', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ __CLASS__, 'leaderboard' ],
			'permission_callback' => [ __CLASS__, 'permission_public_leaderboard' ],
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/platform/gamification/achievements', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ __CLASS__, 'achievements' ],
			'permission_callback' => [ 'NGC_Rest', 'require_login' ],
		] );

		// Export.
		register_rest_route( NGC_Rest::NAMESPACE, '/platform/export', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ __CLASS__, 'export' ],
			'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/platform/export/datasets', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ __CLASS__, 'export_datasets' ],
			'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/platform/export/jobs/(?P<id>\d+)', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ __CLASS__, 'export_job' ],
			'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );

		// Audit.
		register_rest_route( NGC_Rest::NAMESPACE, '/platform/audit', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ __CLASS__, 'audit_search' ],
			'permission_callback' => [ __CLASS__, 'can_view_audit' ],
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/platform/audit/timeline/(?P<user_id>\d+)', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ __CLASS__, 'audit_timeline' ],
			'permission_callback' => [ __CLASS__, 'can_view_audit' ],
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/platform/audit/object/(?P<type>[a-z_]+)/(?P<id>\d+)', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ __CLASS__, 'audit_object' ],
			'permission_callback' => [ __CLASS__, 'can_view_audit' ],
		] );

		// Diagnostics.
		register_rest_route( NGC_Rest::NAMESPACE, '/platform/diagnostics/scan', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ __CLASS__, 'diagnostics_scan' ],
			'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/platform/diagnostics/ai', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ __CLASS__, 'diagnostics_ai' ],
			'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/platform/diagnostics/repair', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ __CLASS__, 'diagnostics_repair' ],
			'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
		] );
		register_rest_route( NGC_Rest::NAMESPACE, '/platform/diagnostics/ai-settings', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'ai_settings_get' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'ai_settings_save' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			],
		] );
	}

	/**
	 * @return bool
	 */
	public static function can_view_audit() {
		return current_user_can( 'ngc_view_audit' ) || current_user_can( 'manage_options' );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function scorecard( $request ) {
		$user_id = get_current_user_id();
		return new WP_REST_Response( [ 'success' => true, 'data' => NGC_Gamification::scorecard( $user_id ) ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function leaderboard( $request ) {
		$board  = sanitize_key( $request['board'] );
		$period = sanitize_key( $request->get_param( 'period' ) ?: 'all_time' );
		$limit  = (int) ( $request->get_param( 'limit' ) ?: 50 );
		$rows   = NGC_Leaderboard_Engine::get( $board, $period, $limit );
		if ( empty( $rows ) ) {
			$rows = NGC_Leaderboard_Engine::compute( $board, $period, $limit );
		}
		return new WP_REST_Response( [ 'success' => true, 'data' => $rows, 'board' => $board, 'period' => $period ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function achievements( $request ) {
		$user_id = get_current_user_id();
		return new WP_REST_Response( [
			'success' => true,
			'data'    => NGC_Achievement_Engine::history( $user_id, (int) ( $request->get_param( 'limit' ) ?: 50 ) ),
		], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function export( $request ) {
		$params = $request->get_json_params() ?: $request->get_params();
		if ( ! empty( $params['background'] ) ) {
			$job_id = NGC_Export_Scheduler::queue( $params );
			return new WP_REST_Response( [ 'success' => true, 'job_id' => $job_id, 'status' => 'queued' ], 202 );
		}
		$result = NGC_Export_Engine::run_export( $params );
		return new WP_REST_Response( $result, ! empty( $result['success'] ) ? 200 : 400 );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function export_datasets() {
		return new WP_REST_Response( [ 'success' => true, 'datasets' => NGC_Export_Engine::datasets() ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function export_job( $request ) {
		global $wpdb;
		$table = NGC_Database::table( 'export_jobs' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$job = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $request['id'] ), ARRAY_A );
		return new WP_REST_Response( [ 'success' => (bool) $job, 'data' => $job ], $job ? 200 : 404 );
	}

	/**
	 * PUBLIC_SAFE — aggregated leaderboard scores only (throttled).
	 *
	 * @return bool|WP_Error
	 */
	public static function permission_public_leaderboard() {
		return NGC_Rest::public_throttled( 'rest_leaderboard', 60, 600 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function audit_search( $request ) {
		$args = $request->get_params();
		return new WP_REST_Response( [
			'success' => true,
			'data'    => NGC_Audit_Service::search( $args ),
		], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function audit_timeline( $request ) {
		return new WP_REST_Response( [
			'success' => true,
			'data'    => NGC_Audit_Service::user_timeline( (int) $request['user_id'] ),
		], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function audit_object( $request ) {
		return new WP_REST_Response( [
			'success' => true,
			'data'    => NGC_Audit_Service::object_history( sanitize_key( $request['type'] ), (int) $request['id'] ),
		], 200 );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function diagnostics_scan() {
		return new WP_REST_Response( [ 'success' => true, 'data' => NGC_Health_Scanner::full_scan() ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function diagnostics_ai( $request ) {
		$include = ! isset( $request['include_ai'] ) || $request['include_ai'];
		return new WP_REST_Response( [ 'success' => true, 'data' => NGC_Ai_Diagnostics::diagnose( (bool) $include ) ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function diagnostics_repair( $request ) {
		$params = $request->get_json_params() ?: $request->get_params();
		$result = NGC_Repair_Engine::execute( [
			'dry_run'  => ! empty( $params['dry_run'] ),
			'approved' => ! empty( $params['approved'] ),
		] );
		return new WP_REST_Response( [ 'success' => true, 'data' => $result ], 200 );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function ai_settings_get() {
		$settings = NGC_Ai_Provider_Registry::get_settings();
		return new WP_REST_Response( [
			'success'   => true,
			'settings'  => $settings,
			'providers' => NGC_Ai_Provider_Registry::providers(),
			'ai_suite'  => admin_url( 'admin.php?page=ngc-ai-suite' ),
		], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function ai_settings_save( $request ) {
		$params = $request->get_json_params() ?: $request->get_params();
		NGC_Ai_Provider_Registry::save_settings( $params );
		return new WP_REST_Response( [ 'success' => true ], 200 );
	}
}
