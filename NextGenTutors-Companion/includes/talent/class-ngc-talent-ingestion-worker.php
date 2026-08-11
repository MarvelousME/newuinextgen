<?php
/**
 * Async talent evaluation worker.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queue type talent.evaluate.
 */
final class NGC_Talent_Ingestion_Worker {

	/**
	 * @param mixed               $result Prior.
	 * @param array<string,mixed> $payload Payload.
	 * @param object|null         $msg Message.
	 * @return true|WP_Error
	 */
	public static function handle( $result, $payload, $msg = null ) {
		unset( $result, $msg );
		if ( ! is_array( $payload ) ) {
			return new WP_Error( 'ngc_talent_ingest', 'Invalid payload' );
		}
		$candidate_id = (string) ( $payload['candidate_id'] ?? '' );
		$application  = isset( $payload['application'] ) && is_array( $payload['application'] ) ? $payload['application'] : [];
		if ( empty( $application ) && $candidate_id && class_exists( 'NGC_Tutor_Lifecycle' ) && method_exists( 'NGC_Tutor_Lifecycle', 'get' ) ) {
			$got = NGC_Tutor_Lifecycle::get( (int) $candidate_id );
			if ( $got && ! is_wp_error( $got ) ) {
				$application = (array) $got;
			}
		}
		$candidate = NGC_Talent_Service::profile_from_application( $application );
		$req       = NGC_Talent_Service::default_requirements();
		if ( ! empty( $req['subjects'] ) && empty( $candidate['subjects'] ) && ! empty( $application['subjects'] ) ) {
			// keep candidate subjects from profile_from_application
		}
		// If requirement subjects empty, use candidate subjects as soft self-profile completeness eval.
		if ( empty( $req['subjects'] ) && ! empty( $candidate['subjects'] ) ) {
			$req['subjects'] = $candidate['subjects'];
		}

		NGC_Talent_Service::evaluate_safe(
			$candidate,
			$req,
			[
				'persist'         => true,
				'candidate_type'  => 'application',
				'candidate_id'    => $candidate_id,
				'idempotency_key' => 'talent-app-' . $candidate_id . '-' . NGC_Talent_Settings::MODEL_VERSION . '-' . NGC_Talent_Settings::WEIGHTS_VERSION,
			]
		);
		return true;
	}
}
