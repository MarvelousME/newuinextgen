<?php
/**
 * Public tutor calendar REST endpoint.
 *
 * Classification: PUBLIC_SAFE (throttled, sanitized for anonymous).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GET /nextgen/v1/tutors/{tutor_id}/calendar
 */
class NGC_Rest_Tutor_Calendar {

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register' ] );
	}

	/**
	 * Register route.
	 */
	public static function register() {
		register_rest_route(
			'nextgen/v1',
			'/tutors/(?P<tutor_id>\d+)/calendar',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'calendar' ],
				'permission_callback' => [ __CLASS__, 'permission_callback' ],
				'args'                => [
					'from'          => [ 'sanitize_callback' => 'sanitize_text_field' ],
					'to'            => [ 'sanitize_callback' => 'sanitize_text_field' ],
					'subject'       => [ 'sanitize_callback' => 'sanitize_text_field' ],
					'delivery_mode' => [ 'sanitize_callback' => 'sanitize_text_field' ],
					'timezone'      => [ 'sanitize_callback' => 'sanitize_text_field' ],
					'demo'          => [ 'sanitize_callback' => 'absint' ],
				],
			]
		);
	}

	/**
	 * PUBLIC_SAFE — rate limited; anonymous receives sanitized availability only.
	 *
	 * @return bool|WP_Error
	 */
	public static function permission_callback() {
		return NGC_Rest::public_throttled( 'rest_tutor_calendar', 30, 600 );
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function calendar( $request ) {
		$tutor_id = (int) $request['tutor_id'];
		$data     = NGC_Tutor_Calendar_Service::get_calendar(
			$tutor_id,
			[
				'from'          => (string) $request->get_param( 'from' ),
				'to'            => (string) $request->get_param( 'to' ),
				'subject'       => (string) $request->get_param( 'subject' ),
				'delivery_mode' => (string) $request->get_param( 'delivery_mode' ),
				'timezone'      => (string) $request->get_param( 'timezone' ),
				'demo'          => (int) $request->get_param( 'demo' ),
			]
		);

		if ( ! is_user_logged_in() ) {
			$data = self::sanitize_public_calendar( $data );
		}

		$status = ! empty( $data['success'] ) ? 200 : 404;
		return new WP_REST_Response( $data, $status );
	}

	/**
	 * Strip internal IDs and private notes from public calendar payloads.
	 *
	 * @param array<string, mixed> $data Raw calendar payload.
	 * @return array<string, mixed>
	 */
	public static function sanitize_public_calendar( $data ) {
		if ( empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return $data;
		}
		unset( $data['data']['user_id'], $data['data']['amelia_employee_id'] );
		if ( ! empty( $data['data']['slots'] ) && is_array( $data['data']['slots'] ) ) {
			$data['data']['slots'] = array_map(
				static function ( $slot ) {
					if ( ! is_array( $slot ) ) {
						return $slot;
					}
					unset( $slot['notes'], $slot['internal_id'], $slot['employee_id'], $slot['booking_id'] );
					return [
						'date'          => sanitize_text_field( (string) ( $slot['date'] ?? '' ) ),
						'start_time'    => sanitize_text_field( (string) ( $slot['start_time'] ?? '' ) ),
						'end_time'      => sanitize_text_field( (string) ( $slot['end_time'] ?? '' ) ),
						'delivery_mode' => sanitize_text_field( (string) ( $slot['delivery_mode'] ?? '' ) ),
						'available'     => ! empty( $slot['available'] ),
					];
				},
				$data['data']['slots']
			);
		}
		$data['data']['tutor_id'] = (int) ( $data['data']['tutor_id'] ?? 0 );
		return $data;
	}
}
