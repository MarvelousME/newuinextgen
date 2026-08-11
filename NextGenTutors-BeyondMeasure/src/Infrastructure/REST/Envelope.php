<?php
declare(strict_types=1);

namespace NGTBM\Infrastructure\REST;

/**
 * Standard API envelopes.
 */
final class Envelope {

	/**
	 * @param mixed                $data
	 * @param array<string,mixed>  $meta
	 */
	public static function success( $data, array $meta = [] ): \WP_REST_Response {
		return new \WP_REST_Response(
			[
				'data' => $data,
				'meta' => array_merge(
					[
						'requestId' => self::request_id(),
						'timestamp' => gmdate( 'c' ),
						'version'   => '1',
					],
					$meta
				),
			],
			200
		);
	}

	public static function error( string $code, string $message, int $status = 400 ): \WP_REST_Response {
		return new \WP_REST_Response(
			[
				'error' => [
					'code'      => $code,
					'message'   => $message,
					'requestId' => self::request_id(),
				],
			],
			$status
		);
	}

	public static function from_wp_error( \WP_Error $error ): \WP_REST_Response {
		$data   = $error->get_error_data();
		$status = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 400;
		return self::error( (string) $error->get_error_code(), (string) $error->get_error_message(), $status );
	}

	private static function request_id(): string {
		return 'ngtbm_' . wp_generate_password( 12, false, false );
	}
}
