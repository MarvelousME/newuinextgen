<?php
/**
 * REST: NGT session launch / read.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Session orchestration endpoints.
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
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'launch' ],
					'permission_callback' => [ 'NGC_Rest', 'require_login' ],
				],
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/sessions/(?P<id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'get' ],
					'permission_callback' => [ 'NGC_Rest', 'require_login' ],
				],
			]
		);
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/sessions/by-booking/(?P<booking_id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'by_booking' ],
					'permission_callback' => [ 'NGC_Rest', 'require_login' ],
				],
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function launch( $request ) {
		$result = NGC_Session_Orchestrator::authorize_launch( (int) $request['id'], get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		// Never echo provider secrets — join URL only for authorized party.
		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get( $request ) {
		$session = NGC_Sessions::get( (int) $request['id'] );
		if ( ! $session ) {
			return NGC_Rest::error_response( new WP_Error( 'ngc_not_found', __( 'Session not found.', 'nextgencompanion' ), [ 'status' => 404 ] ) );
		}
		if ( ! self::can_view( $session ) ) {
			return NGC_Rest::error_response( new WP_Error( 'ngc_forbidden', __( 'Forbidden.', 'nextgencompanion' ), [ 'status' => 403 ] ) );
		}
		$payload = self::public_session( $session );
		$payload['join_window'] = NGC_Session_Orchestrator::join_window_status( $session );
		return new WP_REST_Response( [ 'session' => $payload ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function by_booking( $request ) {
		$session = NGC_Sessions::get_by_booking( (int) $request['booking_id'] );
		if ( ! $session ) {
			return NGC_Rest::error_response( new WP_Error( 'ngc_not_found', __( 'Session not found.', 'nextgencompanion' ), [ 'status' => 404 ] ) );
		}
		if ( ! self::can_view( $session ) ) {
			return NGC_Rest::error_response( new WP_Error( 'ngc_forbidden', __( 'Forbidden.', 'nextgencompanion' ), [ 'status' => 403 ] ) );
		}
		return new WP_REST_Response(
			[
				'session'     => self::public_session( $session ),
				'join_window' => NGC_Session_Orchestrator::join_window_status( $session ),
			],
			200
		);
	}

	/**
	 * @param object $session Session.
	 * @return bool
	 */
	private static function can_view( $session ) {
		$uid = get_current_user_id();
		if ( user_can( $uid, 'manage_options' ) || user_can( $uid, 'ngc_manage_bookings' ) ) {
			return true;
		}
		return in_array(
			$uid,
			[ (int) $session->student_user_id, (int) $session->tutor_user_id, (int) $session->parent_user_id ],
			true
		);
	}

	/**
	 * Strip meeting URL from public GET (launch endpoint issues it).
	 *
	 * @param object $session Session.
	 * @return array<string, mixed>
	 */
	private static function public_session( $session ) {
		return [
			'id'                    => (int) $session->id,
			'session_uuid'          => $session->session_uuid,
			'correlation_id'        => $session->correlation_id,
			'booking_id'            => (int) $session->booking_id,
			'order_id'              => (int) $session->order_id,
			'product_id'            => (int) $session->product_id,
			'student_user_id'       => (int) $session->student_user_id,
			'parent_user_id'        => (int) $session->parent_user_id,
			'tutor_user_id'         => (int) $session->tutor_user_id,
			'subject_name'          => $session->subject_name,
			'status'                => $session->status,
			'payment_status'        => $session->payment_status,
			'booking_status'        => $session->booking_status,
			'lesson_status'         => $session->lesson_status,
			'meeting_status'        => $session->meeting_status,
			'meeting_provider'      => $session->meeting_provider,
			'meeting_id'            => $session->meeting_id,
			'masterstudy_course_id' => (int) $session->masterstudy_course_id,
			'masterstudy_lesson_id' => (int) $session->masterstudy_lesson_id,
			'scheduled_start'       => $session->scheduled_start,
			'scheduled_end'         => $session->scheduled_end,
			'timezone'              => $session->timezone,
		];
	}
}
