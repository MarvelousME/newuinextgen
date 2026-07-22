<?php
/**
 * Delivery result contract.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable delivery outcome.
 */
final class NGTAI_Delivery_Result implements JsonSerializable {

	/** @var array<string,mixed> */
	private $data;

	/** @param array<string,mixed> $data Fields. */
	public function __construct( array $data ) {
		foreach ( [ 'delivery_id', 'status', 'http_status', 'retryable', 'error' ] as $field ) {
			if ( ! array_key_exists( $field, $data ) ) {
				throw new InvalidArgumentException( 'Missing delivery result field: ' . $field );
			}
		}
		if ( (int) $data['delivery_id'] < 1 || ! is_string( $data['status'] ) || ! is_bool( $data['retryable'] ) ) {
			throw new InvalidArgumentException( 'Invalid delivery result.' );
		}
		if ( null !== $data['error'] && ! is_string( $data['error'] ) ) {
			throw new InvalidArgumentException( 'error must be a string or null.' );
		}
		$this->data = [
			'delivery_id' => (int) $data['delivery_id'],
			'status'      => $data['status'],
			'http_status' => (int) $data['http_status'],
			'retryable'   => $data['retryable'],
			'error'       => $data['error'],
		];
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
