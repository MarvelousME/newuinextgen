<?php
/**
 * Sessions REST — launch, complete, attendance.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * POST /ngc/v1/sessions/{id}/launch
 */
class NGC_Rest_Sessions {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/sessions/(?P<id>\d+)/launch',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'launch' ],
				'permission_callback' => [ 'NGC_Rest', 'require_login' ],
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/sessions/(?P<id>\d+)/complete',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'complete' ],
				'permission_callback' => [ 'NGC_Rest', 'require_login' ],
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/sessions/(?P<id>\d+)/attendance',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'attendance' ],
				'permission_callback' => [ 'NGC_Rest', 'require_login' ],
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/sessions/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'get' ],
				'permission_callback' => [ 'NGC_Rest', 'require_login' ],
			]
		);
		register_rest_route(
			'nextgentutors/v1',
			'/sessions/(?P<id>\d+)/launch',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'launch' ],
				'permission_callback' => [ 'NGC_Rest', 'require_login' ],
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function launch( $request ) {
		$result = NGC_Session_Launch::launch( (int) $request['id'], get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();
			if ( is_array( $data ) && isset( $data['window'] ) ) {
				return new WP_REST_Response(
					[
						'code'    => $result->get_error_code(),
						'message' => $result->get_error_message(),
						'reason'  => $data['reason'] ?? '',
						'window'  => $data['window'],
					],
					(int) ( $data['status'] ?? 409 )
				);
			}
			return NGC_Rest::error_response( $result );
		}
		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function complete( $request ) {
		$result = NGC_Session_Launch::complete( (int) $request['id'], get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		return new WP_REST_Response( [ 'session' => $result ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function attendance( $request ) {
		$result = NGC_Session_Launch::attendance(
			(int) $request['id'],
			(string) $request->get_param( 'attendance' ),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		return new WP_REST_Response( [ 'session' => $result ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get( $request ) {
		$session = NGC_Session_Repository::get( (int) $request['id'] );
		if ( ! $session ) {
			return NGC_Rest::error_response( new WP_Error( 'ngc_not_found', __( 'Session not found.', 'nextgencompanion' ), [ 'status' => 404 ] ) );
		}
		if ( ! NGC_Session_Launch::can_participate( $session, get_current_user_id() ) ) {
			return NGC_Rest::error_response( new WP_Error( 'ngc_forbidden', __( 'Forbidden.', 'nextgencompanion' ), [ 'status' => 403 ] ) );
		}
		$row = NGC_Session_Presenter::format_session( $session, get_current_user_id() );
		return new WP_REST_Response( [ 'session' => $row ], 200 );
	}
}
