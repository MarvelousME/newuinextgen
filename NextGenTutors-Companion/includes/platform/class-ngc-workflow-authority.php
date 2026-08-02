<?php
/**
 * Single workflow authority — sole side-effect executor when flag is on.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Producers enqueue; authority executes.
 */
final class NGC_Workflow_Authority {

	/**
	 * Init adapters.
	 */
	public static function init() {
		add_filter( 'ngc_studio_should_execute_side_effects', [ __CLASS__, 'filter_producer_execute' ], 5 );
		add_filter( 'ngc_orchestrator_should_execute_side_effects', [ __CLASS__, 'filter_producer_execute' ], 5 );
		add_filter( 'ngc_hub_should_execute_side_effects', [ __CLASS__, 'filter_producer_execute' ], 5 );
		add_filter( 'ngc_automatorwp_should_execute_side_effects', [ __CLASS__, 'filter_producer_execute' ], 5 );

		add_action( 'ngc_workflow_authority_request', [ __CLASS__, 'request_execute' ], 10, 2 );
		add_action( 'ngc_workflow_authority_execute_studio_workflow', [ __CLASS__, 'handle_studio' ], 10, 1 );
		add_action( 'ngc_workflow_authority_execute_orchestrator_run', [ __CLASS__, 'handle_orchestrator' ], 10, 1 );
		add_action( 'ngc_workflow_authority_execute_retry_workflow', [ __CLASS__, 'handle_retry' ], 10, 1 );
		add_action( 'ngc_workflow_authority_execute_hub_event', [ __CLASS__, 'handle_hub' ], 10, 1 );
	}

	/**
	 * When authority is on, producers must not execute side effects.
	 *
	 * @param bool $should Default.
	 * @return bool
	 */
	public static function filter_producer_execute( $should ) {
		if ( NGC_Platform::authority_enabled() ) {
			return false;
		}
		return (bool) $should;
	}

	/**
	 * Request execution — enqueue when authority on, else fire immediately.
	 *
	 * @param string $action Action name.
	 * @param array  $payload Payload.
	 * @return string|true|WP_Error Message id or true.
	 */
	public static function request_execute( $action, array $payload = [] ) {
		$action = sanitize_key( (string) $action );
		$body   = array_merge(
			$payload,
			[
				'type'   => 'workflow.execute',
				'action' => $action,
			]
		);
		$idem = isset( $payload['idempotency_key'] )
			? (string) $payload['idempotency_key']
			: ( 'wf:' . $action . ':' . NGC_Idempotency::fingerprint( $body ) );

		if ( ! NGC_Platform::authority_enabled() ) {
			return self::execute_job( $body );
		}

		return NGC_Durable_Queue::enqueue(
			NGC_Queue_Worker::QUEUE_WORKFLOW,
			$body,
			[
				'idempotency_key' => $idem,
				'priority'        => isset( $payload['priority'] ) ? (int) $payload['priority'] : 80,
				'trace_id'        => NGC_Platform_Observability::current_trace_id(),
			]
		);
	}

