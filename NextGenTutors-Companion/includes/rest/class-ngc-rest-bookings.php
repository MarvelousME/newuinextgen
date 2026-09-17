<?php
/**
 * Bookings REST CRUD.
 *
 * List/get/update responses never include meeting join URLs. Launch URLs are
 * issued only from POST/GET /bookings/{id}/join after session launch policy.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bookings REST routes.
 */
class NGC_Rest_Bookings {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/bookings',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'list' ],
					'permission_callback' => [ 'NGC_Rest', 'require_login' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'create' ],
					'permission_callback' => [ __CLASS__, 'can_create' ],
				],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/bookings/(?P<id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'get' ],
					'permission_callback' => [ __CLASS__, 'can_view' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ __CLASS__, 'update' ],
					'permission_callback' => [ __CLASS__, 'can_mutate' ],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ __CLASS__, 'delete' ],
					'permission_callback' => [ 'NGC_Rest', 'require_support' ],
				],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/bookings/(?P<id>\d+)/status',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'set_status' ],
				'permission_callback' => [ __CLASS__, 'can_mutate' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/bookings/(?P<id>\d+)/join',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ __CLASS__, 'join' ],
					'permission_callback' => [ __CLASS__, 'can_view' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'join' ],
					'permission_callback' => [ __CLASS__, 'can_view' ],
				],
			]
		);
	}

	/**
	 * @return bool
	 */
	public static function can_create() {
		return current_user_can( 'ngc_book_sessions' ) || current_user_can( 'ngc_manage_bookings' ) || current_user_can( 'manage_options' ) || is_user_logged_in();
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public static function can_view( $request ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$booking = NGC_Bookings::get( (int) $request['id'] );
		return NGC_Access::can_view_booking( $booking );
	}

	/**
	 * Object-level mutate gate (update / status).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public static function can_mutate( $request ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$booking = NGC_Bookings::get( (int) $request['id'] );
		if ( ! NGC_Access::can_mutate_booking( $booking ) ) {
			return new WP_Error( 'ngc_forbidden', __( 'You cannot modify this booking.', 'nextgencompanion' ), [ 'status' => 403 ] );
		}
		return true;
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function list( $request ) {
		return self::bookings_response( self::query_visible_bookings( $request ) );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function create( $request ) {
		$data = NGC_Access::sanitize_booking_create_payload( self::request_payload( $request ) );
		if ( is_wp_error( $data ) ) {
			return NGC_Rest::error_response( $data );
		}
		$id = NGC_Bookings::create( $data );
		if ( is_wp_error( $id ) ) {
			return NGC_Rest::error_response( $id );
		}
		do_action( 'ngc_booking_created', $id );
		return new WP_REST_Response( [ 'booking_id' => $id, 'booking' => self::safe_booking( NGC_Bookings::get( $id ) ) ], 201 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get( $request ) {
		$booking = NGC_Bookings::get( (int) $request['id'] );
		if ( ! $booking ) {
			return NGC_Rest::error_response( new WP_Error( 'ngc_not_found', __( 'Booking not found.', 'nextgencompanion' ), [ 'status' => 404 ] ) );
		}
		return new WP_REST_Response( [ 'booking' => self::safe_booking( $booking ) ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function update( $request ) {
		$id     = (int) $request['id'];
		$result = NGC_Bookings::update( $id, NGC_Access::sanitize_booking_update_payload( self::request_payload( $request ) ) );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		return new WP_REST_Response( [ 'booking' => self::safe_booking( NGC_Bookings::get( $id ) ) ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function delete( $request ) {
		$result = NGC_Bookings::delete( (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		return new WP_REST_Response( [ 'deleted' => true ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function set_status( $request ) {
		$status = sanitize_key( $request->get_param( 'status' ) );
		$result = NGC_Bookings::transition( (int) $request['id'], $status );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		return new WP_REST_Response( [ 'booking' => self::safe_booking( NGC_Bookings::get( (int) $request['id'] ) ) ], 200 );
	}

	/**
	 * Start / join an online A/V lesson for an authorized party.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function join( $request ) {
		$booking_id = (int) $request['id'];
		if ( class_exists( 'NGC_Session_Launch' ) ) {
			$result = NGC_Session_Launch::launch_booking( $booking_id, get_current_user_id() );
			if ( is_wp_error( $result ) ) {
				$denied = self::join_window_response( $result );
				return $denied ? $denied : NGC_Rest::error_response( $result );
			}
			NGC_Audit::log(
				'lesson_join',
				'booking',
				$booking_id,
				[ 'session_id' => $result['session_id'] ?? 0 ],
				get_current_user_id()
			);
			return new WP_REST_Response( $result, 200 );
		}
		$booking = NGC_Bookings::get( $booking_id );
		if ( ! $booking ) {
			return NGC_Rest::error_response( new WP_Error( 'ngc_not_found', __( 'Booking not found.', 'nextgencompanion' ), [ 'status' => 404 ] ) );
		}
		if ( ! class_exists( 'NGC_Meetings' ) || ! NGC_Meetings::can_join_status( $booking ) ) {
			return NGC_Rest::error_response(
				new WP_Error(
					'ngc_meeting_not_joinable',
					__( 'This lesson is not available to join right now.', 'nextgencompanion' ),
					[ 'status' => 409 ]
				)
			);
		}

		$url = NGC_Meetings::join_url_for_user( $booking_id, get_current_user_id() );
		if ( is_wp_error( $url ) ) {
			return NGC_Rest::error_response( $url );
		}

		$meeting = NGC_Bookings::get_meeting_meta( $booking_id );
		NGC_Audit::log(
			'lesson_join',
			'booking',
			$booking_id,
			[
				'provider' => $meeting['provider'] ?? 'jitsi',
				'room'     => $meeting['room'] ?? '',
			],
			get_current_user_id()
		);

		return new WP_REST_Response(
			[
				'booking_id'  => $booking_id,
				'join_url'    => $url,
				'joinUrl'     => $url,
				'provider'    => (string) ( $meeting['provider'] ?? 'jitsi' ),
				'room'        => (string) ( $meeting['room'] ?? '' ),
				'audio_video' => true,
			],
			200
		);
	}

	/**
	 * Visible bookings for the current user. Parents never accept a foreign student_user_id filter.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array<int, object>
	 */
	private static function query_visible_bookings( $request ) {
		$limit  = (int) ( $request->get_param( 'limit' ) ?: 20 );
		$uid    = get_current_user_id();
		$roles  = (array) wp_get_current_user()->roles;
		$status = $request->get_param( 'status' ) ? sanitize_key( $request->get_param( 'status' ) ) : '';
		$args   = [ 'limit' => $limit ];

		if ( NGC_Access::is_ops( $uid ) ) {
			if ( $request->get_param( 'student_user_id' ) ) {
				$args['student_user_id'] = (int) $request->get_param( 'student_user_id' );
			}
			if ( $request->get_param( 'tutor_user_id' ) ) {
				$args['tutor_user_id'] = (int) $request->get_param( 'tutor_user_id' );
			}
			if ( $status ) {
				$args['status'] = $status;
			}
			return NGC_Bookings::query( $args );
		}

		if ( in_array( 'tutor', $roles, true ) || in_array( 'ngt_tutor', $roles, true ) ) {
			$args['tutor_user_id'] = $uid;
			if ( $status ) {
				$args['status'] = $status;
			}
			return NGC_Bookings::query( $args );
		}

		if ( in_array( 'parent', $roles, true ) || in_array( 'ngt_parent', $roles, true ) ) {
			$bookings = NGC_Bookings::query_for_parent( $uid, $limit );
			if ( ! $status ) {
				return $bookings;
			}
			return array_values(
				array_filter(
					$bookings,
					static function ( $booking ) use ( $status ) {
						return isset( $booking->status ) && sanitize_key( $booking->status ) === $status;
					}
				)
			);
		}

		$args['student_user_id'] = $uid;
		if ( $status ) {
			$args['status'] = $status;
		}
		return NGC_Bookings::query( $args );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return array<string, mixed>
	 */
	private static function request_payload( $request ) {
		$data = $request->get_json_params() ?: $request->get_params();
		return is_array( $data ) ? $data : [];
	}

	/**
	 * @param array<int, object|array> $bookings Rows.
	 * @return WP_REST_Response
	 */
	private static function bookings_response( $bookings ) {
		return new WP_REST_Response( [ 'bookings' => self::safe_bookings( $bookings ) ], 200 );
	}

	/**
	 * @param WP_Error $result Join denial.
	 * @return WP_REST_Response|null
	 */
	private static function join_window_response( $result ) {
		$data = $result->get_error_data();
		if ( ! is_array( $data ) || ! isset( $data['window'] ) ) {
			return null;
		}
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

	/**
	 * @param object|array|null $booking Booking.
	 * @return object|array|null
	 */
	private static function safe_booking( $booking ) {
		if ( class_exists( 'NGC_Session_Presenter' ) ) {
			return NGC_Session_Presenter::sanitize_booking_for_rest( $booking );
		}
		return $booking;
	}

	/**
	 * @param array<int, object|array> $bookings Rows.
	 * @return array<int, object|array>
	 */
	private static function safe_bookings( $bookings ) {
		if ( ! is_array( $bookings ) ) {
			return [];
		}
		if ( class_exists( 'NGC_Session_Presenter' ) ) {
			return NGC_Session_Presenter::sanitize_bookings_for_rest( $bookings );
		}
		return $bookings;
	}
}
