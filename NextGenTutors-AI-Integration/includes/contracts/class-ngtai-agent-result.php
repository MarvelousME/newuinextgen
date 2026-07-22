<?php
/**
 * Agent result value object.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validated recommendation result; never an authoritative domain decision.
 */
final class NGTAI_Agent_Result implements JsonSerializable {

	/** @var array<string,mixed> */
	private $data;

	/**
	 * @param array<string,mixed> $data Result data.
	 */
	public function __construct( array $data ) {
		$required = [ 'agent_run_id', 'result_version', 'event_id', 'correlation_id', 'agent_name', 'action_name', 'status', 'result', 'completed_at' ];
		foreach ( $required as $field ) {
			if ( ! array_key_exists( $field, $data ) ) {
				throw new InvalidArgumentException( 'Missing agent result field: ' . $field );
			}
		}
		if ( ! is_int( $data['result_version'] ) || $data['result_version'] < 1 ) {
			throw new InvalidArgumentException( 'result_version must be an integer greater than zero.' );
		}
		if ( ! in_array( $data['status'], [ 'succeeded', 'failed', 'partial' ], true ) ) {
			throw new InvalidArgumentException( 'Invalid agent result status.' );
		}
		foreach ( [ 'agent_run_id', 'event_id', 'correlation_id', 'agent_name', 'action_name' ] as $field ) {
			if ( ! is_string( $data[ $field ] ) || '' === trim( $data[ $field ] ) ) {
				throw new InvalidArgumentException( $field . ' is required.' );
			}
		}
		if ( ! is_array( $data['result'] ) || ( isset( $data['error'] ) && null !== $data['error'] && ! is_array( $data['error'] ) ) ) {
			throw new InvalidArgumentException( 'result and error have invalid types.' );
		}
		if ( false === strtotime( (string) $data['completed_at'] ) ) {
			throw new InvalidArgumentException( 'completed_at is invalid.' );
		}
		$forbidden = self::find_forbidden_key( $data['result'] );
		if ( '' !== $forbidden ) {
			throw new InvalidArgumentException( 'Forbidden domain mutation in result: ' . $forbidden );
		}
		$this->data = [
			'agent_run_id'    => $data['agent_run_id'],
			'result_version'  => $data['result_version'],
			'event_id'        => $data['event_id'],
			'correlation_id'  => $data['correlation_id'],
			'agent_name'      => $data['agent_name'],
			'action_name'     => $data['action_name'],
			'status'          => $data['status'],
			'policy_decision' => $data['policy_decision'] ?? null,
			'approval_id'     => $data['approval_id'] ?? null,
			'result'          => $data['result'],
			'error'           => $data['error'] ?? null,
			'completed_at'    => $data['completed_at'],
		];
	}

	/** @return array<string,mixed> */
	public function jsonSerialize(): array {
		return $this->data;
	}

	/** @return array<string,mixed> */
	public function to_array() {
		return $this->data;
	}

	/** @param string $key Field. @param mixed $default Default. @return mixed */
	public function get( $key, $default = null ) {
		return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : $default;
	}

	/** @param array<string,mixed> $value Result. @return string */
	private static function find_forbidden_key( array $value ) {
		$forbidden = [ 'approve_tutor', 'tutor_approved', 'price', 'price_override', 'set_rate', 'refund', 'payout', 'delete_user' ];
		foreach ( $value as $key => $child ) {
			if ( is_string( $key ) && in_array( strtolower( $key ), $forbidden, true ) ) {
				return strtolower( $key );
			}
			if ( is_array( $child ) ) {
				$found = self::find_forbidden_key( $child );
				if ( '' !== $found ) {
					return $found;
				}
			}
		}
		return '';
	}
}
