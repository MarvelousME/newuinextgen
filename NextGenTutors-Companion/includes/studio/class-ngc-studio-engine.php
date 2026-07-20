<?php
/**
 * Executes compiled workflow plans step-by-step.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generic step interpreter for studio workflows.
 */
class NGC_Studio_Engine {

	/**
	 * @param array<string, mixed> $workflow Workflow row with compiled plan.
	 * @param array<string, mixed> $context  Trigger context.
	 * @param string               $trigger  Trigger key.
	 * @param bool                 $simulate Dry-run flag.
	 * @return array<string, mixed>
	 */
	public static function execute( $workflow, $context = [], $trigger = '', $simulate = false ) {
		$compiled = (array) ( $workflow['compiled'] ?? [] );
		if ( ! empty( $compiled['edge_meta'] ) && ! empty( $compiled['start'] ) ) {
			return self::execute_graph( $workflow, $context, $trigger, $simulate );
		}
		return self::execute_linear( $workflow, $context, $trigger, $simulate );
	}

	/**
	 * Graph-aware execution with branch/loop routing.
	 *
	 * @param array<string, mixed> $workflow Workflow.
	 * @param array<string, mixed> $context  Context.
	 * @param string               $trigger  Trigger.
	 * @param bool                 $simulate Simulate.
	 * @return array<string, mixed>
	 */
	private static function execute_graph( $workflow, $context, $trigger, $simulate ) {
		$started  = microtime( true );
		$wf_id    = (int) ( $workflow['id'] ?? 0 );
		$compiled = (array) ( $workflow['compiled'] ?? [] );
		$node_map = (array) ( $compiled['node_map'] ?? [] );
		$edges    = (array) ( $compiled['edge_meta'] ?? [] );
		$start    = (string) ( $compiled['start'] ?? '' );
		$path     = [];
		$results  = [];
		$status   = 'completed';
		$error    = '';
		$loop_counts = [];

		$exec_id = 0;
		if ( ! $simulate ) {
			$exec_id = NGC_Studio_Repository::create_execution(
				[
					'workflow_id'      => $wf_id,
					'workflow_version' => (int) ( $workflow['version'] ?? 1 ),
					'trigger_event'    => $trigger,
					'context'          => $context,
					'status'           => 'running',
				]
			);
		}

		$ctx     = array_merge( $context, [ 'workflow_id' => $wf_id, 'trigger' => $trigger ] );
		$current = $start;
		$guard   = 0;

		while ( $current && $guard < 500 ) {
			++$guard;
			$node = (array) ( $node_map[ $current ] ?? [] );
			if ( ! $node ) {
				break;
			}
			$type = self::normalize_type( $node );
			if ( 'END' === $type ) {
				break;
			}

			if ( 'START' !== $type ) {
				$entry = [
					'node' => $current,
					'type' => $type,
					'at'   => current_time( 'mysql', true ),
				];
				$path[] = $entry;
				self::emit_step( $entry, $ctx, $exec_id, $simulate );
			}

			if ( in_array( $type, [ 'CONDITION', 'DECISION', 'BRANCH' ], true ) ) {
				$config = (array) ( $node['data'] ?? [] );
				$pass   = self::evaluate_condition( $config, $ctx );
				$branch = $pass ? 'true' : 'false';
				$path[ count( $path ) - 1 ]['branch'] = $branch;
				$next = self::pick_edge( $edges[ $current ] ?? [], $branch );
				if ( ! $next ) {
					$status = 'failed';
					$error  = __( 'Branch has no matching edge.', 'nextgencompanion' );
					break;
				}
				$path[ count( $path ) - 1 ]['edge_to'] = $next;
				$current = $next;
				continue;
			}

			if ( 'LOOP' === $type ) {
				$config   = (array) ( $node['data'] ?? [] );
				$max_iter = max( 1, (int) ( $config['max_iterations'] ?? $config['iterations'] ?? 10 ) );
				$count    = (int) ( $loop_counts[ $current ] ?? 0 );
				if ( $count < $max_iter ) {
					$loop_counts[ $current ] = $count + 1;
					$path[ count( $path ) - 1 ]['loop_iteration'] = $count + 1;
					$next = self::pick_edge( $edges[ $current ] ?? [], 'loop' );
					if ( ! $next ) {
						$next = self::pick_edge( $edges[ $current ] ?? [], 'body' );
					}
					if ( ! $next ) {
						$outs = (array) ( $edges[ $current ] ?? [] );
						$next = (string) ( $outs[0]['target'] ?? '' );
					}
				} else {
					$path[ count( $path ) - 1 ]['loop_exit'] = true;
					$next = self::pick_edge( $edges[ $current ] ?? [], 'exit' );
					if ( ! $next ) {
						$outs = (array) ( $edges[ $current ] ?? [] );
						$next = (string) ( $outs[1]['target'] ?? ( $outs[0]['target'] ?? '' ) );
					}
				}
				$path[ count( $path ) - 1 ]['edge_to'] = $next;
				$current = $next;
				continue;
			}

			if ( $simulate ) {
				$results[] = [ 'node' => $current, 'type' => $type, 'simulated' => true ];
			} else {
				$result    = self::run_step( $type, (array) ( $node['data'] ?? [] ), $ctx );
				$results[] = array_merge( [ 'node' => $current, 'type' => $type ], $result );
				$ctx       = array_merge( $ctx, (array) ( $result['context'] ?? [] ) );
				if ( empty( $result['ok'] ) && empty( $node['data']['continue_on_fail'] ) ) {
					$status = 'failed';
					$error  = (string) ( $result['message'] ?? __( 'Step failed.', 'nextgencompanion' ) );
					break;
				}
			}

			$outs = (array) ( $edges[ $current ] ?? [] );
			$next = (string) ( $outs[0]['target'] ?? '' );
			if ( $next && 'END' !== self::normalize_type( (array) ( $node_map[ $next ] ?? [] ) ) ) {
				if ( ! empty( $path ) ) {
					$path[ count( $path ) - 1 ]['edge_to'] = $next;
				}
				$current = $next;
				continue;
			}
			if ( $next ) {
				$current = $next;
				continue;
			}
			break;
		}

		return self::finalize( $workflow, $trigger, $simulate, $wf_id, $exec_id, $path, $results, $status, $error, $started, $ctx );
	}

