<?php
/**
 * Audit adapter for session lifecycle.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Audit adapter wrapping NGC_Audit.
 */
class NGC_Session_Audit_Adapter implements NGC_Audit_Provider_Interface {

	/**
	 * @param string               $event_type  Event.
	 * @param string               $entity_type Entity.
	 * @param int                  $entity_id   ID.
	 * @param string               $result      Result.
	 * @param array<string, mixed> $meta        Meta.
	 * @return void
	 */
	public function record( $event_type, $entity_type, $entity_id, $result = 'success', array $meta = [] ) {
		if ( ! class_exists( 'NGC_Audit' ) ) {
			return;
		}
		$ctx = $meta;
		unset( $ctx['join_url'], $ctx['password'], $ctx['token'], $ctx['secret'], $ctx['access_token'] );
		NGC_Audit::log(
			sanitize_key( $event_type ),
			sanitize_key( $entity_type ),
			(int) $entity_id,
			$ctx,
			(int) ( $meta['actor'] ?? 0 ),
			[
				'result'         => sanitize_key( $result ),
				'correlation_id' => (string) ( $meta['correlation_id'] ?? '' ),
				'error_code'     => (string) ( $meta['error_code'] ?? '' ),
			]
		);
	}
}
