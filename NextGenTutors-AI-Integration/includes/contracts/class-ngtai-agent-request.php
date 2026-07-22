<?php
/**
 * Agent request value object.
 *
 * @package NextGenTutorsAIIntegration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Represents a governed outbound agent request.
 */
final class NGTAI_Agent_Request implements JsonSerializable {

	/** @var string */
	private $action;

	/** @var array<string,mixed> */
	private $context;

	/** @var string */
	private $correlation_id;

	/**
	 * @param string               $action Action identifier.
	 * @param array<string,mixed>  $context Minimized context.
	 * @param string               $correlation_id Correlation identifier.
	 */
	public function __construct( $action, array $context, $correlation_id ) {
		if ( ! in_array( $action, [ 'agent.recommend', 'agent.observe' ], true ) || '' === trim( (string) $correlation_id ) ) {
			throw new InvalidArgumentException( 'Invalid governed agent request.' );
		}
		$this->action         = $action;
		$this->context        = $context;
		$this->correlation_id = (string) $correlation_id;
	}

	/** @return array<string,mixed> */
	public function jsonSerialize(): array {
		return $this->to_array();
	}

	/** @return array<string,mixed> */
	public function to_array() {
		return [
			'action'         => $this->action,
			'context'        => $this->context,
			'correlation_id' => $this->correlation_id,
		];
	}

	/** @param string $key Field. @param mixed $default Default. @return mixed */
	public function get( $key, $default = null ) {
		$data = $this->to_array();
		return array_key_exists( $key, $data ) ? $data[ $key ] : $default;
	}
}
