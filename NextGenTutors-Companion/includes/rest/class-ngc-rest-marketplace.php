<?php
/**
 * REST — Tutor marketplace search and filters.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Marketplace REST routes.
 */
class NGC_Rest_Marketplace {

	/**
	 * Register routes.
	 */
	public static function register() {
		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/marketplace/tutors',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'tutors' ],
				'permission_callback' => function () {
					return NGC_Rest::public_throttled( 'marketplace_search', 60, 600 );
				},
			]
		);

		register_rest_route(
			NGC_Rest::NAMESPACE,
			'/marketplace/filters',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'filters' ],
				'permission_callback' => function () {
					return NGC_Rest::public_throttled( 'marketplace_search', 60, 600 );
				},
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function tutors( $request ) {
		$allowed = NGC_Rest::public_throttled( 'marketplace_search', 60, 600 );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$data = NGC_Marketplace::query_tutors(
			[
				'q'         => $request->get_param( 'q' ),
				'subject'   => $request->get_param( 'subject' ),
				'grade'     => $request->get_param( 'grade' ),
				'province'  => $request->get_param( 'province' ),
				'format'    => $request->get_param( 'format' ),
				'min_price' => $request->get_param( 'min_price' ),
				'max_price' => $request->get_param( 'max_price' ),
				'verified'  => $request->get_param( 'verified' ),
				'sort'      => $request->get_param( 'sort' ),
				'page'      => $request->get_param( 'page' ),
				'per_page'  => $request->get_param( 'per_page' ),
			]
		);

		if ( class_exists( 'NGC_Audit' ) ) {
			NGC_Audit::log(
				'marketplace_query',
				'tutors',
				0,
				[
					'total' => $data['total'] ?? 0,
					'page'  => $data['page'] ?? 1,
				],
				get_current_user_id(),
				[ 'workflow_key' => 'marketplace' ]
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => $data,
			],
			200
		);
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function filters() {
		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => NGC_Marketplace::filter_options(),
			],
			200
		);
	}
}