	/**
	 * Execute a workflow job (from queue worker).
	 *
	 * @param array $payload Payload with action.
	 * @return true|WP_Error
	 */
	public static function execute_job( array $payload ) {
		$action = isset( $payload['action'] ) ? sanitize_key( (string) $payload['action'] ) : '';
		if ( $action === '' ) {
			return new WP_Error( 'ngc_wf_missing_action', 'Missing workflow action.' );
		}

		$idem = isset( $payload['idempotency_key'] )
			? (string) $payload['idempotency_key']
			: ( 'wfexec:' . $action . ':' . NGC_Idempotency::fingerprint( $payload ) );

		$result = NGC_Idempotency::once(
			$idem,
			function () use ( $action, $payload ) {
				/**
				 * Authority execution hook — side effects live here.
				 *
				 * @param array  $payload Payload.
				 * @param string $action  Action.
				 */
				do_action( 'ngc_workflow_authority_execute', $payload, $action );
				do_action( 'ngc_workflow_authority_execute_' . $action, $payload );

				if ( class_exists( 'NGC_Metrics' ) ) {
					NGC_Metrics::inc( 'workflow_authority_executions_total', 1, [ 'action' => $action ] );
				}

				if ( class_exists( 'NGC_Immutable_Audit' ) ) {
					NGC_Immutable_Audit::append(
						'workflow.execute',
						'workflow',
						0,
						[
							'action'  => $action,
							'payload' => $payload,
						]
					);
				}

				return [ 'ok' => true, 'action' => $action ];
			},
			NGC_Idempotency::fingerprint( $payload ),
			'workflow'
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return true;
	}

	/**
	 * Adapter helper: enqueue from Studio/Hub/Orchestrator producers.
	 *
	 * @param string $source  Producer name.
	 * @param string $action  Action.
	 * @param array  $payload Payload.
	 * @return string|true|WP_Error
	 */
	public static function from_producer( $source, $action, array $payload = [] ) {
		$payload['source'] = sanitize_key( (string) $source );
		$payload['idempotency_key'] = isset( $payload['idempotency_key'] )
			? (string) $payload['idempotency_key']
			: ( 'prod:' . $source . ':' . $action . ':' . NGC_Idempotency::fingerprint( $payload ) );
		return self::request_execute( $action, $payload );
	}

	/**
	 * @param array $payload Payload.
	 */
	public static function handle_studio( $payload ) {
		$wf_id = (int) ( $payload['workflow_id'] ?? 0 );
		$ctx   = (array) ( $payload['context'] ?? [] );
		$trig  = (string) ( $payload['trigger'] ?? '' );
		if ( ! class_exists( 'NGC_Studio_Engine' ) || ! class_exists( 'NGC_Studio_Runtime' ) ) {
			return;
		}
		$all = NGC_Studio_Runtime::active_workflows();
		foreach ( $all as $wf ) {
			if ( (int) ( $wf['id'] ?? 0 ) === $wf_id ) {
				// Force execute under authority (bypass producer filter via simulate=false + direct engine).
				remove_filter( 'ngc_studio_should_execute_side_effects', [ __CLASS__, 'filter_producer_execute' ], 5 );
				NGC_Studio_Engine::execute( $wf, $ctx, $trig );
				add_filter( 'ngc_studio_should_execute_side_effects', [ __CLASS__, 'filter_producer_execute' ], 5 );
				return;
			}
		}
	}

	/**
	 * @param array $payload Payload.
	 */
	public static function handle_orchestrator( $payload ) {
		$workflow = (string) ( $payload['workflow'] ?? '' );
		$context  = (array) ( $payload['context'] ?? [] );
		if ( ! class_exists( 'NGC_Workflow_Orchestrator' ) ) {
			return;
		}
		remove_filter( 'ngc_orchestrator_should_execute_side_effects', [ __CLASS__, 'filter_producer_execute' ], 5 );
		NGC_Workflow_Orchestrator::run( $workflow, $context, true );
		add_filter( 'ngc_orchestrator_should_execute_side_effects', [ __CLASS__, 'filter_producer_execute' ], 5 );
	}

	/**
	 * @param array $payload Payload.
	 */
	public static function handle_retry( $payload ) {
		$workflow = (string) ( $payload['workflow'] ?? '' );
		$context  = (array) ( $payload['context'] ?? [] );
		if ( class_exists( 'NGC_Workflow_Orchestrator' ) ) {
			remove_filter( 'ngc_orchestrator_should_execute_side_effects', [ __CLASS__, 'filter_producer_execute' ], 5 );
			NGC_Workflow_Orchestrator::run( $workflow, $context, true );
			add_filter( 'ngc_orchestrator_should_execute_side_effects', [ __CLASS__, 'filter_producer_execute' ], 5 );
		}
	}

	/**
	 * @param array $payload Payload.
	 */
	public static function handle_hub( $payload ) {
		$event   = (string) ( $payload['event_key'] ?? '' );
		$context = (array) ( $payload['payload'] ?? [] );
		if ( class_exists( 'NGC_Workflows' ) && method_exists( 'NGC_Workflows', 'dispatch' ) ) {
			remove_filter( 'ngc_hub_should_execute_side_effects', [ __CLASS__, 'filter_producer_execute' ], 5 );
			NGC_Workflows::dispatch( $event, $context );
			add_filter( 'ngc_hub_should_execute_side_effects', [ __CLASS__, 'filter_producer_execute' ], 5 );
		}
	}
}
