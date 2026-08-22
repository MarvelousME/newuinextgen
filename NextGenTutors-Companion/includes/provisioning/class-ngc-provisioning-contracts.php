<?php
/**
 * Provisioning contracts — versioned, idempotent setup steps.
 *
 * Extends the existing `wp ngt system *` orchestrator with a step registry
 * matching the production interoperability provisioning order.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable-ish check result.
 */
final class NGC_Provision_Check_Result {

	/** @var bool */
	public $ok;

	/** @var string[] */
	public $blocking = [];

	/** @var string[] */
	public $warnings = [];

	/** @var array<string,mixed> */
	public $evidence = [];

	/**
	 * @param bool                 $ok OK.
	 * @param string[]             $blocking Blocking.
	 * @param string[]             $warnings Warnings.
	 * @param array<string,mixed>  $evidence Evidence.
	 */
	public function __construct( $ok = true, array $blocking = [], array $warnings = [], array $evidence = [] ) {
		$this->ok       = (bool) $ok;
		$this->blocking = $blocking;
		$this->warnings = $warnings;
		$this->evidence = $evidence;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return [
			'ok'       => $this->ok,
			'blocking' => $this->blocking,
			'warnings' => $this->warnings,
			'evidence' => $this->evidence,
		];
	}
}

/**
 * Planned / applied change set.
 */
final class NGC_Provision_Change_Set {

	/** @var array<int,array<string,mixed>> */
	public $creates = [];

	/** @var array<int,array<string,mixed>> */
	public $updates = [];

	/** @var array<int,array<string,mixed>> */
	public $skips = [];

	/** @var array<string,mixed> */
	public $meta = [];

	/**
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return [
			'creates' => $this->creates,
			'updates' => $this->updates,
			'skips'   => $this->skips,
			'meta'    => $this->meta,
		];
	}
}

/**
 * Step apply/rollback result.
 */
final class NGC_Provision_Step_Result {

	/** @var bool */
	public $ok;

	/** @var string */
	public $status;

	/** @var string */
	public $message = '';

	/** @var array<string,mixed> */
	public $created_ids = [];

	/** @var array<string,mixed> */
	public $updated_ids = [];

	/** @var array<string,mixed> */
	public $previous = [];

	/** @var array<string,mixed> */
	public $evidence = [];

	/**
	 * @param bool                 $ok OK.
	 * @param string               $status Status.
	 * @param string               $message Message.
	 * @param array<string,mixed>  $extra Extra fields.
	 */
	public function __construct( $ok, $status, $message = '', array $extra = [] ) {
		$this->ok         = (bool) $ok;
		$this->status     = (string) $status;
		$this->message    = (string) $message;
		$this->created_ids = $extra['created_ids'] ?? [];
		$this->updated_ids = $extra['updated_ids'] ?? [];
		$this->previous    = $extra['previous'] ?? [];
		$this->evidence    = $extra['evidence'] ?? [];
	}

	/**
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return [
			'ok'          => $this->ok,
			'status'      => $this->status,
			'message'     => $this->message,
			'created_ids' => $this->created_ids,
			'updated_ids' => $this->updated_ids,
			'previous'    => $this->previous,
			'evidence'    => $this->evidence,
		];
	}
}

/**
 * Runtime context for a provisioning run.
 */
final class NGC_Provision_Context {

	/** @var string */
	public $correlation_id;

	/** @var bool */
	public $dry_run = false;

	/** @var bool */
	public $force_safe = false;

	/** @var bool */
	public $allow_demo = false;

	/** @var string */
	public $environment = 'unknown';

	/** @var int */
	public $actor_id = 0;

	/** @var array<string,mixed> */
	public $input = [];

	/** @var array<string,mixed> */
	public $state = [];

	/**
	 * @param array<string,mixed> $args Args.
	 */
	public function __construct( array $args = [] ) {
		$this->correlation_id = (string) ( $args['correlation_id'] ?? wp_generate_uuid4() );
		$this->dry_run        = ! empty( $args['dry_run'] );
		$this->force_safe     = ! empty( $args['force_safe'] );
		$this->allow_demo     = ! empty( $args['allow_demo'] );
		$this->environment    = (string) ( $args['environment'] ?? self::detect_environment() );
		$this->actor_id       = (int) ( $args['actor_id'] ?? get_current_user_id() );
		$this->input          = is_array( $args['input'] ?? null ) ? $args['input'] : [];
		$this->state          = is_array( $args['state'] ?? null ) ? $args['state'] : [];
	}

	/**
	 * @return string
	 */
	public static function detect_environment() {
		if ( class_exists( 'NGC_Demo_Env' ) && NGC_Demo_Env::is_production_environment() ) {
			return 'production';
		}
		if ( function_exists( 'wp_get_environment_type' ) && 'production' === wp_get_environment_type() ) {
			return 'production';
		}
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( in_array( $host, [ 'localhost', '127.0.0.1' ], true ) || ( is_string( $host ) && str_ends_with( $host, '.local' ) ) ) {
			return 'local';
		}
		if ( defined( 'NGC_ALLOW_DEMO_SEED' ) && NGC_ALLOW_DEMO_SEED ) {
			return 'demo';
		}
		if ( is_string( $host ) && false !== stripos( $host, 'nextgentutors.co.za' ) ) {
			return 'production';
		}
		return 'staging';
	}

	/**
	 * @return bool
	 */
	public function is_production() {
		return 'production' === $this->environment;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return [
			'correlation_id' => $this->correlation_id,
			'dry_run'        => $this->dry_run,
			'force_safe'     => $this->force_safe,
			'allow_demo'     => $this->allow_demo,
			'environment'    => $this->environment,
			'actor_id'       => $this->actor_id,
		];
	}
}

/**
 * Provisioning step contract.
 */
interface NGC_Provisioning_Step {

	/**
	 * Stable step id (e.g. env-preflight).
	 *
	 * @return string
	 */
	public function id(): string;

	/**
	 * Human label.
	 *
	 * @return string
	 */
	public function label(): string;

	/**
	 * Semantic version of step implementation.
	 *
	 * @return string
	 */
	public function version(): string;

	/**
	 * Order index (1..32).
	 *
	 * @return int
	 */
	public function order(): int;

	/**
	 * Dependency step ids.
	 *
	 * @return string[]
	 */
	public function dependencies(): array;

	/**
	 * Whether failure blocks the pipeline.
	 *
	 * @return bool
	 */
	public function is_critical(): bool;

	/**
	 * Preflight checks.
	 *
	 * @param NGC_Provision_Context $context Context.
	 * @return NGC_Provision_Check_Result
	 */
	public function preflight( NGC_Provision_Context $context ): NGC_Provision_Check_Result;

	/**
	 * Plan changes (dry-run friendly).
	 *
	 * @param NGC_Provision_Context $context Context.
	 * @return NGC_Provision_Change_Set
	 */
	public function plan( NGC_Provision_Context $context ): NGC_Provision_Change_Set;

	/**
	 * Apply changes.
	 *
	 * @param NGC_Provision_Context $context Context.
	 * @return NGC_Provision_Step_Result
	 */
	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result;

	/**
	 * Verify after apply.
	 *
	 * @param NGC_Provision_Context $context Context.
	 * @return NGC_Provision_Check_Result
	 */
	public function verify( NGC_Provision_Context $context ): NGC_Provision_Check_Result;

	/**
	 * Compensating rollback where supported.
	 *
	 * @param NGC_Provision_Context $context Context.
	 * @return NGC_Provision_Step_Result
	 */
	public function rollback( NGC_Provision_Context $context ): NGC_Provision_Step_Result;
}
