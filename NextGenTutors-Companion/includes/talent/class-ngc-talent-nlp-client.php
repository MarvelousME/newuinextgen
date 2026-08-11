<?php
/**
 * Optional NLP similarity client (internal Python sidecar).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calls POST /v1/similarity — never used as LLM gateway.
 */
final class NGC_Talent_Nlp_Client {

	/**
	 * @param string $text_a Text A.
	 * @param string $text_b Text B.
	 * @return float|null Similarity 0–100 or null on failure.
	 */
	public static function similarity( $text_a, $text_b ) {
		if ( ! NGC_Talent_Settings::nlp_allowed() ) {
			return null;
		}
		$base = rtrim( (string) NGC_Talent_Settings::get()['nlp_sidecar_url'], '/' );
		$url  = $base . '/v1/similarity';
		$timeout = max( 1, (int) round( ( (int) NGC_Talent_Settings::get()['timeout_ms'] ) / 1000 ) );
		$response = wp_remote_post(
			$url,
			[
				'timeout' => $timeout,
				'headers' => [ 'Content-Type' => 'application/json', 'Accept' => 'application/json' ],
				'body'    => wp_json_encode(
					[
						'text_a' => (string) $text_a,
						'text_b' => (string) $text_b,
					]
				),
			]
		);
		if ( is_wp_error( $response ) ) {
			return null;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return null;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || ! isset( $body['similarity'] ) ) {
			return null;
		}
		return max( 0, min( 100, (float) $body['similarity'] ) );
	}
}
