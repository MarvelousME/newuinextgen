<?php
/**
 * Audit adapter wrapper.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Structured audit logging for workflows.
 */
class NGC_Audit_Adapter extends NGC_Adapter_Base {

	/**
	 * @return string
	 */
	public function slug() {
		return 'audit';
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		return class_exists( 'NGC_Audit' ) && NGC_Database::tables_exist();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify() {
		return [
			'active' => $this->is_available(),
			'ok'     => $this->is_available(),
			'status' => $this->is_available() ? 'VERIFIED' : 'PARTIAL — audit table missing',
		];
	}

	/**
	 * @param string               $action  log_event.
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	public function create_or_update( $action, $payload ) {
		if ( 'log_event' !== $action ) {
			return $this->handle_error( 'audit_invalid_action', __( 'Unsupported audit action.', 'nextgencompanion' ) );
		}

		$event       = sanitize_key( $payload['event'] ?? '' );
		$object_type = sanitize_key( $payload['object_type'] ?? 'workflow' );
		$object_id   = (int) ( $payload['object_id'] ?? 0 );
		$context     = (array) ( $payload['context'] ?? [] );
		$actor_id    = (int) ( $payload['actor_id'] ?? 0 );

		if ( ! $event ) {
			return $this->handle_error( 'audit_missing_event', __( 'Audit event required.', 'nextgencompanion' ) );
		}

		NGC_Audit::log( $event, $object_type, $object_id, $context, $actor_id );
		return $this->success( [ 'event' => $event, 'object_id' => $object_id ] );
	}

	/**
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>|null
	 */
	public function get_existing( $payload ) {
		return null;
	}
}
