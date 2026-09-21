<?php
/**
 * Audit/event provider contract.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Structured session lifecycle audit.
 */
interface NGC_Audit_Provider_Interface {

	/**
	 * @param string               $event_type Event.
	 * @param string               $entity_type Entity.
	 * @param int                  $entity_id   Entity ID.
	 * @param string               $result      Result (success|denied|error).
	 * @param array<string, mixed> $meta        Metadata (must not include secrets).
	 * @return void
	 */
	public function record( $event_type, $entity_type, $entity_id, $result = 'success', array $meta = [] );
}
