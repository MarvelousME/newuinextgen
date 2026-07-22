<?php
/**
 * Shared v1 event contract for WordPress → agents-api.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable, validated event envelope.
 */
final class NGTAI_Event_Envelope implements JsonSerializable {

	/** @var array<string,mixed> */
	private $data;

	/**
	 * @param array<string,mixed> $data Envelope values.
	 * @throws InvalidArgumentException When the contract is invalid.
	 */
	public function __construct( array $data ) {
		$data['schema_version'] = (int) ( $data['schema_version'] ?? 1 );
		$data['causation_id']   = (string) ( $data['causation_id'] ?? '' );
		$data['payload']        = is_array( $data['payload'] ?? null ) ? $data['payload'] : [];
		$data['consent_context'] = is_array( $data['consent_context'] ?? null ) ? $data['consent_context'] : null;

		$errors = self::validate( $data );
		if ( $errors ) {
			throw new InvalidArgumentException( implode( '; ', $errors ) );
		}
		$this->data = $data;
	}

	/**
	 * @param array<string,mixed> $data Envelope.
	 * @return array<int,string>
	 */
	public static function validate( array $data ) {
		$errors   = [];
		$required = [
			'event_id',
			'event_type',
			'schema_version',
			'tenant_id',
			'source',
			'subject_type',
			'subject_id',
			'occurred_at',
			'correlation_id',
			'data_classification',
			'payload',
		];
		foreach ( $required as $field ) {
			if ( ! array_key_exists( $field, $data ) ) {
				$errors[] = 'missing_' . $field;
				continue;
			}
			if ( is_string( $data[ $field ] ) && '' === trim( $data[ $field ] ) ) {
				$errors[] = 'missing_' . $field;
			}
		}
		if ( isset( $data['event_id'] ) && ! preg_match( '/^[A-Za-z0-9._:-]{8,128}$/', (string) $data['event_id'] ) ) {
			$errors[] = 'invalid_event_id';
		}
		if ( isset( $data['event_type'] ) && ! preg_match( '/^[a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+$/', (string) $data['event_type'] ) ) {
			$errors[] = 'invalid_event_type';
		}
		if ( isset( $data['occurred_at'] ) && false === strtotime( (string) $data['occurred_at'] ) ) {
			$errors[] = 'invalid_occurred_at';
		}
		if ( isset( $data['schema_version'] ) && 1 !== (int) $data['schema_version'] ) {
			$errors[] = 'unsupported_schema_version';
		}
		if ( isset( $data['payload'] ) && ! is_array( $data['payload'] ) ) {
			$errors[] = 'invalid_payload';
		}
		$allowed = [
			'event_id',
			'event_type',
			'schema_version',
			'occurred_at',
			'tenant_id',
			'source',
			'subject_type',
			'subject_id',
			'correlation_id',
			'causation_id',
			'data_classification',
			'consent_context',
			'payload',
		];
		foreach ( array_keys( $data ) as $field ) {
			if ( ! in_array( $field, $allowed, true ) ) {
				$errors[] = 'unknown_field:' . $field;
			}
		}
		return $errors;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return $this->data;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function jsonSerialize(): array {
		return $this->data;
	}

	/**
	 * @param string $key Field.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		return $this->data[ $key ] ?? $default;
	}
}
