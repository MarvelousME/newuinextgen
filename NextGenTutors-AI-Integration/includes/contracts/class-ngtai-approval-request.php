<?php
/**
 * Approval request contract.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable approval request.
 */
final class NGTAI_Approval_Request implements JsonSerializable {

	/** @var array<string,mixed> */
	private $data;

	/** @param array<string,mixed> $data Fields. */
	public function __construct( array $data ) {
		foreach ( [ 'approval_id', 'agent_run_id', 'action_name', 'requested_by', 'payload', 'risk' ] as $field ) {
			if ( ! array_key_exists( $field, $data ) ) {
				throw new InvalidArgumentException( 'Missing approval request field: ' . $field );
			}
		}
		foreach ( [ 'approval_id', 'agent_run_id', 'action_name', 'requested_by', 'risk' ] as $field ) {
			if ( ! is_string( $data[ $field ] ) || '' === trim( $data[ $field ] ) ) {
				throw new InvalidArgumentException( $field . ' is required.' );
			}
		}
		if ( ! is_array( $data['payload'] ) ) {
			throw new InvalidArgumentException( 'payload must be an array.' );
		}
		$this->data = array_intersect_key( $data, array_flip( [ 'approval_id', 'agent_run_id', 'action_name', 'requested_by', 'payload', 'risk' ] ) );
	}

	/** @return array<string,mixed> */
	public function to_array() {
		return $this->data;
	}

	/** @return array<string,mixed> */
	public function jsonSerialize(): array {
		return $this->data;
	}

	/** @param string $key Field. @param mixed $default Default. @return mixed */
	public function get( $key, $default = null ) {
		return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : $default;
	}
}