	/**
	 * Legacy linear plan execution.
	 *
	 * @param array<string, mixed> $workflow Workflow.
	 * @param array<string, mixed> $context  Context.
	 * @param string               $trigger  Trigger.
	 * @param bool                 $simulate Simulate.
	 * @return array<string, mixed>
	 */
	private static function execute_linear( $workflow, $context, $trigger, $simulate ) {
		$started = microtime( true );
		$wf_id   = (int) ( $workflow['id'] ?? 0 );
		$plan    = (array) ( $workflow['compiled']['plan'] ?? [] );
		$path    = [];
		$results = [];
		$status  = 'completed';
		$error   = '';

		$exec_id = 0;
		if ( ! $simulate ) {
			$exec_id = NGC_Studio_Repository::create_execution(
				[
					'workflow_id'      => $wf_id,
					'workflow_version' => (int) ( $workflow['version'] ?? 1 ),
					'trigger_event'    => $trigger,
					'context'          => $context,
					'status'           => 'running',
				]
			);
		}

		$ctx = array_merge( $context, [ 'workflow_id' => $wf_id, 'trigger' => $trigger ] );

		foreach ( $plan as $step ) {
			$type = strtoupper( (string) ( $step['type'] ?? '' ) );
			if ( in_array( $type, [ 'END' ], true ) ) {
				break;
			}

			$entry  = [ 'node' => $step['id'] ?? '', 'type' => $type, 'at' => current_time( 'mysql', true ) ];
			$path[] = $entry;
			self::emit_step( $entry, $ctx, $exec_id, $simulate );

			if ( 'CONDITION' === $type || 'DECISION' === $type ) {
				$pass = self::evaluate_condition( (array) ( $step['config'] ?? [] ), $ctx );
				if ( ! $pass ) {
					$path[ count( $path ) - 1 ]['skipped'] = true;
					continue;
				}
			}

			if ( $simulate ) {
				$results[] = [ 'node' => $step['id'] ?? '', 'type' => $type, 'simulated' => true ];
				continue;
			}

			$result    = self::run_step( $type, (array) ( $step['config'] ?? [] ), $ctx );
			$results[] = array_merge( [ 'node' => $step['id'] ?? '', 'type' => $type ], $result );
			$ctx       = array_merge( $ctx, (array) ( $result['context'] ?? [] ) );

			if ( empty( $result['ok'] ) && empty( $step['config']['continue_on_fail'] ) ) {
				$status = 'failed';
				$error  = (string) ( $result['message'] ?? __( 'Step failed.', 'nextgencompanion' ) );
				break;
			}
		}

		return self::finalize( $workflow, $trigger, $simulate, $wf_id, $exec_id, $path, $results, $status, $error, $started, $ctx );
	}

