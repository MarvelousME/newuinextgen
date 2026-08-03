<?php
/**
 * Bookings REST CRUD.
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
		$args = [ 'limit' => (int) ( $request->get_param( 'limit' ) ?: 20 ) ];
		$uid  = get_current_user_id();
		$user = wp_get_current_user();
		$roles = (array) $user->roles;

		if ( NGC_Access::is_ops( $uid ) ) {
			if ( $request->get_param( 'student_user_id' ) ) {
				$args['student_user_id'] = (int) $request->get_param( 'student_user_id' );
			}
			if ( $request->get_param( 'tutor_user_id' ) ) {
				$args['tutor_user_id'] = (int) $request->get_param( 'tutor_user_id' );
			}
			if ( $request->get_param( 'status' ) ) {
				$args['status'] = sanitize_key( $request->get_param( 'status' ) );
			}
			return new WP_REST_Response( [ 'bookings' => NGC_Bookings::query( $args ) ], 200 );
		}

		if ( in_array( 'tutor', $roles, true ) || in_array( 'ngt_tutor', $roles, true ) ) {
			$args['tutor_user_id'] = $uid;
			if ( $request->get_param( 'status' ) ) {
				$args['status'] = sanitize_key( $request->get_param( 'status' ) );
			}
			return new WP_REST_Response( [ 'bookings' => NGC_Bookings::query( $args ) ], 200 );
		}

		// Parent (or student acting as self): never accept foreign student_user_id filters.
		if ( in_array( 'parent', $roles, true ) || in_array( 'ngt_parent', $roles, true ) ) {
			$bookings = NGC_Bookings::query_for_parent( $uid, (int) $args['limit'] );
			$status   = $request->get_param( 'status' ) ? sanitize_key( $request->get_param( 'status' ) ) : '';
			if ( $status ) {
				$bookings = array_values(
					array_filter(
						$bookings,
						static function ( $b ) use ( $status ) {
							return isset( $b->status ) && sanitize_key( $b->status ) === $status;
						}
					)
				);
			}
			return new WP_REST_Response( [ 'bookings' => $bookings ], 200 );
		}

		$args['student_user_id'] = $uid;
		if ( $request->get_param( 'status' ) ) {
			$args['status'] = sanitize_key( $request->get_param( 'status' ) );
		}
		return new WP_REST_Response( [ 'bookings' => NGC_Bookings::query( $args ) ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function create( $request ) {
		$data = $request->get_json_params() ?: $request->get_params();
		if ( ! is_array( $data ) ) {
			$data = [];
		}
		$data = NGC_Access::sanitize_booking_create_payload( $data );
		if ( is_wp_error( $data ) ) {
			return NGC_Rest::error_response( $data );
		}
		$id = NGC_Bookings::create( $data );
		if ( is_wp_error( $id ) ) {
			return NGC_Rest::error_response( $id );
		}
		do_action( 'ngc_booking_created', $id );
		return new WP_REST_Response( [ 'booking_id' => $id, 'booking' => NGC_Bookings::get( $id ) ], 201 );
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
		return new WP_REST_Response( [ 'booking' => $booking ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function update( $request ) {
		$id   = (int) $request['id'];
		$data = $request->get_json_params() ?: $request->get_params();
		if ( ! is_array( $data ) ) {
			$data = [];
		}
		$data   = NGC_Access::sanitize_booking_update_payload( $data );
		$result = NGC_Bookings::update( $id, $data );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		return new WP_REST_Response( [ 'booking' => NGC_Bookings::get( $id ) ], 200 );
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
		return new WP_REST_Response( [ 'booking' => NGC_Bookings::get( (int) $request['id'] ) ], 200 );
	}

	/**
	 * Start / join an online A/V lesson for an authorized party.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function join( $request ) {
		$booking_id = (int) $request['id'];
		$booking    = NGC_Bookings::get( $booking_id );
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
				'booking_id' => $booking_id,
				'join_url'   => $url,
				'joinUrl'    => $url,
				'provider'   => (string) ( $meeting['provider'] ?? 'jitsi' ),
				'room'       => (string) ( $meeting['room'] ?? '' ),
				'audio_video'=> true,
			],
			200
		);
	}
}
