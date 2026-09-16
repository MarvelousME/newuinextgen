<?php
/**
 * Matching REST endpoints.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * POST /matches, /matches/{id}/accept, /matches/{id}/assign
 */
class NGC_Rest_Matching {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/matches',
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
			'/matches/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'get' ],
				'permission_callback' => [ __CLASS__, 'can_view' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/matches/(?P<id>\d+)/accept',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'accept' ],
				'permission_callback' => [ __CLASS__, 'can_act' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/matches/(?P<id>\d+)/reject',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'reject' ],
				'permission_callback' => [ __CLASS__, 'can_act' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/matches/(?P<id>\d+)/assign',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'assign' ],
				'permission_callback' => [ 'NGC_Rest', 'require_support' ],
			]
		);
	}

	/**
	 * @return bool
	 */
	public static function can_create() {
		return is_user_logged_in() && ( current_user_can( 'ngc_request_match' ) || current_user_can( 'ngc_manage_matches' ) || current_user_can( 'manage_options' ) );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public static function can_view( $request ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$match = NGC_Matching::get( (int) $request['id'] );
		if ( ! $match ) {
			return current_user_can( 'ngc_manage_matches' ) || current_user_can( 'manage_options' );
		}
		return NGC_Access::can_act_on_match( $match );
	}

	/**
	 * Accept / reject ownership gate.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public static function can_act( $request ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$match = NGC_Matching::get( (int) $request['id'] );
		if ( ! NGC_Access::can_act_on_match( $match ) ) {
			return new WP_Error( 'ngc_forbidden', __( 'You cannot act on this match.', 'nextgencompanion' ), [ 'status' => 403 ] );
		}
		return true;
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function list( $request ) {
		$args = [ 'limit' => (int) $request->get_param( 'limit' ) ?: 20 ];
		if ( current_user_can( 'ngc_manage_matches' ) || current_user_can( 'manage_options' ) ) {
			if ( $request->get_param( 'status' ) ) {
				$args['status'] = sanitize_key( (string) $request->get_param( 'status' ) );
			}
		} else {
			$user_id = get_current_user_id();
			$user    = wp_get_current_user();
			if ( in_array( 'tutor', (array) $user->roles, true ) ) {
				$args['tutor_user_id'] = $user_id;
			} elseif ( in_array( 'student', (array) $user->roles, true ) ) {
				$args['student_user_id'] = $user_id;
			} else {
				$args['parent_user_id'] = $user_id;
			}
		}
		return new WP_REST_Response( NGC_Matching::query( $args ), 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get( $request ) {
		$match = NGC_Matching::get( (int) $request['id'] );
		return $match ? new WP_REST_Response( $match, 200 ) : NGC_Rest::error_response( 'not_found', '', 404 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function create( $request ) {
		$student_id = (int) $request->get_param( 'student_user_id' );
		$parent_id  = get_current_user_id();
		if ( $student_id && $student_id !== $parent_id && ! current_user_can( 'ngc_manage_matches' ) && ! current_user_can( 'manage_options' ) ) {
			$parent_of = (int) get_user_meta( $student_id, 'ngt_parent_user_id', true );
			if ( ! $parent_of ) {
				$parent_of = (int) get_user_meta( $student_id, 'ngc_parent_user_id', true );
			}
			if ( $parent_of !== $parent_id ) {
				return NGC_Rest::error_response(
					new WP_Error( 'ngc_forbidden_student', __( 'You cannot create matches for that student.', 'nextgencompanion' ), [ 'status' => 403 ] )
				);
			}
		}

		$data = [
			'subject'         => $request->get_param( 'subject' ),
			'grade'           => $request->get_param( 'grade' ),
			'province'        => $request->get_param( 'province' ) ?: $request->get_param( 'area' ),
			'notes'           => $request->get_param( 'notes' ),
			'student_user_id' => $student_id,
			'parent_user_id'  => $parent_id,
		];

		$result = NGC_Matching::create_from_find_tutor( $data );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}

		return new WP_REST_Response( [ 'match_id' => $result, 'match' => NGC_Matching::get( $result ) ], 201 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function accept( $request ) {
		$id     = (int) $request['id'];
		$result = NGC_Matching::accept( $id );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		return new WP_REST_Response( [ 'ok' => true, 'match' => NGC_Matching::get( $id ) ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function reject( $request ) {
		$id     = (int) $request['id'];
		$reason = sanitize_textarea_field( $request->get_param( 'reason' ) ?? '' );
		$result = NGC_Matching::reject( $id, get_current_user_id(), $reason );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		return new WP_REST_Response( [ 'ok' => true, 'match' => NGC_Matching::get( $id ) ], 200 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function assign( $request ) {
		$id       = (int) $request['id'];
		$tutor_id = (int) $request->get_param( 'tutor_user_id' );
		if ( ! $tutor_id ) {
			return NGC_Rest::error_response( new WP_Error( 'ngc_missing_tutor', __( 'tutor_user_id required.', 'nextgencompanion' ) ) );
		}
		$result = NGC_Matching::manual_assign( $id, $tutor_id );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}
		return new WP_REST_Response( [ 'ok' => true, 'match' => NGC_Matching::get( $id ) ], 200 );
	}
}
