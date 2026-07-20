<?php
/**
 * Reviews and ratings REST endpoints.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * POST /reviews — parent submits tutor review.
 */
class NGC_Rest_Reviews {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/reviews',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ __CLASS__, 'create' ],
				'permission_callback' => [ __CLASS__, 'can_submit' ],
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/reviews/tutor/(?P<tutor_id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'for_tutor' ],
				'permission_callback' => [ __CLASS__, 'permission_public_reviews' ],
			]
		);
	}

	/**
	 * @return bool
	 */
	public static function can_submit() {
		return is_user_logged_in() && (
			current_user_can( 'ngc_submit_reviews' )
			|| current_user_can( 'manage_options' )
			|| in_array( 'parent', (array) wp_get_current_user()->roles, true )
			|| in_array( 'parent_guardian', (array) wp_get_current_user()->roles, true )
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function create( $request ) {
		$data = [
			'parent_user_id'  => get_current_user_id(),
			'tutor_user_id'   => (int) $request->get_param( 'tutor_user_id' ),
			'booking_id'      => (int) $request->get_param( 'booking_id' ),
			'student_user_id' => (int) $request->get_param( 'student_user_id' ),
			'rating'          => (int) $request->get_param( 'rating' ),
			'comment'         => $request->get_param( 'comment' ),
		];

		$result = NGC_Reviews::create_review( $data );
		if ( is_wp_error( $result ) ) {
			return NGC_Rest::error_response( $result );
		}

		NGC_Workflows::dispatch(
			'review.submitted',
			[
				'review_id'     => (string) $result,
				'tutor_user_id' => (string) $data['tutor_user_id'],
				'rating'        => (string) $data['rating'],
			]
		);

		return new WP_REST_Response( [ 'review_id' => $result, 'ok' => true ], 201 );
	}

	/**
	 * PUBLIC_SAFE — average rating summary only (throttled).
	 *
	 * @return bool|WP_Error
	 */
	public static function permission_public_reviews() {
		return NGC_Rest::public_throttled( 'rest_reviews_tutor', 60, 600 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function for_tutor( $request ) {
		$tutor_id = (int) $request['tutor_id'];
		return new WP_REST_Response(
			[
				'tutor_user_id' => $tutor_id,
				'average'       => NGC_Reviews::average_for_tutor( $tutor_id ),
			],
			200
		);
	}
}