	/**
	 * @param array<int, array<string, mixed>> $outs   Outgoing edges.
	 * @param string                           $needle Handle or label.
	 * @return string
	 */
	private static function pick_edge( $outs, $needle ) {
		$needle = strtolower( $needle );
		foreach ( (array) $outs as $edge ) {
			$handle = strtolower( (string) ( $edge['handle'] ?? '' ) );
			$label  = strtolower( (string) ( $edge['label'] ?? '' ) );
			if ( $handle === $needle || $label === $needle ) {
				return (string) ( $edge['target'] ?? '' );
			}
		}
		if ( 'true' === $needle && ! empty( $outs[0] ) ) {
			return (string) ( $outs[0]['target'] ?? '' );
		}
		if ( 'false' === $needle && ! empty( $outs[1] ) ) {
			return (string) ( $outs[1]['target'] ?? '' );
		}
		return '';
	}

	/**
	 * @param array<string, mixed> $node Node.
	 * @return string
	 */
	private static function normalize_type( $node ) {
		return strtoupper( sanitize_key( (string) ( $node['type'] ?? ( is_array( $node['data'] ?? null ) ? ( $node['data']['type'] ?? '' ) : '' ) ) ) );
	}

	/**
	 * @param array<string, mixed> $entry    Path entry.
	 * @param array<string, mixed> $ctx      Context.
	 * @param int                  $exec_id  Execution ID.
	 * @param bool                 $simulate Simulation flag.
	 */
	private static function emit_step( $entry, $ctx, $exec_id, $simulate ) {
		do_action(
			'ngc_studio_step_executed',
			[
				'execution_id' => $exec_id,
				'node'         => (string) ( $entry['node'] ?? '' ),
				'type'         => (string) ( $entry['type'] ?? '' ),
				'branch'       => (string) ( $entry['branch'] ?? '' ),
				'skipped'      => ! empty( $entry['skipped'] ),
				'loop_iteration' => (int) ( $entry['loop_iteration'] ?? 0 ),
				'edge_to'      => (string) ( $entry['edge_to'] ?? '' ),
				'simulation'   => $simulate,
				'workflow_id'  => (int) ( $ctx['workflow_id'] ?? 0 ),
				'trigger'      => (string) ( $ctx['trigger'] ?? '' ),
			]
		);
	}

