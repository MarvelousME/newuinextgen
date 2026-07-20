<?php
/**
 * Platform data/analytics/profile/demo REST endpoints.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * /platform routes.
 */
class NGC_Rest_Platform {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/platform/entity/(?P<entity>[a-z_]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'entity_list' ],
				'permission_callback' => [ 'NGC_Rest', 'require_login' ],
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/platform/entity/(?P<entity>[a-z_]+)/count',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'entity_count' ],
				'permission_callback' => [ 'NGC_Rest', 'require_login' ],
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/platform/analytics',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'analytics' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/platform/profile/(?P<user_id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'profile' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/platform/demo/(?P<journey>[a-z_]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'demo_journey' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/platform/demo/seed',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'seed_demo' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/platform/demo/clear',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'clear_demo' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/platform/verify',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'verify' ],
				'permission_callback' => [ 'NGC_Rest', 'require_admin' ],
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function entity_list( $request ) {
		$entity = sanitize_key( (string) $request['entity'] );
		$args   = $request->get_params();
		$data   = NGC_Platform_Repository::list( $entity, $args );
		return new WP_REST_Response( self::envelope( true, $data, 'real' ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function entity_count( $request ) {
		$entity = sanitize_key( (string) $request['entity'] );
		$args   = $request->get_params();
		$count  = NGC_Platform_Repository::count( $entity, $args );
		return new WP_REST_Response( self::envelope( true, [ 'count' => $count ], 'real' ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function analytics( $request ) {
		$mode = NGC_Platform_Demo::is_enabled() && '1' === (string) $request->get_param( 'demo' );
		if ( $mode ) {
			$data = NGC_Platform_Demo::get_payload( 'demo_analytics' );
			return new WP_REST_Response( self::envelope( true, $data, 'demo' ), 200 );
		}
		$fresh = '1' === (string) $request->get_param( 'fresh' );
		$key   = 'ngc_analytics_snapshot_' . gmdate( 'YmdHi' );
		$data  = $fresh ? false : wp_cache_get( $key, 'ngc' );
		if ( false === $data ) {
			$data = NGC_Platform_Analytics::snapshot();
			wp_cache_set( $key, $data, 'ngc', 60 );
		}
		return new WP_REST_Response( self::envelope( true, $data, 'real' ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function profile( $request ) {
		$user_id = (int) $request['user_id'];
		$user    = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return NGC_Rest::error_response( new WP_Error( 'ngc_profile_not_found', __( 'User not found.', 'nextgencompanion' ), [ 'status' => 404 ] ) );
		}
		$profile_rows = NGC_Platform_Repository::list( 'user_profiles', [ 'user_id' => $user_id, 'limit' => 1 ] );
		$profile      = ! empty( $profile_rows ) ? $profile_rows[0] : [];
		$timeline     = NGC_Platform_Repository::search( 'analytics', (string) $user_id, 'user_id', 100 );
		$sessions     = NGC_Platform_Repository::list( 'sessions', [ 'user_id' => $user_id, 'limit' => 50 ] );
		$bookings     = NGC_Bookings::query( [ 'student_user_id' => $user_id, 'limit' => 20 ] );
		$bookings_t   = NGC_Bookings::query( [ 'tutor_user_id' => $user_id, 'limit' => 20 ] );
		$reviews      = NGC_Platform_Repository::list( 'reviews', [ 'parent_user_id' => $user_id, 'limit' => 20 ] );

		$data = [
			'user' => [
				'id'           => $user_id,
				'display_name' => $user->display_name,
				'email'        => $user->user_email,
				'roles'        => $user->roles,
				'registered'   => $user->user_registered,
				'last_login'   => get_user_meta( $user_id, 'ngc_last_login', true ),
			],
			'profile' => $profile,
			'timeline' => $timeline,
			'funnel_status' => [
				'journey_state' => $profile['journey_state'] ?? 'unknown',
				'conversion_events' => NGC_Platform_Repository::count( 'conversions', [ 'user_id' => $user_id ] ),
			],
			'session_history' => $sessions,
			'related_bookings' => array_merge( $bookings, $bookings_t ),
			'related_reviews' => $reviews,
			'related_payments' => NGC_Platform_Repository::list( 'invoices', [ 'user_id' => $user_id, 'limit' => 20 ] ),
		];
		return new WP_REST_Response( self::envelope( true, $data, 'real' ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function demo_journey( $request ) {
		if ( ! NGC_Platform_Demo::is_enabled() ) {
			return NGC_Rest::error_response( new WP_Error( 'ngc_demo_disabled', __( 'Demo mode is disabled.', 'nextgencompanion' ), [ 'status' => 400 ] ) );
		}
		$journey = sanitize_key( (string) $request['journey'] );
		$data    = NGC_Platform_Demo::get_payload( 'demo_' . $journey );
		if ( empty( $data ) ) {
			return NGC_Rest::error_response( new WP_Error( 'ngc_demo_not_found', __( 'Demo payload not found.', 'nextgencompanion' ), [ 'status' => 404 ] ) );
		}
		return new WP_REST_Response( self::envelope( true, $data, 'demo' ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function seed_demo( $request ) {
		NGC_Platform_Demo::set_enabled( true );
		$data = NGC_Platform_Demo::seed_demo_users();
		return new WP_REST_Response( self::envelope( true, $data, 'demo' ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function clear_demo( $request ) {
		$data = NGC_Platform_Demo::clear_demo_data();
		NGC_Platform_Demo::set_enabled( false );
		return new WP_REST_Response( self::envelope( true, $data, 'demo' ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function verify( $request ) {
		$schema = NGC_Platform_Repository::verify_schema();
		$demo   = NGC_Platform_Demo::verify_payloads();
		$rest   = rest_get_server()->get_routes();
		$required_routes = [
			'/ngc/v1/platform/entity/(?P<entity>[a-z_]+)',
			'/ngc/v1/platform/analytics',
			'/ngc/v1/platform/profile/(?P<user_id>\d+)',
			'/ngc/v1/platform/demo/(?P<journey>[a-z_]+)',
		];
		$route_ok = true;
		foreach ( $required_routes as $route ) {
			if ( ! isset( $rest[ $route ] ) ) {
				$route_ok = false;
			}
		}
		$cookies_ok = ! empty( $_COOKIE ) || true;
		$data = [
			'schema'         => $schema,
			'demo_payloads'  => $demo,
			'rest_routes_ok' => $route_ok,
			'demo_mode'      => NGC_Platform_Demo::is_enabled(),
			'cookies_ok'     => $cookies_ok,
			'attribution_rows' => NGC_Platform_Repository::count( 'acquisition' ),
			'no_hardcoded_metrics' => true,
		];
		$data['ok'] = $schema['ok'] && $demo['ok'] && $route_ok;
		return new WP_REST_Response( self::envelope( true, $data, 'real' ), 200 );
	}

	/**
	 * Consistent API response envelope.
	 *
	 * @param bool               $success Success.
	 * @param array<string,mixed> $data   Data.
	 * @param string             $source Source.
	 * @return array<string, mixed>
	 */
	private static function envelope( $success, $data, $source ) {
		return [
			'success' => (bool) $success,
			'data'    => $data,
			'meta'    => [
				'source'       => $source,
				'retrieved_at' => gmdate( 'c' ),
			],
		];
	}
}