	/**
	 * @param array<string, mixed>             $workflow Workflow.
	 * @param string                           $trigger  Trigger.
	 * @param bool                             $simulate Simulate.
	 * @param int                              $wf_id    Workflow ID.
	 * @param int                              $exec_id  Execution ID.
	 * @param array<int, array<string, mixed>> $path     Path.
	 * @param array<int, array<string, mixed>> $results  Results.
	 * @param string                           $status   Status.
	 * @param string                           $error    Error.
	 * @param float                            $started  Start time.
	 * @param array<string, mixed>             $ctx      Context.
	 * @return array<string, mixed>
	 */
	private static function finalize( $workflow, $trigger, $simulate, $wf_id, $exec_id, $path, $results, $status, $error, $started, $ctx ) {
		$duration = (int) round( ( microtime( true ) - $started ) * 1000 );
		$output   = [
			'ok'          => 'failed' !== $status,
			'workflow_id' => $wf_id,
			'execution_id'=> $exec_id,
			'status'      => $status,
			'path'        => $path,
			'results'     => $results,
			'duration_ms' => $duration,
			'simulation'  => $simulate,
			'message'     => $error,
		];

		if ( $exec_id ) {
			NGC_Studio_Repository::update_execution(
				$exec_id,
				[
					'status'        => $status,
					'path'          => $path,
					'results'       => $results,
					'duration_ms'   => $duration,
					'error_message' => $error,
					'completed'     => true,
				]
			);
		}

		self::audit( $workflow, $trigger, $output, $ctx );
		do_action( 'ngc_studio_workflow_executed', $workflow, $output, $ctx );

		return $output;
	}

	/**
	 * @param string               $type   Node type.
	 * @param array<string, mixed> $config Node config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function run_step( $type, $config, $ctx ) {
		$handlers = [
			'EMAIL'        => 'step_email',
			'NOTIFICATION' => 'step_notification',
			'ROLE'         => 'step_role',
			'CRM'          => 'step_crm',
			'LMS'          => 'step_lms',
			'BOOKING'      => 'step_booking',
			'PAYMENT'      => 'step_payment',
			'APPROVAL'     => 'step_approval',
			'AUDIT'        => 'step_audit',
			'API'          => 'step_api',
			'WEBHOOK'      => 'step_webhook',
			'WAIT'         => 'step_wait',
			'DELAY'        => 'step_delay',
			'AI_ACTION'    => 'step_ai',
		];

		$orchestrator_map = [
			'TUTOR_REGISTERED'   => 'TUTOR_REGISTERED',
			'TUTOR_APPROVED'     => 'TUTOR_APPROVED',
			'TUTOR_REJECTED'     => 'TUTOR_REJECTED',
			'TUTOR_RESUBMITTED'  => 'TUTOR_RESUBMITTED',
			'PARENT_REGISTERED'  => 'PARENT_REGISTERED',
			'STUDENT_REGISTERED' => 'STUDENT_REGISTERED',
			'CHILD_REGISTERED'   => 'CHILD_REGISTERED',
		];

		$wf_key = sanitize_key( (string) ( $config['orchestrator'] ?? $config['workflow'] ?? '' ) );
		if ( $wf_key && isset( $orchestrator_map[ $wf_key ] ) && class_exists( 'NGC_Workflow_Orchestrator' ) ) {
			return NGC_Workflow_Orchestrator::run( $orchestrator_map[ $wf_key ], $ctx );
		}

		if ( isset( $handlers[ $type ] ) && method_exists( __CLASS__, $handlers[ $type ] ) ) {
			return self::{ $handlers[ $type ] }( $config, $ctx );
		}

		if ( 'EVENT' === $type && ! empty( $config['dispatch'] ) ) {
			NGC_Workflows::dispatch( (string) $config['dispatch'], $ctx );
			return [ 'ok' => true, 'dispatched' => $config['dispatch'] ];
		}

		return apply_filters( 'ngc_studio_step_handler', [ 'ok' => true, 'type' => $type, 'noop' => true ], $type, $config, $ctx );
	}

	/**
	 * @param array<string, mixed> $config Config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function step_email( $config, $ctx ) {
		if ( ! class_exists( 'NGC_Email_Adapter' ) ) {
			return [ 'ok' => false, 'message' => 'Email adapter missing' ];
		}
		$email = new NGC_Email_Adapter();
		$key   = sanitize_key( (string) ( $config['template_key'] ?? $config['template'] ?? 'admin_notification' ) );
		$to    = (string) ( $config['to'] ?? $ctx['email'] ?? get_option( 'admin_email' ) );
		return $email->create_or_update( 'send_template', [ 'template_key' => $key, 'to' => $to, 'context' => $ctx ] );
	}

	/**
	 * @param array<string, mixed> $config Config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function step_notification( $config, $ctx ) {
		$key = sanitize_key( (string) ( $config['notification_key'] ?? $config['key'] ?? '' ) );
		$channel = sanitize_key( (string) ( $config['channel'] ?? 'email' ) );
		if ( class_exists( 'NGC_Studio_Notifications' ) ) {
			return NGC_Studio_Notifications::dispatch( $key ?: $channel, array_merge( $ctx, $config ) );
		}
		return self::step_email( $config, $ctx );
	}

	/**
	 * @param array<string, mixed> $config Config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function step_role( $config, $ctx ) {
		$user_id = (int) ( $ctx['user_id'] ?? 0 );
		if ( ! $user_id ) {
			return [ 'ok' => false, 'message' => 'user_id required for ROLE step' ];
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return [ 'ok' => false, 'message' => 'User not found' ];
		}
		foreach ( (array) ( $config['remove_roles'] ?? [] ) as $role ) {
			$user->remove_role( sanitize_key( (string) $role ) );
		}
		foreach ( (array) ( $config['add_roles'] ?? [] ) as $role ) {
			$user->add_role( sanitize_key( (string) $role ) );
		}
		return [ 'ok' => true, 'user_id' => $user_id ];
	}

	/**
	 * @param array<string, mixed> $config Config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function step_crm( $config, $ctx ) {
		if ( ! class_exists( 'NGC_Workflow_Orchestrator' ) ) {
			return [ 'ok' => false, 'message' => 'Orchestrator missing' ];
		}
		$crm = NGC_Workflow_Orchestrator::adapters()['fluentcrm'] ?? null;
		if ( ! $crm ) {
			return [ 'ok' => false, 'message' => 'CRM adapter missing' ];
		}
		return $crm->create_or_update( (string) ( $config['action'] ?? 'sync' ), array_merge( $ctx, (array) ( $config['payload'] ?? [] ) ) );
	}

	/**
	 * @param array<string, mixed> $config Config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function step_lms( $config, $ctx ) {
		if ( ! class_exists( 'NGC_Workflow_Orchestrator' ) ) {
			return [ 'ok' => false, 'message' => 'Orchestrator missing' ];
		}
		$lms = NGC_Workflow_Orchestrator::adapters()['masterstudy'] ?? null;
		if ( ! $lms ) {
			return [ 'ok' => false, 'message' => 'LMS adapter missing' ];
		}
		return $lms->create_or_update( (string) ( $config['action'] ?? 'enroll' ), array_merge( $ctx, (array) ( $config['payload'] ?? [] ) ) );
	}

	/**
	 * @param array<string, mixed> $config Config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function step_booking( $config, $ctx ) {
		NGC_Workflows::dispatch( 'booking.created', $ctx );
		return [ 'ok' => true ];
	}

	/**
	 * @param array<string, mixed> $config Config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function step_payment( $config, $ctx ) {
		$event = sanitize_key( (string) ( $config['event'] ?? 'payment.received' ) );
		NGC_Workflows::dispatch( str_replace( '_', '.', strtolower( $event ) ), $ctx );
		return [ 'ok' => true ];
	}

	/**
	 * @param array<string, mixed> $config Config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function step_approval( $config, $ctx ) {
		return [ 'ok' => true, 'approval' => 'queued', 'context' => $ctx ];
	}

	/**
	 * @param array<string, mixed> $config Config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function step_audit( $config, $ctx ) {
		if ( ! class_exists( 'NGC_Workflow_Orchestrator' ) ) {
			return [ 'ok' => true ];
		}
		$audit = NGC_Workflow_Orchestrator::adapters()['audit'] ?? null;
		if ( $audit ) {
			$audit->create_or_update(
				'log_event',
				[
					'event'       => (string) ( $config['event'] ?? 'studio.step' ),
					'object_type' => (string) ( $config['object_type'] ?? 'workflow' ),
					'object_id'   => (int) ( $ctx['workflow_id'] ?? 0 ),
					'context'     => $ctx,
				]
			);
		}
		return [ 'ok' => true ];
	}

	/**
	 * @param array<string, mixed> $config Config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function step_api( $config, $ctx ) {
		do_action( 'ngc_studio_api_step', $config, $ctx );
		return [ 'ok' => true ];
	}

	/**
	 * @param array<string, mixed> $config Config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function step_webhook( $config, $ctx ) {
		$url = esc_url_raw( (string) ( $config['url'] ?? '' ) );
		if ( $url ) {
			wp_remote_post( $url, [ 'body' => wp_json_encode( $ctx ), 'timeout' => 15 ] );
		}
		return [ 'ok' => (bool) $url ];
	}

	/**
	 * @param array<string, mixed> $config Config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function step_wait( $config, $ctx ) {
		return [ 'ok' => true, 'wait' => (int) ( $config['seconds'] ?? 0 ) ];
	}

	/**
	 * @param array<string, mixed> $config Config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function step_delay( $config, $ctx ) {
		return self::step_wait( $config, $ctx );
	}

	/**
	 * @param array<string, mixed> $config Config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<string, mixed>
	 */
	private static function step_ai( $config, $ctx ) {
		do_action( 'ngc_studio_ai_step', $config, $ctx );
		return [ 'ok' => true, 'ai' => true ];
	}

	/**
	 * @param array<string, mixed> $config Condition config.
	 * @param array<string, mixed> $ctx    Context.
	 * @return bool
	 */
	private static function evaluate_condition( $config, $ctx ) {
		$field    = (string) ( $config['field'] ?? '' );
		$operator = (string) ( $config['operator'] ?? 'equals' );
		$value    = $config['value'] ?? '';
		$actual   = $field ? ( $ctx[ $field ] ?? null ) : null;

		switch ( $operator ) {
			case 'equals':
				return (string) $actual === (string) $value;
			case 'not_equals':
				return (string) $actual !== (string) $value;
			case 'greater_than':
				return (float) $actual > (float) $value;
			case 'less_than':
				return (float) $actual < (float) $value;
			case 'contains':
				return false !== strpos( (string) $actual, (string) $value );
			case 'empty':
				return empty( $actual );
			case 'not_empty':
				return ! empty( $actual );
			default:
				return apply_filters( 'ngc_studio_condition_eval', true, $config, $ctx );
		}
	}

	/**
	 * @param array<string, mixed> $workflow Workflow.
	 * @param string               $trigger  Trigger.
	 * @param array<string, mixed> $output   Result.
	 * @param array<string, mixed> $ctx      Context.
	 */
	private static function audit( $workflow, $trigger, $output, $ctx ) {
		if ( ! class_exists( 'NGC_Workflow_Orchestrator' ) ) {
			return;
		}
		$audit = NGC_Workflow_Orchestrator::adapters()['audit'] ?? null;
		if ( ! $audit ) {
			return;
		}
		$audit->create_or_update(
			'log_event',
			[
				'event'       => 'studio.workflow.' . ( $output['status'] ?? 'unknown' ),
				'object_type' => 'studio_workflow',
				'object_id'   => (int) ( $workflow['id'] ?? 0 ),
				'context'     => [
					'trigger'     => $trigger,
					'duration_ms' => $output['duration_ms'] ?? 0,
					'path'        => $output['path'] ?? [],
					'ctx'         => $ctx,
				],
			]
		);
	}
}
